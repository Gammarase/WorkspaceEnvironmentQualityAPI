<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SensorReadingController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::prefix('auths')->controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
});

// Protected Auth Routes
Route::prefix('auths')->controller(AuthController::class)->middleware('auth:sanctum')->group(function () {
    Route::delete('logout', 'logout');
    Route::get('get-user', 'getUser');
    Route::patch('update-user', 'updateUser');
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Devices
    Route::resource('devices', DeviceController::class)->except(['create', 'edit']);

    // Sensor readings
    Route::prefix('sensor-readings')->controller(SensorReadingController::class)->group(function () {
        Route::get('current', 'current');
        Route::get('history', 'history');
    });
    Route::resource('sensor-readings', SensorReadingController::class)->only(['store']);

    // Recommendations
    Route::prefix('recommendations')->controller(RecommendationController::class)->group(function () {
        Route::patch('acknowledge', 'acknowledge');
        Route::patch('dismiss', 'dismiss');
        Route::get('pending', 'pending');
    });
    Route::resource('recommendations', RecommendationController::class)->only(['index', 'show']);
});
