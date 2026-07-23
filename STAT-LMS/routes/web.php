<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DemoProfileController;
use App\Http\Controllers\MaterialStreamController;
use App\Http\Controllers\PasswordEncryptionKeyController;
use App\Http\Controllers\RoleViewModeController;
use App\Http\Controllers\SharedDemoHealthController;
use App\Http\Controllers\SharedDemoResetController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role;

        return in_array($role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR])
            ? redirect('/admin')
            : redirect('/app');
    }

    return config('demo.enabled')
        ? redirect()->route('demo.profiles.index')
        : redirect('/app/login');
});

Route::get('/demo/profiles', [DemoProfileController::class, 'index'])->name('demo.profiles.index');
Route::post('/demo/profiles', [DemoProfileController::class, 'select'])
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('demo.profiles.select');

// Public key for client-side password encryption — no auth required, no sensitive data
if (! config('demo.enabled')) {
    Route::get('/password-encryption-key', PasswordEncryptionKeyController::class)
        ->middleware('throttle:60,1')
        ->name('password.encryption-key');

    // Google OAuth routes — no auth middleware required for initial redirect/callback
    Route::middleware(['throttle:google-sso'])->group(function () {
        Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
        Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
    });
}

Route::middleware(['auth', 'throttle:material-stream'])->group(function () {
    Route::get('/materials/{record}/viewer', [MaterialStreamController::class, 'viewer'])
        ->name('materials.viewer');

    Route::get('/materials/{record}/stream', [MaterialStreamController::class, 'stream'])
        ->name('materials.stream');
});

Route::post('/admin/role-view-mode', RoleViewModeController::class)
    ->middleware(['auth'])
    ->name('role-view-mode.update');

Route::get('/health/ready', SharedDemoHealthController::class)
    ->middleware('throttle:10,1')
    ->name('shared-demo.health');

Route::post('/internal/shared-demo/reset', SharedDemoResetController::class)
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->middleware('throttle:2,1')
    ->name('shared-demo.reset');
