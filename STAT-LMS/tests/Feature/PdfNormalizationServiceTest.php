<?php

namespace Tests\Feature;

use App\Services\PdfNormalizationService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PdfNormalizationServiceTest extends TestCase
{
    private function minimalValidPdfContent(): string
    {
        return <<<'PDF'
%PDF-1.4
1 0 obj
<< /Type /Catalog /Pages 2 0 R >>
endobj
2 0 obj
<< /Type /Pages /Kids [3 0 R] /Count 1 >>
endobj
3 0 obj
<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R >>
endobj
4 0 obj
<< /Length 44 >>
stream
BT
/F1 12 Tf
72 120 Td
(Hello PDF) Tj
ET
endstream
endobj
xref
0 5
0000000000 65535 f
0000000010 00000 n
0000000063 00000 n
0000000120 00000 n
0000000207 00000 n
trailer
<< /Root 1 0 R /Size 5 >>
startxref
301
%%EOF
PDF;
    }

    /** @test */
    public function it_normalizes_a_valid_pdf_to_pdf_1_4(): void
    {
        $service = new PdfNormalizationService;
        $sourcePath = tempnam(sys_get_temp_dir(), 'norm-test-').'.pdf';
        File::put($sourcePath, $this->minimalValidPdfContent());

        try {
            $resultPath = $service->normalize($sourcePath);

            $this->assertSame($sourcePath, $resultPath);
            $this->assertFileExists($sourcePath);
            $this->assertStringStartsWith('%PDF-1.4', File::get($sourcePath));
        } finally {
            if (is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }
    }

    /** @test */
    public function it_rejects_a_non_pdf_file(): void
    {
        $service = new PdfNormalizationService;
        $sourcePath = tempnam(sys_get_temp_dir(), 'norm-test-').'.txt';
        File::put($sourcePath, 'This is not a PDF.');

        try {
            $this->expectException(\InvalidArgumentException::class);
            $service->normalize($sourcePath);
        } finally {
            if (is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }
    }

    /** @test */
    public function it_rejects_a_missing_file(): void
    {
        $service = new PdfNormalizationService;

        $this->expectException(\InvalidArgumentException::class);
        $service->normalize('/nonexistent/path/file.pdf');
    }
}
