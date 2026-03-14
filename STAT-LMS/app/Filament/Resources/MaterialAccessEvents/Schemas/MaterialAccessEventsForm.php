<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Hidden;

class MaterialAccessEventsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status')
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('status')
                            ->label('Status')
                            ->options([
                                'approved' => 'Approve',
                                'rejected' => 'Reject',
                            ])
                            ->colors([
                                'approved' => 'success',
                                'rejected' => 'danger',
                            ])
                            ->icons([
                                'approved' => 'heroicon-o-check-circle',
                                'rejected' => 'heroicon-o-x-circle',
                            ])
                            ->live()
                            ->required(),
                    ])
                    ->columns(1),

                Section::make('Dates')
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('due_at')
                            ->label('Due Date')
                            ->minDate(now()->addDays(1))
                            ->rules(['date', 'after:today'])
                            ->formatStateUsing(fn ($state) => $state ?? now()->addDays(14)->toDateString()),

                        DatePicker::make('returned_at')
                            ->label('Returned At')
                            ->minDate(now()->addDays(1))
                            ->rules(['date', 'after:today']),
                    ])
                    ->columns(1)
                    ->visible(fn (callable $get) => $get('status') === 'approved'),
            ]);
    }
}
