<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function super_admin_can_load_admin_dashboard_page(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    #[Test]
    public function super_admin_can_load_system_usage_page(): void
    {
        $admin = $this->makeUser('super_admin');

        $this->actingAs($admin)
            ->get('/admin/system-usage')
            ->assertOk();
    }
}
