<?php

namespace Tests\Feature\Security;

use App\Enums\MaterialEventType;
use App\Models\MaterialAccessEvents;
use App\Models\RrMaterials;
use App\Models\User;
use App\Services\PdfWatermarkService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaterialStreamWatermarkSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function minimalValidPdfContent(): string
    {
        return <<<PDF
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

    private function makeApprovedDigitalMaterialForUser(string $userId): RrMaterials
    {
        $parent = $this->makeMaterialParent([
            'access_level' => 1,
            'title' => 'Watermark Test Material',
        ]);

        $material = $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => 'repo/watermark-test.pdf',
        ]);

        MaterialAccessEvents::create([
            'user_id' => $userId,
            'rr_material_id' => $material->id,
            'event_type' => MaterialEventType::REQUEST->value,
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        return $material;
    }

    private function qrPayloadFor(User $user, string $accessedAt = '2026-05-07 14:32:00'): string
    {
        $method = new \ReflectionMethod(PdfWatermarkService::class, 'buildQrPayload');
        $method->setAccessible(true);

        return $method->invoke(
            app(PdfWatermarkService::class),
            $user,
            Carbon::parse($accessedAt, 'Asia/Manila'),
        );
    }

    /** @test */
    public function authorized_stream_returns_watermarked_pdf_payload_from_service_with_pdf_headers(): void
    {
        $student = $this->makeUser('student');
        $material = $this->makeApprovedDigitalMaterialForUser($student->id);

        $storagePath = storage_path('app/private/repo');
        File::ensureDirectoryExists($storagePath);
        File::put($storagePath.'/watermark-test.pdf', $this->minimalValidPdfContent());

        $watermarked = '%PDF-1.4 WATERMARK_SENTINEL: user=student; material=Watermark Test Material';

        $watermarkService = new class($student, $material->parent?->title, $watermarked) extends PdfWatermarkService {
            public function __construct(
                private readonly User $expectedUser,
                private readonly ?string $expectedTitle,
                private readonly string $payload
            ) {}

            public function watermark(string $pdfPath, User $user, string $materialTitle, Carbon $accessedAt): string
            {
                \PHPUnit\Framework\Assert::assertStringEndsWith('repo/watermark-test.pdf', $pdfPath);
                \PHPUnit\Framework\Assert::assertTrue($user->is($this->expectedUser));
                \PHPUnit\Framework\Assert::assertSame($this->expectedTitle, $materialTitle);
                \PHPUnit\Framework\Assert::assertSame('Asia/Manila', $accessedAt->timezoneName);

                return $this->payload;
            }
        };

        $this->app->instance(PdfWatermarkService::class, $watermarkService);

        $response = $this->actingAs($student)
            ->get(route('materials.stream', ['record' => $material->id]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Content-Disposition', 'inline; filename="watermark-test.pdf"');
        $response->assertHeader('Content-Length', (string) strlen($watermarked));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('must-revalidate', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertSame($watermarked, $response->getContent());
        $this->assertStringContainsString('WATERMARK_SENTINEL', (string) $response->getContent());
    }

    /** @test */
    public function watermark_service_failure_returns_422_without_leaking_original_pdf_content(): void
    {
        $student = $this->makeUser('student');
        $material = $this->makeApprovedDigitalMaterialForUser($student->id);

        $storagePath = storage_path('app/private/repo');
        File::ensureDirectoryExists($storagePath);
        $cleanContent = $this->minimalValidPdfContent();
        File::put($storagePath.'/watermark-test.pdf', $cleanContent);

        $watermarkService = new class extends PdfWatermarkService {
            public function watermark(string $pdfPath, User $user, string $materialTitle, Carbon $accessedAt): string
            {
                throw new \RuntimeException('Watermark failed');
            }
        };

        $this->app->instance(PdfWatermarkService::class, $watermarkService);

        $response = $this->actingAs($student)
            ->get(route('materials.stream', ['record' => $material->id]));

        $response->assertStatus(422);
        $this->assertStringNotContainsString($cleanContent, (string) $response->getContent());
    }

    /** @test */
    public function watermark_service_generates_a_readable_pdf_with_actual_pdf_stack(): void
    {
        $sourcePath = tempnam(sys_get_temp_dir(), 'watermark-source-').'.pdf';

        $sourcePdf = new \TCPDF();
        $sourcePdf->setPrintHeader(false);
        $sourcePdf->setPrintFooter(false);
        $sourcePdf->AddPage();
        $sourcePdf->SetFont('helvetica', '', 12);
        $sourcePdf->Text(20, 20, 'Clean source PDF');
        File::put($sourcePath, $sourcePdf->Output('', 'S'));

        try {
            $user = User::factory()->make([
                'id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Watermark User',
                'email' => 'watermark-user@example.test',
                'role' => 'student',
            ]);

            $watermarked = app(PdfWatermarkService::class)->watermark(
                pdfPath: $sourcePath,
                user: $user,
                materialTitle: 'Watermark Smoke Material',
                accessedAt: Carbon::parse('2026-05-07 14:32:00', 'Asia/Manila'),
            );

            $this->assertStringStartsWith('%PDF-', $watermarked);
            $this->assertGreaterThan(filesize($sourcePath), strlen($watermarked));
        } finally {
            if (is_file($sourcePath)) {
                @unlink($sourcePath);
            }
        }
    }

    /** @test */
    public function qr_payload_uses_student_number_when_present(): void
    {
        $student = $this->makeUser('student', [
            'std_number' => '2026-00001',
        ]);

        $this->assertSame('S|2026-00001|260507-14:32', $this->qrPayloadFor($student));
    }

    /** @test */
    public function qr_payload_uses_name_code_when_student_number_is_missing(): void
    {
        $student = $this->makeUser('faculty', [
            'f_name' => 'Maria',
            'm_name' => null,
            'l_name' => 'Santos',
            'name' => 'Maria Santos',
            'std_number' => null,
        ]);

        $this->assertSame('S|MSantos|260507-14:32', $this->qrPayloadFor($student));
    }

    /** @test */
    public function qr_payload_adds_numeric_suffix_for_duplicate_name_codes(): void
    {
        $first = $this->makeUser('faculty', [
            'f_name' => 'Aaron',
            'm_name' => 'Bryan',
            'l_name' => 'Cruz',
            'name' => 'Aaron Bryan Cruz',
            'email' => 'aaron.cruz@example.test',
            'std_number' => null,
        ]);
        $second = $this->makeUser('faculty', [
            'f_name' => 'Alice',
            'm_name' => 'Bea',
            'l_name' => 'Cruz',
            'name' => 'Alice Bea Cruz',
            'email' => 'alice.cruz@example.test',
            'std_number' => null,
        ]);

        $this->assertSame('S|ABCruz1|260507-14:32', $this->qrPayloadFor($first));
        $this->assertSame('S|ABCruz2|260507-14:32', $this->qrPayloadFor($second));
    }
}
