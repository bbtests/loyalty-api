<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['prefix' => 'api/v1', 'middleware' => ['auth:sanctum']]);

// Define private user channel for WebSocket updates
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
