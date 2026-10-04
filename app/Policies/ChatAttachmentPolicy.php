<?php

namespace App\Policies;

use App\Models\ChatAttachment;
use App\Models\Customer;
use App\Models\User;

class ChatAttachmentPolicy
{
    public function viewCustomer(Customer $customer, ChatAttachment $attachment): bool
    {
        return $attachment->message?->conversation?->customer_id === $customer->id;
    }

    public function viewAdmin(User $admin, ChatAttachment $attachment): bool
    {
        return $admin->hasAdminAccess() && $attachment->message?->conversation !== null;
    }
}
