<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Private channels require authentication. The closure receives the
| authenticated user and must return true/false or an array of data.
|
*/

// Default user channel (standard Laravel)
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// ─── Real-Time Notification Channel ─────────────────────────
// Each user subscribes to their own private channel.
// Authorization: user can only listen to their own notifications.
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
