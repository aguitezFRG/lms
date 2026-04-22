<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMaterialAccessEvents extends ListRecords
{
    use HasTabs;

    protected static string $resource = MaterialAccessEventsResource::class;

    protected ?string $pollingInterval = '60s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => MaterialAccessEvents::count())
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending')
                ->badge(fn () => MaterialAccessEvents::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'approved' => Tab::make('Approved')
                ->badge(fn () => MaterialAccessEvents::whereIn('status', ['approved', 'returned', 'revoked'])->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['approved', 'returned', 'revoked'])),

            'rejected' => Tab::make('Rejected')
                ->badge(fn () => MaterialAccessEvents::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }

    public function getTablePollingInterval(): ?string
    {
        return '30s';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }
}
