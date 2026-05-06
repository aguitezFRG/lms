<?php

namespace App\Filament\Pages\User;

use App\Services\PasswordEncryptionService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class UserProfile extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected string $view = 'filament.pages.user.user-profile';

    protected static bool $shouldRegisterNavigation = false;

    // ── Tab & Filter State ────────────────────────────────────────────────────

    #[Url]
    public string $activeProfileTab = 'details';

    public string $notifReadFilter = 'all';

    public string $notifTypeFilter = 'all';

    public function setProfileTab(string $tab): void
    {
        if (in_array($tab, ['details', 'notifications'])) {
            $this->activeProfileTab = $tab;
        }
    }

    public function setNotifReadFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'unread', 'read'])) {
            $this->notifReadFilter = $filter;
        }
    }

    public function setNotifTypeFilter(string $type): void
    {
        $allowed = ['all', 'request_status_changed', 'access_level_changed', 'account_details_changed', 'borrow_due_soon'];
        if (in_array($type, $allowed)) {
            $this->notifTypeFilter = $type;
        }
    }

    // ── All Notifications (profile table) ────────────────────────────────────

    #[Computed]
    public function allNotifications(): array
    {
        $query = auth()->user()->notifications()->latest();

        if ($this->notifReadFilter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($this->notifReadFilter === 'read') {
            $query->whereNotNull('read_at');
        }

        return $query->get()
            ->filter(fn ($n) => $this->notifTypeFilter === 'all'
                || ($n->data['type'] ?? '') === $this->notifTypeFilter)
            ->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->data['title'] ?? 'Notification',
                'message' => $n->data['message'] ?? '',
                'type' => $n->data['type'] ?? 'general',
                'since' => $n->created_at->diffForHumans(),
                'date' => $n->created_at->format('M d, Y g:i A'),
                'is_unread' => is_null($n->read_at),
            ])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function allUnreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markProfileNotifRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->first()?->markAsRead();
        unset($this->allNotifications);
        unset($this->allUnreadCount);
    }

    public function markAllProfileNotifRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
        unset($this->allNotifications);
        unset($this->allUnreadCount);
    }

    // ── Dynamic title ─────────────────────────────────────────────────────────

    public function getTitle(): string
    {
        return 'Welcome, '.(auth()->user()->f_name ?? auth()->user()->name).'!';
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
                ->form([
                    TextInput::make('current_password')
                        ->label('Current Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->autocomplete('current-password'),

                    TextInput::make('new_password')
                        ->label('New Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->autocomplete('new-password')
                        ->helperText('Min. 8 characters with uppercase, lowercase, number, and symbol.')
                        ->rules([Password::min(8)->mixedCase()->numbers()->symbols()])
                        ->different('current_password'),

                    TextInput::make('confirm_password')
                        ->label('Confirm New Password')
                        ->password()
                        ->revealable()
                        ->required()
                        ->same('new_password')
                        ->autocomplete('new-password'),
                ])
                ->action(function (array $data, Action $action): void {
                    if (! Hash::check($data['current_password'], auth()->user()->password)) {
                        Notification::make()
                            ->title('Incorrect password')
                            ->body('Your current password is wrong.')
                            ->danger()
                            ->send();

                        $action->halt();

                        return;
                    }

                    auth()->user()->update(['password' => Hash::make($data['new_password'])]);

                    Notification::make()
                        ->title('Password updated successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    // ── Encrypted Password Change ─────────────────────────────────────────────
    //
    // Called directly (e.g. by tests) with RSA-OAEP ciphertext. Verifies the
    // ENC: prefix was present so we can be sure encryption was used.

    public function submitEncryptedPasswordChange(string $encryptedCurrent, string $encryptedNew): void
    {
        if (! str_starts_with($encryptedCurrent, 'ENC:') || ! str_starts_with($encryptedNew, 'ENC:')) {
            Notification::make()
                ->title('Security error')
                ->body('Password must be submitted via encrypted connection.')
                ->danger()
                ->send();

            return;
        }

        $service = app(PasswordEncryptionService::class);

        try {
            $currentPassword = $service->decrypt($this->stripEncPrefix($encryptedCurrent));
            $newPassword = $service->decrypt($this->stripEncPrefix($encryptedNew));
        } catch (\Throwable) {
            Notification::make()
                ->title('Security error')
                ->body('Password could not be decrypted. Please try again.')
                ->danger()
                ->send();

            return;
        }

        if (! Hash::check($currentPassword, auth()->user()->password)) {
            Notification::make()
                ->title('Incorrect password')
                ->body('Your current password is wrong.')
                ->danger()
                ->send();

            return;
        }

        auth()->user()->update(['password' => Hash::make($newPassword)]);

        Notification::make()
            ->title('Password updated successfully')
            ->success()
            ->send();
    }

    private function stripEncPrefix(string $value): string
    {
        return str_starts_with($value, 'ENC:') ? substr($value, 4) : $value;
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
                            ->color(fn () => $user->role->getColor())
                            ->formatStateUsing(fn () => $user->role->getLabel()),

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

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        return [
            'initials' => strtoupper(substr(auth()->user()->f_name ?? auth()->user()->name, 0, 1))
                             .strtoupper(substr(auth()->user()->l_name ?? '', 0, 1)),
        ];
    }
}
