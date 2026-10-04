<?php

namespace App\Http\Controllers;

use App\Services\BirdWebhookSignatureVerifier;
use App\Services\BirdWhatsAppWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;

class BirdWhatsAppWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        BirdWebhookSignatureVerifier $signatureVerifier,
        BirdWhatsAppWebhookService $webhooks
    ): JsonResponse {
        if (!$signatureVerifier->verify($request)) {
            return response()->json(['message' => 'Webhook tidak valid.'], 401);
        }

        try {
            $event = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return response()->json(['message' => 'Payload tidak valid.'], 422);
        }

        if (!is_array($event)) {
            return response()->json(['message' => 'Payload tidak valid.'], 422);
        }

        $status = $webhooks->handle(
            $event,
            (string) $request->header('webhook-id')
        );

        return response()->json(['status' => $status]);
    }
}
