<?php

namespace App\Services;

use Illuminate\Http\Request;

class BirdWebhookSignatureVerifier
{
    public function verify(Request $request): bool
    {
        $secret = trim((string) config('whatsapp.bird.webhook_secret'));
        $deliveryId = trim((string) $request->header('webhook-id'));
        $timestamp = trim((string) $request->header('webhook-timestamp'));
        $signatureHeader = trim((string) $request->header('webhook-signature'));

        if ($secret === '' || $deliveryId === '' || $timestamp === '' || $signatureHeader === '') {
            return false;
        }

        if (!ctype_digit($timestamp)) {
            return false;
        }

        $tolerance = max(1, (int) config('whatsapp.bird.webhook_tolerance', 300));
        if (abs(now()->timestamp - (int) $timestamp) > $tolerance) {
            return false;
        }

        $encodedKey = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $key = base64_decode($encodedKey, true);
        if ($key === false || $key === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac(
            'sha256',
            $deliveryId . '.' . $timestamp . '.' . $request->getContent(),
            $key,
            true
        ));

        foreach (preg_split('/\s+/', $signatureHeader) ?: [] as $signature) {
            [$version, $value] = array_pad(explode(',', $signature, 2), 2, null);
            if ($version === 'v1' && is_string($value) && hash_equals($expected, $value)) {
                return true;
            }
        }

        return false;
    }
}
