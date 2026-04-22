<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\MaterialAccessEvents;
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
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->selectRaw('user_id as id, user_id, COUNT(*) as request_count, MAX(created_at) as last_activity')
                    ->whereIn('event_type', ['request', 'borrow'])
                    ->groupBy('user_id')
                    ->orderByDesc('request_count')
                    ->limit(5)
                    ->with('user')
            )
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->state(fn ($record, $rowLoop) => $rowLoop->iteration)
                    ->width('60px')
                    ->alignment('center'),

                TextColumn::make('user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('medium'),

                TextColumn::make('user.email')
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
