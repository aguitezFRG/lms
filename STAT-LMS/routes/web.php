<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\MaterialStreamController;
use App\Http\Controllers\PasswordEncryptionKeyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $role = auth()->user()->role;

        return in_array($role, [UserRole::SUPER_ADMIN, UserRole::COMMITTEE, UserRole::IT, UserRole::RR])
            ? redirect('/admin')
            : redirect('/app');
    }

    return redirect('/app/login');
});

// Public key for client-side password encryption — no auth required, no sensitive data
Route::get('/password-encryption-key', PasswordEncryptionKeyController::class)
    ->middleware('throttle:60,1')
    ->name('password.encryption-key');

// Google OAuth routes — no auth middleware required for initial redirect/callback
Route::middleware(['throttle:google-sso'])->group(function () {
    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('auth.google.callback');
});

Route::middleware(['auth', 'throttle:material-stream'])->group(function () {
    Route::get('/materials/{record}/viewer', [MaterialStreamController::class, 'viewer'])
        ->name('materials.viewer');

    Route::get('/materials/{record}/stream', [MaterialStreamController::class, 'stream'])
        ->name('materials.stream');
});
