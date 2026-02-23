<?php

namespace App\Filament\Resources\RrMaterialParents;

use App\Filament\Resources\RrMaterialParents\Pages\CreateRrMaterialParents;
use App\Filament\Resources\RrMaterialParents\Pages\EditRrMaterialParents;
use App\Filament\Resources\RrMaterialParents\Pages\ListRrMaterialParents;
use App\Filament\Resources\RrMaterialParents\Pages\ViewRrMaterialParents;
use App\Filament\Resources\RrMaterialParents\Schemas\RrMaterialParentsForm;
use App\Filament\Resources\RrMaterialParents\Schemas\RrMaterialParentsInfolist;
use App\Filament\Resources\RrMaterialParents\Tables\RrMaterialParentsTable;
use App\Models\RrMaterialParents;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RrMaterialParentsResource extends Resource
{
    protected static ?string $model = RrMaterialParents::class;

    // Sidebar Icon
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    // Sidebar Label
    protected static ?string $navigationLabel = 'RR Materials';

    // Sidebar Grouping
    protected static string | UnitEnum | null $navigationGroup = 'Repository';

    // Header Breadcrumb
    protected static ?string $breadcrumb = 'Reading Room Materials';

    // Method Titles
    public static function getLabel(): string
    {
        return 'Material Parent';
    }

    // Infolist Title
    public static function getPluralLabel(): string
    {
        return 'Material Catalog';
    }

    public static function form(Schema $schema): Schema
    {
        return RrMaterialParentsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RrMaterialParentsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RrMaterialParentsTable::configure($table);
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
            'index' => ListRrMaterialParents::route('/'),
            'create' => CreateRrMaterialParents::route('/create'),
            'view' => ViewRrMaterialParents::route('/{record}'),
            'edit' => EditRrMaterialParents::route('/{record}/edit'),
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
