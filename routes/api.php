<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SensorReadingController;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('auths')->controller(AuthController::class)->group(function () {
    Route::get('register', 'register');
    Route::get('login', 'login');
    Route::get('logout', 'logout');
    Route::get('get-user', 'getUser');
    Route::get('update-user', 'updateUser');
});

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
    Route::get('acknowledge', 'acknowledge');
    Route::get('dismiss', 'dismiss');
    Route::get('pending', 'pending');
});
Route::resource('recommendations', RecommendationController::class)->only(['index', 'show']);
