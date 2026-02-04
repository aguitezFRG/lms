<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MaterialAccessEventsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('rr_material_id')
                    ->required()
                    ->numeric(),
                TextInput::make('approver_id')
                    ->required()
                    ->numeric(),
                TextInput::make('event_type')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                DateTimePicker::make('due_at'),
                DateTimePicker::make('returned_at'),
                Toggle::make('is_overdue')
                    ->required(),
                DateTimePicker::make('approved_at'),
                DateTimePicker::make('completed_at'),
            ]);
    }
}
