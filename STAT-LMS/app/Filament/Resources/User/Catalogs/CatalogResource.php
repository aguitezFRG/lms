<?php

namespace App\Filament\Resources\User\Catalogs;

use App\Enums\UserRole;
use App\Filament\Resources\RrMaterialParents\Schemas\RrMaterialParentsInfolist;
use App\Filament\Resources\User\Catalogs\Pages\ListCatalogs;
use App\Filament\Resources\User\Catalogs\Pages\ViewCatalog;
use App\Models\RrMaterialParents;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Filament\Facades\Filament;

class CatalogResource extends Resource
{
    protected static ?string $model = RrMaterialParents::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $navigationLabel = 'Catalog';

    protected static ?int $navigationSort = 2;

    protected static ?string $breadcrumb = 'Material Catalog';

    /**
     * Only register navigation when running inside the user panel.
     * Prevents the admin panel's discoverResources() from picking this up.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::getCurrentPanel()?->getId() === 'user';
    }

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

    /**
     * Always resolve URLs within the user panel so Filament doesn't fall
     * through to RrMaterialParentsResource in the admin panel, which shares
     * the same model.
     */
    public static function getUrl(
        ?string $name = null,
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?Model $tenant = null,
        bool $shouldGuessMissingParameters = false,
    ): string {
        return parent::getUrl($name, $parameters, $isAbsolute, $panel ?? 'user', $tenant, $shouldGuessMissingParameters);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCatalogs::route('/'),
            'view'  => ViewCatalog::route('/{record}'),
        ];
    }
}