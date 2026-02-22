<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
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
                            ->label('UUID')
                            ->copyable(),

                        TextEntry::make('f_name')
                            ->label('First Name'),

                        TextEntry::make('m_name')
                            ->label('Middle Name')
                            ->placeholder('N/A'),

                        TextEntry::make('l_name')
                            ->label('Last Name'),

                        TextEntry::make('std_number')
                            ->label('Student Number')
                            ->placeholder('N/A'),

                        TextEntry::make('email')
                            ->label('Email Address')
                            ->icon('heroicon-m-envelope'),

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

                    Section::make('Account Details')
                    ->components([
                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime('F d, Y h:i A'),

                        TextEntry::make('deleted_at')
                            ->label('Soft Deleted At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('Not Deleted')
                            ->color('danger'),

                        TextEntry::make('revoked_at')
                            ->label('Access Revoked At')
                            ->dateTime('F d, Y h:i A')
                            ->placeholder('Active Account')
                            ->color('warning'),
                    ])->columns(2),
            ]);
    }
}