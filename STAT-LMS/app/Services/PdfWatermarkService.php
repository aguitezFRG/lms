<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use InvalidArgumentException;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfWatermarkService
{
    public function watermark(string $pdfPath, User $user, string $materialTitle, Carbon $accessedAt): string
    {
        if (! is_file($pdfPath) || ! is_readable($pdfPath)) {
            throw new InvalidArgumentException("PDF file not found or unreadable: {$pdfPath}");
        }

        $tempBase = tempnam(sys_get_temp_dir(), 'stat_lms_qr_');
        if ($tempBase === false) {
            throw new RuntimeException('Failed to allocate temp file for watermark QR code.');
        }

        $qrPngPath = $tempBase.'.png';
        if (! @rename($tempBase, $qrPngPath)) {
            @unlink($tempBase);
            throw new RuntimeException('Failed to prepare temp QR file path.');
        }

        try {
            $payload = $this->buildQrPayload($user, $accessedAt);
            $this->generateQrCode($payload, $qrPngPath);

            $pdf = new Fpdi;
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->SetMargins(0, 0, 0);
            $pdf->SetCreator('STAT-LMS');
            $pdf->SetAuthor('INSTAT');
            $pdf->SetTitle($materialTitle);

            $pageCount = $pdf->setSourceFile($pdfPath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // $this->drawTiledDiagonalText($pdf, (float) $size['width'], (float) $size['height']);
                $this->drawPrimaryWatermarkText($pdf, (float) $size['width'], (float) $size['height']);
                $this->drawQrAndInfoBlock(
                    $pdf,
                    (float) $size['width'],
                    (float) $size['height'],
                    $this->buildInfoLine($user, $accessedAt),
                    $qrPngPath
                );
            }

            return $pdf->Output('', 'S');
        } finally {
            if (is_file($qrPngPath)) {
                @unlink($qrPngPath);
            }
        }
    }

    private function generateQrCode(string $payload, string $outputPath): void
    {
        $result = (new Builder)->build(
            writer: new PngWriter,
            data: $payload,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Quartile,
            size: 90,
            margin: 6,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255),
        );

        $result->saveToFile($outputPath);
    }

    private function drawTiledDiagonalText(Fpdi $pdf, float $pageWidth, float $pageHeight): void
    {
        $tileText = 'INSTAT-RR-SPRIS';

        $pdf->SetAlpha(0.1);
        $pdf->SetTextColor(130, 130, 130);
        $pdf->SetFont('helvetica', 'B', 14);

        $centerX = $pageWidth / 2.0;
        $centerY = $pageHeight / 2.0;

        $tileWidth = $pdf->GetStringWidth($tileText);
        $stepX = max($tileWidth + 120.0, 160.0);
        $stepY = max($tileWidth + 8.0, 52.0);
        // $stepX = $tileWidth * 1.5;
        // $stepY = $tileWidth * 1.5;
        $coverage = ceil(sqrt(($pageWidth ** 2) + ($pageHeight ** 2))) + $stepX + $stepY + 240.0;

        $pdf->StartTransform();
        $pdf->Rotate(42, $centerX, $centerY);

        $rowIndex = 0;

        for ($y = -$coverage; $y <= $coverage; $y += $stepY, $rowIndex++) {
            $rowOffset = $rowIndex % 2 === 0 ? 0.0 : $stepX / 2.0;

            for ($x = -$coverage - $rowOffset; $x <= $coverage; $x += $stepX) {
                $pdf->Text($centerX + $x, $centerY + $y, $tileText);
            }
        }

        $pdf->StopTransform();
        $pdf->SetAlpha(1);
    }

    private function drawPrimaryWatermarkText(Fpdi $pdf, float $pageWidth, float $pageHeight): void
    {
        $centerX = $pageWidth / 2.0;
        $centerY = $pageHeight / 2.0;

        $pdf->SetAlpha(0.25);
        $pdf->SetTextColor(190, 0, 0);
        $pdf->SetFont('helvetica', 'B', 46);

        $propertyText = 'PROPERTY OF INSTAT';
        $propertyWidth = $pdf->GetStringWidth($propertyText);

        $pdf->StartTransform();
        $pdf->Rotate(35, $centerX, $centerY);
        $pdf->Text($centerX - ($propertyWidth / 2.0), $centerY - 6.0, $propertyText);
        $pdf->StopTransform();

        $pdf->SetFont('helvetica', 'B', 32);
        $reproText = 'NOT TO BE REPRODUCED';
        $reproWidth = $pdf->GetStringWidth($reproText);

        $pdf->StartTransform();
        $pdf->Rotate(35, $centerX, $centerY);
        $pdf->Text($centerX - ($reproWidth / 2.0), $centerY + 10.0, $reproText);
        $pdf->StopTransform();

        $pdf->SetAlpha(1);
    }

    private function drawQrAndInfoBlock(Fpdi $pdf, float $pageWidth, float $pageHeight, string $infoLine, string $qrPath): void
    {
        $qrSize = 12.0;
        $edgeOffset = 0.5;
        $blockHeight = 18.0;
        $blockWidth = max(72.0, min($pageWidth - ($edgeOffset * 2), 112.0));

        $startX = $edgeOffset;
        $startY = $pageHeight - $blockHeight - $edgeOffset;

        $pdf->Image($qrPath, $startX + 2.0, $startY + 2.0, $qrSize, $qrSize, 'PNG');

        $pdf->SetTextColor(40, 40, 40);
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetXY($startX + $qrSize + 2.0, $startY + $qrSize - 2.0);
        $pdf->Cell($blockWidth - $qrSize - 7.0, 4.0, $infoLine, 0, 0, 'L', false, '', 1);
    }

    public function watermarkMetadata(User $user, Carbon $accessedAt): array
    {
        $tempBase = tempnam(sys_get_temp_dir(), 'stat_lms_qr_');
        if ($tempBase === false) {
            return ['qrDataUrl' => null, 'infoLine' => $this->buildInfoLine($user, $accessedAt)];
        }

        $qrPngPath = $tempBase.'.png';
        if (! @rename($tempBase, $qrPngPath)) {
            @unlink($tempBase);

            return ['qrDataUrl' => null, 'infoLine' => $this->buildInfoLine($user, $accessedAt)];
        }

        try {
            $payload = $this->buildQrPayload($user, $accessedAt);
            $this->generateQrCode($payload, $qrPngPath);
            $qrDataUrl = 'data:image/png;base64,'.base64_encode(file_get_contents($qrPngPath));

            return [
                'qrDataUrl' => $qrDataUrl,
                'infoLine' => $this->buildInfoLine($user, $accessedAt),
            ];
        } finally {
            if (is_file($qrPngPath)) {
                @unlink($qrPngPath);
            }
        }
    }

    private function buildQrPayload(User $user, Carbon $accessedAt): string
    {
        return implode('|', [
            'S',
            $this->buildQrUserCode($user),
            $accessedAt->copy()->timezone('Asia/Manila')->format('ymd-H:i'),
        ]);
    }

    private function buildInfoLine(User $user, Carbon $accessedAt): string
    {
        return implode(' · ', [
            $this->stringValue($user->name ?? null),
            $accessedAt->copy()->timezone('Asia/Manila')->format('Y-m-d H:i:s').' PHT',
        ]);
    }

    private function stringValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'N/A';
        }

        return (string) $value;
    }

    private function buildQrUserCode(User $user): string
    {
        $studentNumber = $this->stringValue($user->std_number ?? null);

        if ($studentNumber !== 'N/A') {
            return $studentNumber;
        }

        $baseCode = $this->buildNameCode($user);

        if ($baseCode === 'N/A') {
            return substr($this->stringValue($user->id ?? null), 0, 8);
        }

        $matchingUsers = User::query()
            ->whereNull('std_number')
            ->where('l_name', $user->l_name)
            ->get(['id', 'f_name', 'm_name', 'l_name', 'email', 'std_number'])
            ->filter(fn (User $candidate): bool => $this->buildNameCode($candidate) === $baseCode)
            ->sortBy(fn (User $candidate): string => strtolower(implode('|', [
                $this->stringValue($candidate->f_name ?? null),
                $this->stringValue($candidate->m_name ?? null),
                $this->stringValue($candidate->l_name ?? null),
                $this->stringValue($candidate->email ?? null),
                $this->stringValue($candidate->id ?? null),
            ])))
            ->values();

        if ($matchingUsers->doesntContain(fn (User $candidate): bool => $candidate->is($user))) {
            $matchingUsers->push($user);
            $matchingUsers = $matchingUsers
                ->sortBy(fn (User $candidate): string => strtolower(implode('|', [
                    $this->stringValue($candidate->f_name ?? null),
                    $this->stringValue($candidate->m_name ?? null),
                    $this->stringValue($candidate->l_name ?? null),
                    $this->stringValue($candidate->email ?? null),
                    $this->stringValue($candidate->id ?? null),
                ])))
                ->values();
        }

        if ($matchingUsers->count() <= 1) {
            return $baseCode;
        }

        $index = $matchingUsers->search(fn (User $candidate): bool => $candidate->is($user));

        return $baseCode.(string) (($index === false ? 0 : $index) + 1);
    }

    private function buildNameCode(User $user): string
    {
        $firstInitial = substr($this->cleanNamePart($user->f_name ?? null), 0, 1);
        $middleInitial = substr($this->cleanNamePart($user->m_name ?? null), 0, 1);
        $lastName = $this->cleanNamePart($user->l_name ?? null);

        $code = $firstInitial.$middleInitial.$lastName;

        return $code === '' ? 'N/A' : $code;
    }

    private function cleanNamePart(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return preg_replace('/[^A-Za-z0-9]/', '', (string) $value) ?: '';
    }
}
