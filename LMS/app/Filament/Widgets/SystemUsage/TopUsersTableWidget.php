<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\MaterialAccessEvents;
use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Gate;

class TopUsersTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top 5 Most Active Users';

    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsage::class);
    }

    public function table(Table $table): Table
    {
        $requestCountSubquery = MaterialAccessEvents::query()
            ->selectRaw('COUNT(*)')
            ->whereColumn('material_access_events.user_id', 'users.id')
            ->whereIn('event_type', ['request', 'borrow']);

        $lastActivitySubquery = MaterialAccessEvents::query()
            ->selectRaw('MAX(created_at)')
            ->whereColumn('material_access_events.user_id', 'users.id')
            ->whereIn('event_type', ['request', 'borrow']);

        return $table
            ->defaultKeySort(false)
            ->query(
                User::query()
                    ->select('users.*')
                    ->selectSub($requestCountSubquery, 'request_count')
                    ->selectSub($lastActivitySubquery, 'last_activity')
                    ->whereExists(function ($query) {
                        $query->selectRaw('1')
                            ->from('material_access_events')
                            ->whereColumn('material_access_events.user_id', 'users.id')
                            ->whereIn('event_type', ['request', 'borrow']);
                    })
                    ->orderByDesc('request_count')
                    ->orderByDesc('last_activity')
                    ->limit(5)
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->state(fn ($_record, $rowLoop) => $rowLoop->iteration)
                    ->width('60px')
                    ->alignment('center'),

                TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('medium'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('request_count')
                    ->label('Requests')
                    ->numeric()
                    ->sortable()
                    ->alignment('center')
                    ->width('100px')
                    ->badge()
                    ->color('success'),

                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->emptyStateHeading('No active users found')
            ->emptyStateDescription('Once users start making requests, they will appear here.')
            ->emptyStateIcon('heroicon-o-users')
            ->paginated(false);
    }
}
