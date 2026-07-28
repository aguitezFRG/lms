<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Models\User;
use App\Policies\SystemUsagePolicy;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
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
        // Subquery to get top user IDs with their stats
        $userStatsSubquery = DB::table('material_access_events')
            ->select('user_id', DB::raw('COUNT(*) as request_count'), DB::raw('MAX(created_at) as last_activity'))
            ->whereIn('event_type', ['request', 'borrow'])
            ->groupBy('user_id')
            ->orderByDesc('request_count')
            ->limit(5);

        // Main query: get User models with stats joined, ordered by request_count
        return $table
            ->query(
                User::query()
                    ->joinSub($userStatsSubquery, 'stats', function ($join) {
                        $join->on('users.id', '=', 'stats.user_id');
                    })
                    ->select('users.*', 'stats.request_count', 'stats.last_activity')
                    ->orderByDesc('stats.request_count')
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
