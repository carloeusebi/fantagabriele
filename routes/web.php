<?php

use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('players', [PlayerController::class, 'index'])->name('players.index');

    Route::post('players/import', [PlayerController::class, 'import'])
        ->middleware('admin')
        ->name('players.import');
});

require __DIR__.'/settings.php';
require __DIR__.'/auctions.php';
