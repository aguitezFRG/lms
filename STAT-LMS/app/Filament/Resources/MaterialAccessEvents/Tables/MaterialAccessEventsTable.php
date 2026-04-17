<?php

namespace App\Filament\Resources\MaterialAccessEvents\Tables;

use App\Enums\MaterialEventType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
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
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->limit(20),

                TextColumn::make('material.parent.title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('event_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                    ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),

                TextColumn::make('status')
                    ->label('Status')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('approver.name')
                    ->label('Updated By')
                    ->searchable()
                    ->sortable()
                    ->default('-----------')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                TrashedFilter::make(),
                Filter::make('Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'revoked' => 'Revoked',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            filled($data['status']),
                            fn (Builder $query) => $query->where('status', $data['status'])
                        );
                    }),

            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'rejected', 'approved'])
                        )
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
