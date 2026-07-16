<?php

use App\Http\Controllers\OdigoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OdigoController::class, 'index']);

Route::prefix('odigo')->group(function () {
    Route::get('filters', [OdigoController::class, 'filters']);
    Route::get('people', [OdigoController::class, 'people']);
    Route::get('person/{handle}', [OdigoController::class, 'person']);
    Route::get('stats', [OdigoController::class, 'stats']);
    Route::get('messages/{handle}', [OdigoController::class, 'history']);
    Route::post('messages', [OdigoController::class, 'send']);
    Route::post('friends', [OdigoController::class, 'addFriend']);
});
