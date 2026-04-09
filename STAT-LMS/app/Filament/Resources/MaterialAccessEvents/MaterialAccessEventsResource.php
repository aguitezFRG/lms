<?php

namespace App\Filament\Resources\MaterialAccessEvents;

use App\Filament\Resources\MaterialAccessEvents\Pages\CreateMaterialAccessEvents;
use App\Filament\Resources\MaterialAccessEvents\Pages\EditMaterialAccessEvents;
use App\Filament\Resources\MaterialAccessEvents\Pages\ListMaterialAccessEvents;
use App\Filament\Resources\MaterialAccessEvents\Pages\ViewMaterialAccessEvents;
use App\Filament\Resources\MaterialAccessEvents\Schemas\MaterialAccessEventsForm;
use App\Filament\Resources\MaterialAccessEvents\Schemas\MaterialAccessEventsInfolist;
use App\Filament\Resources\MaterialAccessEvents\Tables\MaterialAccessEventsTable;
use App\Models\MaterialAccessEvents;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaterialAccessEventsResource extends Resource
{
    protected static ?string $model = MaterialAccessEvents::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Material Access Logs';

    protected static string | UnitEnum | null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return MaterialAccessEventsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaterialAccessEventsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaterialAccessEventsTable::configure($table);
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
            'index' => ListMaterialAccessEvents::route('/'),
            // 'create' => CreateMaterialAccessEvents::route('/create'),
            'view' => ViewMaterialAccessEvents::route('/{record}'),
            'edit' => EditMaterialAccessEvents::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
