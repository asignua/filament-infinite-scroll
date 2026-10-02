<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll;

use Asignua\FilamentInfiniteScroll\Livewire\InfiniteScrollHook;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Table;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentInfiniteScrollServiceProvider extends PackageServiceProvider
{
    public const string PACKAGE = 'asignua/filament-infinite-scroll';

    public const string STYLESHEET = 'filament-infinite-scroll';

    public static string $name = 'filament-infinite-scroll';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations();
    }

    public function packageRegistered(): void
    {
        $this->app->scoped(InfiniteScrollRegistry::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make(self::STYLESHEET, __DIR__.'/../resources/dist/filament-infinite-scroll.css'),
        ], self::PACKAGE);

        Livewire::componentHook(InfiniteScrollHook::class);

        Table::macro('infiniteScroll', function (
            ?int $perPage = null,
            InfiniteScrollMode|string|null $mode = null,
            int|false|null $maxRecords = null,
            ?int $rootMargin = null,
        ): Table {
            // The closure is bound to the Table the macro is called on.
            return InfiniteScroll::configure($this, $perPage, $mode, $maxRecords, $rootMargin); // @phpstan-ignore argument.type
        });

        // After the rows and before Filament's own pagination (which the stylesheet hides for
        // these tables): no override of the table view is needed.
        FilamentView::registerRenderHook(
            TablesRenderHook::CONTENT_AFTER,
            function (array $data): string {
                $table = $data['table'] ?? null;
                $records = $data['records'] ?? null;

                if (!$table instanceof Table || !$records instanceof LengthAwarePaginator) {
                    return '';
                }

                $config = $this->app->make(InfiniteScrollRegistry::class)->get($table);

                if ($config === null || $records->isEmpty()) {
                    return '';
                }

                return view('filament-infinite-scroll::footer', [ // @phpstan-ignore argument.type
                    'config' => $config,
                    'loaded' => count($records->items()),
                    'total' => $records->total(),
                    'method' => InfiniteScrollHook::METHOD,
                ])->render();
            },
        );
    }
}
