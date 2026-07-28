<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class BrowserDemoBuildScriptTest extends TestCase
{
    #[Test]
    public function lossless_favicon_is_copied_to_the_static_deployment_root(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/build-browser-demo.sh');

        $this->assertIsString($script);
        $this->assertStringContainsString(
            'cp public/favicon.svg "$client_root/public/favicon.svg"',
            $script,
        );
        $this->assertStringContainsString(
            'rm -f -- "$client_root/public/favicon.svg"',
            $script,
        );
    }
}
