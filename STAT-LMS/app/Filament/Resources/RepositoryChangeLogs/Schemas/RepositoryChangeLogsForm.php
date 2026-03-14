<?php

namespace App\Filament\Resources\RepositoryChangeLogs\Schemas;

use App\Enums\RepositoryChangeType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class RepositoryChangeLogsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Change Overview')
                    ->schema([
                        TextInput::make('editor_id')
                            ->label('Editor')
                            ->disabled(),

                        TextInput::make('table_changed')
                            ->label('Table Changed')
                            ->disabled(),

                        Select::make('change_type')
                            ->label('Change Type')
                            ->options(collect(RepositoryChangeType::cases())
                                ->mapWithKeys(fn ($case) => [$case->value => $case->getLabel()]))
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('Related Records')
                    ->schema([
                        TextInput::make('rr_material_id')
                            ->label('Related Material')
                            ->disabled(),

                        TextInput::make('target_user_id')
                            ->label('Target User')
                            ->disabled(),

                        DateTimePicker::make('changed_at')
                            ->label('Changed At')
                            ->disabled(),
                    ])
                    ->columns(3),

                Section::make('Change Details')
                    ->schema([
                        KeyValue::make('change_made')
                            ->label('Changes Made')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->disabled(),
                    ]),
            ]);
    }
}