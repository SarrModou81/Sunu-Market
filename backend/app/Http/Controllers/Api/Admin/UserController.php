<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSellerVerificationRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\Admin\UserListResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private readonly AdminLogService $adminLog) {}

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['profile', 'sellerProfile'])
            ->withCount('products')
            ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where('phone', 'like', $term)
                    ->orWhereHas('profile', fn ($p) => $p->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term));
            })
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->integer('per_page', 20), 100));

        return response()->json([
            'data' => UserListResource::collection($users->items()),
            'meta' => [
                'current_page' => $users->currentPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json(['data' => new UserResource($user->load(['profile.city', 'sellerProfile']))]);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        $user->forceFill(['status' => $request->string('status')])->save();

        if ($request->string('status') !== 'active') {
            $user->tokens()->delete();
        }

        $this->adminLog->log(
            $request->user(),
            'user.status.update',
            $user,
            "Statut changé en {$request->string('status')}".($request->filled('reason') ? " ({$request->input('reason')})" : ''),
            $request->ip(),
        );

        return response()->json(['data' => new UserResource($user->fresh(['profile', 'sellerProfile']))]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->forceFill(['role' => $request->string('role')])->save();

        $this->adminLog->log($request->user(), 'user.role.update', $user, "Rôle changé en {$request->string('role')}", $request->ip());

        return response()->json(['data' => new UserResource($user->fresh(['profile', 'sellerProfile']))]);
    }

    public function updateSellerVerification(UpdateSellerVerificationRequest $request, User $user): JsonResponse
    {
        $user->sellerProfile()->update(['badge_verified' => $request->boolean('badge_verified')]);

        $this->adminLog->log(
            $request->user(),
            'seller.badge.update',
            $user,
            $request->boolean('badge_verified') ? 'Badge vérifié accordé' : 'Badge vérifié retiré',
            $request->ip(),
        );

        return response()->json(['data' => new UserResource($user->fresh(['profile', 'sellerProfile']))]);
    }
}
