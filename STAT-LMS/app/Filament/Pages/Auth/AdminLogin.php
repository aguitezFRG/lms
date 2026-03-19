<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Actions\Action;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class AdminLogin extends Login
{
    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->color('success');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            'Student or faculty? <a href="/app/login" class="text-primary-600 hover:underline font-medium">Sign in here</a>'
        );
    }
}