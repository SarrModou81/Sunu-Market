<?php

use App\Http\Controllers\Api\Admin\AdminLogController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Api\Admin\StatsController;
use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OtpController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\BoostController;
use App\Http\Controllers\Api\BoostPlanController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CityController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\WebhookController;
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
    Route::post('profile', [ProfileController::class, 'update']); // POST + _method=PUT pour l'upload multipart (avatar)
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
    Route::post('products/{product}/reveal-phone', [ProductController::class, 'revealPhone'])
        ->middleware('throttle:30,1');
});

Route::get('sellers/{seller}/reviews', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('favorites', [FavoriteController::class, 'index']);
    Route::post('favorites', [FavoriteController::class, 'store']);
    Route::delete('favorites/{product}', [FavoriteController::class, 'destroy']);

    Route::get('conversations', [ConversationController::class, 'index']);
    Route::post('conversations', [ConversationController::class, 'store']);
    Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
    Route::post('conversations/{conversation}/block', [ConversationController::class, 'toggleBlock']);

    Route::post('sellers/{seller}/reviews', [ReviewController::class, 'store']);

    Route::post('reports', [ReportController::class, 'store']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
});

Route::get('boost-plans', [BoostPlanController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('products/{product}/boost', [BoostController::class, 'store'])
        ->middleware('throttle:10,1');
    Route::post('subscriptions/pro', [SubscriptionController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('payments/{payment}', [PaymentController::class, 'show']);
});

// Callback de simulation (développement/tests uniquement, voir PaymentController::fakeComplete).
Route::get('payments/{payment}/fake-complete', [PaymentController::class, 'fakeComplete'])
    ->name('payments.fake-complete');

// Webhooks fournisseurs de paiement : non authentifiés (Sanctum), l'authenticité est
// garantie par la vérification de signature effectuée dans WebhookController.
Route::middleware('throttle:120,1')->group(function () {
    Route::post('webhooks/wave', [WebhookController::class, 'wave'])->name('webhooks.wave');
    Route::post('webhooks/orange-money', [WebhookController::class, 'orangeMoney'])->name('webhooks.orange-money');
});

Route::middleware(['auth:sanctum', 'admin:moderator'])->prefix('admin')->group(function () {
    Route::get('stats', [StatsController::class, 'index']);

    Route::get('categories', [AdminCategoryController::class, 'index']);
    Route::post('categories', [AdminCategoryController::class, 'store']);
    Route::put('categories/{category}', [AdminCategoryController::class, 'update']);
    Route::delete('categories/{category}', [AdminCategoryController::class, 'destroy']);
    Route::post('categories/{category}/subcategories', [AdminCategoryController::class, 'storeSubcategory']);

    Route::get('users', [AdminUserController::class, 'index']);
    Route::get('users/{user}', [AdminUserController::class, 'show']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole']);
    Route::patch('users/{user}/seller-verification', [AdminUserController::class, 'updateSellerVerification']);

    Route::get('products', [AdminProductController::class, 'index']);
    Route::post('products/{product}/approve', [AdminProductController::class, 'approve']);
    Route::post('products/{product}/refuse', [AdminProductController::class, 'refuse']);
    Route::post('products/{product}/block', [AdminProductController::class, 'block']);

    Route::get('reports', [AdminReportController::class, 'index']);
    Route::post('reports/{report}/resolve', [AdminReportController::class, 'resolve']);

    Route::get('payments', [AdminPaymentController::class, 'index']);
    Route::get('subscriptions', [AdminSubscriptionController::class, 'index']);

    Route::get('logs', [AdminLogController::class, 'index']);
});
