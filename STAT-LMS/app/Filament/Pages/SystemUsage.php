<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Filament\Widgets\SystemUsage\SystemUsageStatsWidget;
use App\Models\MaterialAccessEvents;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class SystemUsage extends Page
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.system-usage';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowTrendingUp;

    protected static ?string $navigationLabel = 'System Usage ';

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 1;

    public string $activeTab = 'materials';

    protected ?string $pollingInterval = '120s';

    // Filter state for export (used by export action)
    public ?string $filterStatus = null;

    public ?string $filterType = null;

    public ?string $filterDateFrom = null;

    public ?string $filterDateTo = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && in_array($user->role, [
            UserRole::SUPER_ADMIN,
            UserRole::COMMITTEE,
            UserRole::IT,
            UserRole::RR,
        ]);
    }

    public function setTab(string $tab): void
    {
        $allowedTabs = ['materials', 'trend', 'users'];
        if (in_array($tab, $allowedTabs)) {
            $this->activeTab = $tab;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),

            Action::make('export_preview')
                ->label('Export Data')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => SystemUsageExportPreview::getUrl()),
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
            $query->where('created_at', '>=', $this->filterDateFrom.' 00:00:00');
        }

        if ($this->filterDateTo) {
            $query->where('created_at', '<=', $this->filterDateTo.' 23:59:59');
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

        $filename = 'material_access_events_'.now()->format('Ymd_His').'.csv';

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

    protected function getWidgets(): array
    {
        return [
            SystemUsageStatsWidget::class,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'activeTab' => $this->activeTab,
        ];
    }
}
