<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BrowserDemoBuildScriptTest extends TestCase
{
    #[Test]
    public function rounded_png_favicon_is_copied_to_the_static_deployment_root(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/build-browser-demo.sh');

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'cp public/lms_favicon.png "$client_root/public/lms_favicon.png"',
            $script,
        );
        $this->assertStringContainsString(
            'rm -f -- "$client_root/public/lms_favicon.png"',
            $script,
        );
    }
}
