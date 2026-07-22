<?php

namespace App\Filament\Components;

use Illuminate\Support\Facades\Blade;

abstract class FeatureCards
{
    protected static function renderHtml(string $html): string
    {
        if (config('demo.enabled')) {
            $prefix = rtrim((string) config('demo.internal_prefix', '/__php'), '/');
            $html = preg_replace(
                '/href="\/(?!__php(?:\/|"))/',
                'href="'.$prefix.'/',
                $html,
            ) ?? $html;
        }

        return Blade::render($html);
    }
}
