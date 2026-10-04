<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChatMessageValidator
{
    public function validate(Request $request): array
    {
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);
        $postLimit = $this->bytesFromIni((string) ini_get('post_max_size'));
        if ($postLimit > 0 && $contentLength > $postLimit
            && empty($request->allFiles()) && trim((string) $request->input('body', '')) === '') {
            throw ValidationException::withMessages([
                'attachments' => 'Ukuran total unggahan terlalu besar. Kurangi jumlah atau ukuran file, lalu coba lagi.',
            ]);
        }

        $data = Validator::make($request->all(), [
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'bail', 'required', 'file',
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile && ($error = app(ChatAttachmentFileValidator::class)->error($value))) {
                        $fail($error);
                    }
                },
            ],
        ], [
            'body.string' => 'Pesan harus berupa teks.',
            'body.max' => 'Pesan maksimal 5.000 karakter.',
            'attachments.array' => 'Pilih file yang ingin dikirim.',
            'attachments.max' => 'Maksimal 5 file dalam satu pesan.',
            'attachments.*.required' => 'File belum terpilih.',
            'attachments.*.file' => 'File tidak dapat dibaca. Silakan pilih ulang.',
            'attachments.*.uploaded' => 'File gagal diunggah. Periksa ukuran file lalu coba lagi.',
        ])->validate();

        $body = trim($data['body'] ?? '');
        $files = $data['attachments'] ?? [];
        if ($body === '' && count($files) === 0) {
            throw ValidationException::withMessages(['body' => 'Tulis pesan atau pilih file sebelum mengirim.']);
        }

        return ['body' => $body, 'attachments' => $files];
    }

    private function bytesFromIni(string $value): int
    {
        if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*([KMG]?)B?\s*$/i', $value, $match)) {
            return 0;
        }
        $power = match (strtoupper($match[2])) {
            'K' => 1,
            'M' => 2,
            'G' => 3,
            default => 0,
        };

        return (int) ((float) $match[1] * (1024 ** $power));
    }
}
