<?php

namespace App\Services\Sms;

interface SmsProvider
{
    public function send(string $phone, string $message): bool;
}
