<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user-login', function () {
    return true;
});
