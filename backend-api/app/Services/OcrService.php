<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OcrService
{
    public function recognize(string $absolutePath): array
    {
        $baseUrl = rtrim(config('services.ocr.url'), '/');
        $health = $this->health($baseUrl);

        if (($health['model_ready'] ?? false) !== true) {
            return [
                'success' => false,
                'raw_text' => [],
                'candidates' => [],
                'suggested_value' => null,
                'confidence' => null,
                'error' => $health['model_error'] ?: 'Модуль распознавания ещё загружается. Укажите показания вручную или повторите попытку позже.',
            ];
        }

        $url = $baseUrl . '/recognize';
        $timeout = min((int) config('services.ocr.timeout', 45), 45);

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

    private function health(string $baseUrl): array
    {
        try {
            $response = Http::timeout(2)->get($baseUrl . '/health');

            if ($response->ok()) {
                return $response->json();
            }
        } catch (\Throwable) {
        }

        return [
            'ok' => false,
            'model_ready' => false,
            'model_error' => 'OCR-сервис временно недоступен. Укажите показания вручную или повторите попытку позже.',
        ];
    }
}
