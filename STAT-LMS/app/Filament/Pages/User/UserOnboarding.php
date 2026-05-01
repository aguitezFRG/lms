<?php

namespace App\Filament\Pages\User;

use App\Filament\Components\User\FacultyFeatureCards;
use App\Filament\Components\User\StudentFeatureCards;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Blade;

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
        return auth()->check() && in_array(auth()->user()->role, [
            \App\Enums\UserRole::FACULTY,
            \App\Enums\UserRole::STUDENT,
        ]);
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
        $role = auth()->user()?->role;
        $roleValue = $role?->value;

        $roleLabel = match ($roleValue) {
            'faculty' => 'Faculty Member',
            'student' => 'Student User',
            default => $role?->getLabel() ?? 'a user',
        };

        $roleColorClass = match ($roleValue) {
            'faculty' => 'text-primary-600 dark:text-primary-400',
            'student' => 'text-blue-600 dark:text-blue-400',
            default => 'text-gray-700 dark:text-gray-300',
        };

        $bannerHtml = Blade::render(match ($roleValue) {
            'faculty' => '
                <p class="mt-4 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    As a faculty member, you can browse and access research materials from the INSTAT Reading Room —
                    including faculty-level and student-level materials.
                    Use the navigation on the left to explore the catalog, submit requests, and track their progress.
                </p>
            ',
            'student' => '
                <p class="mt-4 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    As a student, you can browse and access student-level research materials from the INSTAT Reading Room.
                    Use the navigation on the left to explore the catalog, submit requests, and track their progress.
                </p>
                <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50 p-3 dark:bg-blue-950">
                    <x-heroicon-o-information-circle class="mt-0.5 h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        Note: Some materials are restricted to faculty and committee members only.
                    </p>
                </div>
            ',
            default => '',
        });

        $cardsHtml = match ($roleValue) {
            'faculty' => FacultyFeatureCards::render(),
            'student' => StudentFeatureCards::render(),
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
