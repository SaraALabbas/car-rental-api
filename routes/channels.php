<?php

use Illuminate\Support\Facades\Broadcast;
use Laravel\Sanctum\PersonalAccessToken;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::routes([
    'middleware' => ['auth:sanctum'],
]);