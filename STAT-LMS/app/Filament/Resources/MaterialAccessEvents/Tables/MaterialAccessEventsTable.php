<?php

namespace App\Filament\Resources\MaterialAccessEvents\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

use App\Enums\MaterialEventType;

class MaterialAccessEventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->label('Approved by')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?: "---")
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
