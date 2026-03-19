<?php

namespace App\Filament\Resources\User\Catalogs;

use App\Enums\UserRole;
use App\Filament\Resources\RrMaterialParents\Schemas\RrMaterialParentsInfolist;
use App\Filament\Resources\User\Catalogs\Pages\ListCatalogs;
use App\Filament\Resources\User\Catalogs\Pages\ViewCatalog;
use App\Models\RrMaterialParents;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CatalogResource extends Resource
{
    protected static ?string $model = RrMaterialParents::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Catalog';

    protected static string | UnitEnum | null $navigationGroup = 'Repository';

    protected static ?int $navigationSort = 2;

    protected static ?string $breadcrumb = 'Material Catalog';

    public static function getLabel(): string
    {
        return 'Material';
    }

    public static function getPluralLabel(): string
    {
        return 'Material Catalog';
    }

    public static function infolist(Schema $schema): Schema
    {
        return RrMaterialParentsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Not used — ListCatalogs renders a custom card grid view
        return $table;
    }

    public static function getEloquentQuery(): Builder
    {
        $user  = Auth::user();
        $query = parent::getEloquentQuery();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $userLevel = UserRole::from($user->role)->getAccessLevel();

        return $query->where('access_level', '<=', $userLevel);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogs::route('/'),
            'view'  => ViewCatalog::route('/{record}'),
        ];
    }
}