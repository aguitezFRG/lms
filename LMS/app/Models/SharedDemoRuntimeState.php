<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SharedDemoRuntimeState extends Model
{
    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'status',
        'maintenance',
        'last_idempotency_key',
        'last_bootstrapped_at',
        'last_reset_at',
        'last_duration_ms',
        'canonical_counts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'maintenance' => 'boolean',
            'last_bootstrapped_at' => 'datetime',
            'last_reset_at' => 'datetime',
            'canonical_counts' => 'array',
        ];
    }
}
