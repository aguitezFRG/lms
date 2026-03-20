<?php

namespace App\Models;

use App\Notifications\AccessLevelChanged;
use App\Models\MaterialAccessEvents;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RrMaterialParents extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

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

    protected static function booted() : void
    {
        static::updated(function (RrMaterialParents $material) {
            if (! $material->wasChanged('access_level')) {
                return;
            }

            $oldLevel = (int) $material->getOriginal('access_level');
            $newLevel = (int) $material->access_level;

            // Notify all users who have any access event (borrow or request)
            // linked to a copy of this material
            $affectedUserIds = MaterialAccessEvents::whereHas('material', fn ($q) =>
                $q->where('material_parent_id', $material->id)
            )
            ->whereIn('event_type', ['borrow', 'request'])
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('user_id')
            ->unique();

            User::whereIn('id', $affectedUserIds)->each(function (User $user) use ($material, $oldLevel, $newLevel) {
                $user->notify(new AccessLevelChanged($material, $oldLevel, $newLevel));
            });
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