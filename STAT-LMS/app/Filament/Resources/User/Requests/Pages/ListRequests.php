<?php

namespace App\Filament\Resources\User\Requests\Pages;

use App\Enums\MaterialEventType;
use App\Filament\Resources\User\Requests\RequestsResource;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ListRequests extends ListRecords
{
    use HasTabs;

    protected static string $resource = RequestsResource::class;

    protected ?array $tabBadgeCounts = null;

    protected ?string $pollingInterval = '60s';

    public function getTablePollingInterval(): ?string
    {
        return '20s';
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

    public function getTabs(): array
    {
        $userId = Auth::id();
        $requestEventTypes = [MaterialEventType::REQUEST->value, MaterialEventType::BORROW->value];

        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getTabBadgeCounts()['all'])
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending')
                ->badge(fn () => $this->getTabBadgeCounts()['pending'])
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                    ->whereIn('event_type', $requestEventTypes)
                    ->where('status', 'pending')),

            'approved' => Tab::make('Approved')
                ->badge(fn () => $this->getTabBadgeCounts()['approved'])
                ->badgeColor('success')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                    ->whereIn('event_type', $requestEventTypes)
                    ->where('status', 'approved')),

            'closed' => Tab::make('Closed')
                ->badge(fn () => $this->getTabBadgeCounts()['closed'])
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query
                    ->whereIn('event_type', $requestEventTypes)
                    ->whereIn('status', [
                        'rejected', 'cancelled', 'completed', 'returned', 'revoked',
                    ])),
        ];
    }

    protected function getTabBadgeCounts(): array
    {
        if ($this->tabBadgeCounts !== null) {
            return $this->tabBadgeCounts;
        }

        $requestEventTypes = [MaterialEventType::REQUEST->value, MaterialEventType::BORROW->value];

        $countsByStatus = MaterialAccessEvents::query()
            ->where('user_id', Auth::id())
            ->whereIn('event_type', $requestEventTypes)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return $this->tabBadgeCounts = [
            'all' => (int) $countsByStatus->sum(),
            'pending' => (int) ($countsByStatus['pending'] ?? 0),
            'approved' => (int) ($countsByStatus['approved'] ?? 0),
            'closed' => (int) ($countsByStatus['rejected'] ?? 0)
                + (int) ($countsByStatus['cancelled'] ?? 0)
                + (int) ($countsByStatus['completed'] ?? 0)
                + (int) ($countsByStatus['returned'] ?? 0)
                + (int) ($countsByStatus['revoked'] ?? 0),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('material.parent.title')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'revoked' => 'danger',
                        'completed' => 'gray',
                        'returned' => 'gray',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),

                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->color(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'danger' : null)
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow' => 'Physical Borrow',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('gray'),

                    Action::make('cancel')
                        ->label('Cancel Request')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (MaterialAccessEvents $record) => $record->status === 'pending')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Request')
                        ->modalDescription('Are you sure you want to cancel this request? This cannot be undone.')
                        ->modalSubmitActionLabel('Yes, cancel it')
                        ->action(function (MaterialAccessEvents $record) {
                            $record->update(['status' => 'cancelled']);

                            Notification::make()
                                ->title('Request cancelled')
                                ->body('Your request has been successfully cancelled.')
                                ->success()
                                ->send();
                        }),
                ])->color('gray'),
            ])
            ->emptyStateHeading('No requests yet')
            ->emptyStateDescription('Browse the catalog to request or borrow materials.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
