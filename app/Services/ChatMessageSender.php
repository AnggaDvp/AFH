<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ChatMessageSender
{
    public function send(ChatConversation $conversation, string $senderType, int $senderId, array $data): ChatMessage
    {
        $paths = [];
        $disk = Storage::disk('chat_private');

        try {
            return DB::transaction(function () use ($conversation, $senderType, $senderId, $data, $disk, &$paths) {
                $locked = ChatConversation::whereKey($conversation->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->status === 'open', 404);
                $message = $locked->messages()->create([
                    'sender_type' => $senderType,
                    'sender_id' => $senderId,
                    'body' => $data['body'] === '' ? null : $data['body'],
                ]);

                foreach ($data['attachments'] as $file) {
                    $metadata = app(ChatAttachmentFileValidator::class)->metadata($file);
                    $name = Str::uuid()->toString() . '.' . $metadata['extension'];
                    $directory = 'chat/' . $locked->id . '/' . $message->id;
                    $path = $directory . '/' . $name;
                    // Track the intended path before writing, including partially failed writes.
                    $paths[] = $path;
                    if (!$disk->putFileAs($directory, $file, $name, ['visibility' => 'private'])) {
                        throw new \RuntimeException('Chat attachment storage failed.');
                    }
                    $message->attachments()->create($metadata + [
                        'stored_name' => $name, 'path' => $path, 'disk' => 'chat_private',
                    ]);
                }

                $locked->update(['last_message_at' => $message->created_at]);

                return $message;
            });
        } catch (Throwable $exception) {
            foreach ($paths as $path) {
                try {
                    if (!$disk->delete($path)) {
                        Log::error('Chat attachment cleanup could not complete.');
                    }
                } catch (Throwable $cleanupError) {
                    Log::error('Chat attachment cleanup failed.', ['exception_type' => get_class($cleanupError)]);
                }
            }
            if ($exception instanceof HttpExceptionInterface) {
                throw $exception;
            }
            // Do not log message text, original filenames, file content, or SQL bindings.
            Log::error('Chat message could not be saved.', ['exception_type' => get_class($exception)]);
            throw ValidationException::withMessages([
                'attachments' => 'Pesan belum tersimpan. File yang dipilih belum terkirim. Silakan coba lagi.',
            ]);
        }
    }
}
