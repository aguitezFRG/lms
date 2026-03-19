<?php

namespace App\Filament\Resources\User\Requests\Pages;

use App\Enums\MaterialEventType;
use App\Filament\Resources\User\Requests\RequestsResource;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ListRequests extends ListRecords
{
    protected static string $resource = RequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
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
                        'pending'   => 'warning',
                        'approved'  => 'success',
                        'rejected'  => 'danger',
                        'completed' => 'gray',
                        'cancelled' => 'gray',
                        default     => 'gray',
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
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending'   => 'Pending',
                        'approved'  => 'Approved',
                        'rejected'  => 'Rejected',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),

                SelectFilter::make('event_type')
                    ->label('Type')
                    ->options([
                        'request' => 'Digital Request',
                        'borrow'  => 'Physical Borrow',
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