<?php

use App\Models\Auction;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('auction.{auction}', function (User $user, Auction $auction) {
    return true;
});

Broadcast::channel('auction-presence.{auction}', function (User $user, Auction $auction) {
    return ['id' => $user->id, 'name' => $user->name];
});
