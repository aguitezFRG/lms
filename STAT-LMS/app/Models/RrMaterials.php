<?php

namespace App\Models;

use App\Services\PdfNormalizationService;
use finfo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

            $disk = Storage::disk(self::materialDisk());
            if (! $disk->exists($copy->file_name)) {
                return;
            }

            try {
                self::normalizeStoredPdf($copy->file_name);
            } catch (\Throwable $e) {
                Log::error('PDF normalization failed', [
                    'material_id' => $copy->id,
                    'file_name' => $copy->file_name,
                    'error' => $e->getMessage(),
                ]);

                $disk->delete($copy->file_name);

                throw new \RuntimeException(
                    'The uploaded PDF could not be processed for secure viewing. '.
                    'Please re-save it as PDF 1.4 (e.g., using "Save As" in Adobe Acrobat) and try again.'
                );
            }

            try {
                self::enforceSharedUploadQuota($copy);
            } catch (\Throwable $e) {
                $disk->delete($copy->file_name);

                throw $e;
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

            $disk = Storage::disk(self::materialDisk());
            if ($disk->exists($oldPath)) {
                $disk->delete($oldPath);
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

    private static function materialDisk(): string
    {
        return (string) config('demo.material_disk', 'local');
    }

    private static function normalizeStoredPdf(string $objectKey): void
    {
        $disk = Storage::disk(self::materialDisk());
        $temporaryPath = null;

        try {
            try {
                $path = $disk->path($objectKey);
            } catch (\Throwable) {
                $temporaryPath = tempnam(sys_get_temp_dir(), 'stat_lms_pdf_');
                if ($temporaryPath === false) {
                    throw new \RuntimeException('Unable to allocate temporary PDF storage.');
                }

                $contents = $disk->get($objectKey);
                if (file_put_contents($temporaryPath, $contents) === false) {
                    throw new \RuntimeException('Unable to stage the uploaded PDF.');
                }

                $path = $temporaryPath;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if ($finfo->file($path) !== 'application/pdf') {
                throw new \RuntimeException('The uploaded file is not a valid PDF.');
            }

            app(PdfNormalizationService::class)->normalize($path);

            if ($temporaryPath !== null) {
                $stream = fopen($temporaryPath, 'rb');
                if ($stream === false) {
                    throw new \RuntimeException('Unable to reopen the normalized PDF.');
                }

                try {
                    $disk->put($objectKey, $stream);
                } finally {
                    fclose($stream);
                }
            }
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private static function enforceSharedUploadQuota(RrMaterials $copy): void
    {
        if (
            ! config('demo.enabled')
            || config('demo.runtime') !== 'server'
            || ! str_starts_with((string) $copy->file_name, 'uploads/')
        ) {
            return;
        }

        $disk = Storage::disk(self::materialDisk());
        $uploadBytes = array_sum(array_map($disk->size(...), $disk->allFiles('uploads')));
        $oldPath = (string) $copy->getOriginal('file_name');

        if (
            $copy->exists
            && $oldPath !== (string) $copy->file_name
            && str_starts_with($oldPath, 'uploads/')
            && $disk->exists($oldPath)
        ) {
            $uploadBytes -= $disk->size($oldPath);
        }

        if ($uploadBytes <= (int) config('demo.max_shared_upload_bytes')) {
            return;
        }

        throw new \RuntimeException(
            'The shared demo upload quota has been reached. Please wait for the next daily reset.'
        );
    }
}
