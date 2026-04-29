<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordEncryptionService;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class AdminProfile extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected string $view = 'filament.pages.auth.admin-profile';

    protected static bool $shouldRegisterNavigation = false;

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
            $newPassword = $service->decrypt($this->stripEncPrefix($encryptedNew));
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
                            ->color(fn () => $user->role->getColor())
                            ->formatStateUsing(fn () => $user->role->getLabel()),

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

    // ── View Data ─────────────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        return [
            'initials' => strtoupper(substr(auth()->user()->f_name ?? auth()->user()->name, 0, 1))
                           .strtoupper(substr(auth()->user()->l_name ?? '', 0, 1)),
        ];
    }
}
