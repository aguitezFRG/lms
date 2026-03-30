<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class RepositoryChangeLogs extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'editor_id',
        'rr_material_id',
        'material_parent_id',
        'target_user_id',
        'table_changed',
        'change_type',
        'change_made',
        'changed_at',
    ];

    protected $casts = [
        'change_made' => 'json',
        'changed_at' => 'datetime',
    ];

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function materialParent()
    {
        return $this->belongsTo(RrMaterialParents::class, 'material_parent_id');
    }

    public function material()
    {
        return $this->belongsTo(RrMaterials::class, 'rr_material_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }
}
