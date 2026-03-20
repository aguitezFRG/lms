<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class PhysicalDigitalChartWidget extends ChartWidget
{
    protected ?string $heading = 'Physical vs Digital Materials';

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 1;

    protected ?string $pollingInterval = '60s';

    protected function getFilters(): ?array
    {
        return [
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
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Physical Copies',
                    'data'            => $physical,
                    'borderColor'     => '#1a3a8f',
                    'backgroundColor' => 'rgba(26,58,143,0.08)',
                    'tension'         => 0.4,
                    'fill'            => false,
                    'pointBackgroundColor' => '#1a3a8f',
                ],
                [
                    'label'           => 'Digital Access',
                    'data'            => $digital,
                    'borderColor'     => '#F3AA2C',
                    'backgroundColor' => 'rgba(243,170,44,0.08)',
                    'tension'         => 0.4,
                    'fill'            => false,
                    'pointBackgroundColor' => '#F3AA2C',
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales'  => [
                'x' => ['grid' => ['color' => 'rgba(156,163,175,0.2)']],
                'y' => ['grid' => ['color' => 'rgba(156,163,175,0.2)'], 'beginAtZero' => true],
            ],
        ];
    }

    private function buildSeries(): array
    {
        $days    = match ($this->filter ?? 'weekly') {
            'monthly' => 30,
            'yearly' => 365,
            default => 7
        };
        $labels  = $physical = $digital = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date      = Carbon::today()->subDays($i);
            $labels[]  = $days <= 7 ? $date->format('D d') : $date->format('M d');
            $physical[] = MaterialAccessEvents::where('event_type', 'borrow')
                ->whereDate('created_at', $date)->count();
            $digital[]  = MaterialAccessEvents::where('event_type', 'request')
                ->whereDate('created_at', $date)->count();
        }

        return [$labels, $physical, $digital];
    }
}