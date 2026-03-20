<?php

namespace App\Observers;

use App\Notifications\AccountDetailsChanged;
use App\Models\RepositoryChangeLogs;
use App\Enums\RepositoryChangeType;
use Illuminate\Database\Eloquent\Model;

class RepositoryChangeLogsObserver
{

    protected function filterAttributes(Model $model, array $attributes): array
    {
        $excluded = $model->excludedFromChangeLogs ?? [];

        return collect($attributes)
            ->except($excluded)
            ->toArray();
    }

    /**
     * Handle the RepositoryChangeLogs "created" event.
     */
    public function created(Model $model): void
    {
        RepositoryChangeLogs::create([
            'editor_id'      => auth()->id(),
            'rr_material_id' => $this->getMaterialId($model),
            'target_user_id' => $this->getTargetUserId($model),
            'table_changed'  => $model->getTable(),
            'change_type'    => RepositoryChangeType::CREATE->value,
            'change_made'    => collect($this->filterAttributes($model, $model->getAttributes()))
                ->mapWithKeys(fn ($value, $key) => [
                    $key => ['old' => null, 'new' => $value]
                ])->toArray(),
            'changed_at'     => now(),
        ]);
    }

    /**
     * Handle the RepositoryChangeLogs "updated" event.
     */
    public function updated(Model $model): void
    {

        RepositoryChangeLogs::create([
            'editor_id'      => auth()->id(),
            'rr_material_id' => $this->getMaterialId($model),
            'target_user_id' => $this->getTargetUserId($model),
            'table_changed'  => $model->getTable(),
            'change_type'    => RepositoryChangeType::UPDATE->value,
            'change_made'    => collect($this->filterAttributes($model, $model->getAttributes()))
                ->mapWithKeys(fn ($value, $key) => [
                    $key => ['old' => null, 'new' => $value]
                ])->toArray(),
            'changed_at'     => now(),
        ]);


        // Notify the user if an admin changed their account details
        // Only fires when the editor is a different person than the target
        if (
            $model instanceof User &&
            $model->getTable() === 'users' &&
            auth()->id() !== $model->id
        ) {
            $excluded = array_merge(
                $model->excludedFromChangeLogs ?? [],
                ['remember_token', 'updated_at']
            );

            $changedFields = array_keys(
                collect($model->getDirty())->except($excluded)->toArray()
            );

            if (count($changedFields) > 0)
            {
                $model->notify(new AccountDetailsChanged($changedFields));
            }
        }
    }

    /**
     * Handle the RepositoryChangeLogs "deleted" event.
     */
    public function deleted(Model $model): void
    {
        RepositoryChangeLogs::create([
            'editor_id'      => auth()->id(),
            'rr_material_id' => $this->getMaterialId($model),
            'target_user_id' => $this->getTargetUserId($model),
            'table_changed'  => $model->getTable(),
            'change_type'    => RepositoryChangeType::DELETE->value,
            'change_made'    => collect($this->filterAttributes($model, $model->getAttributes()))
                ->mapWithKeys(fn ($value, $key) => [
                    $key => ['old' => null, 'new' => $value]
                ])->toArray(),
            'changed_at'     => now(),
        ]);
    }

    /**
     * Handle the RepositoryChangeLogs "restored" event.
     */
    public function restored(Model $model): void
    {
        RepositoryChangeLogs::create([
            'editor_id'      => auth()->id(),
            'rr_material_id' => $this->getMaterialId($model),
            'target_user_id' => $this->getTargetUserId($model),
            'table_changed'  => $model->getTable(),
            'change_type'    => RepositoryChangeType::RESTORE->value,
            'change_made'    => collect($this->filterAttributes($model, $model->getAttributes()))
                ->mapWithKeys(fn ($value, $key) => [
                    $key => ['old' => null, 'new' => $value]
                ])->toArray(),
            'changed_at'     => now(),
        ]);
    }

    /**
     * Handle the RepositoryChangeLogs "force deleted" event.
     */
    public function forceDeleted(Model $model): void
    {
        RepositoryChangeLogs::create([
            'editor_id'      => auth()->id(),
            'rr_material_id' => $this->getMaterialId($model),
            'target_user_id' => $this->getTargetUserId($model),
            'table_changed'  => $model->getTable(),
            'change_type'    => RepositoryChangeType::DELETE->value,
            'change_made'    => collect($this->filterAttributes($model, $model->getAttributes()))
                ->mapWithKeys(fn ($value, $key) => [
                    $key => ['old' => null, 'new' => $value]
                ])->toArray(),
            'changed_at'     => now(),
        ]);
    }

    private function getMaterialId(Model $model): ?string
    {
        return match (true) {
            isset($model->rr_material_id) => $model->rr_material_id,
            $model->getTable() === 'rr_materials' => $model->id,
            default => null,
        };
    }

    private function getTargetUserId(Model $model): ?string
    {
        return match (true) {
            isset($model->user_id) => $model->user_id,
            isset($model->target_user_id) => $model->target_user_id,
            $model->getTable() === 'users' => $model->id,
            default => null,
        };
    }
}
