<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\TextColumn;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Profile')
                    ->components([
                        TextEntry::make('id')
                            ->label('UUID'),

                        TextEntry::make('f_name')
                            ->label('First Name'),

                        TextEntry::make('m_name')
                        ->label('Middle Name'),

                        TextEntry::make('l_name')
                            ->label('Last Name'),

                        TextEntry::make('std_number')
                            ->label('Student Number'),

                        TextEntry::make('email')
                            ->label('Email Address'),

                        TextEntry::make('role')
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
                    ])->columns(2),
            ]);
    }
}
