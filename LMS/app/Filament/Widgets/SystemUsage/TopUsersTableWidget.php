<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Models\User;
use App\Policies\SystemUsagePolicy;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class TopUsersTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top 5 Most Active Users';

    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = true;

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsagePolicy::class);
    }

    public function table(Table $table): Table
    {
        $activityFilter = fn (Builder $query): Builder => $query
            ->whereIn('event_type', ['request', 'borrow']);

        return $table
            ->query(
                User::query()
                    ->whereHas('materialAccessEvents', $activityFilter)
                    ->withCount([
                        'materialAccessEvents as request_count' => $activityFilter,
                    ])
                    ->withMax([
                        'materialAccessEvents as last_activity' => $activityFilter,
                    ], 'created_at')
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
            ->paginated(false)
            ->deferLoading();
    }
}
