<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RrMaterialParents extends Model
{
    use HasFactory;

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
    ];
}