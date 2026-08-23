<?php

namespace App\Http\Controllers\Api\Auth;

use App\Exceptions\OtpException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePhoneRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\ImageService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ImageService $imageService,
    ) {}

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->except('avatar');

        if ($request->hasFile('avatar')) {
            if ($user->profile->avatar_path) {
                $this->imageService->delete($user->profile->avatar_path);
            }
            $data['avatar_path'] = $this->imageService->storeCompressed($request->file('avatar'), 'avatars', maxWidth: 600);
        }

        $user->profile()->update($data);

        return response()->json([
            'user' => new UserResource($user->fresh(['profile.city', 'sellerProfile'])),
        ]);
    }

    public function changePhone(ChangePhoneRequest $request): JsonResponse
    {
        $newPhone = PhoneNumber::normalize($request->string('new_phone'));

        try {
            $this->authService->changePhone($request->user(), $newPhone, $request->string('code'));
        } catch (OtpException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Numéro de téléphone mis à jour.']);
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Compte supprimé.']);
    }
}
