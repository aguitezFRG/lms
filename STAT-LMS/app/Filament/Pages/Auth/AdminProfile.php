<?php

namespace App\Filament\Pages\Auth;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

use App\Services\PasswordEncryptionService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class AdminProfile extends Page implements HasTable, HasInfolists
{
    use InteractsWithTable;
    use InteractsWithInfolists;

    protected string $view = 'filament.pages.auth.admin-profile';

    protected static bool $shouldRegisterNavigation = false;

    protected ?string $pollingInterval = '120s';

    public string $activeTab = 'history';

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
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'admin', $tenant);
    }


    // ── Header Actions ────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),

            Action::make('changePassword')
                ->label('Change Password')
                ->icon('heroicon-o-lock-closed')
                ->color('warning')
                ->modalHeading('Change Your Password')
                ->modalDescription('Enter your current password, then choose a new one.')
                ->modalWidth('md')
                ->modalContent(view('filament.components.password-change-modal'))
                ->modalFooterActions([])
                ->action(fn () => null),

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

    // ── Encrypted Password Change ─────────────────────────────────────────────
    //
    // Called by the Alpine modal — receives RSA-OAEP ciphertext, never plaintext.

    public function submitEncryptedPasswordChange(string $encryptedCurrent, string $encryptedNew): void
    {
        $service = app(PasswordEncryptionService::class);

        try {
            $currentPassword = $service->decrypt($this->stripEncPrefix($encryptedCurrent));
            $newPassword     = $service->decrypt($this->stripEncPrefix($encryptedNew));
        } catch (\Throwable) {
            Notification::make()->title('Security error')->body('Password could not be decrypted. Please try again.')->danger()->send();
            return;
        }

        if (! Hash::check($currentPassword, auth()->user()->password)) {
            Notification::make()->title('Incorrect password')->body('Your current password is wrong.')->danger()->send();
            return;
        }

        auth()->user()->update(['password' => Hash::make($newPassword)]);

        Notification::make()->title('Password updated successfully')->success()->send();
    }

    private function stripEncPrefix(string $value): string
    {
        return str_starts_with($value, 'ENC:') ? substr($value, 4) : $value;
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

    public function profileInfolist(Schema $schema): Schema
    {
        $user = auth()->user();

        $fullName = trim(implode(' ', array_filter([
            $user->f_name,
            $user->m_name ? mb_substr($user->m_name, 0, 1).'.' : null,
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

                        TextEntry::make('id')
                            ->label('UUID')
                            ->state($user->id)
                            ->copyable()
                            ->limit(24),
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

    // ── History Table ─────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('created_at', 'desc')
            ->query(
                MaterialAccessEvents::query()
                    ->with(['material.parent'])
                    ->where('user_id', auth()->id())
                    ->whereIn('event_type', ['borrow', 'request'])
            )
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
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow'  => 'Physical Borrow',
                    ]),
            ])
            ->emptyStateHeading('No access history yet.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        return [
            'initials'    => strtoupper(substr(auth()->user()->f_name ?? auth()->user()->name, 0, 1))
                           . strtoupper(substr(auth()->user()->l_name ?? '', 0, 1)),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
            'activeTab'   => $this->activeTab,

            'notifications' => auth()->user()->notifications()->latest()->get()->map(fn ($n) => [
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