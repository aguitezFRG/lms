<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class MaterialAccessEvents extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'user_id',
        'rr_material_id',
        'approver_id',
        'event_type',
        'status',
        'due_at',
        'returned_at',
        'is_overdue',
        'approved_at',
        'completed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'is_overdue' => 'boolean',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejection_reason' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function material()
    {
        return $this->belongsTo(RrMaterials::class, 'rr_material_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
