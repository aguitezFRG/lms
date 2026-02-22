<?php

namespace App\Filament\Resources\RrMaterialParents\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Actions\Action;

use Filament\Tables\Enums\FiltersResetActionPosition;
use Filament\Tables\Filters\Filter;

use Illuminate\Database\Eloquent\Builder;

class RrMaterialParentsTable
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

                TextColumn::make('title')
                    ->searchable()
                    ->limit(50)
                    ->sortable(),


                TextColumn::make('author')
                    ->label('Author')
                    ->formatStateUsing(function ($record) {
                        // 1. Try to get the name from the registered user relationship
                        // 2. If it's null (meaning they aren't registered), show the raw string
                        return $record->authorUser?->name ?? $record->author;
                    })
                    // This custom search ensures you can search by BOTH the raw name AND the registered user's name
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->where('author', 'like', "%{$search}%")
                            ->orWhereHas('authorUser', function (Builder $query) use ($search) {
                                $query->where('name', 'like', "%{$search}%");
                            });
                    })
                    ->sortable(),

                TextColumn::make('material_type')
                    ->badge()
                    ->colors([
                        'primary' => 1, // Book
                        'success' => 2, // Thesis
                        'warning' => 3, // Journal
                        'danger' => 4,  // Dissertation
                        'secondary' => 5, // Others
                    ])
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                        1 => 'Book',
                        2 => 'Thesis',
                        3 => 'Journal',
                        4 => 'Dissertation',
                        5 => 'Others',
                        default => 'Unknown',
                    }),

                TextColumn::make('publication_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('material_type')
                    ->label('Material Type')
                    ->options([
                        1 => 'Book',
                        2 => 'Thesis',
                        3 => 'Journal',
                        4 => 'Dissertation',
                        5 => 'Others',
                    ])
                    ->multiple(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersApplyAction(
                fn (Action $action) => $action->color('success') // UP Forest Green!
            )
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