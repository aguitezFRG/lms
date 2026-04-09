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

    protected ?string $pollingInterval = '120s';

    protected ?int $numDays = 4;

    protected ?int $numWeeks = 4;

    protected ?int $numMonths = 4;

    protected ?int $numYears = 4;

    protected function getFilters(): ?array
    {
        return [
            'daily'  => 'Daily',
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
                    'barThickness'    => 50,
                ],
                [
                    'label'           => 'Borrower',
                    'data'            => $borrowers,
                    'backgroundColor' => '#F3AA2C',
                    'borderRadius'    => 4,
                    'barThickness'    => 50,
                ],
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true]],
            'scales'  => [
                'x' => ['grid' => ['color' => 'rgba(156,163,175,0.2)']],
                'y' => ['grid' => ['color' => 'rgba(156,163,175,0.2)'], 'beginAtZero' => true],
            ],
        ];
    }

    private function buildSeries(): array
    {
        $labels = $visitors = $borrowers = [];

        match ($this->filter ?? 'daily') {

            'daily' => (function () use (&$labels, &$visitors, &$borrowers) {
                for ($i = $this->numDays; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);

                    $labels[]    = $i === 0 ? 'Today' : $date->format('D, M d');
                    $visitors[]  = MaterialAccessEvents::whereDate('created_at', $date)
                        ->distinct('user_id')->count('user_id');
                    $borrowers[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereDate('created_at', $date)->count();
                }
            })(),

            'weekly' => (function () use (&$labels, &$visitors, &$borrowers) {
                for ($i = $this->numWeeks; $i >= 0; $i--) {
                    $start = Carbon::today()->startOfWeek()->subWeeks($i);
                    $end   = $start->copy()->endOfWeek();

                    $labels[]    = $start->format('M d') . '–' . $end->format('M d');
                    $visitors[]  = MaterialAccessEvents::whereBetween('created_at', [$start, $end])
                        ->distinct('user_id')->count('user_id');
                    $borrowers[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            'monthly' => (function () use (&$labels, &$visitors, &$borrowers) {
                for ($i = $this->numMonths; $i >= 0; $i--) {
                    $month = Carbon::today()->startOfMonth()->subMonths($i);
                    $start = $month->copy()->startOfMonth();
                    $end   = $month->copy()->endOfMonth();

                    $labels[]    = $month->format('M Y');
                    $visitors[]  = MaterialAccessEvents::whereBetween('created_at', [$start, $end])
                        ->distinct('user_id')->count('user_id');
                    $borrowers[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            'yearly' => (function () use (&$labels, &$visitors, &$borrowers) {
                for ($i = $this->numYears; $i >= 0; $i--) {
                    $year  = Carbon::today()->startOfYear()->subYears($i);
                    $start = $year->copy()->startOfYear();
                    $end   = $year->copy()->endOfYear();

                    $labels[]    = $year->format('Y');
                    $visitors[]  = MaterialAccessEvents::whereBetween('created_at', [$start, $end])
                        ->distinct('user_id')->count('user_id');
                    $borrowers[] = MaterialAccessEvents::where('event_type', 'borrow')
                        ->whereBetween('created_at', [$start, $end])->count();
                }
            })(),

            default => throw new \InvalidArgumentException("Invalid filter value: {$this->filter}"),
        };

        return [$labels, $visitors, $borrowers];
    }
}