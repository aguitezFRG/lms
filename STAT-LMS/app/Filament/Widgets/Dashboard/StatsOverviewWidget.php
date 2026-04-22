<?php

namespace App\Filament\Widgets\Dashboard;

use App\Filament\Pages\Dashboard;
use App\Models\MaterialAccessEvents;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\HtmlString;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    /* Exposed so the Dashboard page can toggle visibility */
    public bool $visible = true;

    public static function canView(): bool
    {
        return Gate::allows('viewGeneral', Dashboard::class);
    }

    protected function getStats(): array
    {
        $currentPeriod = now();
        $previousPeriod = now()->subDay();

        $visitorTotal = MaterialAccessEvents::distinct('user_id')->count('user_id');
        $visitorToday = MaterialAccessEvents::whereDate('created_at', $currentPeriod)->distinct('user_id')->count('user_id');
        $visitorYesterday = MaterialAccessEvents::whereDate('created_at', $previousPeriod)->distinct('user_id')->count('user_id');
        $visitorChange = $this->calculateChange($visitorToday, $visitorYesterday);

        return [
            $this->makeStat('Borrowed', MaterialAccessEvents::where('event_type', 'borrow'), $currentPeriod, $previousPeriod, 'heroicon-o-book-open', 'primary'),
            $this->makeStat('Overdue', MaterialAccessEvents::where('is_overdue', true), $currentPeriod, $previousPeriod, 'heroicon-o-clock', 'danger'),
            $this->makeStat('Requests', MaterialAccessEvents::where('event_type', 'request'), $currentPeriod, $previousPeriod, 'heroicon-o-document-text', 'warning'),
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
                )),
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
        $total = (clone $query)->count();
        $today = (clone $query)->whereDate('created_at', $currentPeriod)->count();
        $yesterday = (clone $query)->whereDate('created_at', $previousPeriod)->count();
        $change = $this->calculateChange($today, $yesterday);

        return Stat::make($label, $total)
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
            ));
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
