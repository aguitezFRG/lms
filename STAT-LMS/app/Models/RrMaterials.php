<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\PdfNormalizationService;
use finfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RrMaterials extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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

    protected static function booted(): void
    {
        static::saving(function (RrMaterials $copy) {
            if (! $copy->isDirty('file_name') || blank($copy->file_name)) {
                return;
            }

            $path = Storage::disk('local')->path($copy->file_name);
            if (! is_file($path)) {
                return;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($path) !== 'application/pdf') {
                return;
            }

            try {
                app(PdfNormalizationService::class)->normalize($path);
            } catch (\Throwable $e) {
                Log::error('PDF normalization failed', [
                    'material_id' => $copy->id,
                    'file_name' => $copy->file_name,
                    'error' => $e->getMessage(),
                ]);

                Storage::disk('local')->delete($copy->file_name);

                throw new \RuntimeException(
                    'The uploaded PDF could not be processed for secure viewing. '.
                    'Please re-save it as PDF 1.4 (e.g., using "Save As" in Adobe Acrobat) and try again.'
                );
            }
        });

        static::updating(function (RrMaterials $copy) {
            if (! $copy->isDirty('file_name')) {
                return;
            }

            $oldPath = $copy->getOriginal('file_name');

            if (blank($oldPath)) {
                return;
            }

            if ($oldPath === $copy->file_name) {
                return;
            }

            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        });

        static::deleting(function (RrMaterials $copy) {
            $copy->updateQuietly(['is_available' => false]);
        });

        static::restored(function (RrMaterials $copy) {
            $copy->updateQuietly(['is_available' => true]);
        });
    }

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
