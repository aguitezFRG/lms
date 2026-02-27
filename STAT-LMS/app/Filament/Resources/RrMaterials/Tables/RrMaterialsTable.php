<?php

namespace App\Filament\Resources\RrMaterials\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RrMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                IconColumn::make('is_digital')
                    ->label('Format')
                    ->boolean()
                    ->sortable()
                    ->searchable()
                    ->trueIcon('heroicon-o-computer-desktop')
                    ->falseIcon('heroicon-o-book-open')
                    ->color(fn ($state) => $state ? 'info' : 'primary'),

                IconColumn::make('is_available')
                    ->label('Status')
                    ->boolean()
                    ->sortable()
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('file_name')
                    ->label('Storage Identifier')
                    ->limit(15)
                    ->copyable()
                    ->placeholder('Physical Copy'),

            ])
            ->modifyQueryUsing(function ($query) {
                // Ensure we include soft-deleted records for filtering and actions
                $query->withTrashed();
                $query->whereHas('parent');
            })
            ->filters([
                // 1. Format Filter (Digital vs Physical)
                TernaryFilter::make('is_digital')
                    ->label('Material Format')
                    ->placeholder('All Formats')
                    ->trueLabel('Digital Only')
                    ->falseLabel('Physical Only'),

                // 2. Access Level Filter (Public, Restricted, Confidential)
                SelectFilter::make('parent_id')
                    ->label('Access Level')
                    ->options([
                        1 => 'Public',
                        2 => 'Restricted',
                        3 => 'Confidential',
                    ])
                    ->query(fn ($query, $data) => $query->when(
                        $data['value'],
                        fn ($query, $value) => $query->whereRelation('parent', 'access_level', $value)
                    )),

                // 3. Status Filter (Available vs Unavailable)
                TernaryFilter::make('is_available')
                    ->label('Circulation Status'),

                // 4. Soft Delete Filter (Critical for Restore Actions)
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning'),
                    RestoreAction::make()
                        ->visible(fn ($record) => $record && $record->trashed())
                        ->color('success'),
                    DeleteAction::make()
                        ->visible(fn ($record) => $record && !$record->trashed())
                        ->color('danger'),
                ])
                ->color('gray'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->color('danger'),
                    RestoreBulkAction::make()
                        ->color('success'),
                ]),
            ]);
    }
}