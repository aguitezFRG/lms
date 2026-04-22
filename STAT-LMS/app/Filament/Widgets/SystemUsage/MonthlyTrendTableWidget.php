<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\MaterialAccessEvents;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Gate;

class MonthlyTrendTableWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Borrowing Trend (Last 6 Months)';

    protected static ?string $pollingInterval = '120s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsage::class);
    }

    public function getTableRecordKey($record): string
    {
        return $record->month;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MaterialAccessEvents::query()
                    ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count, MIN(id) as sort_id")
                    ->whereIn('event_type', ['request', 'borrow'])
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
                    ->orderBy('month')
            )
            ->columns([
                TextColumn::make('month')
                    ->label('Month')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => \Carbon\Carbon::createFromFormat('Y-m', $state)->format('F Y')),

                TextColumn::make('count')
                    ->label('Requests')
                    ->sortable()
                    ->alignment('center')
                    ->width('120px')
                    ->badge()
                    ->color('info'),

                TextColumn::make('change')
                    ->label('Change from Previous')
                    ->state(function ($record, $rowLoop) {
                        if ($rowLoop->iteration === 1) {
                            return '—';
                        }

                        $current = $record->count;
                        $previousRow = $this->getTable()->getRecords()->get($rowLoop->index - 1);
                        $previous = $previousRow?->count ?? 0;

                        if ($previous === 0) {
                            return $current > 0 ? '+100%' : '0%';
                        }

                        $change = round((($current - $previous) / $previous) * 100);

                        return ($change >= 0 ? '+' : '').$change.'%';
                    })
                    ->alignment('center')
                    ->width('150px')
                    ->color(function ($state) {
                        if ($state === '—') {
                            return 'gray';
                        }
                        $value = (int) str_replace(['+', '%'], '', $state);

                        return $value > 0 ? 'success' : ($value < 0 ? 'danger' : 'gray');
                    })
                    ->icon(function ($state) {
                        if ($state === '—') {
                            return null;
                        }
                        $value = (int) str_replace(['+', '%'], '', $state);

                        return $value > 0 ? 'heroicon-o-arrow-trending-up' : ($value < 0 ? 'heroicon-o-arrow-trending-down' : 'heroicon-o-minus');
                    }),
            ])
            ->emptyStateHeading('No borrowing activity recorded')
            ->emptyStateDescription('Activity from the last 6 months will appear here.')
            ->emptyStateIcon('heroicon-o-calendar')
            ->paginated(false)
            ->deferLoading();
    }
}
