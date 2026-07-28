<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RenderNginxConfigurationTest extends TestCase
{
    public function test_module_scripts_are_served_with_a_javascript_mime_type(): void
    {
        $configuration = file_get_contents(
            dirname(__DIR__, 2).'/docker/render/nginx.conf.template',
        );

        $this->assertIsString($configuration);
        $this->assertStringContainsString('location ~* \.mjs$', $configuration);
        $this->assertStringContainsString('default_type application/javascript;', $configuration);
        $this->assertStringContainsString('try_files $uri =404;', $configuration);
    }

    public function test_render_uses_local_temporary_uploads_before_persisting_to_supabase(): void
    {
        $blueprint = file_get_contents(dirname(__DIR__, 3).'/render.yaml');

        $this->assertIsString($blueprint);
        $this->assertMatchesRegularExpression(
            '/key: LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK\s+value: local/',
            $blueprint,
        );
    }

    public function test_render_requires_google_oauth_credentials_at_startup(): void
    {
        $startupScript = file_get_contents(
            dirname(__DIR__, 2).'/docker/render/start.sh',
        );

        $this->assertIsString($startupScript);
        $this->assertStringContainsString('OAUTH_CLIENT_ID', $startupScript);
        $this->assertStringContainsString('OAUTH_CLIENT_SECRET', $startupScript);
    }
}
