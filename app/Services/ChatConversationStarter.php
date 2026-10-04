<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\MotorProduct;
use Illuminate\Support\Facades\DB;

class ChatConversationStarter
{
    /**
     * @return array{0: ChatConversation, 1: bool}
     */
    public function start(Customer $customer, string $contextType, ?MotorProduct $motorProduct = null): array
    {
        return DB::transaction(function () use ($customer, $contextType, $motorProduct): array {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $conversationQuery = ChatConversation::query()
                ->where('customer_id', $customer->id)
                ->where('context_type', $contextType)
                ->where('status', 'open');

            if ($contextType === 'motor') {
                $conversationQuery->where('motor_product_id', $motorProduct?->id);
            } else {
                $conversationQuery->whereNull('motor_product_id');
            }

            $existing = $conversationQuery->first();

            if ($existing) {
                return [$existing, false];
            }

            $conversation = ChatConversation::create([
                'customer_id' => $customer->id,
                'context_type' => $contextType,
                'motor_product_id' => $contextType === 'motor' ? $motorProduct?->id : null,
                'status' => 'open',
            ]);
            $body = $contextType === 'dana_tunai'
                ? 'Halo, saya tertarik dengan layanan Dana Tunai / Gadai BPKB Motor. Saya ingin mendapatkan informasi lebih lanjut.'
                : 'Halo, saya tertarik dengan motor ' . $motorProduct?->name . '. Saya ingin mendapatkan informasi lebih lanjut.';
            $message = $conversation->messages()->create([
                'sender_type' => 'customer',
                'sender_id' => $customer->id,
                'body' => $body,
            ]);

            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return [$conversation, true];
        });
    }
}
