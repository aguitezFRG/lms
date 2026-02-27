<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MaterialStreamController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/materials/stream/{record}', [MaterialStreamController::class, 'stream'])
        ->name('materials.stream');
});
