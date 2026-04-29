<?php

namespace App\Http\Controllers;

use App\Enums\MaterialEventType;
use App\Enums\UserRole;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterials;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MaterialStreamController extends Controller
{
    /**
     * Render the secure PDF viewer page.
     * This is the URL users are sent to from the Filament action.
     */
    public function viewer(RrMaterials $record)
    {
        $this->authorizeAccess($record);

        $path = $this->resolveMaterialPath($record);

        if ($path === null) {
            abort(404);
        }

        if (! file_exists($path)) {
            abort(404);
        }

        return view('filament.pdf.viewer', [
            'record' => $record,
            'streamUrl' => route('materials.stream', ['record' => $record->id]),
            'user' => auth()->user(),
            'title' => $record->parent?->title ?? basename($record->file_name),
        ]);
    }

    /**
     * Stream the raw PDF bytes.
     * Called only by the viewer Blade — not exposed as a direct download link.
     */
    public function stream(RrMaterials $record)
    {
        $this->authorizeAccess($record);

        $path = $this->resolveMaterialPath($record);

        if ($path === null) {
            Log::warning('Stream blocked: invalid material file path', [
                'material_id' => $record->id,
            ]);
            abort(404);
        }

        if (! file_exists($path)) {
            Log::error('Stream failed: file not found', [
                'material_id' => $record->id,
            ]);
            abort(404);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($path);

        if ($detectedMime !== 'application/pdf') {
            Log::error('Stream blocked: non-PDF file detected', [
                'material_id' => $record->id,
                'detected_mime' => $detectedMime,
            ]);
            abort(415, 'The stored file is not a valid PDF.');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($record->file_name).'"',
            // Prevent the browser from caching the authenticated PDF URL
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            // Block embedding in third-party iframes
            'X-Frame-Options' => 'SAMEORIGIN',
            // Tell browsers not to sniff the content type
            'X-Content-Type-Options' => 'nosniff',
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

        // Committee bypass approval requirement
        if (in_array($user->role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE])) {
            return;
        }

        // IT bypasses approval requirement only for level 1 and 2 materials
        if ($user->role === UserRole::IT && $level <= 2) {
            return;
        }

        $hasApproved = MaterialAccessEvents::where('user_id', $user->id)
            ->where('rr_material_id', $record->id)
            ->where('event_type', MaterialEventType::REQUEST->value)
            ->where('status', 'approved')
            ->exists();

        if (! $hasApproved) {
            abort(403, 'You do not have an approved request for this material.');
        }
    }

    protected function resolveMaterialPath(RrMaterials $record): ?string
    {
        $baseDirectory = storage_path('app/private');
        $baseRealPath = realpath($baseDirectory);

        if ($baseRealPath === false) {
            return null;
        }

        $relativePath = ltrim((string) $record->file_name, '/\\');
        $candidatePath = $baseDirectory.DIRECTORY_SEPARATOR.$relativePath;
        $parentRealPath = realpath(dirname($candidatePath));

        if ($parentRealPath === false) {
            return null;
        }

        if (! Str::startsWith($parentRealPath, $baseRealPath)) {
            return null;
        }

        return $parentRealPath.DIRECTORY_SEPARATOR.basename($candidatePath);
    }
}
