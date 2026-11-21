<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\NodeController;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1'); // Limit to 10 requests per minute
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');


// Authenticated (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (\Illuminate\Http\Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/nodes', [NodeController::class, 'index']);
    Route::post('/nodes', [NodeController::class, 'store']);

});
