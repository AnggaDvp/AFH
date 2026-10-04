<?php

namespace App\Contracts;

interface WhatsAppProviderInterface
{
    /** @param array<string, string> $parameters Approved template body values keyed by variable name. */
    public function sendTemplateMessage(
        string $recipient,
        string $templateName,
        string $language,
        array $parameters
    ): string;

    public function sendTextMessage(string $recipient, string $body): string;
}
