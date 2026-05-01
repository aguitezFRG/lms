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
        'approver_id',
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
            // Physical borrows consume a copy; digital approvals should preserve availability.
            if (
                $event->wasChanged('status') &&
                $event->status === 'approved' &&
                $event->event_type === 'borrow'
            ) {
                $event->material?->updateQuietly(['is_available' => false]);
            }

            // Returning a borrow closes the event and re-opens copy availability.
            // Keep these writes explicit so downstream observers receive final state.
            if ($event->wasChanged('returned_at') && $event->returned_at !== null) {
                $event->material()->update(['is_available' => true]);
                $event->update(['status' => 'returned']);
                $event->update(['completed_at' => now()]);
            }

            // Rejection finalizes the record and clears approval-related timestamps.
            // saveQuietly avoids recursive status side-effects during cleanup.
            if ($event->wasChanged('status') && $event->status === 'rejected') {
                $event->material()->update(['is_available' => true]);
                $event->completed_at = now();
                $event->returned_at = null;
                $event->approved_at = null;
                $event->due_at = null;
                $event->saveQuietly();
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
