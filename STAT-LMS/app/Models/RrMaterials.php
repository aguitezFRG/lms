<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RrMaterials extends Model
{
    use HasFactory, SoftDeletes;


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
