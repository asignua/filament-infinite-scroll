<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Teams;

use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Teams\Pages\EditTeam;
use Workbench\App\Filament\Resources\Teams\Pages\ListTeams;
use Workbench\App\Filament\Resources\Teams\RelationManagers\PostsRelationManager;
use Workbench\App\Models\Team;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('name')->required()]);
    }

    /**
     * A plain table: the control for "the plugin leaves other tables alone".
     */
    public static function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('name')])->defaultSort('id');
    }

    public static function getRelations(): array
    {
        return [PostsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeams::route('/'),
            'edit' => EditTeam::route('/{record}/edit'),
        ];
    }
}
