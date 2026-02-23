<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('UUID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->limit(16, end: '...'),

                TextColumn::make('l_name')
                    ->label('Last Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                TextColumn::make('f_name')
                    ->label('First Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('m_name')
                    ->label('Middle Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('std_number')
                    ->label('Student Number')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->colors([
                        'primary' => 'student',
                        'success' => 'faculty',
                        'warning' => 'staff/custodian',
                        'danger' => 'it',
                        'info' => 'committee',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'staff/custodian' => 'Staff/Custodian',
                        'it' => 'IT',
                        'committee' => 'Committee',
                        default => ucfirst($state),
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'student' => 'Student',
                        'faculty' => 'Faculty',
                        'staff/custodian' => 'Staff/Custodian',
                        'it' => 'IT',
                        'committee' => 'Committee',
                    ])
                    ->multiple()
                    ->searchable(),
                TrashedFilter::make(),
            ])
            ->filtersApplyAction(
                fn ($action) => $action->color('success')
            )
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                RestoreAction::make()
                    ->visible(fn ($record) => $record && $record->trashed())   ,
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make()
                        ->visible(fn ($record) => $record && $record->trashed()),
                ]),
            ]);
    }
}
