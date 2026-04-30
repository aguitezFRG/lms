<?php

namespace App\Filament\Resources\User\Catalogs\Pages;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Filament\Resources\User\Catalogs\CatalogResource;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterialParents;
use App\Models\RrMaterials;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewCatalog extends ViewRecord
{
    protected static string $resource = CatalogResource::class;

    protected array $availableCopyCache = [];

    protected array $activeRequestCache = [];

    protected array $approvedAccessCache = [];

    protected ?RrMaterials $digitalCopyCache = null;

    protected bool $digitalCopyResolved = false;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            // ── Request Digital Copy ──────────────────────────────────────
            Action::make('requestDigital')
                ->label('Request Digital Copy')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn () => $this->hasAvailableCopy(digital: true))
                ->disabled(fn () => auth()->user()?->is_banned || $this->hasActiveRequest(digital: true))
                ->tooltip(fn () => auth()->user()?->is_banned
                    ? 'Your account is banned from submitting requests.'
                    : ($this->hasActiveRequest(digital: true)
                        ? 'You already have an active request for the digital copy of this material.'
                        : null)
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
                ->disabled(fn () => auth()->user()?->is_banned || $this->hasActiveRequest(digital: false))
                ->tooltip(fn () => auth()->user()?->is_banned
                    ? 'Your account is banned from submitting requests.'
                    : ($this->hasActiveRequest(digital: false)
                        ? 'You already have an active borrow request for this material.'
                        : null)
                )
                ->requiresConfirmation()
                ->modalHeading('Borrow Physical Copy')
                ->modalDescription('A borrow request will be submitted. Please collect the copy from the reading room once approved.')
                ->modalSubmitActionLabel('Submit Borrow Request')
                ->action(fn () => $this->submitRequest(digital: false)),

            // ── View Document ─────────────────────────────────────────────
            Action::make('viewDocument')
                ->label('View Document')
                ->color('warning')
                ->icon('heroicon-o-eye')
                ->hidden(fn () => ! $this->canViewDocument())
                ->url(fn () => $this->getDocumentUrl(), shouldOpenInNewTab: true)
                ->extraAttributes([
                    'x-on:click.prevent' => '$wire.logAccessedEvent().then(() => window.open($el.href, `_blank`))',
                ]),

            // ── Refresh ───────────────────────────────────────────────────
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->tooltip('Refresh the data')
                ->action(fn () => $this->dispatch('$refresh')),
        ];
    }

    protected function hasAvailableCopy(bool $digital): bool
    {
        $key = $digital ? 'digital' : 'physical';

        if (! array_key_exists($key, $this->availableCopyCache)) {
            $this->availableCopyCache[$key] = ! $this->hasApprovedAccess($digital)
                && RrMaterials::where('material_parent_id', $this->record->id)
                    ->where('is_digital', $digital)
                    ->where('is_available', true)
                    ->whereNull('deleted_at')
                    ->exists();
        }

        return $this->availableCopyCache[$key];
    }

    protected function hasActiveRequest(bool $digital): bool
    {
        $key = $digital ? 'digital' : 'physical';

        if (! array_key_exists($key, $this->activeRequestCache)) {
            $this->activeRequestCache[$key] = MaterialAccessEvents::where('user_id', auth()->id())
                ->where('event_type', $digital
                    ? MaterialEventType::REQUEST->value
                    : MaterialEventType::BORROW->value
                )
                ->whereIn('status', ['pending', 'approved'])
                ->whereHas('material', fn ($q) => $q->where('material_parent_id', $this->record->id)
                    ->where('is_digital', $digital)
                )
                ->exists();
        }

        return $this->activeRequestCache[$key];
    }

    protected function submitRequest(bool $digital): void
    {
        $user = auth()->user();

        if (! $user || $user->is_banned) {
            Notification::make()
                ->title('Account restricted')
                ->body('Your account has been banned. You are not allowed to submit new requests.')
                ->danger()
                ->send();

            return;
        }

        $latestParent = RrMaterialParents::query()
            ->whereKey($this->record->id)
            ->select(['id', 'access_level'])
            ->first();

        if (! $latestParent || $user->role->getAccessLevel() < (int) $latestParent->access_level) {
            $this->forcePageRefresh();

            return;
        }

        $eventType = $digital ? MaterialEventType::REQUEST : MaterialEventType::BORROW;

        try {
            DB::transaction(function () use ($digital, $eventType, $user) {
                $parent = RrMaterialParents::query()
                    ->whereKey($this->record->id)
                    ->lockForUpdate()
                    ->first();

                if (! $parent || $user->role->getAccessLevel() < (int) $parent->access_level) {
                    throw new \RuntimeException('forbidden_access');
                }

                $duplicate = MaterialAccessEvents::where('user_id', auth()->id())
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereHas('material', fn ($q) => $q
                        ->where('material_parent_id', $this->record->id)
                        ->where('is_digital', $digital)
                    )
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new \RuntimeException('duplicate');
                }

                $copy = RrMaterials::where('material_parent_id', $this->record->id)
                    ->where('is_digital', $digital)
                    ->where('is_available', true)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (! $copy) {
                    throw new \RuntimeException('unavailable');
                }

                MaterialAccessEvents::create([
                    'user_id' => auth()->id(),
                    'rr_material_id' => $copy->id,
                    'event_type' => $eventType->value,
                    'status' => 'pending',
                ]);
            });
        } catch (\RuntimeException $e) {
            match ($e->getMessage()) {
                'forbidden_access' => null,
                'duplicate' => Notification::make()
                    ->title('Duplicate request')
                    ->body('You already have an active request for this material.')
                    ->warning()
                    ->send(),
                'unavailable' => Notification::make()
                    ->title('No copies available')
                    ->body('All copies of this type are currently unavailable. Please try again later.')
                    ->warning()
                    ->send(),
                default => Notification::make()
                    ->title('Request failed')
                    ->body('An unexpected error occurred. Please try again.')
                    ->danger()
                    ->send(),
            };

            if ($e->getMessage() === 'forbidden_access') {
                $this->forcePageRefresh();
            }

            return;
        }

        Notification::make()
            ->title($digital ? 'Digital request submitted!' : 'Borrow request submitted!')
            ->body('Your request is pending approval from the reading room staff.')
            ->success()
            ->send();
    }

    protected function forcePageRefresh(): void
    {
        $this->redirect(
            CatalogResource::getUrl().'?requestBlocked=1',
            navigate: false
        );
    }

    protected function canViewDocument(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Committee and IT bypass approval requirement
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT])) {
            return $this->getDigitalCopy() !== null;
        }

        $userLevel = $user->role->getAccessLevel();
        $accessLevel = (int) $this->record->access_level;

        if ($userLevel < $accessLevel) {
            return false;
        }

        // Approved request is always required for students and faculty
        return $this->hasApprovedAccess(digital: true);
    }

    protected function getDocumentUrl(): ?string
    {
        $copy = $this->getDigitalCopy();

        return $copy ? route('materials.viewer', ['record' => $copy->id]) : null;
    }

    public function logAccessedEvent(): void
    {
        $copy = $this->getDigitalCopy();

        if (! $copy) {
            return;
        }

        MaterialAccessEvents::create([
            'user_id' => auth()->id(),
            'rr_material_id' => $copy->id,
            'event_type' => MaterialEventType::ACCESSED->value,
        ]);
    }

    protected function hasApprovedAccess(bool $digital): bool
    {
        $key = $digital ? 'digital' : 'physical';

        if (! array_key_exists($key, $this->approvedAccessCache)) {
            $this->approvedAccessCache[$key] = MaterialAccessEvents::where('user_id', auth()->id())
                ->whereIn('event_type', ['request', 'borrow'])
                ->where('status', 'approved')
                ->whereHas('material', fn ($q) => $q->where('material_parent_id', $this->record->id)->where('is_digital', $digital))
                ->exists();
        }

        return $this->approvedAccessCache[$key];
    }

    protected function getDigitalCopy(): ?RrMaterials
    {
        if (! $this->digitalCopyResolved) {
            $this->digitalCopyCache = RrMaterials::where('material_parent_id', $this->record->id)
                ->where('is_digital', true)
                ->whereNotNull('file_name')
                ->whereNull('deleted_at')
                ->first();
            $this->digitalCopyResolved = true;
        }

        return $this->digitalCopyCache;
    }
}
