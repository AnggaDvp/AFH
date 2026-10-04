<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\ChatMessageSender;
use App\Services\ChatMessageValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminChatController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['open', 'closed'])],
            'context' => ['nullable', Rule::in(['motor', 'dana_tunai'])],
            'unread' => ['nullable', Rule::in(['0', '1'])],
        ]);
        $conversations = ChatConversation::query()
            ->with([
                'customer:id,full_name,phone',
                'motorProduct:id,name',
                'latestMessage',
            ])
            ->withCount([
                'messages as unread_count' => fn (Builder $query) => $query
                    ->where('sender_type', 'customer')
                    ->whereNull('read_at'),
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->whereHas('customer', fn (Builder $customer) => $customer
                    ->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%'));
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['context'] ?? null, fn (Builder $query, string $context) => $query->where('context_type', $context))
            ->when(isset($filters['unread']), function (Builder $query) use ($filters): void {
                $unread = fn (Builder $messages) => $messages
                    ->where('sender_type', 'customer')
                    ->whereNull('read_at');
                $filters['unread'] === '1'
                    ? $query->whereHas('messages', $unread)
                    : $query->whereDoesntHave('messages', $unread);
            })
            ->orderByDesc('last_message_at')
            ->paginate(25)
            ->withQueryString();
        $conversations->getCollection()->transform(fn (ChatConversation $conversation): array => [
                'id' => $conversation->id,
                'customerName' => $conversation->customer->full_name,
                'customerPhone' => $conversation->customer->phone,
                'serviceLabel' => $conversation->context_type === 'dana_tunai'
                    ? 'Dana Tunai / Gadai BPKB'
                    : 'Motor Honda',
                'motorName' => $conversation->motorProduct?->name,
                'lastMessage' => $conversation->latestMessage
                    ? ($conversation->latestMessage->body ?: 'Lampiran')
                    : null,
                'status' => $conversation->status,
                'unreadCount' => $conversation->unread_count,
                'lastMessageAt' => $conversation->last_message_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/ChatIndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'conversations' => $conversations,
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, ChatConversation $conversation): Response
    {
        $conversation->messages()
            ->where('sender_type', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        $conversation->load([
            'customer:id,full_name,phone',
            'motorProduct:id,name,family',
            'latestCashLoanRequest',
            'messages' => fn ($query) => $query->orderBy('id'),
            'messages.attachments',
        ]);

        return Inertia::render('admin/ChatConversationPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'conversation' => $this->conversationProps($conversation),
        ]);
    }

    public function sendMessage(
        Request $request,
        ChatConversation $conversation,
        ChatMessageValidator $validator,
        ChatMessageSender $sender
    ): RedirectResponse
    {
        abort_unless($conversation->status === 'open', 404);
        $data = $validator->validate($request);

        /** @var User $admin */
        $admin = $request->user();
        $sender->send($conversation, 'admin', $admin->id, $data);

        return redirect()->route('admin.chat.show', $conversation);
    }

    private function conversationProps(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'customerName' => $conversation->customer->full_name,
            'customerPhone' => $conversation->customer->phone,
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
                'attachments' => $message->attachments->map(fn ($attachment) => $attachment->metadata('admin'))->values(),
                'createdAt' => $message->created_at?->toIso8601String(),
                'readAt' => $message->read_at?->toIso8601String(),
            ])->values(),
        ];
    }
}
