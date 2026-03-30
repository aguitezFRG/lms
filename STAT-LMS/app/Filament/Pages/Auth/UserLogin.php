<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Actions\Action;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class UserLogin extends Login
{
    protected function getAuthenticateFormAction(): Action
    {
        return parent::getAuthenticateFormAction()
            ->color('success');
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'User Sign In';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString(
            'Staff or committee member? <a href="/admin/login" class="text-primary-600 hover:underline font-medium">Sign in here</a>'
        );
    }
}