<?php

namespace App\Filament\Resources\MaterialAccessEvents\Schemas;

use App\Enums\MaterialEventType;
use App\Models\MaterialAccessEvents;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaterialAccessEventsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Overview')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('user_id')
                            ->label('Requested By')
                            ->tooltip(fn (MaterialAccessEvents $record) => $record->user?->name ?? 'N/A'),

                        TextEntry::make('rr_material_id')
                            ->label('Requested Material')
                            ->tooltip(fn (MaterialAccessEvents $record) => $record->material?->parent?->title ?? 'N/A'),

                        TextEntry::make('approver_id')
                            ->label('Edited By')
                            ->placeholder('N/A')
                            ->tooltip(fn (MaterialAccessEvents $record) => $record->approver?->name ?? 'N/A'),

                        TextEntry::make('event_type')
                            ->label('Event Type')
                            ->color(fn (string $state) => MaterialEventType::from($state)->getColor())
                            ->formatStateUsing(fn (string $state) => MaterialEventType::from($state)->getLabel()),
                    ])
                    ->columns(2),

                Section::make('Request Details')
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('status')
                            ->label('Request Status'),

                        TextEntry::make('rejection_reason')
                            ->label('Rejection Reason(s)')
                            ->placeholder('N/A')
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : $state)
                            ->visible(fn (MaterialAccessEvents $record) => $record->status === 'rejected'),

                        TextEntry::make('approved_at')
                            ->label('Approved At')
                            ->placeholder('N/A')
                            ->datetime('F d, Y h:i A'),

                        TextEntry::make('created_at')
                            ->label('Requested At')
                            ->datetime('F d, Y h:i A'),

                        TextEntry::make('due_at')
                            ->label('Due At')
                            ->placeholder('N/A')
                            ->datetime('F d, Y h:i A'),

                        TextEntry::make('returned_at')
                            ->label('Returned At')
                            ->placeholder('N/A')
                            ->datetime('F d, Y h:i A'),

                        TextEntry::make('completed_at')
                            ->label('Completed At')
                            ->placeholder('N/A')
                            ->datetime('F d, Y h:i A'),

                        TextEntry::make('is_overdue')
                            ->label('Overdue')
                            ->icon(fn (bool $state) => $state ? 'heroicon-m-x-circle' : 'heroicon-m-check-circle')
                            ->iconColor(fn (bool $state) => $state ? 'danger' : 'success')
                            ->formatStateUsing(fn (bool $state) => $state ? 'Overdue' : 'Not Overdue')
                            ->color(fn (bool $state) => $state ? 'danger' : 'success'),

                        TextEntry::make('id')
                            ->label('Overdue Fee')
                            ->columnSpanFull()
                            ->color(fn (MaterialAccessEvents $record) => $record->returned_at ? 'success' : 'danger')
                            ->icon(fn (MaterialAccessEvents $record) => $record->returned_at ? 'heroicon-m-check-circle' : 'heroicon-m-banknotes')
                            ->formatStateUsing(function (MaterialAccessEvents $record) {
                                if (! $record->is_overdue) {
                                    return 'No fee — not overdue.';
                                }

                                $feePerDay = config('reading_room.overdue_fee_per_day', 10);
                                $daysOverdue = (int) now()->diffInDays($record->due_at, absolute: true);
                                $totalFee = $daysOverdue * $feePerDay;

                                $status = $record->returned_at
                                    ? 'PAID (marked returned)'
                                    : 'UNPAID';

                                return "₱{$totalFee}.00 ({$daysOverdue} day(s) × ₱{$feePerDay}/day) — {$status}";
                            })
                            ->visible(fn (MaterialAccessEvents $record) => $record->is_overdue),
                    ])
                    ->columns(3),
            ]);
    }
}
