<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class ChatAttachmentFileValidator
{
    public const IMAGE_MIMES = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'webp' => 'image/webp',
    ];

    private const DOCUMENT_MIMES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
        'xls' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
    ];

    public function error(UploadedFile $file): ?string
    {
        if (!$file->isValid()) {
            return 'File gagal diunggah. Silakan pilih ulang file.';
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $isImage = isset(self::IMAGE_MIMES[$extension]);
        if (!$isImage && !isset(self::DOCUMENT_MIMES[$extension])) {
            return 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, WEBP, PDF, DOC, DOCX, XLS, atau XLSX.';
        }

        $limit = ($isImage ? 5 : 10) * 1024 * 1024;
        if ($file->getSize() > $limit) {
            return $isImage ? 'Ukuran foto maksimal 5 MB per file.' : 'Ukuran dokumen maksimal 10 MB per file.';
        }
        if ($file->getSize() === 0) {
            return 'File kosong tidak dapat dikirim.';
        }

        // Inspect bytes on disk, never the browser-supplied Content-Type.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file->getPathname());
        $allowed = $isImage ? [self::IMAGE_MIMES[$extension]] : self::DOCUMENT_MIMES[$extension];
        if (!in_array($mime, $allowed, true)) {
            return 'Isi file tidak sesuai dengan formatnya. Pilih file asli dengan format yang didukung.';
        }

        if ($isImage) {
            $image = @getimagesize($file->getPathname());
            if (!$image || $image['mime'] !== self::IMAGE_MIMES[$extension]) {
                return 'Foto tidak dapat dibaca. Pilih foto JPG, PNG, atau WEBP yang valid.';
            }
        } elseif ($extension === 'pdf') {
            if (file_get_contents($file->getPathname(), false, null, 0, 5) !== '%PDF-') {
                return 'Dokumen PDF tidak valid.';
            }
        } elseif (in_array($extension, ['doc', 'xls'], true)) {
            if (file_get_contents($file->getPathname(), false, null, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
                return 'Dokumen Office tidak valid.';
            }
            $content = file_get_contents($file->getPathname());
            $streams = $extension === 'doc' ? ['WordDocument'] : ['Workbook', 'Book'];
            $matches = array_filter($streams, fn ($stream) => str_contains($content, mb_convert_encoding($stream, 'UTF-16LE')));
            if (!$matches) {
                return 'Isi dokumen Office tidak sesuai dengan ekstensi file.';
            }
        } elseif (!$this->validOfficeArchive($file, $extension)) {
            return 'Dokumen Office tidak valid atau berisi format yang tidak didukung.';
        }

        return null;
    }

    public function metadata(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $original = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $original = preg_replace('/[\p{C}<>:"|?*]/u', '_', $original) ?: 'attachment.' . $extension;

        return [
            'original_name' => mb_strcut($original, 0, 200, 'UTF-8'),
            'extension' => $extension,
            'mime_type' => self::IMAGE_MIMES[$extension] ?? self::DOCUMENT_MIMES[$extension][0],
            'size' => $file->getSize(),
            'attachment_type' => isset(self::IMAGE_MIMES[$extension]) ? 'image' : 'document',
        ];
    }

    private function validOfficeArchive(UploadedFile $file, string $extension): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getPathname()) !== true) {
            return false;
        }

        try {
            if ($zip->numFiles > 2000) {
                return false;
            }
            $expandedSize = 0;
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                if (preg_match('/^(?:\/|[a-z]:)|(?:^|\/)\.\.(?:\/|$)|\\\\/', $entry['name'])) {
                    return false;
                }
                $expandedSize += $entry['size'];
                $compressedSize = max(1, $entry['comp_size']);
                if ($expandedSize > 100 * 1024 * 1024
                    || $entry['size'] > 10 * 1024 * 1024
                    || $entry['size'] / $compressedSize > 100
                    || preg_match('/(?:vbaProject\.bin|\.(?:exe|bat|cmd|js|php|sh|dll|com|scr|msi))$/i', $entry['name'])) {
                    return false;
                }
            }
            $types = $zip->statName('[Content_Types].xml');
            if (!$types || $types['size'] > 128 * 1024) {
                return false;
            }
            $xml = $zip->getFromName('[Content_Types].xml');
            if ($xml === false || preg_match('/<!DOCTYPE|<!ENTITY|macroEnabled|vbaProject/i', $xml)) {
                return false;
            }
            $previous = libxml_use_internal_errors(true);
            try {
                $document = simplexml_load_string($xml, \SimpleXMLElement::class, LIBXML_NONET);
                if ($document === false) {
                    return false;
                }
                $part = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
                $contentType = $extension === 'docx'
                    ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
                foreach ($document->children('http://schemas.openxmlformats.org/package/2006/content-types') as $node) {
                    $attributes = $node->attributes();
                    if ((string) $attributes['PartName'] === '/' . $part && (string) $attributes['ContentType'] === $contentType) {
                        return $zip->locateName($part) !== false;
                    }
                }
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }

            return false;
        } finally {
            $zip->close();
        }
    }
}
