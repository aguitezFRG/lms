<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

class MaterialAccessEventsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'revoked' => 'Revoked',
                            ])
                            ->required(),
                        Toggle::make('is_overdue')
                            ->label('Overdue'),
                    ])
                    ->columns(1),
                Section::make('Dates')
                    ->schema([
                        DatePicker::make('due_at')
                            ->label('Due Date')
                            ->formatStateUsing(fn ($state) => $state ?? now()->addDays(14)->toDateString()),
                        DatePicker::make('returned_at')
                            ->label('Returned At'),
                    ])
                    ->columns(1),
            ]);
    }
}
