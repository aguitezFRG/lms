<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FaviconAssetsTest extends TestCase
{
    #[Test]
    public function svg_embeds_the_rounded_png_without_reencoding_it(): void
    {
        $publicPath = dirname(__DIR__, 2).'/public';
        $png = file_get_contents($publicPath.'/lms_favicon.png');
        $svg = file_get_contents($publicPath.'/favicon.svg');

        $this->assertIsString($png);
        $this->assertIsString($svg);
        $this->assertSame(1, preg_match('/href="data:image\/png;base64,([^"]+)"/', $svg, $matches));
        $this->assertSame($png, base64_decode($matches[1], true));
    }

    #[Test]
    public function ico_contains_the_expected_browser_icon_sizes(): void
    {
        $ico = file_get_contents(dirname(__DIR__, 2).'/public/favicon.ico');

        $this->assertIsString($ico);
        $header = unpack('vreserved/vtype/vcount', substr($ico, 0, 6));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 6], $header);

        $sizes = [];

        for ($index = 0; $index < $header['count']; $index++) {
            $width = ord($ico[6 + ($index * 16)]);
            $sizes[] = $width === 0 ? 256 : $width;
        }

        $this->assertSame([256, 128, 64, 48, 32, 16], $sizes);

        $largestEntry = unpack('Vbytes/Voffset', substr($ico, 14, 8));
        $largestImage = imagecreatefromstring(substr($ico, $largestEntry['offset'], $largestEntry['bytes']));
        $this->assertNotFalse($largestImage);
        $this->assertSame(256, imagesx($largestImage));
        $this->assertSame(256, imagesy($largestImage));

        $corner = imagecolorsforindex($largestImage, imagecolorat($largestImage, 0, 0));
        $this->assertSame(127, $corner['alpha']);
        imagedestroy($largestImage);
    }
}
