<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class VisitorBorrowerChartWidget extends ChartWidget
{
    protected ?string $heading = 'Visitor & Borrower';

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
        return 'bar';
    }

    protected function getData(): array
    {
        [$labels, $visitors, $borrowers] = $this->buildSeries();

        return [
            'labels'   => $labels,
            'datasets' => [
                [
                    'label'           => 'Visitor',
                    'data'            => $visitors,
                    'backgroundColor' => '#1a3a8f',
                    'borderRadius'    => 4,
                ],
                [
                    'label'           => 'Borrower',
                    'data'            => $borrowers,
                    'backgroundColor' => '#F3AA2C',
                    'borderRadius'    => 4,
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
        $days   = match ($this->filter ?? 'weekly' ) {
            'monthly' => 30,
            'yearly' => 365,
            default => 7
        };
        $labels = $visitors = $borrowers = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date        = Carbon::today()->subDays($i);
            $labels[]    = $days <= 7 ? $date->format('D d') : $date->format('M d');
            $visitors[]  = MaterialAccessEvents::whereDate('created_at', $date)
                ->distinct('user_id')->count('user_id');
            $borrowers[] = MaterialAccessEvents::where('event_type', 'borrow')
                ->whereDate('created_at', $date)->count();
        }

        return [$labels, $visitors, $borrowers];
    }
}