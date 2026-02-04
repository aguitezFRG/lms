<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RepositoryChangeLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('editor_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rr_material_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('target_user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('table_changed')
                    ->searchable(),
                TextColumn::make('change_type')
                    ->searchable(),
                TextColumn::make('changed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
