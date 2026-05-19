<?php

namespace App\Models;

use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UnitEnum;

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

    public function getChangeRowsAttribute(): array
    {
        return collect($this->change_made ?? [])
            ->map(fn (mixed $value, string|int $field): array => [
                'field' => (string) $field,
                'old_value' => self::formatChangeValue($value['old'] ?? null),
                'new_value' => self::formatChangeValue($value['new'] ?? null),
            ])
            ->values()
            ->all();
    }

    public static function formatChangeValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            $value instanceof CarbonInterface => $value->toDayDateTimeString(),
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof UnitEnum => $value->name,
            is_array($value) => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]',
            default => (string) $value,
        };
    }
}
