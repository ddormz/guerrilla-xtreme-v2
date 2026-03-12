<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Routes for AJAX/WebSocket operations (referee actions, live state).
| Inertia pages use web routes. Only pure JSON APIs go here.
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Referee AJAX endpoints (used by WebSocket-connected Vue components)
    // Route::post('matches/{match}/action', [RefereeApiController::class, 'addAction']);
    // Route::post('matches/{match}/undo', [RefereeApiController::class, 'undoLast']);
    // Route::post('matches/{match}/finalize', [RefereeApiController::class, 'finalize']);
});
