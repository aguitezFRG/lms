<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PhysicalDigitalChartWidget extends ChartWidget
{
    protected ?string $heading = 'Physical vs Digital Materials';

    protected int|string|array $columnSpan = 1;

    protected ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    protected ?int $numDays = 5;

    protected ?int $numWeeks = 5;

    protected ?int $numMonths = 5;

    protected ?int $numYears = 5;

    protected function getFilters(): ?array
    {
        return [
            'daily'   => 'Daily',
            'weekly'  => 'Weekly',
            'monthly' => 'Monthly',
            'yearly'  => 'Yearly',
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        [$labels, $physical, $digital] = $this->buildSeries();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Physical Copies',
                    'data' => $physical,
                    'borderColor' => '#1a3a8f',
                    'backgroundColor' => 'rgba(26,58,143,0.08)',
                    'tension' => 0.4,
                    'fill' => false,
                    'pointBackgroundColor' => '#1a3a8f',
                ],
                [
                    'label' => 'Digital Access',
                    'data' => $digital,
                    'borderColor' => '#F3AA2C',
                    'backgroundColor' => 'rgba(243,170,44,0.08)',
                    'tension' => 0.4,
                    'fill' => false,
                    'pointBackgroundColor' => '#F3AA2C',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => ['legend' => ['display' => true]],
            'scales' => [
                'x' => ['grid' => (object) []],
                'y' => ['grid' => (object) [], 'beginAtZero' => true],
            ],
            'layout' => [
                'padding' => ['bottom' => 0],
            ],
        ];
    }

    protected function getContainerStyle(): string
    {
        return 'max-height: 75vh; height: 75vh;';
    }

    private function buildSeries(): array
    {
        $labels = $physical = $digital = [];

        match ($this->filter ?? 'daily') {

            'daily' => (function () use (&$labels, &$physical, &$digital) {
                for ($i = $this->numDays; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);

                    $labels[] = $i === 0 ? 'Today' : $date->format('D, M d');
                    $physical[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereDate('created_at', $date)->count();
                    $digital[] = MaterialAccessEvents::where('event_type', 'request')
                        ->whereDate('created_at', $date)->count();
                }
            })(),

            'weekly' => (function () use (&$labels, &$physical, &$digital) {
                for ($i = $this->numWeeks - 1; $i >= 0; $i--) {
                    $start = Carbon::today()->startOfWeek()->subWeeks($i);
                    $end = $start->copy()->endOfWeek();

                    $labels[] = $start->format('M d').'–'.$end->format('M d');
                    $physical[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                    $digital[] = MaterialAccessEvents::where('event_type', 'request')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            'monthly' => (function () use (&$labels, &$physical, &$digital) {
                for ($i = $this->numMonths - 1; $i >= 0; $i--) {
                    $month = Carbon::today()->startOfMonth()->subMonths($i);
                    $start = $month->copy()->startOfMonth();
                    $end = $month->copy()->endOfMonth();

                    $labels[] = $month->format('M Y');
                    $physical[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                    $digital[] = MaterialAccessEvents::where('event_type', 'request')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            'yearly' => (function () use (&$labels, &$physical, &$digital) {
                for ($i = $this->numYears; $i >= 0; $i--) {
                    $year = Carbon::today()->startOfYear()->subYears($i);
                    $start = $year->copy()->startOfYear();
                    $end = $year->copy()->endOfYear();

                    $labels[] = $year->format('Y');
                    $physical[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                    $digital[] = MaterialAccessEvents::where('event_type', 'request')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            default => throw new \InvalidArgumentException("Invalid filter value: {$this->filter}"),
        };

        return [$labels, $physical, $digital];
    }
}
