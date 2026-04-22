<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\MaterialAccessEvents;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;

class SystemUsageStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsage::class);
    }

    protected function getStats(): array
    {
        $baseQuery = MaterialAccessEvents::query()
            ->whereIn('event_type', ['request', 'borrow']);

        $currentPeriod = now();
        $previousPeriod = now()->subDay();

        return [
            $this->makeStat(
                'Total',
                (clone $baseQuery),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-clipboard-document-list',
                'gray'
            ),
            $this->makeStat(
                'Pending',
                (clone $baseQuery)->where('status', 'pending'),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-clock',
                'warning'
            ),
            $this->makeStat(
                'Approved',
                (clone $baseQuery)->where('status', 'approved'),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-check-circle',
                'success'
            ),
            $this->makeStat(
                'Rejected',
                (clone $baseQuery)->where('status', 'rejected'),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-x-circle',
                'danger'
            ),
            $this->makeStat(
                'Revoked',
                (clone $baseQuery)->where('status', 'revoked'),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-no-symbol',
                'gray'
            ),
            $this->makeStat(
                'Overdue',
                (clone $baseQuery)->where('is_overdue', true),
                $currentPeriod,
                $previousPeriod,
                'heroicon-o-exclamation-triangle',
                'danger'
            ),
        ];
    }

    private function makeStat(
        string $label,
        $query,
        $currentPeriod,
        $previousPeriod,
        string $icon,
        string $color
    ): Stat {
        $currentCount = $query->count();

        $previousCountQuery = clone $query;
        $previousCount = $previousCountQuery
            ->whereDate('created_at', '<=', $previousPeriod)
            ->count();

        $todayCount = $query
            ->whereDate('created_at', $currentPeriod)
            ->count();

        $yesterdayCount = (clone $query)
            ->whereDate('created_at', $previousPeriod)
            ->count();

        $change = $this->calculateChange($todayCount, $yesterdayCount);

        return Stat::make($label, $currentCount)
            ->icon($icon)
            ->color($color)
            ->description($change['text'])
            ->descriptionIcon($change['icon'])
            ->descriptionColor($change['color']);
    }

    private function calculateChange(int $today, int $yesterday): array
    {
        if ($yesterday === 0) {
            if ($today === 0) {
                return [
                    'text' => 'No change from yesterday',
                    'icon' => 'heroicon-o-minus',
                    'color' => 'gray',
                ];
            }

            return [
                'text' => "+{$today} from yesterday",
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'success',
            ];
        }

        $percentage = round((($today - $yesterday) / $yesterday) * 100);
        $absoluteChange = abs($today - $yesterday);

        if ($percentage === 0) {
            return [
                'text' => 'No change from yesterday',
                'icon' => 'heroicon-o-minus',
                'color' => 'gray',
            ];
        }

        if ($today > $yesterday) {
            return [
                'text' => "+{$absoluteChange} (+{$percentage}%) from yesterday",
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'success',
            ];
        }

        return [
            'text' => "-{$absoluteChange} ({$percentage}%) from yesterday",
            'icon' => 'heroicon-o-arrow-trending-down',
            'color' => 'danger',
        ];
    }
}
