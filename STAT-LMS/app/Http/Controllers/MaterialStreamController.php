<?php

namespace App\Http\Controllers;

use App\Models\RrMaterials;
use Illuminate\Support\Facades\Log;
use setasign\Fpdi\Tcpdf\Fpdi; // Use the TCPDF version of FPDI
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

use App\Enums\UserRole;

class MaterialStreamController extends Controller
{
    public function stream(RrMaterials $record)
    {
        $this->authorizeAccess($record);
        $path = storage_path('app/private/' . $record->file_name);

        if (!file_exists($path)) {
            Log::error("Stream failed: File not found at {$path}");
            abort(404);
        }

        // 1. Initialize TCPDF/FPDI
        $pdf = new Fpdi('P', 'mm', 'A4', true, 'UTF-8', false);

        // 2. Set Robust Protection
        // Permissions: ['copy'] is omitted to block text selection.
        // User password: '' (Empty so it opens without a prompt).
        // Owner password: A secure random key.
        // Mode 2: 128-bit AES encryption (much harder for Brave/Chrome to ignore).
        $ownerPassword = bin2hex(random_bytes(32));
        $pdf->SetProtection(['print'], '', $ownerPassword, 2);

        // 3. Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(false);

        $pageCount = $pdf->setSourceFile($path);
        $user = auth()->user();
        $timestamp = now()->format('Y-m-d H:i:s');

        // 4. Process each page
        // Can be modified so only first page is shown, but for now we apply the same protection to all pages.
        $endPage  = min($pageCount, 1);
        for ($pageNo = 1; $pageNo <= $endPage; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);

            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId, 0, 0, $size['width'], $size['height'], true);

            $this->applyScramblerLayer($pdf, $size);

            // Apply watermarks
            $this->applyTextWatermark($pdf, $size, $user, $timestamp);
            $this->applyQrWatermark($pdf, $size, $user, $timestamp);
        }

        // Output to browser
        $pdf->Output(basename($record->file_name), 'I');
        exit;
    }

    protected function applyScramblerLayer($pdf, $size): void
    {
        $pdf->SetFont('helvetica', 'B', 10);

        // Set transparency (0.1 = 10% opacity)
        $pdf->SetAlpha(0.12);
        $pdf->SetTextColor(200, 200, 200);

        $stepX = 6; // Horizontal spacing
        $stepY = 6; // Vertical spacing

        for ($y = 10; $y < $size['height']; $y += $stepY) {
            for ($x = 10; $x < $size['width']; $x += $stepX) {
                $scramble = 'INSTAT-RR-SPRIS-' . Str::random(8);

                $pdf->StartTransform();
                $pdf->Rotate(45, $x, $y);
                $pdf->Text($x, $y, $scramble);
                $pdf->StopTransform();
            }
        }

        $pdf->SetAlpha(1);
    }

    protected function applyTextWatermark($pdf, $size, $user, $timestamp): void
    {
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);

        $text = "{$user->name} | {$user->id} | {$timestamp}";

        // Use TCPDF's Text method for precise overlay
        $pdf->Text(10, $size['height'] - 10, $text);
    }

    protected function applyQrWatermark($pdf, $size, $user, $timestamp): void
    {
        $qrData = "VERIFIED|ID:{$user->id}|TS:{$timestamp}";
        $qrImage = QrCode::format('png')->size(150)->margin(1)->generate($qrData);

        $tempPath = storage_path("app/private/temp_qr_{$user->id}.png");
        file_put_contents($tempPath, $qrImage);

        $pdf->Image($tempPath, $size['width'] - 35, $size['height'] - 35, 25, 25);

        if (file_exists($tempPath)) {
            unlink($tempPath);
        }
    }

    protected function authorizeAccess($record)
    {
        $user = auth()->user();
        $level = (int) $record->parent->access_level;

        $user_access_level = UserRole::from($user->role)->getAccessLevel();

        $allowed = $user_access_level >= $level;

        if (!$allowed) {
            abort(403, 'Unauthorized access to secured library material.');
        }
    }
}