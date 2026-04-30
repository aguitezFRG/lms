<?php

namespace App\Filament\Resources\MaterialAccessEvents\Tables;

use App\Enums\MaterialEventType;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MaterialAccessEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->searchPlaceholder('Search material titles in pending requests')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Requester')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                TextColumn::make('material.parent.title')
                    ->label('Material')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (MaterialAccessEvents $record): ?string => $record->material?->parent?->title),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state): string => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state): string => MaterialEventType::from($state)->getLabel()),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'returned' => 'gray',
                        'rejected', 'revoked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),

                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->color(fn (MaterialAccessEvents $record): ?string => $record->is_overdue ? 'danger' : null)
                    ->description(fn (MaterialAccessEvents $record): ?string => $record->is_overdue ? 'Overdue!' : null),

                TextColumn::make('approver.name')
                    ->label('Processed By')
                    ->searchable()
                    ->sortable()
                    ->default('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('returned_at')
                    ->label('Returned On')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('circulation_status')
                    ->label('Circulation Status')
                    ->placeholder('All')
                    ->options([
                        'distributed' => 'Distributed',
                        'overdue' => 'Overdue',
                        'returned' => 'Returned',
                        'revoked' => 'Revoked',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'distributed' => $query->where('status', 'approved')->where('is_overdue', false),
                            'overdue' => $query->where('is_overdue', true),
                            'returned' => $query->where('status', 'returned'),
                            'revoked' => $query->where('status', 'revoked'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    static::makeApproveAction(),
                    static::makeRejectAction(),
                    EditAction::make()
                        ->visible(fn (MaterialAccessEvents $record): bool => in_array($record->status, ['rejected', 'approved'], true))
                        ->mutateFormDataUsing(fn (array $data): array => array_merge($data, [
                            'approver_id' => auth()->id(),
                        ]))
                        ->color('warning'),
                ])
                    ->color('gray'),
            ]);
    }

    protected static function makeApproveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (MaterialAccessEvents $record): bool => $record->status === 'pending')
            ->modalHeading(fn (MaterialAccessEvents $record): string => 'Material Access Logs / '.$record->material->parent->title)
            ->modalDescription(fn (MaterialAccessEvents $record): string => sprintf(
                'Requested by %s on %s.',
                $record->user->name,
                $record->created_at->format('M d, Y g:i A'),
            ))
            ->modalWidth('md')
            ->modalSubmitActionLabel('Approve request')
            ->form([
                DatePicker::make('due_at')
                    ->label('Due Date')
                    ->required()
                    ->minDate(now()->addDay()->startOfDay())
                    ->rules(['required', 'date', 'after_or_equal:'.now()->addDay()->toDateString()]),
            ])
            ->action(function (array $data, MaterialAccessEvents $record, $livewire): void {
                $record->update([
                    'status' => 'approved',
                    'approver_id' => auth()->id(),
                    'approved_at' => now(),
                    'due_at' => Carbon::parse($data['due_at'])->endOfDay()->toDateTimeString(),
                    'rejection_reason' => null,
                ]);

                Notification::make()
                    ->title('Request approved')
                    ->success()
                    ->send();

                $livewire->dispatch('request-actioned');
            });
    }

    protected static function makeRejectAction(): Action
    {
        return Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-o-x-mark')
            ->color('danger')
            ->visible(fn (MaterialAccessEvents $record): bool => $record->status === 'pending')
            ->modalHeading(fn (MaterialAccessEvents $record): string => 'Material Access Logs / '.$record->material->parent->title)
            ->modalDescription(fn (MaterialAccessEvents $record): string => sprintf(
                'Requested by %s on %s.',
                $record->user->name,
                $record->created_at->format('M d, Y g:i A'),
            ))
            ->modalWidth('md')
            ->modalSubmitActionLabel('Reject request')
            ->form([
                TagsInput::make('rejection_reason')
                    ->label('Rejection Reason(s)')
                    ->required()
                    ->placeholder('Select or type a reason...')
                    ->suggestions([
                        'Overdue materials on record',
                        'Outstanding fees',
                        'Request limit reached',
                        'Incomplete request details',
                        'Access level restriction',
                        'Material currently unavailable',
                        'Policy violation',
                        'Duplicate request',
                    ])
                    ->hint('Select from suggestions or type a custom reason and press Enter.')
                    ->hintColor('gray'),
            ])
            ->action(function (array $data, MaterialAccessEvents $record, $livewire): void {
                $record->update([
                    'status' => 'rejected',
                    'approver_id' => auth()->id(),
                    'rejection_reason' => $data['rejection_reason'],
                ]);

                Notification::make()
                    ->title('Request rejected')
                    ->danger()
                    ->send();

                $livewire->dispatch('request-actioned');
            });
    }
}
