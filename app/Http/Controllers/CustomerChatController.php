<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\MotorProduct;
use App\Services\AdminNotificationService;
use App\Services\ChatConversationStarter;
use App\Services\ChatMessageSender;
use App\Services\ChatMessageValidator;
use App\Services\WhatsAppReminderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class CustomerChatController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        $conversations = ChatConversation::query()
            ->with(['motorProduct:id,name', 'latestMessage'])
            ->withCount([
                'messages as unread_count' => fn (Builder $query) => $query
                    ->where('sender_type', 'admin')
                    ->whereNull('read_at'),
            ])
            ->where('customer_id', $customer->id)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (ChatConversation $conversation): array => [
                'id' => $conversation->id,
                'contextType' => $conversation->context_type,
                'serviceLabel' => $conversation->context_type === 'dana_tunai'
                    ? 'Dana Tunai / Gadai BPKB'
                    : 'Motor Honda',
                'motorName' => $conversation->motorProduct?->name,
                'lastMessage' => $conversation->latestMessage
                    ? ($conversation->latestMessage->body ?: 'Lampiran')
                    : null,
                'unreadCount' => $conversation->unread_count,
                'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
                'status' => $conversation->status,
            ]);

        return Inertia::render('customer/ChatPage', [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
            'conversations' => $conversations,
        ]);
    }

    public function show(Request $request, ChatConversation $conversation): Response
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        abort_unless($conversation->customer_id === $customer->id, 404);

        $conversation->messages()
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $conversation->load([
            'motorProduct:id,name,family',
            'latestCashLoanRequest',
            'messages' => fn ($query) => $query->orderBy('id'),
            'messages.attachments',
        ]);

        return Inertia::render('customer/ConversationPage', [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
            'conversation' => $this->conversationProps($conversation),
        ]);
    }

    public function start(
        Request $request,
        ChatConversationStarter $starter,
        WhatsAppReminderService $reminder,
        AdminNotificationService $notifications
    ): RedirectResponse {
        $validated = Validator::make($request->all(), [
            'context_type' => ['required', 'in:motor,dana_tunai'],
            'motor_product_id' => [
                'nullable',
                'required_if:context_type,motor',
                'prohibited_if:context_type,dana_tunai',
                'integer',
                'exists:motor_products,id',
            ],
        ])->validate();

        $contextType = $validated['context_type'];
        $motorProduct = null;

        if ($contextType === 'motor') {
            $motorProduct = MotorProduct::query()
                ->where('status', 'active')
                ->findOrFail($validated['motor_product_id']);
        }

        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        [$conversation, $created] = $starter->start($customer, $contextType, $motorProduct);

        if ($created) {
            $service = $contextType === 'motor' ? 'Motor Honda' : 'Dana Tunai / Gadai BPKB';
            $type = $contextType === 'motor' ? 'new_motor_interest' : 'new_dana_tunai_interest';
            $detail = $contextType === 'motor' ? ': ' . $motorProduct?->name : '';
            $notifications->notifyActiveAdmins(
                $type,
                'Minat layanan baru',
                $customer->full_name . ' tertarik pada ' . $service . $detail . '.',
                [
                    'customer_id' => $customer->id,
                    'conversation_id' => $conversation->id,
                    'url' => '/admin/chat/' . $conversation->id,
                ]
            );
            if ($contextType === 'motor') {
                $reminder->queueMotorInterest($conversation);
            } else {
                $reminder->queueDanaTunaiInterest($conversation);
            }
        }

        return redirect()->route('customer.chat.show', $conversation);
    }

    public function sendMessage(
        Request $request,
        ChatConversation $conversation,
        ChatMessageValidator $validator,
        ChatMessageSender $sender,
        WhatsAppReminderService $reminder,
        AdminNotificationService $notifications
    ): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        abort_unless($conversation->customer_id === $customer->id, 404);
        abort_unless($conversation->status === 'open', 404);

        $data = $validator->validate($request);
        $message = $sender->send($conversation, 'customer', $customer->id, $data);
        $service = $conversation->context_type === 'motor' ? 'Motor Honda' : 'Dana Tunai / Gadai BPKB';
        $notificationData = [
            'customer_id' => $customer->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'url' => '/admin/chat/' . $conversation->id,
        ];
        $notifications->notifyActiveAdmins(
            'new_chat_message',
            'Pesan customer baru',
            $customer->full_name . ' mengirim pesan pada percakapan ' . $service . '.',
            $notificationData
        );
        $reminder->queueNewChatMessage($message);
        if (count($data['attachments']) > 0) {
            $reminder->queueNewAttachment($message);
        }
        if (count($data['attachments']) > 0) {
            $notifications->notifyActiveAdmins(
                'new_attachment',
                'Attachment baru',
                $customer->full_name . ' mengirim attachment pada percakapan ' . $service . '.',
                $notificationData
            );
        }

        return redirect()->route('customer.chat.show', $conversation);
    }

    private function conversationProps(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'contextType' => $conversation->context_type,
            'serviceLabel' => $conversation->context_type === 'dana_tunai'
                ? 'Dana Tunai / Gadai BPKB'
                : 'Motor Honda',
            'motorName' => $conversation->motorProduct?->name,
            'cashLoanRequest' => $conversation->latestCashLoanRequest?->chatContext(),
            'status' => $conversation->status,
            'messages' => $conversation->messages->map(fn ($message): array => [
                'id' => $message->id,
                'senderType' => $message->sender_type,
                'senderName' => $message->sender_type === 'system' ? 'Affiyah' : null,
                'body' => $message->body,
                'attachments' => $message->attachments->map(fn ($attachment) => $attachment->metadata('customer'))->values(),
                'createdAt' => $message->created_at?->toIso8601String(),
                'readAt' => $message->read_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
