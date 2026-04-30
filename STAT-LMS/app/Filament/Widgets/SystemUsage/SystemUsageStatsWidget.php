<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Models\MaterialAccessEvents;
use App\Policies\SystemUsagePolicy;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class SystemUsageStatsWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    private const PLACEHOLDER_COLUMNS = [
        'lg' => 3,
        'xl' => 3,
    ];

    protected ?string $placeholderHeight = '8rem';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsagePolicy::class);
    }

    public function placeholder(): View
    {
        return view('filament.widgets.stats-overview-skeleton', [
            'cards' => 6,
            'columns' => self::PLACEHOLDER_COLUMNS,
        ]);
    }

    protected function getStats(): array
    {
        $currentPeriod = now()->toDateString();
        $previousPeriod = now()->subDay()->toDateString();

        $metrics = Cache::remember(
            "system-usage.stats.{$currentPeriod}",
            now()->addMinutes(5),
            function () use ($currentPeriod, $previousPeriod): array {
                $baseQuery = MaterialAccessEvents::query()
                    ->whereIn('event_type', ['request', 'borrow']);

                return [
                    'total' => $this->collectDailyMetrics(clone $baseQuery, $currentPeriod, $previousPeriod),
                    'pending' => $this->collectDailyMetrics((clone $baseQuery)->where('status', 'pending'), $currentPeriod, $previousPeriod),
                    'approved' => $this->collectDailyMetrics((clone $baseQuery)->where('status', 'approved'), $currentPeriod, $previousPeriod),
                    'rejected' => $this->collectDailyMetrics((clone $baseQuery)->where('status', 'rejected'), $currentPeriod, $previousPeriod),
                    'revoked' => $this->collectDailyMetrics((clone $baseQuery)->where('status', 'revoked'), $currentPeriod, $previousPeriod),
                    'overdue' => $this->collectDailyMetrics((clone $baseQuery)->where('is_overdue', true), $currentPeriod, $previousPeriod),
                ];
            }
        );

        return [
            $this->makeStat(
                'Total',
                $metrics['total'],
                'heroicon-o-clipboard-document-list',
                'gray'
            ),
            $this->makeStat(
                'Pending',
                $metrics['pending'],
                'heroicon-o-clock',
                'warning'
            ),
            $this->makeStat(
                'Approved',
                $metrics['approved'],
                'heroicon-o-check-circle',
                'success'
            ),
            $this->makeStat(
                'Rejected',
                $metrics['rejected'],
                'heroicon-o-x-circle',
                'danger'
            ),
            $this->makeStat(
                'Revoked',
                $metrics['revoked'],
                'heroicon-o-no-symbol',
                'gray'
            ),
            $this->makeStat(
                'Overdue',
                $metrics['overdue'],
                'heroicon-o-exclamation-triangle',
                'danger'
            ),
        ];
    }

    private function makeStat(
        string $label,
        array $metrics,
        string $icon,
        string $color
    ): Stat {
        $currentCount = $metrics['total'];
        $todayCount = $metrics['today'];
        $yesterdayCount = $metrics['yesterday'];

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

    private function collectDailyMetrics($query, string $currentPeriod, string $previousPeriod): array
    {
        return [
            'total' => (clone $query)->count(),
            'today' => (clone $query)->whereDate('created_at', $currentPeriod)->count(),
            'yesterday' => (clone $query)->whereDate('created_at', $previousPeriod)->count(),
        ];
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
