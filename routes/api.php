<?php

use App\Http\Controllers\Api\V1\MatchController;
use Illuminate\Support\Facades\Route;

/*
| Read-only output API, served under /api/v1. Every route except /ping needs the bearer token
| of an active api_clients row (create one with `php artisan api-client:create`).
*/

Route::name('api.v1.')->middleware('throttle:api-client')->group(function (): void {
    Route::get('/ping', fn () => response()->json(['status' => 'ok']))->name('ping');

    Route::middleware('api.client')->group(function (): void {
        Route::get('/matches', MatchController::class)->name('matches.index');
    });
});
