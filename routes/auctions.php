<?php

use App\Http\Controllers\Auction\AuctionAdvisorController;
use App\Http\Controllers\Auction\AuctionAssignmentController;
use App\Http\Controllers\Auction\AuctionCallController;
use App\Http\Controllers\Auction\AuctionController;
use App\Http\Controllers\Auction\AuctionParticipantController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('auctions', [AuctionController::class, 'index'])->name('auctions.index');
    Route::get('auctions/create', [AuctionController::class, 'create'])->name('auctions.create');
    Route::post('auctions', [AuctionController::class, 'store'])->name('auctions.store');
    Route::get('auctions/{auction}', [AuctionController::class, 'show'])->name('auctions.show');
    Route::patch('auctions/{auction}/status', [AuctionController::class, 'updateStatus'])->name('auctions.status.update');
    Route::delete('auctions/{auction}', [AuctionController::class, 'destroy'])->name('auctions.destroy');
    Route::post('auctions/{auction}/join', [AuctionController::class, 'join'])->name('auctions.join');
    Route::post('auctions/{auction}/unlock', [AuctionController::class, 'unlock'])->name('auctions.unlock');

    Route::post('auctions/{auction}/participants', [AuctionParticipantController::class, 'store'])->name('auctions.participants.store');
    Route::patch('auctions/{auction}/participants/{participant}', [AuctionParticipantController::class, 'update'])->name('auctions.participants.update');
    Route::delete('auctions/{auction}/participants/{participant}', [AuctionParticipantController::class, 'destroy'])->name('auctions.participants.destroy');
    Route::put('auctions/{auction}/participants-order', [AuctionParticipantController::class, 'reorder'])->name('auctions.participants.reorder');

    Route::post('auctions/{auction}/call', [AuctionCallController::class, 'store'])->name('auctions.call.store');
    Route::delete('auctions/{auction}/call', [AuctionCallController::class, 'destroy'])->name('auctions.call.destroy');

    Route::post('auctions/{auction}/assignments', [AuctionAssignmentController::class, 'store'])->name('auctions.assignments.store');
    Route::patch('auctions/{auction}/assignments/{assignment}', [AuctionAssignmentController::class, 'update'])->name('auctions.assignments.update');
    Route::delete('auctions/{auction}/assignments/{assignment}', [AuctionAssignmentController::class, 'destroy'])->name('auctions.assignments.destroy');

    Route::get('auctions/{auction}/advisor/call', [AuctionAdvisorController::class, 'suggestCall'])->name('auctions.advisor.call');
    Route::get('auctions/{auction}/advisor/bid', [AuctionAdvisorController::class, 'suggestMaxBid'])->name('auctions.advisor.bid');
});
