<?php

namespace App\Filament\Resources\RrMaterials;

use App\Enums\UserRole;
use App\Filament\Resources\RrMaterials\Pages\CreateRrMaterials;
use App\Filament\Resources\RrMaterials\Pages\EditRrMaterials;
use App\Filament\Resources\RrMaterials\Pages\ListRrMaterials;
use App\Filament\Resources\RrMaterials\Pages\ViewRrMaterials;
use App\Filament\Resources\RrMaterials\Schemas\RrMaterialsForm;
use App\Filament\Resources\RrMaterials\Schemas\RrMaterialsInfolist;
use App\Filament\Resources\RrMaterials\Tables\RrMaterialsTable;
use App\Models\RrMaterials;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RrMaterialsResource extends Resource
{
    protected static ?string $model = RrMaterials::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    // protected static ?string $recordTitleAttribute = 'RR Material Copies';

    protected static ?string $navigationLabel = 'Material Copy';

    protected static string|UnitEnum|null $navigationGroup = 'Repository';

    // Header Breadcrumb
    protected static ?string $breadcrumb = 'Reading Room Materials';

    // Method Titles
    public static function getLabel(): string
    {
        return 'Material Copy';
    }

    // Infolist Title
    public static function getPluralLabel(): string
    {
        return 'Material Copies';
    }

    public static function form(Schema $schema): Schema
    {
        return RrMaterialsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RrMaterialsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RrMaterialsTable::configure($table);
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
            'index' => ListRrMaterials::route('/'),
            'create' => CreateRrMaterials::route('/create'),
            'view' => ViewRrMaterials::route('/{record}'),
            'edit' => EditRrMaterials::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if (! $user) {
            return $query->whereNull('id');
        } // cleaner than whereRaw

        $userLevel = UserRole::from($user->role)->getAccessLevel();

        // Filter based on the access_level of the related Parent material
        return $query->whereHas('parent', function (Builder $query) use ($userLevel) {
            $query->where('access_level', '<=', (int) $userLevel);
        });
    }
}
