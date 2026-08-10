<?php

use App\Http\Controllers\StrategyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('strategies', [StrategyController::class, 'index'])->name('strategies.index');
    Route::get('strategies/create', [StrategyController::class, 'create'])->name('strategies.create');
    Route::post('strategies', [StrategyController::class, 'store'])->name('strategies.store');
    Route::get('strategies/{strategy}/edit', [StrategyController::class, 'edit'])->name('strategies.edit');
    Route::put('strategies/{strategy}', [StrategyController::class, 'update'])->name('strategies.update');
    Route::delete('strategies/{strategy}', [StrategyController::class, 'destroy'])->name('strategies.destroy');
});
