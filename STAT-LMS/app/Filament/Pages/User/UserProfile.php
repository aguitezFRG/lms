<?php

namespace App\Filament\Pages\User;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserProfile extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithInfolists;

    protected string $view = 'filament.pages.user.user-profile';

    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'pending';

    // ── Dynamic title ─────────────────────────────────────────────────────────

    public function getTitle(): string
    {
        return 'Welcome, ' . (auth()->user()->f_name ?? auth()->user()->name) . '!';
    }

    // ── Routing ───────────────────────────────────────────────────────────────

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?\Illuminate\Database\Eloquent\Model $tenant = null
    ): string {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'user', $tenant);
    }

    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllRead')
                ->label('Mark All as Read')
                ->icon('heroicon-o-check-circle')
                ->color('gray')
                ->visible(fn () =>
                    $this->activeTab === 'notifications' &&
                    auth()->user()->unreadNotifications()->count() > 0
                )
                ->action(fn () => auth()->user()->unreadNotifications->markAsRead()),
        ];
    }

    // ── Tab Switching ─────────────────────────────────────────────────────────

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function markRead(string $id): void
    {
        auth()->user()
            ->notifications()
            ->where('id', $id)
            ->first()
            ?->markAsRead();
    }

    // ── Profile Infolist ──────────────────────────────────────────────────────
    //
    // 2x2 grid: Full Name / Role / Email / Student Number.
    // UUID omitted — not relevant for end users.

    public function profileInfolist(Schema $schema): Schema
    {
        $user = auth()->user();

        $fullName = trim(implode(' ', array_filter([
            $user->f_name,
            $user->m_name ? mb_substr($user->m_name, 0, 1) . '.' : null,
            $user->l_name,
        ]))) ?: $user->name;

        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('full_name')
                            ->label('Full Name')
                            ->state($fullName)
                            ->weight('semibold'),

                        TextEntry::make('role')
                            ->label('Role')
                            ->badge()
                            ->state($user->role)
                            ->color(fn () => UserRole::from($user->role)->getColor())
                            ->formatStateUsing(fn () => UserRole::from($user->role)->getLabel()),

                        TextEntry::make('email')
                            ->label('Email')
                            ->state($user->email)
                            ->icon('heroicon-m-envelope')
                            ->copyable(),

                        TextEntry::make('std_number')
                            ->label('Student Number')
                            ->state($user->std_number ?? '—')
                            ->icon('heroicon-m-identification')
                            ->color(fn (string $state) => $state === '—' ? 'gray' : null),
                    ]),
                ]),
        ]);
    }

    // ── Notifications Infolist ────────────────────────────────────────────────

    public function notificationsInfolist(Schema $schema): Schema
    {
        $notifications = auth()->user()->notifications()->latest()->get();

        $items = $notifications->map(fn ($n) => [
            'id'        => $n->id,
            'title'     => $n->data['title'] ?? 'Notification',
            'message'   => $n->data['message'] ?? '',
            'since'     => $n->created_at->diffForHumans(),
            'is_unread' => is_null($n->read_at),
        ])->values()->toArray();

        return $schema->components([
            Section::make()
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->state($items)
                        ->schema([
                            TextEntry::make('title')
                                ->label('')
                                ->weight('semibold')
                                ->size('sm'),

                            TextEntry::make('message')
                                ->label('')
                                ->color('gray')
                                ->size('sm'),

                            TextEntry::make('since')
                                ->label('')
                                ->color('gray')
                                ->size('xs'),
                        ])
                        ->columns(1),
                ])
                ->visible(fn () => count($items) > 0),

            Section::make()
                ->schema([
                    TextEntry::make('empty')
                        ->label('')
                        ->state('No notifications yet.')
                        ->color('gray'),
                ])
                ->visible(fn () => count($items) === 0),
        ]);
    }

    // ── Request Table ─────────────────────────────────────────────────────────
    //
    // Query switches on $activeTab — one table instance covers all three tabs.

    public function table(Table $table): Table
    {
        $query = MaterialAccessEvents::query()
            ->with(['material.parent'])
            ->where('user_id', auth()->id())
            ->whereIn('event_type', ['request', 'borrow']);

        $query = match ($this->activeTab) {
            'pending'  => $query->where('status', 'pending'),
            'approved' => $query->where('status', 'approved'),
            'closed'   => $query->whereIn('status', ['rejected', 'cancelled', 'completed']),
            default    => $query->where('status', 'pending'),
        };

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('material.parent.title')
                    ->label('Material')
                    ->limit(40)
                    ->searchable()
                    ->wrap(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'completed' => 'gray',
                        'cancelled' => 'gray',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->color(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'danger' : null)
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null)
                    ->visible(fn () => in_array($this->activeTab, ['approved', 'closed'])),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow'  => 'Physical Borrow',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('cancel')
                        ->label('Cancel Request')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (MaterialAccessEvents $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Request')
                        ->modalDescription('Are you sure you want to cancel this request? This cannot be undone.')
                        ->modalSubmitActionLabel('Yes, cancel it')
                        ->action(function (MaterialAccessEvents $record) {
                            $record->update(['status' => 'cancelled']);

                            Notification::make()
                                ->title('Request cancelled')
                                ->success()
                                ->send();
                        }),
                ])->color('gray'),
            ])
            ->emptyStateHeading(match ($this->activeTab) {
                'pending'  => 'No pending requests',
                'approved' => 'No approved requests',
                'closed'   => 'No closed requests',
                default    => 'No requests found',
            })
            ->emptyStateDescription(match ($this->activeTab) {
                'pending'  => 'Browse the catalog to submit a request.',
                'approved' => 'Approved requests will appear here.',
                'closed'   => 'Rejected or cancelled requests will appear here.',
                default    => '',
            })
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $user   = auth()->user();
        $userId = $user->id;

        return [
            'initials'      => strtoupper(substr($user->f_name ?? $user->name, 0, 1))
                             . strtoupper(substr($user->l_name ?? '', 0, 1)),
            'pendingCount'  => MaterialAccessEvents::where('user_id', $userId)
                                ->where('status', 'pending')->count(),
            'approvedCount' => MaterialAccessEvents::where('user_id', $userId)
                                ->where('status', 'approved')->count(),
            'totalCount'    => MaterialAccessEvents::where('user_id', $userId)
                                ->whereIn('event_type', ['request', 'borrow'])->count(),
            'unreadCount'   => $user->unreadNotifications()->count(),
            'activeTab'     => $this->activeTab,

            'notifications' => $user->notifications()->latest()->get()->map(fn ($n) => [
            'id'        => $n->id,
            'title'     => $n->data['title']   ?? 'Notification',
            'message'   => $n->data['message'] ?? '',
            'type'      => $n->data['type']    ?? 'general',
            'since'     => $n->created_at->diffForHumans(),
            'is_unread' => is_null($n->read_at),
        ])->values()->toArray(),
        ];
    }
}