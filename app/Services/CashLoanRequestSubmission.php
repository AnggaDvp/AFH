<?php

namespace App\Services;

use App\Models\CashLoanRequest;
use App\Models\ChatConversation;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CashLoanRequestSubmission
{
    public const AUTO_REPLY = 'Terima kasih kak sudah mengajukan dana tunai di Agency Finance Motor. Mohon ditunggu yah kak, Affiyah sedang proses pengajuannya.';

    /**
     * @return array{0: CashLoanRequest, 1: bool}
     */
    public function submit(Customer $customer, array $data): array
    {
        return DB::transaction(function () use ($customer, $data): array {
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $existingRequest = CashLoanRequest::query()
                ->with('conversation')
                ->where('customer_id', $customer->id)
                ->where('submission_key', $data['submission_key'])
                ->first();

            if ($existingRequest) {
                return [$existingRequest, false];
            }

            $conversation = ChatConversation::query()
                ->where('customer_id', $customer->id)
                ->where('context_type', 'dana_tunai')
                ->where('status', 'open')
                ->whereNull('motor_product_id')
                ->lockForUpdate()
                ->first();

            if (!$conversation) {
                $conversation = ChatConversation::create([
                    'customer_id' => $customer->id,
                    'context_type' => 'dana_tunai',
                    'motor_product_id' => null,
                    'status' => 'open',
                ]);
            }

            $cashLoanRequest = CashLoanRequest::create([
                'customer_id' => $customer->id,
                'conversation_id' => $conversation->id,
                'submission_key' => $data['submission_key'],
                'nopol' => mb_strtoupper(trim($data['nopol'])),
                'motor_brand' => $data['motor_brand'],
                'stnk_name_type' => $data['stnk_name_type'],
                'house_ownership' => $data['house_ownership'],
                'status' => 'submitted',
            ]);

            $message = $conversation->messages()->create([
                'sender_type' => 'system',
                'sender_id' => null,
                'body' => self::AUTO_REPLY,
            ]);
            $conversation->forceFill(['last_message_at' => $message->created_at])->save();

            return [$cashLoanRequest, true];
        });
    }
}
