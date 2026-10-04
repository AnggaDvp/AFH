<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ChatAttachment extends Model
{
    protected $fillable = [
        'original_name', 'stored_name', 'path', 'disk',
        'mime_type', 'extension', 'size', 'attachment_type',
    ];

    protected $hidden = ['path', 'disk', 'stored_name'];

    protected $casts = ['message_id' => 'integer', 'size' => 'integer'];

    protected static function booted(): void
    {
        static::deleting(function (ChatAttachment $attachment) {
            $disk = Storage::disk($attachment->disk);
            if ($disk->exists($attachment->path) && !$disk->delete($attachment->path)) {
                throw new \RuntimeException('Private chat attachment could not be removed.');
            }
        });
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function metadata(string $audience): array
    {
        $url = route($audience . '.chat.attachments.show', $this, false);

        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'size' => $this->size,
            'attachment_type' => $this->attachment_type,
            'download_url' => $url,
            'preview_url' => $this->attachment_type === 'image' ? $url . '?preview=1' : null,
        ];
    }
}
