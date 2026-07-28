<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialAccessEventsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Decision')
                    ->columnSpanFull()
                    ->schema([
                        ToggleButtons::make('status')
                            ->inline()
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
                            ->required()
                            ->columnSpanFull(),

                        Placeholder::make('approved_details')
                            ->label('Approval Details')
                            ->content('Set the due/return dates for approved requests.')
                            ->visible(fn (callable $get) => $get('status') === 'approved')
                            ->columnSpanFull(),

                        DatePicker::make('due_at')
                            ->label('Due Date')
                            ->minDate(now()->addDays(1)->startOfDay())
                            ->rules(['nullable', 'date', 'after_or_equal:'.now()->addDays(1)->toDateString()])
                            ->formatStateUsing(fn ($state) => $state ?? now()->addDays(14)->toDateString())
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('due_at', $state ? $state.' 23:59:59' : null)
                            )
                            ->dehydrated(fn (callable $get) => $get('status') === 'approved')
                            ->visible(fn (callable $get) => $get('status') === 'approved'),

                        DatePicker::make('returned_at')
                            ->label('Returned At')
                            ->maxDate(now())
                            ->rules(['nullable', 'date', 'before_or_equal:today'])
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('returned_at', $state ? $state.' 23:59:59' : null)
                            )
                            ->dehydrated(fn (callable $get) => $get('status') === 'approved')
                            ->visible(fn (callable $get) => $get('status') === 'approved'),

                        Placeholder::make('rejection_details')
                            ->label('Rejection Details')
                            ->content('Provide one or more reasons for rejection.')
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),

                        TagsInput::make('rejection_reason')
                            ->label('Reason(s)')
                            ->placeholder('Select or type a reason...')
                            ->suggestions([
                                'Overdue materials on record',
                                'Outstanding fees',
                                'Request limit reached',
                                'Incomplete request details',
                                'Access level restriction',
                                'Material currently unavailable',
                                'Policy violation',
                                'Duplicate request',
                            ])
                            ->hint('Select from suggestions or type a custom reason and press Enter.')
                            ->hintColor('gray')
                            ->visible(fn (callable $get) => $get('status') === 'rejected')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
