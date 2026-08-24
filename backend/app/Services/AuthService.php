<?php

namespace App\Services;

use App\Exceptions\FirebaseAuthException;
use App\Exceptions\OtpException;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Firebase\FirebaseVerifierFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly FirebaseVerifierFactory $firebaseVerifierFactory,
    ) {}

    /**
     * @param  array{phone: string, code: string, password: string, first_name: string, last_name: string, email?: ?string, city_id?: ?int}  $data
     *
     * @throws OtpException
     */
    public function register(array $data): User
    {
        $this->otpService->verify($data['phone'], $data['code'], OtpCode::PURPOSE_REGISTER);

        return DB::transaction(function () use ($data) {
            $user = User::create([
                'phone' => $data['phone'],
                'password' => $data['password'],
                'phone_verified_at' => now(),
                'role' => User::ROLE_CLIENT,
                'status' => 'active',
            ]);

            $user->profile()->create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'] ?? null,
                'city_id' => $data['city_id'] ?? null,
            ]);

            $user->sellerProfile()->create([]);

            return $user;
        });
    }

    public function attemptLogin(string $phone, string $password): ?User
    {
        $user = User::where('phone', $phone)->first();

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            return null;
        }

        if ($user->status !== 'active') {
            return null;
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return $user;
    }

    /**
     * @throws OtpException
     */
    public function loginWithOtp(string $phone, string $code): ?User
    {
        $this->otpService->verify($phone, $code, OtpCode::PURPOSE_LOGIN);

        $user = User::where('phone', $phone)->first();

        if (! $user || $user->status !== 'active') {
            return null;
        }

        $user->forceFill(['last_login_at' => now(), 'phone_verified_at' => $user->phone_verified_at ?? now()])->save();

        return $user;
    }

    /**
     * @throws OtpException
     */
    public function resetPassword(string $phone, string $code, string $password): void
    {
        $this->otpService->verify($phone, $code, OtpCode::PURPOSE_RESET_PASSWORD);

        User::where('phone', $phone)->firstOrFail()
            ->forceFill(['password' => $password])
            ->save();
    }

    /**
     * @throws OtpException
     */
    public function changePhone(User $user, string $newPhone, string $code): void
    {
        $this->otpService->verify($newPhone, $code, OtpCode::PURPOSE_CHANGE_PHONE);

        $user->forceFill(['phone' => $newPhone, 'phone_verified_at' => now()])->save();
    }

    /**
     * Connecte ou inscrit un utilisateur à partir d'un jeton Firebase Authentication
     * (téléphone déjà vérifié par Firebase). $profile n'est utilisé que si aucun
     * compte n'existe pour ce numéro (inscription) et doit contenir au minimum
     * first_name/last_name dans ce cas.
     *
     * @param  array{first_name?: string, last_name?: string, email?: ?string, city_id?: ?int}  $profile
     * @return array{user: User, isNew: bool}|null null si le compte existe mais est suspendu.
     *
     * @throws FirebaseAuthException
     */
    public function loginOrRegisterWithFirebase(string $idToken, array $profile = []): ?array
    {
        $phone = $this->firebaseVerifierFactory->make()->verify($idToken);

        $user = User::where('phone', $phone)->first();

        if ($user) {
            if ($user->status !== 'active') {
                return null;
            }

            $user->forceFill([
                'last_login_at' => now(),
                'phone_verified_at' => $user->phone_verified_at ?? now(),
            ])->save();

            return ['user' => $user, 'isNew' => false];
        }

        if (blank($profile['first_name'] ?? null) || blank($profile['last_name'] ?? null)) {
            throw FirebaseAuthException::missingProfile();
        }

        $user = DB::transaction(function () use ($phone, $profile) {
            $user = User::create([
                'phone' => $phone,
                'phone_verified_at' => now(),
                'role' => User::ROLE_CLIENT,
                'status' => 'active',
            ]);

            $user->profile()->create([
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'email' => $profile['email'] ?? null,
                'city_id' => $profile['city_id'] ?? null,
            ]);

            $user->sellerProfile()->create([]);

            return $user;
        });

        return ['user' => $user, 'isNew' => true];
    }

    public function issueToken(User $user): NewAccessToken
    {
        return $user->createToken('sunumarket-mobile');
    }
}
