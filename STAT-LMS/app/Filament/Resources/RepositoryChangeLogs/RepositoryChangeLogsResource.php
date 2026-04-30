<?php

namespace App\Filament\Resources\RepositoryChangeLogs;

use App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Pages\ViewRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Schemas\RepositoryChangeLogsForm;
use App\Filament\Resources\RepositoryChangeLogs\Schemas\RepositoryChangeLogsInfolist;
use App\Filament\Resources\RepositoryChangeLogs\Tables\RepositoryChangeLogsTable;
use App\Models\RepositoryChangeLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RepositoryChangeLogsResource extends Resource
{
    protected static ?string $model = RepositoryChangeLogs::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static ?string $navigationLabel = 'Repository Change Logs';

    protected static string|UnitEnum|null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RepositoryChangeLogsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RepositoryChangeLogsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RepositoryChangeLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRepositoryChangeLogs::route('/'),
            'view' => ViewRepositoryChangeLogs::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'editor',
            'materialParent',
            'material.parent',
            'targetUser',
        ]);
    }
}
