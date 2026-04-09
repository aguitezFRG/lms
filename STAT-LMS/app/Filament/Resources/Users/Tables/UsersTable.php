<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;


use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;

use App\Enums\UserRole;

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
                    ->trueIcon('heroicon-m-no-symbol')
                    ->falseIcon('heroicon-m-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('role')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->color(fn (string $state) => UserRole::from($state)->getColor())
                    // TO DO: Make this in the enums
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
                    // TODO: Make the bulk actions only show for the appropriate records
                    DeleteBulkAction::make()
                        ->color('danger'),
                    RestoreBulkAction::make()
                        ->color('success'),
                ]),
            ]);
    }
}
