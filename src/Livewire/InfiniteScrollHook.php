<?php

declare(strict_types=1);

namespace Asignua\FilamentInfiniteScroll\Livewire;

use Asignua\FilamentInfiniteScroll\InfiniteScrollConfig;
use Asignua\FilamentInfiniteScroll\InfiniteScrollRegistry;
use Closure;
use Error;
use Filament\Tables\Contracts\HasTable;
use Livewire\Component;
use Livewire\ComponentHook;
use Livewire\Mechanisms\HandleComponents\ComponentContext;

/**
 * The server half of the plugin. It is a global Livewire hook, so no base class has to change
 * on the pages, relation managers and widgets that use `->infiniteScroll()`:
 *
 * - answers the `infiniteScrollLoadMore` call the footer makes (a call no component declares);
 * - brings the page size back to the first chunk when search, filters, sort or grouping change;
 * - keeps the page size inside [one chunk, the ceiling] and the page number at 1.
 */
class InfiniteScrollHook extends ComponentHook
{
    public const string METHOD = 'infiniteScrollLoadMore';

    // Names, not calls: the properties and methods come from Filament's `InteractsWithTable`
    // trait, which the `HasTable` contract does not declare.
    private const string PER_PAGE = 'tableRecordsPerPage';

    private const string RESET_PAGE = 'resetPage';

    private const string MEMO = 'infiniteScroll';

    private const string STORE = 'infinite-scroll.signature';

    /**
     * The properties that decide WHICH rows the table shows. A change in any of them starts
     * the list over from the first chunk.
     */
    private const array QUERY_PROPERTIES = [
        'tableSearch',
        'tableColumnSearches',
        'tableFilters',
        'tableSort',
        'tableGrouping',
        'activeTab',
    ];

    /**
     * @param array<string, mixed> $memo
     */
    public function hydrate(array $memo): void
    {
        if (isset($memo[self::MEMO]) && is_string($memo[self::MEMO])) {
            $this->storeSet(self::STORE, $memo[self::MEMO]);
        }
    }

    public function render(): void
    {
        $this->sync();
    }

    /**
     * @param array<int, mixed> $params
     */
    public function call(string $method, array $params, Closure $returnEarly): Closure
    {
        if ($method === self::METHOD && ($config = $this->config()) !== null) {
            $this->grow($config);
            $returnEarly();
        }

        return function (): void {
            $this->sync();
        };
    }

    public function update(): Closure
    {
        return function (): void {
            $this->sync();
        };
    }

    public function dehydrate(ComponentContext $context): void
    {
        $signature = $this->storeGet(self::STORE);

        if (is_string($signature) && $this->config() !== null) {
            $context->addMemo(self::MEMO, $signature);
        }
    }

    /**
     * @return (Component&HasTable)|null
     */
    private function table(): ?HasTable
    {
        $component = $this->component;

        if (!$component instanceof Component || !$component instanceof HasTable) {
            return null;
        }

        // Every component that uses `InteractsWithTable` has both; they are not on the contract.
        return method_exists($component, 'flushCachedTableRecords') && method_exists($component, 'resetPage')
            ? $component
            : null;
    }

    private function config(): ?InfiniteScrollConfig
    {
        $component = $this->table();

        if ($component === null) {
            return null;
        }

        try {
            $table = $component->getTable();
        } catch (Error) {
            // The table is built in a late lifecycle step: before it, there is nothing to read.
            return null;
        }

        return app(InfiniteScrollRegistry::class)->get($table);
    }

    private function grow(InfiniteScrollConfig $config): void
    {
        $component = $this->table();

        if ($component === null) {
            return;
        }

        $this->sync();

        $this->setPerPage($component, $config->next($this->perPage($component)));

        $this->flush($component);
    }

    private function sync(): void
    {
        $component = $this->table();
        $config = $this->config();

        if ($component === null || $config === null) {
            return;
        }

        $signature = $this->signature($component);
        $previous = $this->storeGet(self::STORE);

        // The very first request has nothing to compare with: that is not a change.
        if (is_string($previous) && $previous !== $signature) {
            $this->setPerPage($component, $config->perPage);
        }

        $this->storeSet(self::STORE, $signature);

        $perPage = $config->clamp($this->perPage($component));

        if ($this->perPage($component) !== $perPage) {
            $this->setPerPage($component, $perPage);
            $this->flush($component);
        }

        // Growth never uses pages. A stale `?page=3` from a bookmark would skip the first rows.
        if ((int) $component->getTablePage() !== 1) {
            $this->invoke($component, self::RESET_PAGE);
            $this->flush($component);
        }
    }

    private function setPerPage(Component $component, int $perPage): void
    {
        $this->write($component, self::PER_PAGE, $perPage);
    }

    private function read(Component $component, string $property): mixed
    {
        return $component->{$property};
    }

    private function write(Component $component, string $property, mixed $value): void
    {
        $component->{$property} = $value;
    }

    private function invoke(Component $component, string $method): void
    {
        $component->{$method}();
    }

    private function perPage(Component $component): int
    {
        $value = $this->read($component, self::PER_PAGE);

        return is_numeric($value) ? (int) $value : 0;
    }

    private function flush(Component $component): void
    {
        if (method_exists($component, 'flushCachedTableRecords')) {
            $component->flushCachedTableRecords();
        }
    }

    private function signature(Component $component): string
    {
        $values = [];

        foreach (self::QUERY_PROPERTIES as $property) {
            if (property_exists($component, $property)) {
                $values[$property] = $component->{$property};
            }
        }

        return md5((string) json_encode($values));
    }
}
