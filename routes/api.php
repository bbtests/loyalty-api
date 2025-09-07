<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;


Route::middleware('throttle:api')->prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json(['message' => 'Welcome to the Bumpa API v1']);
    });
});
