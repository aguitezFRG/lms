<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaterialAccessEvents extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'rr_material_id',
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
        'created_at' => 'datetime',
        'is_overdue' => 'boolean',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejection_reason' => 'array',
    ];

    protected static function booted(): void
    {
        static::updated(function (MaterialAccessEvents $event) {
            // Request is approved -> mark material as unavailable
            if ($event->wasChanged('status') && $event->status === 'approved') {
                $event->material()->update(['is_available' => false]);
            }

            // Material is returned -> mark material as available and event as completed
            if ($event->wasChanged('returned_at') && $event->returned_at !== null) {
                $event->material()->update(['is_available' => true]);
                $event->update(['status' => 'returned']);
                $event->update(['completed_at' => now()]);
            }

            // Request is rejected -> mark event as completed (material availability remains unchanged)
            if ($event->wasChanged('status') && $event->status === 'rejected') {
                $event->material()->update(['is_available' => true]);
                $event->update(['completed_at' => now()]);
                $event->update(['returned_at' => null]);
            }
        });
    }

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
