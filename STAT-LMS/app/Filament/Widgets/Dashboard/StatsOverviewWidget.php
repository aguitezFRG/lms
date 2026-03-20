<?php

namespace App\Filament\Widgets\Dashboard;

use App\Models\MaterialAccessEvents;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    /* Exposed so the Dashboard page can toggle visibility */
    public bool $visible = true;

    public static function canView(): bool
    {
        return Gate::allows('viewGeneral', Dashboard::class);
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Borrowed',
                MaterialAccessEvents::where('event_type', 'borrow')->count()
            )
                ->icon('heroicon-o-book-open')
                ->color('primary'),

            Stat::make('Overdue',
                MaterialAccessEvents::where('is_overdue', true)->count()
            )
                ->icon('heroicon-o-clock')
                ->color('danger'),

            Stat::make('Accessed',
                MaterialAccessEvents::whereIn('event_type', ['request', 'accessed'])->count()
            )
                ->icon('heroicon-o-document-text')
                ->color('warning'),

            Stat::make('Visitors',
                MaterialAccessEvents::distinct('user_id')->count('user_id')
            )
                ->icon('heroicon-o-users')
                ->color('info'),
        ];
    }
}