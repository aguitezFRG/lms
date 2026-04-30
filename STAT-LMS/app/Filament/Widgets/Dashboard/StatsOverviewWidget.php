<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Dashboard;
use App\Models\MaterialAccessEvents;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = true;

    private const STAT_CARD_MIN_HEIGHT_CLASS = 'min-h-36';
    private const PLACEHOLDER_COLUMNS = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $placeholderHeight = '8rem';

    protected int|string|array $columnSpan = 'full';
    protected int|array|null $columns = [
        'md' => 4,
        'xl' => 4,
    ];

    protected ?string $pollingInterval = '60s';

    /* Exposed so the Dashboard page can toggle visibility */
    public bool $visible = true;

    public static function canView(): bool
    {
        return Gate::allows('viewGeneral', Dashboard::class);
    }

    public function placeholder(): View
    {
        return view('filament.widgets.stats-overview-skeleton', [
            'cards' => 4,
            'columns' => self::PLACEHOLDER_COLUMNS,
        ]);
    }

    protected function getStats(): array
    {
        $currentPeriod = now()->toDateString();
        $previousPeriod = now()->subDay()->toDateString();

        $metrics = Cache::remember(
            "dashboard.stats-overview.{$currentPeriod}",
            now()->addMinutes(5),
            function () use ($currentPeriod, $previousPeriod): array {
                return [
                    'borrowed' => $this->collectDailyMetrics(
                        MaterialAccessEvents::where('event_type', 'borrow'),
                        $currentPeriod,
                        $previousPeriod
                    ),
                    'overdue' => $this->collectDailyMetrics(
                        MaterialAccessEvents::where('is_overdue', true),
                        $currentPeriod,
                        $previousPeriod
                    ),
                    'requests' => $this->collectDailyMetrics(
                        MaterialAccessEvents::where('event_type', 'request'),
                        $currentPeriod,
                        $previousPeriod
                    ),
                    'visitors' => [
                        'total' => MaterialAccessEvents::distinct('user_id')->count('user_id'),
                        'today' => MaterialAccessEvents::whereDate('created_at', $currentPeriod)->distinct('user_id')->count('user_id'),
                        'yesterday' => MaterialAccessEvents::whereDate('created_at', $previousPeriod)->distinct('user_id')->count('user_id'),
                    ],
                ];
            }
        );

        $visitorTotal = $metrics['visitors']['total'];
        $visitorToday = $metrics['visitors']['today'];
        $visitorYesterday = $metrics['visitors']['yesterday'];
        $visitorChange = $this->calculateChange($visitorToday, $visitorYesterday);

        return [
            $this->makeStat('Borrowed', $metrics['borrowed'], 'heroicon-o-book-open', 'primary'),
            $this->makeStat('Overdue', $metrics['overdue'], 'heroicon-o-clock', 'danger'),
            $this->makeStat('Requests', $metrics['requests'], 'heroicon-o-document-text', 'warning'),
            $this->withUniformHeight(
                Stat::make('Visitors', $visitorTotal)
                    ->icon('heroicon-o-users')
                    ->color('info')
                    ->description(new HtmlString(
                        "<span style=\"color: rgb(156, 163, 175);\">{$visitorToday} today, {$visitorYesterday} yesterday</span><br>".
                        '<span style="'.match ($visitorChange['color']) {
                            'success' => 'color: rgb(22, 163, 74);',
                            'danger' => 'color: rgb(220, 38, 38);',
                            'warning' => 'color: rgb(202, 138, 4);',
                            'gray' => 'color: rgb(75, 85, 99);',
                            default => 'color: rgb(37, 99, 235);',
                        }."\">{$visitorChange['text']}</span>"
                    ))
            ),
        ];
    }

    private function makeStat(
        string $label,
        array $metrics,
        string $icon,
        string $color
    ): Stat {
        $total = $metrics['total'];
        $today = $metrics['today'];
        $yesterday = $metrics['yesterday'];
        $change = $this->calculateChange($today, $yesterday);

        return $this->withUniformHeight(
            Stat::make($label, $total)
                ->icon($icon)
                ->color($color)
                ->description(new HtmlString(
                    "<span style=\"color: rgb(156, 163, 175);\">{$today} today, {$yesterday} yesterday</span><br>".
                    '<span style="'.match ($change['color']) {
                        'success' => 'color: rgb(22, 163, 74);',
                        'danger' => 'color: rgb(220, 38, 38);',
                        'warning' => 'color: rgb(202, 138, 4);',
                        'gray' => 'color: rgb(75, 85, 99);',
                        default => 'color: rgb(37, 99, 235);',
                    }."\">{$change['text']}</span>"
                ))
        );
    }

    private function withUniformHeight(Stat $stat): Stat
    {
        return $stat->extraAttributes([
            'class' => self::STAT_CARD_MIN_HEIGHT_CLASS,
        ]);
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
        if ($yesterday == 0) {
            if ($today == 0) {
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

        if ($pct == 0) {
            return [
                'text' => 'No change',
                'icon' => 'heroicon-o-minus',
                'color' => 'gray',
            ];
        }

        if ($pct <= 0) {
            return [
                'text' => "{$sign}{$pct}% from yesterday",
                'icon' => 'heroicon-o-arrow-trending-down',
                'color' => 'danger',
            ];
        }

        return [
            'text' => "{$sign}{$pct}% from yesterday",
            'icon' => 'heroicon-o-arrow-trending-up',
            'color' => 'success',
        ];

    }
}
