<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PatientBookingApiController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'me']);

    Route::prefix('patient/booking')->group(function () {
        Route::get('/services', [PatientBookingApiController::class, 'services']);
        Route::get('/teams', [PatientBookingApiController::class, 'teams']);
        Route::get('/days', [PatientBookingApiController::class, 'days']);
        Route::get('/slots', [PatientBookingApiController::class, 'slots']);
        Route::post('/', [PatientBookingApiController::class, 'store']);
    });
});
