<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Services\OtpService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class OtpController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    /**
     * Génère et envoie un code OTP par SMS pour le motif demandé
     * (register, login, reset_password, change_phone).
     */
    public function request(RequestOtpRequest $request): JsonResponse
    {
        $phone = PhoneNumber::normalize($request->string('phone'));

        try {
            $this->otpService->generate($phone, $request->string('purpose'), $request->ip());
        } catch (OtpException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        return response()->json([
            'message' => 'Un code de vérification a été envoyé par SMS.',
            'expires_in_minutes' => (int) config('otp.expiration_minutes'),
        ]);
    }
}
