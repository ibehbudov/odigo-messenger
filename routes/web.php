<?php

use App\Http\Controllers\OdigoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OdigoController::class, 'index']);

// One route per panel window (rendered as a native window on desktop,
// or inside a draggable iframe on the web shell).
Route::get('/w/{name}', [OdigoController::class, 'panel']);

Route::prefix('odigo')->group(function () {
    Route::get('filters', [OdigoController::class, 'filters']);
    Route::get('people', [OdigoController::class, 'people']);
    Route::get('person/{handle}', [OdigoController::class, 'person']);
    Route::get('stats', [OdigoController::class, 'stats']);
    Route::get('messages/{handle}', [OdigoController::class, 'history']);
    Route::post('messages', [OdigoController::class, 'send']);
    Route::post('friends', [OdigoController::class, 'addFriend']);
    // desktop window control
    Route::post('win/open', [OdigoController::class, 'winOpen']);
    Route::post('win/close', [OdigoController::class, 'winClose']);
});
