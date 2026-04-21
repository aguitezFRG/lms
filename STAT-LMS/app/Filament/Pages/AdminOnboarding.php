<?php

namespace App\Filament\Pages;

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
        return auth()->check();
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
        return [
            'role' => auth()->user()?->role,
        ];
    }
}
