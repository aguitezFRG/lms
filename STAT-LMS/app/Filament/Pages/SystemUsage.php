<?php

namespace App\Filament\Pages;

use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\User;
use App\Enums\UserRole;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;

class SystemUsage extends Page
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.system-usage';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::ArrowTrendingUp;

    protected static ?string $navigationLabel = 'System Usage ';

    protected static string | \UnitEnum | null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'summary';

    // Filter state
    public ?string $filterStatus   = null;
    public ?string $filterType     = null;
    public ?string $filterDateFrom = null;
    public ?string $filterDateTo   = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, [
            UserRole::COMMITTEE->value,
            UserRole::IT->value,
            UserRole::RR->value,
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    // ── Summary Statistics ────────────────────────────────────────────────────

    public function getSummaryStats(): array
    {
        $query = MaterialAccessEvents::query()
            ->with(['material.parent', 'user'])
            ->whereIn('event_type', ['request', 'borrow']);

        $total      = $query->count();
        $pending    = (clone $query)->where('status', 'pending')->count();
        $approved   = (clone $query)->where('status', 'approved')->count();
        $rejected   = (clone $query)->where('status', 'rejected')->count();
        $revoked    = (clone $query)->where('status', 'revoked')->count();
        $overdue    = (clone $query)->where('is_overdue', true)->count();
        $overdueRate = $total > 0 ? round(($overdue / $total) * 100, 1) : 0;

        // Most requested materials (top 5)
        $topMaterials = MaterialAccessEvents::query()
            ->selectRaw('rr_material_id, COUNT(*) as request_count')
            ->whereIn('event_type', ['request', 'borrow'])
            ->groupBy('rr_material_id')
            ->orderByDesc('request_count')
            ->limit(5)
            ->with('material.parent')
            ->get()
            ->map(fn ($row) => [
                'title' => $row->material?->parent?->title ?? 'Unknown',
                'count' => $row->request_count,
            ]);

        // Monthly borrow/request trend (last 6 months)
        $monthlyTrend = MaterialAccessEvents::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->whereIn('event_type', ['request', 'borrow'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'count' => $row->count,
            ]);

        // Most active users (top 5)
        $topUsers = MaterialAccessEvents::query()
            ->selectRaw('user_id, COUNT(*) as request_count')
            ->whereIn('event_type', ['request', 'borrow'])
            ->groupBy('user_id')
            ->orderByDesc('request_count')
            ->limit(5)
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'name'  => $row->user?->name ?? 'Unknown',
                'count' => $row->request_count,
            ]);

        return compact(
            'total', 'pending', 'approved', 'rejected', 'revoked',
            'overdue', 'overdueRate', 'topMaterials', 'monthlyTrend', 'topUsers'
        );
    }

    // ── CSV Export ────────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadCsv')
                ->label('Download CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => $this->exportCsv()),
        ];
    }

    public function exportCsv()
    {
        $query = MaterialAccessEvents::query()
            ->with(['user', 'material.parent', 'approver'])
            ->whereIn('event_type', ['request', 'borrow']);

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterType) {
            $query->where('event_type', $this->filterType);
        }

        if ($this->filterDateFrom) {
            $query->where('created_at', '>=', $this->filterDateFrom . ' 00:00:00');
        }

        if ($this->filterDateTo) {
            $query->where('created_at', '<=', $this->filterDateTo . ' 23:59:59');
        }

        $records = $query->orderBy('created_at', 'desc')->get();

        $csvRows = [];
        $csvRows[] = [
            'ID', 'User', 'Material Title', 'Event Type', 'Status',
            'Approver', 'Due Date', 'Returned At', 'Is Overdue',
            'Approved At', 'Completed At', 'Rejection Reason', 'Created At',
        ];

        foreach ($records as $row) {
            $csvRows[] = [
                $row->id,
                $row->user?->name ?? '',
                $row->material?->parent?->title ?? '',
                $row->event_type,
                $row->status,
                $row->approver?->name ?? '',
                $row->due_at?->format('Y-m-d') ?? '',
                $row->returned_at?->format('Y-m-d') ?? '',
                $row->is_overdue ? 'Yes' : 'No',
                $row->approved_at?->format('Y-m-d H:i') ?? '',
                $row->completed_at?->format('Y-m-d H:i') ?? '',
                is_array($row->rejection_reason) ? implode(', ', $row->rejection_reason) : ($row->rejection_reason ?? ''),
                $row->created_at?->format('Y-m-d H:i') ?? '',
            ];
        }

        $filename = 'material_access_events_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($csvRows) {
            $handle = fopen('php://output', 'w');
            foreach ($csvRows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        };

        return Response::streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
            'stats'     => $this->activeTab === 'summary' ? $this->getSummaryStats() : [],
        ];
    }
}
