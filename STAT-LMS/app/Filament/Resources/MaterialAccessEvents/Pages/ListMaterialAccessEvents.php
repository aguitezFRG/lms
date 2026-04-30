<?php

namespace App\Filament\Resources\MaterialAccessEvents\Pages;

use App\Filament\Resources\MaterialAccessEvents\MaterialAccessEventsResource;
use Filament\Actions\Action;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMaterialAccessEvents extends ListRecords
{
    use HasTabs;

    protected static string $resource = MaterialAccessEventsResource::class;

    protected ?array $tabBadgeCounts = null;

    protected ?string $pollingInterval = '60s';

    protected $listeners = ['request-actioned' => '$refresh'];

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getTabBadgeCounts()['all'])
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->reorder()->orderByDesc('created_at')),

            'pending' => Tab::make('Pending')
                ->badge(fn () => $this->getTabBadgeCounts()['pending'])
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')->reorder()->orderBy('created_at')),

            'approved' => Tab::make('Approved')
                ->badge(fn () => $this->getTabBadgeCounts()['approved'])
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['approved', 'returned', 'revoked'])->reorder()->orderByDesc('created_at')),

            'rejected' => Tab::make('Rejected')
                ->badge(fn () => $this->getTabBadgeCounts()['rejected'])
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')->reorder()->orderByDesc('created_at')),
        ];
    }

    protected function getTabBadgeCounts(): array
    {
        if ($this->tabBadgeCounts !== null) {
            return $this->tabBadgeCounts;
        }

        $countsByStatus = MaterialAccessEventsResource::getEloquentQuery()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $approvedLike = (int) ($countsByStatus['approved'] ?? 0)
            + (int) ($countsByStatus['returned'] ?? 0)
            + (int) ($countsByStatus['revoked'] ?? 0);

        return $this->tabBadgeCounts = [
            'all' => (int) $countsByStatus->sum(),
            'pending' => (int) ($countsByStatus['pending'] ?? 0),
            'approved' => $approvedLike,
            'rejected' => (int) ($countsByStatus['rejected'] ?? 0),
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
