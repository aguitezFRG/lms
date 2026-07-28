<?php

namespace App\Filament\Widgets\SystemUsage;

use App\Filament\Pages\SystemUsage;
use App\Models\RrMaterialParents;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class MaterialStatisticsWidget extends Widget
{
    protected string $view = 'filament.widgets.system-usage.material-statistics';

    protected int|string|array $columnSpan = 'full';

    protected static bool $isLazy = true;

    protected static ?string $pollingInterval = '120s';

    public array $frames = [];

    public int $inputStartMonth = 8;

    public int $inputEndMonth = 12;

    public ?int $inputEndYear = null;

    private const TYPE_MAP = [
        1 => 'Book',
        2 => 'Thesis',
        3 => 'Journal',
        4 => 'Dissertation',
        5 => 'Others',
    ];

    private const TYPE_COLORS = [
        'Total' => 'rgb(107,114,128)',
        'Book' => 'rgb(59,130,246)',
        'Thesis' => 'rgb(34,197,94)',
        'Journal' => 'rgb(249,115,22)',
        'Dissertation' => 'rgb(168,85,247)',
        'Others' => 'rgb(239,68,68)',
    ];

    public static function canView(): bool
    {
        return Gate::allows('viewAny', SystemUsage::class);
    }

    public function mount(): void
    {
        $this->inputEndYear = (int) now()->year;

        $this->frames = [
            [
                'startMonth' => 8,
                'endMonth' => 12,
                'endYear' => (int) now()->year,
            ],
        ];
    }

    public function addTimeFrame(): void
    {
        $startMonth = (int) $this->inputStartMonth;
        $endMonth = (int) $this->inputEndMonth;

        if ($startMonth < 1 || $startMonth > 12 || $endMonth < 1 || $endMonth > 12) {
            return;
        }

        if ($startMonth > $endMonth) {
            return;
        }

        if (empty($this->frames)) {
            $endYear = (int) $this->inputEndYear;
            if ($endYear < 1900 || $endYear > now()->year + 50) {
                return;
            }
        } else {
            $prev = last($this->frames);
            $endYear = $this->inferNextEndYear($prev['endMonth'], $prev['endYear'], $startMonth);
        }

        foreach ($this->frames as $f) {
            if ($f['startMonth'] === $startMonth && $f['endMonth'] === $endMonth && $f['endYear'] === $endYear) {
                return;
            }
        }

        $this->frames[] = [
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'endYear' => $endYear,
        ];

        $this->dispatch('updateChartData', data: $this->getChartData());
    }

    public function removeFrame(int $index): void
    {
        if (count($this->frames) <= 1) {
            return;
        }

        array_splice($this->frames, $index, 1);

        $this->dispatch('updateChartData', data: $this->getChartData());
    }

    private function inferNextEndYear(int $prevEndMonth, int $prevEndYear, int $newStartMonth): int
    {
        return $newStartMonth > $prevEndMonth ? $prevEndYear : $prevEndYear + 1;
    }

    public function getChartData(): array
    {
        if (empty($this->frames)) {
            return ['labels' => [], 'datasets' => []];
        }

        $multiFrame = count($this->frames) > 1;
        $yearExpr = $this->yearExpression();

        if ($multiFrame) {
            $labels = ['Year 1', 'Year 2', 'Year 3', 'Year 4', 'Year 5'];
        } else {
            $frame = $this->frames[0];
            $monthAbbr = Carbon::create(2000, $frame['startMonth'])->format('M');
            $labels = array_map(
                fn ($i) => $monthAbbr.'–'.($frame['endYear'] - 4 + $i),
                range(0, 4)
            );
        }

        $datasets = [];

        foreach ($this->frames as $frameIndex => $frame) {
            $startYear = $frame['endYear'] - 4;
            $endYear = $frame['endYear'];
            $monthAbbr = Carbon::create(2000, $frame['startMonth'])->format('M');
            $endMonthAbbr = Carbon::create(2000, $frame['endMonth'])->format('M');
            $suffix = $multiFrame ? " ({$monthAbbr}–{$endMonthAbbr} {$endYear})" : '';

            $borderDash = match ($frameIndex % 3) {
                1 => [6, 3],
                2 => [2, 2],
                default => [],
            };

            $rows = RrMaterialParents::query()
                ->selectRaw("{$yearExpr} as yr, material_type, COUNT(*) as cnt")
                ->where(function ($q) use ($frame, $startYear, $endYear) {
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $q->orWhere(function ($sub) use ($year, $frame) {
                            $sub->whereYear('created_at', $year)
                                ->whereMonth('created_at', '>=', $frame['startMonth'])
                                ->whereMonth('created_at', '<=', $frame['endMonth']);
                        });
                    }
                })
                ->groupBy('yr', 'material_type')
                ->get()
                ->groupBy('material_type')
                ->map(fn ($g) => $g->keyBy('yr'));

            $totalData = array_map(function ($offset) use ($rows, $startYear) {
                return (int) $rows->flatten(1)->where('yr', $startYear + $offset)->sum('cnt');
            }, range(0, 4));

            $datasets[] = [
                'label' => 'Total'.$suffix,
                'data' => $totalData,
                'borderColor' => self::TYPE_COLORS['Total'],
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'borderDash' => $borderDash,
                'tension' => 0.3,
                'pointRadius' => 4,
            ];

            foreach (self::TYPE_MAP as $typeId => $typeName) {
                $typeRows = $rows->get($typeId, collect());

                $data = array_map(
                    fn ($offset) => (int) ($typeRows->get($startYear + $offset)?->cnt ?? 0),
                    range(0, 4)
                );

                $datasets[] = [
                    'label' => $typeName.$suffix,
                    'data' => $data,
                    'borderColor' => self::TYPE_COLORS[$typeName],
                    'backgroundColor' => 'transparent',
                    'borderWidth' => 2,
                    'borderDash' => $borderDash,
                    'tension' => 0.3,
                    'pointRadius' => 4,
                ];
            }
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    public function getChartOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 16,
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    private function yearExpression(): string
    {
        return match (DB::getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(YEAR FROM created_at)::integer',
            'mysql', 'mariadb' => 'YEAR(created_at)',
            default => throw new LogicException('Unsupported database driver for material statistics grouping.'),
        };
    }
}
