<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class SystemUsageExportPreview extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.system-usage-export-preview-page';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowDownTray;

    protected static ?string $navigationLabel = 'Export Data Preview';

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'system-usage/export-preview';

    protected static bool $shouldRegisterNavigation = false;

    public function getBreadcrumbs(): array
    {
        return [
            SystemUsage::getUrl() => 'System Usage',
            '' => 'Export Usage',
        ];
    }

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

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('created_at', 'desc')
            ->query(
                MaterialAccessEvents::query()
                    ->with(['user', 'material.parent', 'approver'])
                    ->whereIn('event_type', ['request', 'borrow'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->limit(8, '...')
                    ->fontFamily('mono')
                    ->copyable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('material.parent.title')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(fn (MaterialAccessEvents $record): string => $record->material?->parent?->title ?? ''),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'borrow' => 'primary',
                        'request' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revoked' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revoked' => 'Revoked',
                    ])
                    ->native(false),

                SelectFilter::make('event_type')
                    ->label('Event Type')
                    ->options([
                        'request' => 'Request (digital)',
                        'borrow' => 'Borrow (physical)',
                    ])
                    ->native(false),

                Filter::make('date_range')
                    ->form([
                        Section::make('Date Range')
                            ->schema([
                                DatePicker::make('date_from')
                                    ->label('Date From')
                                    ->maxDate(fn ($get) => $get('date_to') ?: now()),

                                DatePicker::make('date_to')
                                    ->label('Date To')
                                    ->minDate(fn ($get) => $get('date_from'))
                                    ->maxDate(now()),
                            ])
                            ->columns(1),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                filled($data['date_from']),
                                fn ($q) => $q->where('created_at', '>=', $data['date_from'].' 00:00:00')
                            )
                            ->when(
                                filled($data['date_to']),
                                fn ($q) => $q->where('created_at', '<=', $data['date_to'].' 23:59:59')
                            );
                    }),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export to CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => $this->exportCsv()),
            ])
            ->emptyStateHeading('No records match the selected filters')
            ->emptyStateDescription('Try adjusting your filters or clear them to see all records.')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass')
            ->paginated([10, 25, 50, 100]);
    }

    public function exportCsv()
    {
        // Check both active and deferred filters (table uses deferred loading)
        $filters = $this->tableFilters ?? [];
        $deferred = $this->tableDeferredFilters ?? [];

        $query = MaterialAccessEvents::query()
            ->with(['user', 'material.parent', 'approver'])
            ->whereIn('event_type', ['request', 'borrow']);

        // Apply status filter (check both active and deferred)
        $status = $filters['status']['value'] ?? $deferred['status']['value'] ?? null;
        if (! empty($status)) {
            $query->where('status', $status);
        }

        // Apply event_type filter (check both active and deferred)
        $eventType = $filters['event_type']['value'] ?? $deferred['event_type']['value'] ?? null;
        if (! empty($eventType)) {
            $query->where('event_type', $eventType);
        }

        // Apply date range filter (check both active and deferred)
        $dateFrom = $filters['date_range']['date_from'] ?? $deferred['date_range']['date_from'] ?? null;
        if (! empty($dateFrom)) {
            $query->where('created_at', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = $filters['date_range']['date_to'] ?? $deferred['date_range']['date_to'] ?? null;
        if (! empty($dateTo)) {
            $query->where('created_at', '<=', $dateTo.' 23:59:59');
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
}
