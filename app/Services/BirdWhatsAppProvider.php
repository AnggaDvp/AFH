<?php

namespace App\Services;

use App\Contracts\WhatsAppProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BirdWhatsAppProvider implements WhatsAppProviderInterface
{
    public function sendTemplateMessage(
        string $recipient,
        string $templateName,
        string $language,
        array $parameters
    ): string {
        if ($templateName === '' || $language === '') {
            throw new RuntimeException('Bird WhatsApp template is not configured.');
        }

        return $this->send($recipient, [
            'template' => [
                'slug' => $templateName,
                'language' => $language,
                'components' => [[
                    'type' => 'body',
                    'parameters' => collect($parameters)
                        ->map(fn (string $value, string $name): array => [
                            'type' => 'text',
                            'name' => $name,
                            'text' => $value,
                        ])
                        ->values()
                        ->all(),
                ]],
            ],
        ]);
    }

    public function sendTextMessage(string $recipient, string $body): string
    {
        if (trim($body) === '') {
            throw new RuntimeException('Bird WhatsApp message body is empty.');
        }

        return $this->send($recipient, ['text' => ['body' => $body]]);
    }

    private function send(string $recipient, array $content): string
    {
        $apiKey = trim((string) config('whatsapp.bird.api_key'));
        $region = strtolower(trim((string) config('whatsapp.bird.region')));
        $sender = $this->normalizePhoneNumber((string) config('whatsapp.sender_phone'));

        if (
            $apiKey === ''
            || $region === ''
            || !preg_match('/^[a-z0-9-]+$/', $region)
            || $sender === ''
        ) {
            throw new RuntimeException('Bird WhatsApp provider is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(15)
            ->post("https://{$region}.platform.bird.com/v1/whatsapp/messages", array_merge([
                'to' => $this->normalizePhoneNumber($recipient),
                'from' => $sender,
            ], $content))
            ->throw();

        $messageId = $response->json('id');
        if (!is_string($messageId) || $messageId === '') {
            throw new RuntimeException('Bird WhatsApp provider returned no message ID.');
        }

        return $messageId;
    }

    public function normalizePhoneNumber(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '62')) {
            return '+' . $digits;
        }
        if (str_starts_with($digits, '0')) {
            return '+62' . substr($digits, 1);
        }
        if (str_starts_with($digits, '8')) {
            return '+62' . $digits;
        }

        return $digits === '' ? '' : '+' . $digits;
    }
}
