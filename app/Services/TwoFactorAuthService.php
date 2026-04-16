<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    public function generateSecret(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function generateQrCodeUrl(User $user, string $secret): string
    {
        $company = config('app.name', 'NSclinic');
        $issuer = rawurlencode($company);

        $otpauthUrl = "otpauth://totp/{$issuer}:{$user->email}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";

        return $otpauthUrl;
    }

    public function generateQrCodeImage(string $otpauthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new ImagickImageBackEnd
        );

        $writer = new Writer($renderer);
        $tempPath = storage_path('app/temp/qr_'.Str::random(10).'.png');

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $writer->writeFile($otpauthUrl, $tempPath);

        return $tempPath;
    }

    public function verifyCode(string $secret, string $code): bool
    {
        $timeSlice = floor(time() / 30);

        for ($i = -1; $i <= 1; $i++) {
            $calculatedCode = $this->getCode($secret, $timeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function getCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0).pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);

        $offset = ord($hash[19]) & 0xF;

        $binary = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $otp = $binary % 1000000;

        return str_pad((string) $otp, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $encoded): string
    {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32Chars = strtoupper($base32Chars);
        $output = '';
        $v = 0;
        $bits = 0;

        for ($i = 0; $i < strlen($encoded); $i++) {
            $v = ($v << 5) | strpos($base32Chars, $encoded[$i]);
            $bits += 5;

            if ($bits >= 8) {
                $output .= chr(($v >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $output;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(Str::random(4).'-'.Str::random(4));
        }

        return $codes;
    }

    public function enableTwoFactor(User $user, string $secret, array $recoveryCodes): void
    {
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_verified_at' => now(),
        ]);
    }

    public function disableTwoFactor(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_verified_at' => null,
        ]);
    }

    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (! $user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        $code = strtoupper($code);

        $index = array_search($code, $codes);

        if ($index !== false) {
            unset($codes[$index]);
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
            ]);

            return true;
        }

        return false;
    }

    public function isEnabled(User $user): bool
    {
        return $user->two_factor_enabled && $user->two_factor_secret;
    }
}
