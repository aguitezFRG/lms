<?php

namespace App\Filament\Resources\RepositoryChangeLogs;

use App\Filament\Resources\RepositoryChangeLogs\Pages\CreateRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Pages\EditRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Pages\ListRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Pages\ViewRepositoryChangeLogs;
use App\Filament\Resources\RepositoryChangeLogs\Schemas\RepositoryChangeLogsForm;
use App\Filament\Resources\RepositoryChangeLogs\Schemas\RepositoryChangeLogsInfolist;
use App\Filament\Resources\RepositoryChangeLogs\Tables\RepositoryChangeLogsTable;
use App\Models\RepositoryChangeLogs;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RepositoryChangeLogsResource extends Resource
{
    protected static ?string $model = RepositoryChangeLogs::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    // protected static ?string $recordTitleAttribute = 'Repository Change Logs';

    protected static ?string $navigationLabel = 'Repository Change Logs';

    protected static string | UnitEnum | null $navigationGroup = 'Logs';

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
            'create' => CreateRepositoryChangeLogs::route('/create'),
            'view' => ViewRepositoryChangeLogs::route('/{record}'),
            'edit' => EditRepositoryChangeLogs::route('/{record}/edit'),
        ];
    }
}
