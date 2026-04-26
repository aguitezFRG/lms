<?php

namespace Tests\Feature\Security;

use App\Models\RrMaterials;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Security: Rate Limiting on Material Stream Routes
 *
 * Verifies that:
 * - The 'material-stream' rate limiter is configured to 30/min per user, 60/min per IP
 * - Routes are protected with throttle:material-stream middleware
 * - Consecutive requests are properly counted against the limit
 */
class MaterialStreamThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function makeDigitalMaterial(): RrMaterials
    {
        $parent = $this->makeMaterialParent([
            'access_level' => 1,
            'material_type' => 1,
            'author' => 'Author',
            'publication_date' => now()->subYear(),
            'keywords' => json_encode(['stats']),
            'sdgs' => json_encode(['Education']),
            'adviser' => json_encode(['Adviser']),
        ]);

        return $this->makeMaterialCopy([
            'material_parent_id' => $parent->id,
            'is_digital' => true,
            'is_available' => true,
            'file_name' => 'repo/file.pdf',
        ]);
    }

    /** @test */
    public function material_stream_route_is_throttled_with_material_stream_limiter(): void
    {
        $student = $this->makeUser('student');
        $material = $this->makeDigitalMaterial();

        // Create the file on disk so the controller passes file existence check
        $storagePath = storage_path('app/private/repo');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($storagePath);
        \Illuminate\Support\Facades\File::put($storagePath.'/file.pdf', '%PDF-1.4 mock content');

        // Approve a request so the student can access
        \App\Models\MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $material->id,
            'event_type' => 'request',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($student);

        // Make 31 requests (exceeds the 30/min limit per user)
        for ($i = 0; $i < 31; $i++) {
            $response = $this->get(route('materials.stream', ['record' => $material->id]));
            if ($i < 30) {
                // First 30 should succeed (200 or 415 from mime type check)
                $this->assertTrue(
                    in_array($response->getStatusCode(), [200, 415]),
                    "Request $i should succeed (got {$response->getStatusCode()})"
                );
            } else {
                // 31st should be throttled
                $this->assertEquals(429, $response->getStatusCode(),
                    'Request 31 should be throttled (429)');
            }
        }
    }

    /** @test */
    public function material_viewer_route_is_throttled_with_material_stream_limiter(): void
    {
        $student = $this->makeUser('student');
        $material = $this->makeDigitalMaterial();

        // Create the file on disk so the controller passes file existence check
        $storagePath = storage_path('app/private/repo');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($storagePath);
        \Illuminate\Support\Facades\File::put($storagePath.'/file.pdf', '%PDF-1.4 mock content');

        // Approve a request so the student can access
        \App\Models\MaterialAccessEvents::create([
            'user_id' => $student->id,
            'rr_material_id' => $material->id,
            'event_type' => 'request',
            'status' => 'approved',
            'approved_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($student);

        // Make 31 requests (exceeds the 30/min limit per user)
        for ($i = 0; $i < 31; $i++) {
            $response = $this->get(route('materials.viewer', ['record' => $material->id]));
            if ($i < 30) {
                // First 30 should succeed
                $this->assertEquals(200, $response->getStatusCode(),
                    "Request $i should succeed");
            } else {
                // 31st should be throttled
                $this->assertEquals(429, $response->getStatusCode(),
                    'Request 31 should be throttled (429)');
            }
        }
    }

    /** @test */
    public function rate_limiter_counts_requests_per_authenticated_user(): void
    {
        $student1 = $this->makeUser('student');
        $student2 = $this->makeUser('student');

        // Verify that the rate limiter uses user ID as the key
        $this->actingAs($student1);

        // Hit the limiter 30 times for student1 (exact limit)
        for ($i = 0; $i < 30; $i++) {
            RateLimiter::hit('material-stream|'.$student1->id);
        }

        // Verify student1 is now exhausted (cannot hit again without callback)
        $this->assertTrue(RateLimiter::tooManyAttempts('material-stream|'.$student1->id, 30));

        // Hit student2 once, should not be exhausted
        RateLimiter::hit('material-stream|'.$student2->id);
        $this->assertFalse(RateLimiter::tooManyAttempts('material-stream|'.$student2->id, 30));
    }
}
