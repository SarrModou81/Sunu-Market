<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
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

Route::get('cities', [CityController::class, 'index']);
Route::get('categories', [CategoryController::class, 'index']);

Route::get('products', [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('my/products', [ProductController::class, 'mine']);
    Route::post('products', [ProductController::class, 'store']);
    Route::post('products/{product}', [ProductController::class, 'update']); // POST + _method=PUT pour l'upload multipart
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
    Route::post('products/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary']);
});

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('categories', [AdminCategoryController::class, 'index']);
    Route::post('categories', [AdminCategoryController::class, 'store']);
    Route::put('categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy']);
    Route::post('categories/{category}/subcategories', [AdminCategoryController::class, 'storeSubcategory']);
});
