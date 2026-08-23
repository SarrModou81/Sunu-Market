<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Boost;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Report;
use App\Models\SellerProfile;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'users' => [
                'total' => User::count(),
                'sellers' => SellerProfile::count(),
                'sellers_pro' => SellerProfile::where('seller_type', 'pro')->count(),
                'suspended' => User::where('status', 'suspended')->count(),
                'banned' => User::where('status', 'banned')->count(),
            ],
            'products' => [
                'total' => Product::count(),
                'active' => Product::where('status', Product::STATUS_ACTIVE)->count(),
                'pending_moderation' => Product::whereIn('status', [Product::STATUS_PENDING, Product::STATUS_REPORTED])->count(),
                'blocked' => Product::where('status', Product::STATUS_BLOCKED)->count(),
            ],
            'boosts' => [
                'sold' => Boost::whereHas('payments', fn ($q) => $q->where('status', Payment::STATUS_PAID))->count(),
                'active' => Boost::where('status', Boost::STATUS_ACTIVE)->count(),
            ],
            'subscriptions' => [
                'active_pro' => Subscription::where('status', Subscription::STATUS_ACTIVE)->count(),
            ],
            'revenue' => [
                'total' => (int) Payment::where('status', Payment::STATUS_PAID)->sum('amount'),
                'boosts' => (int) Payment::where('status', Payment::STATUS_PAID)->where('payable_type', 'boost')->sum('amount'),
                'subscriptions' => (int) Payment::where('status', Payment::STATUS_PAID)->where('payable_type', 'subscription')->sum('amount'),
            ],
            'reports' => [
                'pending' => Report::where('status', Report::STATUS_PENDING)->count(),
            ],
        ]]);
    }
}
