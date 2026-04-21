<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->defaultSort('created_at', 'desc')
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

                IconColumn::make('is_banned')
                    ->label('Banned')
                    ->boolean()
                    ->trueIcon('heroicon-m-check-circle')
                    ->falseIcon('heroicon-m-no-symbol')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('role')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->color(fn (UserRole|string $state) => ($state instanceof UserRole ? $state : UserRole::from($state))->getColor())
                    ->formatStateUsing(fn (UserRole|string $state): string => ($state instanceof UserRole ? $state : UserRole::from($state))->getLabel()),
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
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->color('warning'),
                    RestoreAction::make()
                        ->visible(fn ($record) => $record && $record->trashed())
                        ->color('success'),
                    DeleteAction::make()
                        ->visible(fn ($record) => $record && ! $record->trashed())
                        ->color('danger'),
                ])
                    ->color('gray'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // TODO: Make the bulk actions only show for the appropriate records
                    DeleteBulkAction::make()
                        ->color('danger'),
                    RestoreBulkAction::make()
                        ->color('success'),
                ]),
            ]);
    }
}
