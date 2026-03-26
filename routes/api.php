<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Reserved for future external/mobile API consumers requiring token-based
| auth (Sanctum). Internal dashboard report endpoints live in web.php
| under the session-authenticated api/v1 prefix.
|
*/
Route::prefix('v2')->group(function () {
    Route::get('/reports/daily-sales', function () {
        return response()->json([
            'version' => 'v2',
            'message' => 'This is version 2'
        ]);
    });
});