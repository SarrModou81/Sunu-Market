<?php

namespace App\Services;

use App\Exceptions\OtpException;
use App\Models\OtpCode;
use App\Services\Sms\HttpSmsProvider;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\SmsProvider;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    private SmsProvider $provider;

    public function __construct()
    {
        $this->provider = config('otp.driver') === 'sms'
            ? app(HttpSmsProvider::class)
            : app(LogSmsProvider::class);
    }

    /**
     * Génère un nouveau code OTP pour le couple (téléphone, motif) et l'envoie par SMS.
     *
     * @throws OtpException si une demande a déjà été faite trop récemment (anti-spam).
     */
    public function generate(string $phone, string $purpose, ?string $ipAddress = null): OtpCode
    {
        $lastOtp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        $resendSeconds = (int) config('otp.resend_seconds');
        if ($lastOtp && $lastOtp->created_at->diffInSeconds(now()) < $resendSeconds) {
            throw OtpException::tooSoon($resendSeconds - (int) $lastOtp->created_at->diffInSeconds(now()));
        }

        $code = (string) random_int(
            (int) str_pad('1', config('otp.length'), '0'),
            (int) str_pad('', config('otp.length'), '9')
        );

        $otp = OtpCode::create([
            'phone' => $phone,
            'code_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes((int) config('otp.expiration_minutes')),
            'attempts' => 0,
            'ip_address' => $ipAddress,
        ]);

        $this->provider->send(
            $phone,
            "SunuMarket : votre code de vérification est {$code}. Il expire dans ".config('otp.expiration_minutes').' minutes.'
        );

        return $otp;
    }

    /**
     * Vérifie le code fourni pour le couple (téléphone, motif). Consomme le code si valide.
     *
     * @throws OtpException si le code est invalide, expiré, déjà utilisé ou trop de tentatives.
     */
    public function verify(string $phone, string $code, string $purpose): OtpCode
    {
        $otp = OtpCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp) {
            throw OtpException::invalidOrExpired();
        }

        if ($otp->attempts >= (int) config('otp.max_attempts')) {
            throw OtpException::tooManyAttempts();
        }

        if ($otp->isExpired()) {
            throw OtpException::invalidOrExpired();
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');
            throw OtpException::invalidOrExpired();
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return $otp;
    }
}
