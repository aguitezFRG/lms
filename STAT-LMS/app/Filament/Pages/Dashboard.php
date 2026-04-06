<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Dashboard\PhysicalDigitalChartWidget;
use App\Filament\Widgets\Dashboard\StatsOverviewWidget;
use App\Filament\Widgets\Dashboard\VisitorBorrowerChartWidget;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Support\Htmlable;

use Filament\Forms\Components\Select;

use Illuminate\Support\Facades\Gate;

class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected ?string $pollingInterval = '8s'; // poll every 8 seconds for real-time updates

    protected $listeners = ['request-actioned' => '$refresh'];

    public string $activeTab = 'general';

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
            'access'  => Gate::allows('viewAccess', static::class),
            default   => false,
        };

        if ($allowed) {
            $this->activeTab = $tab;
        }
    }

    // ── Widget Registration (General tab only) ────────────────────────────────

    public function getWidgets(): array
    {
        return [
            StatsOverviewWidget::class,
            VisitorBorrowerChartWidget::class,
            PhysicalDigitalChartWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 1;
    }

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        return [
            'activeTab'          => $this->activeTab,
            'canViewGeneral'     => Gate::allows('viewGeneral', static::class),
            'canViewBorrows'     => Gate::allows('viewBorrows', static::class),
            'canViewAccess'      => Gate::allows('viewAccess', static::class),
            'pendingBorrowCount' => MaterialAccessEvents::where('event_type', 'borrow')
                ->where('status', 'pending')->count(),
            'pendingAccessCount' => Gate::allows('viewAccess', static::class)
                ? MaterialAccessEvents::where('event_type', 'request')
                    ->where('status', 'pending')->count()
                : 0,
        ];
    }
}