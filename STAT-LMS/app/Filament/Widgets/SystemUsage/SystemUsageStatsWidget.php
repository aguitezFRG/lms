<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Models\MaterialAccessEvents;
use App\Policies\SystemUsagePolicy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class SystemUsageStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsagePolicy::class);
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
        $currentCount = (clone $query)->count();

        $todayCount = (clone $query)
            ->whereDate('created_at', $currentPeriod)
            ->count();

        $yesterdayCount = (clone $query)
            ->whereDate('created_at', $previousPeriod)
            ->count();

        $change = $this->calculateChange($todayCount, $yesterdayCount);

        $changeColor = match ($change['color']) {
            'success' => 'rgb(22, 163, 74)',
            'danger' => 'rgb(220, 38, 38)',
            'warning' => 'rgb(202, 138, 4)',
            'gray' => 'rgb(75, 85, 99)',
            default => 'rgb(37, 99, 235)',
        };

        return Stat::make($label, $currentCount)
            ->icon($icon)
            ->color($color)
            ->description(new HtmlString(
                "<span style=\"color: rgb(156, 163, 175);\">{$todayCount} today, {$yesterdayCount} yesterday</span><br>".
                "<span style=\"color: {$changeColor};\">{$change['text']}</span>"
            ));
    }

    private function calculateChange(int $today, int $yesterday): array
    {
        if ($yesterday === 0) {
            if ($today === 0) {
                return [
                    'text' => 'No change',
                    'icon' => 'heroicon-o-minus',
                    'color' => 'gray',
                ];
            }

            return [
                'text' => "+{$today} today",
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'success',
            ];
        }

        $pct = round((($today - $yesterday) / $yesterday) * 100);
        $sign = $pct > 0 ? '+' : '';

        if ($pct === 0) {
            return [
                'text' => 'No change',
                'icon' => 'heroicon-o-minus',
                'color' => 'gray',
            ];
        }

        if ($pct > 0) {
            return [
                'text' => "{$sign}{$pct}% from yesterday",
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'success',
            ];
        }

        return [
            'text' => "{$sign}{$pct}% from yesterday",
            'icon' => 'heroicon-o-arrow-trending-down',
            'color' => 'danger',
        ];
    }
}
