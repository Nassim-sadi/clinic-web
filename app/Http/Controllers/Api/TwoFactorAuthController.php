<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TwoFactorAuthController extends Controller
{
    public function __construct(private TwoFactorAuthService $twoFactorService) {}

    public function status(): JsonResponse
    {
        $user = auth()->user();

        return response()->json([
            'enabled' => $this->twoFactorService->isEnabled($user),
            'verified_at' => $user->two_factor_verified_at,
        ]);
    }

    public function setup(): JsonResponse
    {
        $user = auth()->user();

        if ($this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is already enabled'], 400);
        }

        $secret = $this->twoFactorService->generateSecret();
        $qrCodeUrl = $this->twoFactorService->generateQrCodeUrl($user, $secret);
        $qrCodeImage = $this->twoFactorService->generateQrCodeImage($qrCodeUrl);

        Cache::put("2fa_setup_{$user->id}", $secret, now()->addMinutes(10));

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'qr_code_image' => base64_encode(file_get_contents($qrCodeImage)),
        ]);
    }

    public function verifyAndEnable(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();
        $secret = Cache::get("2fa_setup_{$user->id}");

        if (! $secret) {
            return response()->json(['message' => 'Setup expired. Please start again.'], 400);
        }

        if (! $this->twoFactorService->verifyCode($secret, $request->code)) {
            return response()->json(['message' => 'Invalid verification code'], 422);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();
        $this->twoFactorService->enableTwoFactor($user, $secret, $recoveryCodes);

        Cache::forget("2fa_setup_{$user->id}");

        return response()->json([
            'message' => '2FA enabled successfully',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid password'], 422);
        }

        $secret = decrypt($user->two_factor_secret);

        if (! $this->twoFactorService->verifyCode($secret, $request->code)) {
            if (! $this->twoFactorService->verifyRecoveryCode($user, $request->code)) {
                return response()->json(['message' => 'Invalid verification code'], 422);
            }
        }

        $this->twoFactorService->disableTwoFactor($user);

        return response()->json(['message' => '2FA disabled successfully']);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth()->user();

        if (! $this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is not enabled'], 400);
        }

        $secret = decrypt($user->two_factor_secret);

        if ($this->twoFactorService->verifyCode($secret, $request->code)) {
            return response()->json([
                'valid' => true,
                'message' => 'Code verified',
            ]);
        }

        if ($this->twoFactorService->verifyRecoveryCode($user, $request->code)) {
            return response()->json([
                'valid' => true,
                'message' => 'Recovery code verified',
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid code',
        ], 422);
    }

    public function regenerateRecoveryCodes(): JsonResponse
    {
        $user = auth()->user();

        if (! $this->twoFactorService->isEnabled($user)) {
            return response()->json(['message' => '2FA is not enabled'], 400);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return response()->json([
            'message' => 'Recovery codes regenerated',
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
