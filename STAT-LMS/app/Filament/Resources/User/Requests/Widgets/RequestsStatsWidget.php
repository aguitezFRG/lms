<?php

namespace App\Filament\Resources\User\Requests\Widgets;

use App\Models\MaterialAccessEvents;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class RequestsStatsWidget extends BaseWidget
{
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $userId = Auth::id();

        $pendingCount = MaterialAccessEvents::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();

        $approvedCount = MaterialAccessEvents::where('user_id', $userId)
            ->where('status', 'approved')
            ->count();

        $totalCount = MaterialAccessEvents::where('user_id', $userId)
            ->whereIn('event_type', ['request', 'borrow'])
            ->count();

        return [
            Stat::make('Pending', $pendingCount)
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Active / Approved', $approvedCount)
                ->icon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Requests', $totalCount)
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray'),
        ];
    }
}
