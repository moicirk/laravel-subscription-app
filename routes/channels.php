<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user-login', function () {
    return true;
});

// Presence channel for online users
Broadcast::channel('users.online', function ($user) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    return false;
});
