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
        $endYear = (int) $this->inputEndYear;

        if ($startMonth < 1 || $startMonth > 12 || $endMonth < 1 || $endMonth > 12) {
            return;
        }

        if ($startMonth > $endMonth) {
            return;
        }

        if ($endYear < 1900 || $endYear > now()->year + 50) {
            return;
        }

        $candidate = [
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'endYear' => $endYear,
        ];

        if ($this->isDuplicateOrNestedTimeFrame($candidate)) {
            return;
        }

        $this->frames[] = $candidate;

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

    private function isDuplicateOrNestedTimeFrame(array $candidate): bool
    {
        [$candidateStart, $candidateEnd] = $this->timeFrameBounds($candidate);

        foreach ($this->frames as $frame) {
            [$existingStart, $existingEnd] = $this->timeFrameBounds($frame);

            $isDuplicate = $candidateStart->equalTo($existingStart)
                && $candidateEnd->equalTo($existingEnd);

            $candidateContainsExisting = $candidateStart->lessThanOrEqualTo($existingStart)
                && $candidateEnd->greaterThanOrEqualTo($existingEnd);

            $existingContainsCandidate = $existingStart->lessThanOrEqualTo($candidateStart)
                && $existingEnd->greaterThanOrEqualTo($candidateEnd);

            if ($isDuplicate || $candidateContainsExisting || $existingContainsCandidate) {
                return true;
            }
        }

        return false;
    }

    private function timeFrameBounds(array $frame): array
    {
        $start = Carbon::create($frame['endYear'] - 4, $frame['startMonth'], 1)->startOfMonth();
        $end = Carbon::create($frame['endYear'], $frame['endMonth'], 1)->endOfMonth();

        return [$start, $end];
    }

    public function getChartData(): array
    {
        if (empty($this->frames)) {
            return ['labels' => [], 'datasets' => []];
        }

        $multiFrame = count($this->frames) > 1;
        $yearExpr = $this->yearExpression();
        $minimumYear = min(array_map(fn (array $frame): int => $frame['endYear'] - 4, $this->frames));
        $maximumYear = max(array_column($this->frames, 'endYear'));
        $years = range($minimumYear, $maximumYear);
        $labels = array_map(fn (int $year): string => (string) $year, $years);
        $datasets = [];

        foreach ($this->frames as $frameIndex => $frame) {
            $startYear = $frame['endYear'] - 4;
            $endYear = $frame['endYear'];
            $monthAbbr = Carbon::create(2000, $frame['startMonth'])->format('M');
            $endMonthAbbr = Carbon::create(2000, $frame['endMonth'])->format('M');
            $suffix = $multiFrame ? " ({$monthAbbr}–{$endMonthAbbr} {$startYear}–{$endYear})" : '';

            $borderDash = match ($frameIndex % 3) {
                1 => [6, 3],
                2 => [2, 2],
                default => [],
            };

            $rows = RrMaterialParents::query()
                ->selectRaw("{$yearExpr} as yr, material_type, COUNT(*) as cnt")
                ->where(function ($query) use ($frame, $startYear, $endYear) {
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $query->orWhere(function ($subQuery) use ($year, $frame) {
                            $subQuery->whereYear('created_at', $year)
                                ->whereMonth('created_at', '>=', $frame['startMonth'])
                                ->whereMonth('created_at', '<=', $frame['endMonth']);
                        });
                    }
                })
                ->groupBy('yr', 'material_type')
                ->get()
                ->groupBy('material_type')
                ->map(fn ($group) => $group->keyBy(fn ($row): int => (int) $row->yr));

            $totalsByYear = $rows
                ->flatten(1)
                ->groupBy(fn ($row): int => (int) $row->yr)
                ->map(fn ($group): int => (int) $group->sum('cnt'));

            $totalData = array_map(
                fn (int $year): ?int => $year < $startYear || $year > $endYear
                    ? null
                    : (int) $totalsByYear->get($year, 0),
                $years
            );

            $datasets[] = [
                'label' => 'Total'.$suffix,
                'data' => $totalData,
                'borderColor' => self::TYPE_COLORS['Total'],
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'borderDash' => $borderDash,
                'tension' => 0.3,
                'pointRadius' => 4,
                'spanGaps' => false,
            ];

            foreach (self::TYPE_MAP as $typeId => $typeName) {
                $typeRows = $rows->get($typeId, collect());

                $data = array_map(
                    fn (int $year): ?int => $year < $startYear || $year > $endYear
                        ? null
                        : (int) ($typeRows->get($year)?->cnt ?? 0),
                    $years
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
                    'spanGaps' => false,
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
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Year',
                    ],
                ],
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
