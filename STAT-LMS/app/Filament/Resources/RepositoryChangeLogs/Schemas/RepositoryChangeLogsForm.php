<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class RepositoryChangeLogsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('editor_id')
                    ->required()
                    ->numeric(),
                TextInput::make('rr_material_id')
                    ->required()
                    ->numeric(),
                TextInput::make('target_user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('table_changed')
                    ->required(),
                TextInput::make('change_type')
                    ->required(),
                Textarea::make('change_made')
                    ->columnSpanFull(),
                DateTimePicker::make('changed_at')
                    ->required(),
            ]);
    }
}
