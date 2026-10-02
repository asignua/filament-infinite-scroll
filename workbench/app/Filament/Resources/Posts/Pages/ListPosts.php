<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Workbench\App\Filament\Resources\Posts\PostResource;

class ListPosts extends ListRecords
{
    protected static string $resource = PostResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make(),
            'published' => Tab::make()->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'published')),
        ];
    }

    /**
     * Reads the rows and then changes the search in code, as an action might.
     */
    public function searchFromCode(): void
    {
        $this->getTableRecords();

        $this->tableSearch = 'published';
    }
}
