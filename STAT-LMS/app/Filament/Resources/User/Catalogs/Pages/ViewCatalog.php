<?php

namespace App\Filament\Resources\User\Catalogs\Pages;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Filament\Resources\User\Catalogs\CatalogResource;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterials;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCatalog extends ViewRecord
{
    protected static string $resource = CatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // ── Request Digital Copy ──────────────────────────────────────
            Action::make('requestDigital')
                ->label('Request Digital Copy')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->hasAvailableCopy(digital: true))
                ->disabled(fn () => $this->hasActiveRequest(digital: true))
                ->tooltip(fn () => $this->hasActiveRequest(digital: true)
                    ? 'You already have an active request for the digital copy of this material.'
                    : null
                )
                ->requiresConfirmation()
                ->modalHeading('Request Digital Copy')
                ->modalDescription('A request will be submitted for staff review. You will be notified once it is approved.')
                ->modalSubmitActionLabel('Submit Request')
                ->action(fn () => $this->submitRequest(digital: true)),

            // ── Borrow Physical Copy ──────────────────────────────────────
            Action::make('borrowPhysical')
                ->label('Borrow Physical Copy')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->visible(fn () => $this->hasAvailableCopy(digital: false))
                ->disabled(fn () => $this->hasActiveRequest(digital: false))
                ->tooltip(fn () => $this->hasActiveRequest(digital: false)
                    ? 'You already have an active borrow request for this material.'
                    : null
                )
                ->requiresConfirmation()
                ->modalHeading('Borrow Physical Copy')
                ->modalDescription('A borrow request will be submitted. Please collect the copy from the reading room once approved.')
                ->modalSubmitActionLabel('Submit Borrow Request')
                ->action(fn () => $this->submitRequest(digital: false)),

            // ── View Document ─────────────────────────────────────────────
            Action::make('viewDocument')
                ->label('View Document')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn () => $this->canViewDocument())
                ->url(fn () => $this->getDocumentUrl(), shouldOpenInNewTab: true),
        ];
    }

    // ── Helper: does an available copy of this type exist? ─────────────────
    protected function hasAvailableCopy(bool $digital): bool
    {
        return RrMaterials::where('material_parent_id', $this->record->id)
            ->where('is_digital', $digital)
            ->where('is_available', true)
            ->whereNull('deleted_at')
            ->exists();
    }

    // ── Helper: does the user already have a pending/approved request? ──────
    protected function hasActiveRequest(bool $digital): bool
    {
        $eventType = $digital
            ? MaterialEventType::REQUEST->value
            : MaterialEventType::BORROW->value;

        return MaterialAccessEvents::where('user_id', auth()->id())
            ->where('event_type', $eventType)
            ->whereIn('status', ['pending', 'approved'])
            ->whereHas('material', fn ($q) =>
                $q->where('material_parent_id', $this->record->id)
                  ->where('is_digital', $digital)
            )
            ->exists();
    }

    // ── Action: auto-assign first available copy and create the event ───────
    protected function submitRequest(bool $digital): void
    {
        // Auto-assign: pick the first available copy (FIFO)
        $copy = RrMaterials::where('material_parent_id', $this->record->id)
            ->where('is_digital', $digital)
            ->where('is_available', true)
            ->whereNull('deleted_at')
            ->first();

        if (! $copy) {
            Notification::make()
                ->title('No copies available')
                ->body('All copies of this type are currently unavailable. Please try again later.')
                ->warning()
                ->send();
            return;
        }

        $eventType = $digital ? MaterialEventType::REQUEST : MaterialEventType::BORROW;

        // Duplicate guard on the specific copy
        $duplicate = MaterialAccessEvents::where('user_id', auth()->id())
            ->where('rr_material_id', $copy->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($duplicate) {
            Notification::make()
                ->title('Duplicate request')
                ->body('You already have an active request for this material.')
                ->warning()
                ->send();
            return;
        }

        MaterialAccessEvents::create([
            'user_id'        => auth()->id(),
            'rr_material_id' => $copy->id,
            'event_type'     => $eventType->value,
            'status'         => 'pending',
        ]);

        Notification::make()
            ->title($digital ? 'Digital request submitted!' : 'Borrow request submitted!')
            ->body('Your request is pending approval from the reading room staff.')
            ->success()
            ->send();
    }

    // ── Helper: can this user view the document right now? ──────────────────
    protected function canViewDocument(): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        $userLevel   = UserRole::from($user->role)->getAccessLevel();
        $accessLevel = (int) $this->record->access_level;

        if ($userLevel < $accessLevel) return false;

        // Check for an approved REQUEST event on any digital copy of this parent
        $hasApproved = MaterialAccessEvents::where('user_id', $user->id)
            ->where('event_type', MaterialEventType::REQUEST->value)
            ->where('status', 'approved')
            ->whereHas('material', fn ($q) =>
                $q->where('material_parent_id', $this->record->id)
                  ->where('is_digital', true)
            )
            ->exists();

        if ($hasApproved) return true;

        // Public (level 1) digital materials are directly readable without a request
        return $accessLevel === 1
            && RrMaterials::where('material_parent_id', $this->record->id)
                ->where('is_digital', true)
                ->whereNotNull('file_name')
                ->whereNull('deleted_at')
                ->exists();
    }

    // ── Helper: get the stream URL for the first available digital copy ──────
    protected function getDocumentUrl(): ?string
    {
        $copy = RrMaterials::where('material_parent_id', $this->record->id)
            ->where('is_digital', true)
            ->whereNotNull('file_name')
            ->whereNull('deleted_at')
            ->first();

        return $copy ? route('materials.stream', ['record' => $copy->id]) : null;
    }
}