<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function buildOtpAuthUrl(string $email, string $secret, string $issuer): string
    {
        return $this->google2fa->getQRCodeUrl($issuer, $email, $secret);
    }

    public function generateQrDataUrl(string $otpauth): string
    {
        $qr = new QrCode($otpauth, size: 240, margin: 1);
        $writer = new PngWriter();
        $result = $writer->write($qr);
        $data = base64_encode($result->getString());
        return 'data:image/png;base64,' . $data;
    }

    public function verifyToken(string $secret, string $token): bool
    {
        return $this->google2fa->verifyKey($secret, $token);
    }
}
