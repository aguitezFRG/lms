<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure($schema)
    {

        $updateFullName = function (callable $set, callable $get) {
            $fName = $get('f_name');
            $mName = $get('m_name');
            $lName = $get('l_name');

            $fullName = trim(implode(' ', array_filter([$fName, $mName, $lName])));

            $set('name', $fullName);
        };

        return $schema
            ->schema([
                Section::make('Personal Information')
                    ->schema([
                        TextInput::make('id')
                            ->label('User ID (UUID)')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view'])
                            ->columnSpanFull(),
                        TextInput::make('f_name')
                            ->label('First Name')
                            ->maxLength(255)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated($updateFullName),
                        TextInput::make('m_name')
                            ->label('Middle Name')
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated($updateFullName),
                        TextInput::make('l_name')
                            ->label('Last Name')
                            ->maxLength(255)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated($updateFullName),
                        TextInput::make('name')
                            ->label('Display Name (Full)')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                // This section is only visible for student and faculty roles
                Section::make('Account Details')
                    ->schema([
                        TextInput::make('std_number')
                            ->label('Student Number')
                            ->unique(table: User::class, column: 'std_number')
                            ->mask('9999-99999')
                            ->placeholder('e.g. 2020-12345')
                            ->length(10)
                            ->rules(['nullable', 'regex:/^\d{4}-\d{5}$/']),
                        Select::make('role')
                            ->options([
                                'student' => 'Student',
                                'faculty' => 'Faculty',
                                'staff/custodian' => 'Staff/Custodian',
                                'it' => 'IT',
                                'committee' => 'Committee',
                            ])
                            ->default('student')
                            ->required()
                            ->live(),
                        TextInput::make('email')
                            ->email()
                            ->unique(table: User::class, column: 'email')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->maxLength(255)
                            ->rules([
                                fn (string $context) => $context === 'create'
                                ? Password::min(8)
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                                    // ->uncompromised() // uncomment if the hosted environment supports it

                                : Password::min(8)
                                    ->mixedCase()
                                    ->numbers()
                                    ->symbols()
                                    // ->uncompromised() // uncomment if the hosted environment supports it
                                    ->sometimes(),
                            ])
                            ->helperText('Minimum 8 characters with at least one uppercase letter, one lowercase letter, one number, and one symbol.'),
                    ])->columns(2),

                Section::make('Account Status')
                    ->schema([
                        Toggle::make('is_banned')
                            ->label('Banned')
                            ->helperText('Banned users cannot log in or submit new requests, and lose access to approved materials.')
                            ->onColor('danger')
                            ->default(false)
                            ->visibleOn(['edit']),
                    ])
                    ->visible(fn (Get $get) => in_array($get('role'), ['student', 'faculty']))
                    ->visibleOn(['edit'])
                    ->columns(1),

            ]);
    }
}
