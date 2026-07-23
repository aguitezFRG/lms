<?php

namespace App\Http\Controllers;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterials;
use App\Notifications\RequestStatusChanged;
use App\Services\PdfWatermarkService;
use finfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MaterialStreamController extends Controller
{
    /**
     * Render the secure PDF viewer page.
     * This is the URL users are sent to from the Filament action.
     */
    public function viewer(RrMaterials $record, PdfWatermarkService $pdfWatermarkService)
    {
        $this->authorizeAccess($record);

        if (config('demo.enabled') && config('demo.runtime') === 'browser') {
            $streamUrl = $this->demoStaticPdfUrl($record);
        } else {
            if ($this->materialObjectKey($record) === null) {
                abort(404);
            }

            $streamUrl = route('materials.stream', ['record' => $record->id]);
        }

        $metadata = $pdfWatermarkService->watermarkMetadata(
            auth()->user(),
            now()->setTimezone('Asia/Manila')
        );

        return view('filament.pdf.viewer', [
            'record' => $record,
            'streamUrl' => $streamUrl,
            'user' => auth()->user(),
            'title' => $record->parent?->title ?? basename($record->file_name),
            'qrDataUrl' => $metadata['qrDataUrl'],
            'infoLine' => $metadata['infoLine'],
        ]);
    }

    /**
     * Stream the raw PDF bytes.
     * Called only by the viewer Blade — not exposed as a direct download link.
     */
    public function stream(RrMaterials $record, PdfWatermarkService $pdfWatermarkService)
    {
        $this->authorizeAccess($record);

        // Packaged PDFs are fetched directly from the static host in browser demo mode.
        abort_if(config('demo.enabled') && config('demo.runtime') === 'browser', 404);

        $objectKey = $this->materialObjectKey($record);

        if ($objectKey === null) {
            Log::warning('Stream blocked: invalid material file path', [
                'material_id' => $record->id,
            ]);
            abort(404);
        }

        $disk = Storage::disk((string) config('demo.material_disk', 'local'));

        if (! $disk->exists($objectKey)) {
            Log::error('Stream failed: file not found', [
                'material_id' => $record->id,
            ]);
            abort(404);
        }

        $original = $disk->get($objectKey);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->buffer($original);

        if ($detectedMime !== 'application/pdf') {
            Log::error('Stream blocked: non-PDF file detected', [
                'material_id' => $record->id,
                'detected_mime' => $detectedMime,
            ]);
            abort(415, 'The stored file is not a valid PDF.');
        }

        $temporaryPath = null;

        if ((string) config('demo.material_disk', 'local') === 'local') {
            $watermarkPath = $disk->path($objectKey);
        } else {
            $temporaryPath = tempnam(sys_get_temp_dir(), 'stat_lms_stream_');

            if ($temporaryPath === false || file_put_contents($temporaryPath, $original) === false) {
                Log::error('Stream failed: unable to stage PDF', [
                    'material_id' => $record->id,
                ]);
                abort(503, 'The document could not be prepared for viewing.');
            }

            $watermarkPath = $temporaryPath;
        }

        try {
            $watermarked = $pdfWatermarkService->watermark(
                pdfPath: $watermarkPath,
                user: auth()->user(),
                materialTitle: $record->parent?->title ?? basename($record->file_name),
                accessedAt: now()->setTimezone('Asia/Manila'),
            );
        } catch (\Throwable $e) {
            Log::error('Stream failed: watermarking error', [
                'material_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            return response($original, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.basename($record->file_name).'"',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
                'Pragma' => 'no-cache',
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
                'X-Watermark-Fallback' => 'true',
                'Content-Length' => (string) strlen($original),
            ]);
        } finally {
            if ($temporaryPath !== null) {
                @unlink($temporaryPath);
            }
        }

        return response($watermarked, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($record->file_name).'"',
            // Prevent the browser from caching the authenticated PDF URL
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            // Block embedding in third-party iframes
            'X-Frame-Options' => 'SAMEORIGIN',
            // Tell browsers not to sniff the content type
            'X-Content-Type-Options' => 'nosniff',
            'Content-Length' => (string) strlen($watermarked),
        ]);
    }

    /**
     * Authorization
     */
    protected function authorizeAccess(RrMaterials $record): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403, 'Unauthorized access to secured library material.');
        }

        $level = (int) ($record->parent?->access_level ?? 1);
        $userAccessLevel = $user->role->getAccessLevel();

        if ($userAccessLevel < $level) {
            abort(403, 'Unauthorized access to secured library material.');
        }

        // Admin-tier roles can bypass per-request approvals.
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE])) {
            return;
        }

        // IT bypass is intentionally capped below level 3.
        if ($user->role === UserRole::IT && $level <= 2) {
            return;
        }

        $this->revokeExpiredDigitalAccess($record);

        $hasApproved = MaterialAccessEvents::where('user_id', $user->id)
            ->where('rr_material_id', $record->id)
            ->where('event_type', MaterialEventType::REQUEST->value)
            ->where('status', 'approved')
            ->where(function ($query): void {
                $query->whereNull('due_at')
                    ->orWhere('due_at', '>', now());
            })
            ->exists();

        if (! $hasApproved) {
            abort(403, 'You do not have an approved request for this material.');
        }
    }

    protected function revokeExpiredDigitalAccess(RrMaterials $record): void
    {
        if (! $record->is_digital) {
            return;
        }

        $expired = MaterialAccessEvents::with('user')
            ->where('rr_material_id', $record->id)
            ->where('event_type', MaterialEventType::REQUEST->value)
            ->where('status', 'approved')
            ->whereNotNull('due_at')
            ->where('due_at', '<=', now())
            ->get();

        foreach ($expired as $event) {
            $event->updateQuietly([
                'status' => 'revoked',
                'completed_at' => now(),
                'is_overdue' => false,
            ]);

            $event->user?->notify(new RequestStatusChanged($event));
        }
    }

    protected function materialObjectKey(RrMaterials $record): ?string
    {
        $objectKey = trim(str_replace('\\', '/', (string) $record->file_name), '/');
        $segments = explode('/', $objectKey);

        if (
            $objectKey === ''
            || str_contains($objectKey, "\0")
            || in_array('.', $segments, true)
            || in_array('..', $segments, true)
        ) {
            return null;
        }

        return $objectKey;
    }

    private function demoStaticPdfUrl(RrMaterials $record): string
    {
        $baseUrl = rtrim((string) config('demo.static_asset_url'), '/');
        $parts = parse_url($baseUrl);
        $rawFilename = trim(str_replace('\\', '/', (string) $record->file_name));
        $filename = basename($rawFilename);

        $validOrigin = filter_var($baseUrl, FILTER_VALIDATE_URL)
            && is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && filled($parts['host'] ?? null)
            && blank($parts['user'] ?? null)
            && blank($parts['pass'] ?? null)
            && blank($parts['query'] ?? null)
            && blank($parts['fragment'] ?? null)
            && in_array($parts['path'] ?? '', ['', '/'], true);

        abort_if(! $validOrigin || blank($filename) || in_array($filename, ['.', '..'], true), 404);

        return $baseUrl.'/pdfs/'.rawurlencode($filename);
    }
}
