<?php

namespace App\Filament\Pages;

use App\Filament\Components\Admin\CommitteeFeatureCards;
use App\Filament\Components\Admin\StaffFeatureCards;
use App\Filament\Components\Admin\SuperAdminFeatureCards;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AdminOnboarding extends Page
{
    protected string $view = 'filament.pages.admin-onboarding';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Welcome';

    protected static ?int $navigationSort = -10;

    protected static ?string $slug = 'admin-onboarding';

    protected static bool $shouldRegisterNavigation = true;

    public static function canAccess(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, [
            \App\Enums\UserRole::SUPER_ADMIN,
            \App\Enums\UserRole::COMMITTEE,
            \App\Enums\UserRole::IT,
            \App\Enums\UserRole::RR,
        ]);
    }

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?\Illuminate\Database\Eloquent\Model $tenant = null
    ): string {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'admin', $tenant);
    }

    protected function getViewData(): array
    {
        $role = auth()->user()?->role;
        $roleValue = $role?->value;

        $roleLabel = match ($roleValue) {
            'committee' => 'Reading Room Committee',
            'it' => 'IT Administrator',
            'staff/custodian' => 'Reading Room Staff',
            'super_admin' => 'Super Administrator',
            default => $role?->getLabel() ?? 'an administrator',
        };

        $roleColorClass = match ($roleValue) {
            'committee' => 'text-warning-600 dark:text-warning-400',
            'it' => 'text-danger-600 dark:text-danger-400',
            'staff/custodian' => 'text-success-600 dark:text-success-400',
            'super_admin' => 'text-purple-600 dark:text-purple-400',
            default => 'text-gray-700 dark:text-gray-300',
        };

        $bannerHtml = match ($roleValue) {
            'committee' => '<p class="mt-4 text-sm text-warning-800 dark:text-warning-200">As a Reading Room Committee member, you oversee institutional policy and material curation. You have full access to all system features.</p>',
            'it' => '<p class="mt-4 text-sm text-danger-800 dark:text-danger-200">As an IT Administrator, you support system integrity and user access. You share operational permissions with committee members.</p>',
            'staff/custodian' => '<p class="mt-4 text-sm text-success-800 dark:text-success-200">As Reading Room Staff, you handle day-to-day material access operations and borrow request processing.</p>',
            'super_admin' => '<p class="mt-4 text-sm text-purple-800 dark:text-purple-200">As a Super Administrator, you have unrestricted access to all system features, including full catalog control, user management at every privilege level, audit logs, analytics, and the ability to manage or override any record in the system.</p>',
            default => '',
        };

        $cardsHtml = match ($roleValue) {
            'committee', 'it' => CommitteeFeatureCards::render(),
            'staff/custodian' => StaffFeatureCards::render(),
            'super_admin' => SuperAdminFeatureCards::render(),
            default => '',
        };

        return [
            'roleLabel' => $roleLabel,
            'roleColorClass' => $roleColorClass,
            'bannerHtml' => $bannerHtml,
            'cardsHtml' => $cardsHtml,
        ];
    }
}
