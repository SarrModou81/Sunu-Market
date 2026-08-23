<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('otp/request', [OtpController::class, 'request'])
        ->middleware('throttle:6,1');
    Route::post('otp/login', [AuthController::class, 'loginWithOtp'])
        ->middleware('throttle:10,1');
    Route::post('register', [AuthController::class, 'register'])
        ->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');
    Route::post('password/reset', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/phone/change', [ProfileController::class, 'changePhone'])
        ->middleware('throttle:10,1');
    Route::delete('account', [ProfileController::class, 'destroy']);
});
