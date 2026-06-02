<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OcrService
{
    public function recognize(string $absolutePath): array
    {
        $url = rtrim(config('services.ocr.url'), '/') . '/recognize';
        $timeout = (int) config('services.ocr.timeout', 60);

        $handle = fopen($absolutePath, 'r');

        try {
            $response = Http::timeout($timeout)
                ->attach('image', $handle, basename($absolutePath))
                ->post($url);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if (! $response->ok()) {
            throw new RuntimeException('OCR-сервис недоступен.');
        }

        return $response->json();
    }
}
