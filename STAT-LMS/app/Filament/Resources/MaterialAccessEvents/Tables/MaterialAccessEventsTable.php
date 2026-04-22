<?php

namespace App\Filament\Resources\MaterialAccessEvents\Tables;

use App\Enums\MaterialEventType;
use App\Models\MaterialAccessEvents;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MaterialAccessEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('created_at', 'desc')
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
                    ->tooltip(fn ($record) => $record->material?->parent?->title),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),

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
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime('M d, Y')
                    ->sortable(),

                TextColumn::make('due_at')
                    ->label('Due Date')
                    ->dateTime('M d, Y')
                    ->placeholder('—')
                    ->color(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'danger' : null)
                    ->description(fn (MaterialAccessEvents $record) => $record->is_overdue ? 'Overdue!' : null),

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
                    EditAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'rejected', 'approved']))
                        ->mutateFormDataUsing(fn (array $data) => array_merge($data, [
                            'approver_id' => auth()->id(),
                        ]))
                        ->color('warning'),
                ])
                    ->color('gray'),
            ]);
        // ->bulkActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //         ForceDeleteBulkAction::make(),
        //         RestoreBulkAction::make(),
        //     ]),
        // ]);
    }
}
