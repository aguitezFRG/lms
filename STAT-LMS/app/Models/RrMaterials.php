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
        return $this->belongsTo(rr_material_parents::class, 'material_parent_id');
    }

    public function accessEvents()
    {
        return $this->hasMany(material_access_events::class, 'rr_material_id');
    }

    public function changeLogs()
    {
        return $this->hasMany(repository_change_logs::class, 'rr_material_id');
    }
}
