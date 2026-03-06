<?php

namespace App\Filament\Resources\RrMaterials\Pages;

use App\Filament\Resources\RrMaterials\RrMaterialsResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

use App\Enums\UserRole;
use App\Enums\MaterialEventType;
use App\Models\MaterialAccessEvents;

use Filament\Support\Facades\FilamentView;

class ViewRrMaterials extends ViewRecord
{
    protected static string $resource = RrMaterialsResource::class;

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::content.start',
            fn (): \Illuminate\Contracts\View\View => view('filament.hooks.log-view-after-delay', [
                'wireId' => $this->getId(),
            ]),
        );
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewDocument')
                ->label('View Document')
                ->color('success') // UP Forest Green
                ->visible(fn ($record) => $this->canViewDocument($record))
                ->url(fn ($record) => route('materials.stream', ['record' => $record->id]), shouldOpenInNewTab: true)
                ->after(function ($record) {
                    MaterialAccessEvents::create([
                        'user_id' => auth()->id(),
                        'rr_material_id' => $record->id,
                        'event_type' => MaterialEventTypes::ACCESSED,
                    ]);
                }),

            EditAction::make()
                ->color('danger'),

            Action::make('logView')
                ->action(function () {
                    MaterialAccessEvents::create([
                        'user_id' => auth()->id(),
                        'rr_material_id' => $record->id,
                        'event_type' => MaterialEventType::VIEW,
                    ]);
                })
                ->hidden(), // Keep it invisible
        ];
    }

    protected function canViewDocument($record): bool
    {
        // 1. Must be a digital copy
        if (!$record->is_digital || empty($record->file_name)) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false; // Not logged in, deny access
        }

        $user_access_level = UserRole::from($user->role)->getAccessLevel();
        $accessLevel = (int) $record->parent->access_level;

        // 2. User's access level must be greater than or equal to the material's access level
        return $user_access_level >= $accessLevel;
    }

    protected $listeners = ['logView' => 'handleLogView'];

    public function handleLogView(): void
    {
        MaterialAccessEvents::create([
            'user_id' => auth()->id(),
            'rr_material_id' => $this->record->id,
            'event_type' => MaterialEventType::VIEW,
        ]);
    }
}
