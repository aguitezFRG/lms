<?php

namespace App\Filament\Components;

use Illuminate\Support\Facades\Blade;

abstract class FeatureCards
{
    /**
     * @param  array<string, string>  $demoReplacements
     */
    protected static function renderHtml(string $html, array $demoReplacements = []): string
    {
        if (config('demo.enabled')) {
            $html = str_replace(
                array_keys($demoReplacements),
                array_values($demoReplacements),
                $html,
            );
        }

        return Blade::render($html);
    }
}
