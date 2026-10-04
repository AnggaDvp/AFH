<?php

namespace Tests;

use Illuminate\Http\UploadedFile;
use ZipArchive;

trait AttachmentFixtures
{
    private array $temporaryFiles = [];

    private function upload(string $extension, ?string $content = null, ?string $name = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'afh-attachment-');
        $this->temporaryFiles[] = $path;
        if ($content !== null) {
            file_put_contents($path, $content);
        } elseif (in_array($extension, ['docx', 'xlsx'], true)) {
            $word = $extension === 'docx';
            $part = $word ? 'word/document.xml' : 'xl/workbook.xml';
            $type = $word ? 'wordprocessingml.document' : 'spreadsheetml.sheet';
            $zip = new ZipArchive();
            $zip->open($path, ZipArchive::OVERWRITE);
            $zip->setCompressionName('[Content_Types].xml', ZipArchive::CM_STORE);
            $zip->setCompressionName($part, ZipArchive::CM_STORE);
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/' . $part . '" ContentType="application/vnd.openxmlformats-officedocument.' . $type . '.main+xml"/></Types>');
            $zip->addFromString($part, '<?xml version="1.0"?><test>Attachment fixture</test>');
            $zip->close();
        } elseif (in_array($extension, ['doc', 'xls'], true)) {
            // Minimal CFB container with directory and regular stream, for MIME inspection.
            $header = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 16)
                . pack('vvvvv', 0x3e, 3, 0xfffe, 9, 6) . str_repeat("\0", 6)
                . pack('VVVVVVVVV', 0, 1, 0, 0, 4096, 0xfffffffe, 0, 0xfffffffe, 0)
                . pack('V', 1) . str_repeat("\xff", 108 * 4);
            $root = $this->cfbDirectory('Root Entry', 5, 1, 0xfffffffe, 0);
            $stream = $this->cfbDirectory($extension === 'doc' ? 'WordDocument' : 'Workbook', 2, 0xffffffff, 2, 4096);
            $directory = str_pad($root . $stream, 512, "\0");
            $fat = pack('VV', 0xfffffffe, 0xfffffffd);
            for ($sector = 2; $sector <= 9; $sector++) {
                $fat .= pack('V', $sector === 9 ? 0xfffffffe : $sector + 1);
            }
            $fat = str_pad($fat, 512, "\xff");
            file_put_contents($path, $header . $directory . $fat . str_pad('Attachment fixture', 4096, "\0"));
        } else {
            $bytes = match ($extension) {
                'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='),
                'jpg', 'jpeg' => file_get_contents(base_path('public/assets/images/logo.jpeg')),
                'webp' => base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'),
                'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
                default => 'unsupported content',
            };
            file_put_contents($path, $bytes);
        }

        // A bogus client MIME proves upload validation uses file bytes, not this value.
        return new UploadedFile($path, $name ?? 'fixture.' . $extension, 'application/octet-stream', null, true);
    }

    private function cfbDirectory(string $name, int $type, int $child, int $start, int $size): string
    {
        $encoded = mb_convert_encoding($name . "\0", 'UTF-16LE');
        return str_pad($encoded, 64, "\0") . pack('vCCVVV', strlen($encoded), $type, 1, 0xffffffff, 0xffffffff, $child)
            . str_repeat("\0", 36) . pack('VVV', $start, $size, 0);
    }

    private function removeUploadFixtures(): void
    {
        foreach ($this->temporaryFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
