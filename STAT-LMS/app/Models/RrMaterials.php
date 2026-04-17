<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class RrMaterials extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'material_parent_id',
        'is_digital',
        'is_available',
        'file_name',
    ];

    protected $casts = [
        'is_digital' => 'boolean',
        'is_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::updating(function (RrMaterials $copy) {
            if (! $copy->isDirty('file_name')) {
                return;
            }

            $oldPath = $copy->getOriginal('file_name');

            if (blank($oldPath)) {
                return;
            }

            if ($oldPath === $copy->file_name) {
                return;
            }

            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        });
    }

    public function parent()
    {
        return $this->belongsTo(RrMaterialParents::class, 'material_parent_id');
    }

    public function accessEvents()
    {
        return $this->hasMany(MaterialAccessEvents::class, 'rr_material_id');
    }

    public function changeLogs()
    {
        return $this->hasMany(RepositoryChangeLogs::class, 'rr_material_id');
    }
}
