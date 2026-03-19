<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaterialStreamController;

use App\Enums\UserRole;

Route::get('/', function () {
    if(auth()->check()) {
        $role = auth()->user()->role;
        return in_array($role, [UserRole::COMMITTEE, UserRole::IT, UserRole::RR])
            ? redirect('/admin')
            : redirect('/app');
    }
    return redirect('/app/login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/materials/stream/{record}', [MaterialStreamController::class, 'stream'])
        ->name('materials.stream');
});
