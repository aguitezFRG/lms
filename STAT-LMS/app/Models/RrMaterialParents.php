<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RrMaterialParents extends Model
{
    use HasFactory, HasUuids;

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

    public function authorUser()
    {
        return $this->belongsTo(User::class, 'author', 'name');
    }

    public function materials()
    {
        return $this->hasMany(RrMaterials::class, 'material_parent_id');
    }
}