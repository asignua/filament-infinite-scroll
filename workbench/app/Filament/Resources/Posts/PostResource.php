<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Posts;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Posts\Pages\ListPosts;
use Workbench\App\Models\Post;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->alignEnd(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published']),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('publish')->action(fn ($records) => $records->each->update(['status' => 'published'])),
                ]),
            ])
            ->defaultSort('id')
            ->infiniteScroll(perPage: 10, maxRecords: 35);
    }

    public static function getPages(): array
    {
        return ['index' => ListPosts::route('/')];
    }
}
