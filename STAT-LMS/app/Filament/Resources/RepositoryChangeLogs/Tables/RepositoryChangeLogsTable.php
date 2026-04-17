<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Tables;

use App\Enums\RepositoryChangeType;
use App\Enums\RepositoryTable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RepositoryChangeLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('changed_at', 'desc')
            ->columns([
                TextColumn::make('editor.name')
                    ->label('Editor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('table_changed')
                    ->label('Table')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('change_type')
                    ->label('Change Type')
                    ->badge()
                    ->color(fn (string $state) => RepositoryChangeType::from($state)->getColor())
                    ->sortable()
                    ->searchable(),

                TextColumn::make('materialParent.title')
                    ->label('Material Title')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('material.parent.title')
                    ->label('Copy Title')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('targetUser.name')
                    ->label('Target User')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('changed_at')
                    ->label('Changed At')
                    ->datetime('F d, Y h:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                SelectFilter::make('change_type')
                    ->label('Change Type')
                    ->options(RepositoryChangeType::class),

                SelectFilter::make('table_changed')
                    ->label('Table Changed')
                    ->options(RepositoryTable::class),
            ])
            ->recordActions([
                ViewAction::make(),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('changed_at', 'desc');
    }
}
