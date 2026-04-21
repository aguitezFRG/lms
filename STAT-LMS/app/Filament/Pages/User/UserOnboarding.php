<?php

namespace App\Filament\Pages\User;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class UserOnboarding extends Page
{
    protected string $view = 'filament.pages.user.user-onboarding';

    protected static ?string $navigationLabel = 'Welcome';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -10;

    protected static ?string $slug = 'user-onboarding';

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return filament()->getCurrentPanel()?->getId() === 'user';
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getUrl(
        array $parameters = [],
        bool $isAbsolute = true,
        ?string $panel = null,
        ?\Illuminate\Database\Eloquent\Model $tenant = null
    ): string {
        return parent::getUrl($parameters, $isAbsolute, $panel ?? 'user', $tenant);
    }

    protected function getViewData(): array
    {
        return [
            'role' => auth()->user()?->role,
        ];
    }
}
