<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\FirebaseAuthException;
use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\FirebaseAuthRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register([
                ...$request->validated(),
                'phone' => PhoneNumber::normalize($request->string('phone')),
            ]);
        } catch (OtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $token = $this->authService->issueToken($user);

        return response()->json([
            'user' => new UserResource($user->load(['profile', 'sellerProfile'])),
            'token' => $token->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->string('phone'));
        $user = $this->authService->attemptLogin($phone, $request->string('password'));

        if (! $user) {
            return response()->json(['message' => 'Identifiants invalides.'], 401);
        }

        $token = $this->authService->issueToken($user);

        return response()->json([
            'user' => new UserResource($user->load(['profile', 'sellerProfile'])),
            'token' => $token->plainTextToken,
        ]);
    }

    public function loginWithOtp(VerifyOtpRequest $request): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->string('phone'));

        try {
            $user = $this->authService->loginWithOtp($phone, $request->string('code'));
        } catch (OtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $user) {
            return response()->json(['message' => 'Compte introuvable ou suspendu.'], 401);
        }

        $token = $this->authService->issueToken($user);

        return response()->json([
            'user' => new UserResource($user->load(['profile', 'sellerProfile'])),
            'token' => $token->plainTextToken,
        ]);
    }

    public function firebase(FirebaseAuthRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginOrRegisterWithFirebase($request->string('id_token'), [
                'first_name' => $request->string('first_name')->value() ?: null,
                'last_name' => $request->string('last_name')->value() ?: null,
                'email' => $request->string('email')->value() ?: null,
                'city_id' => $request->integer('city_id') ?: null,
            ]);
        } catch (FirebaseAuthException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        if (! $result) {
            return response()->json(['message' => 'Compte introuvable ou suspendu.'], 401);
        }

        $token = $this->authService->issueToken($result['user']);

        return response()->json([
            'user' => new UserResource($result['user']->load(['profile', 'sellerProfile'])),
            'token' => $token->plainTextToken,
            'is_new' => $result['isNew'],
        ], $result['isNew'] ? 201 : 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->string('phone'));

        try {
            $this->authService->resetPassword($phone, $request->string('code'), $request->string('password'));
        } catch (OtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load(['profile.city', 'sellerProfile'])),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }
}
