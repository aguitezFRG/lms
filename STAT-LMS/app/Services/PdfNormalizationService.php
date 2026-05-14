<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class PdfNormalizationService
{
    /**
     * Normalize a PDF to PDF 1.4 using Ghostscript, making it compatible with FPDI.
     * The original file is replaced in-place.
     */
    public function normalize(string $pdfPath): string
    {
        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new InvalidArgumentException("PDF not found or unreadable: {$pdfPath}");
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($pdfPath) !== 'application/pdf') {
            throw new InvalidArgumentException("File is not a valid PDF: {$pdfPath}");
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'stat_lms_norm_').'.pdf';

        $command = sprintf(
            'gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellarg($tempPath),
            escapeshellarg($pdfPath)
        );

        exec($command, $output, $exitCode);

        if ($exitCode !== 0 || ! is_file($tempPath) || filesize($tempPath) === 0) {
            @unlink($tempPath);
            throw new RuntimeException('PDF normalization failed: '.implode("\n", array_slice($output, 0, 10)));
        }

        if (! rename($tempPath, $pdfPath)) {
            if (! copy($tempPath, $pdfPath)) {
                @unlink($tempPath);
                throw new RuntimeException('Failed to replace original PDF with normalized version.');
            }
            @unlink($tempPath);
        }

        return $pdfPath;
    }
}
