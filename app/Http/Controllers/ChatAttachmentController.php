<?php

namespace App\Http\Controllers;

use App\Models\ChatAttachment;
use App\Policies\ChatAttachmentPolicy;
use App\Services\ChatAttachmentFileValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function customer(Request $request, ChatAttachment $attachment, ChatAttachmentPolicy $policy): StreamedResponse
    {
        abort_unless($policy->viewCustomer($request->attributes->get('customer'), $attachment), 404);

        return $this->serve($request, $attachment);
    }

    public function admin(Request $request, ChatAttachment $attachment, ChatAttachmentPolicy $policy): StreamedResponse
    {
        abort_unless($policy->viewAdmin($request->user(), $attachment), 404);

        return $this->serve($request, $attachment);
    }

    private function serve(Request $request, ChatAttachment $attachment): StreamedResponse
    {
        $expectedPath = 'chat/' . $attachment->message->chat_conversation_id . '/'
            . $attachment->message_id . '/' . $attachment->stored_name;
        abort_unless($attachment->disk === 'chat_private'
            && $attachment->path === $expectedPath
            && preg_match('/^[a-f0-9-]{36}\.[a-z]+$/', $attachment->stored_name), 404);
        $disk = Storage::disk('chat_private');
        abort_unless($disk->exists($attachment->path), 404);

        $inline = $request->boolean('preview')
            && $attachment->attachment_type === 'image'
            && in_array($attachment->mime_type, ChatAttachmentFileValidator::IMAGE_MIMES, true);

        return $disk->response($attachment->path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox; frame-ancestors 'none'",
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ], $inline ? 'inline' : 'attachment');
    }
}
