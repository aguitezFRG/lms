<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use App\Models\MaterialAccessEvents;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Facades\FilamentView;

class ViewRrMaterials extends ViewRecord
{
    protected static string $resource = RrMaterialsResource::class;

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return $this->record->parent->title;
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::content.start',
            fn (): \Illuminate\Contracts\View\View => view('filament.hooks.log-view-after-delay', [
                'wireId' => $this->getId(),
            ]),
        );
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewDocument')
                ->label('View Document')
                ->color('success')
                ->hidden(fn () => ! $this->record?->is_digital || ! $this->canViewDocument($this->record))
                ->url(fn () => route('materials.viewer', ['record' => $this->record->id]), shouldOpenInNewTab: true)
                ->extraAttributes([
                    'x-on:click.prevent' => '$wire.logAccessedEvent().then(() => window.open($el.href, `_blank`))',
                ]),

            EditAction::make()
                ->color('danger'),

            Action::make('logView')
                ->action(function () {
                    MaterialAccessEvents::create([
                        'user_id' => auth()->id(),
                        'rr_material_id' => $this->record->id,
                        'event_type' => MaterialEventType::VIEW->value,
                    ]);
                })
                ->hidden(), // Keep it invisible
        ];
    }

    protected function canViewDocument($record): bool
    {
        // 1. Must be a digital copy
        if (! $record->is_digital || empty($record->file_name)) {
            return false;
        }

        $user = auth()->user();
        if (! $user) {
            return false; // Not logged in, deny access
        }

        // 2. Super Admin,IT and Committee can always view
        if (in_array($user->role, [UserRole::SUPER_ADMIN->value, UserRole::IT->value, UserRole::COMMITTEE->value])) {
            return true;
        }

        // 3. Check access level
        $userAccessLevel = UserRole::from($user->role)->getAccessLevel();
        $materialAccessLevel = (int) $record->parent->access_level;

        if ($userAccessLevel < $materialAccessLevel) {
            return false; // User's access level is too low
        }

        // 4. Approved request is required
        return MaterialAccessEvents::where('user_id', $user->id)
            ->where('rr_material_id', $record->id)
            ->where('event_type', MaterialEventType::REQUEST->value)
            ->where('status', 'approved')
            ->exists();
    }

    protected $listeners = ['logView' => 'handleLogView'];

    public function handleLogView(): void
    {
        MaterialAccessEvents::create([
            'user_id' => auth()->id(),
            'rr_material_id' => $this->record->id,
            'event_type' => MaterialEventType::VIEW->value,
        ]);
    }

    public function logAccessedEvent(): void
    {
        MaterialAccessEvents::create([
            'user_id' => auth()->id(),
            'rr_material_id' => $this->record->id,
            'event_type' => MaterialEventType::ACCESSED->value,
        ]);
    }
}
