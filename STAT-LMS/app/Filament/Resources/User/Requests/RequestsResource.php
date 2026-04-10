<?php

namespace App\Filament\Resources\User\Requests;

use App\Filament\Resources\MaterialAccessEvents\Schemas\MaterialAccessEventsInfolist;
use App\Filament\Resources\User\Requests\Pages\ListRequests;
use App\Filament\Resources\User\Requests\Pages\ViewRequests;
use App\Models\MaterialAccessEvents;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use Filament\Facades\Filament;

class RequestsResource extends Resource
{
    protected static ?string $model = MaterialAccessEvents::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'My Requests';

    // protected static string | UnitEnum | null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 3;

    protected static ?string $breadcrumb = 'My Requests';

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
        return 'Request';
    }

    public static function getPluralLabel(): string
    {
        return 'My Requests';
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaterialAccessEventsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        // Configured directly in ListRequests
        return $table;
    }

    /**
     * Always scope to the authenticated user — users must never
     * see other people's requests.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id())
            ->whereIn('event_type', ['request', 'borrow']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRequests::route('/'),
            'view'  => ViewRequests::route('/{record}'),
        ];
    }
}