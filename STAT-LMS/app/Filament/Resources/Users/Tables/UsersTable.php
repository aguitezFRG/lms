<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(16, end: '...'),

                TextColumn::make('l_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('f_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('m_name')
                    ->label('Middle Name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('std_number')
                    ->label('Student Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->badge()
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
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersApplyAction(
                fn ($action) => $action->color('success')
            )
            ->filtersResetActionPosition(FiltersResetActionPosition::Footer)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
