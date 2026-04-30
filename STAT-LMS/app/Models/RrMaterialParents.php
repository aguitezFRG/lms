<?php

namespace App\Models;

use App\Notifications\AccessLevelChanged;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Notification;

class RrMaterialParents extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'material_type',
        'title',
        'abstract',
        'keywords',
        'sdgs',
        'publication_date',
        'author',
        'adviser',
        'access_level',
    ];

    protected $casts = [
        'publication_date' => 'date',
        'adviser' => 'array',
        'keywords' => 'array',
        'sdgs' => 'array',
    ];

    protected static function booted(): void
    {
        static::updated(function (RrMaterialParents $material) {
            if (! $material->wasChanged('access_level')) {
                return;
            }

            $oldLevel = (int) $material->getOriginal('access_level');
            $newLevel = (int) $material->access_level;

            $activeEvents = MaterialAccessEvents::whereHas('material', fn ($q) => $q->where('material_parent_id', $material->id))
                ->whereIn('event_type', ['borrow', 'request'])
                ->whereIn('status', ['pending', 'approved'])
                ->with('user')
                ->get();

            if ($activeEvents->isEmpty()) {
                return;
            }

            // Notify all affected users
            $affectedUsers = User::whereIn('id', $activeEvents->pluck('user_id')->unique()->values())->get();
            Notification::send($affectedUsers, new AccessLevelChanged($material, $oldLevel, $newLevel));

            // Revoke events for users who no longer qualify for the new access level
            $disqualifiedRoles = match (true) {
                $newLevel >= 3 => ['student', 'faculty', 'staff/custodian'],
                $newLevel >= 2 => ['student'],
                default => [],
            };

            if (empty($disqualifiedRoles)) {
                return;
            }

            $activeEvents
                ->filter(fn ($event) => $event->user && in_array($event->user->role->value, $disqualifiedRoles))
                ->each(fn ($event) => $event->update([
                    'status' => 'rejected',
                    'rejection_reason' => ['Material access level was changed'],
                ]));
        });

        // If a parent material is deleted, mark all its copies as unavailable
        static::deleted(function (RrMaterialParents $parent) {
            $parent->materials()->withTrashed()->update(['is_available' => false]);
        });

        // If a parent material is restored, mark all its copies as available (only if they are not soft-deleted themselves)
        static::restored(function (RrMaterialParents $parent) {
            $parent->materials()->withTrashed()->whereNull('deleted_at')->update(['is_available' => true]);
        });
    }

    public function authorUser()
    {
        return $this->belongsTo(User::class, 'author', 'name');
    }

    public function materials()
    {
        return $this->hasMany(RrMaterials::class, 'material_parent_id');
    }
}
