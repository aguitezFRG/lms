<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Dashboard\StatsOverviewWidget;
use App\Models\MaterialAccessEvents;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected ?string $pollingInterval = '60s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public string $activeTab = 'general';

    public string $activeChartTab = 'visitor-borrower';

    public function mount(): void
    {
        if (! Gate::allows('viewGeneral', static::class)) {
            $this->activeTab = 'borrows';
        }
    }

    // ── Tab Switching ─────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $allowed = match ($tab) {
            'general' => Gate::allows('viewGeneral', static::class),
            'borrows' => Gate::allows('viewBorrows', static::class),
            'access' => Gate::allows('viewAccess', static::class),
            default => false,
        };

        if ($allowed) {
            $this->activeTab = $tab;
        }
    }

    public function setChartTab(string $tab): void
    {
        if (in_array($tab, ['visitor-borrower', 'physical-digital'])) {
            $this->activeChartTab = $tab;
        }
    }

    // ── Widget Registration (General tab only) ────────────────────────────────

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $canViewBorrows = Gate::allows('viewBorrows', static::class);
        $canViewAccess = Gate::allows('viewAccess', static::class);

        // Cache pending counts for 5 minutes — refreshed on action events and on each poll.
        $pendingBorrowCount = $canViewBorrows
            ? Cache::remember('dashboard.pending_borrows', 300, fn () => MaterialAccessEvents::where('event_type', 'borrow')
                ->where('status', 'pending')->count()
            )
            : 0;

        $pendingAccessCount = $canViewAccess
            ? Cache::remember('dashboard.pending_accesses', 300, fn () => MaterialAccessEvents::where('event_type', 'request')
                ->where('status', 'pending')->count()
            )
            : 0;

        return [
            'activeTab' => $this->activeTab,
            'activeChartTab' => $this->activeChartTab,
            'canViewGeneral' => Gate::allows('viewGeneral', static::class),
            'canViewBorrows' => $canViewBorrows,
            'canViewAccess' => $canViewAccess,
            'pendingBorrowCount' => $pendingBorrowCount,
            'pendingAccessCount' => $pendingAccessCount,
        ];
    }
}
