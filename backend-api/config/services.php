<?php

return [
    'ocr' => [
        'url' => env('OCR_SERVICE_URL', 'http://ocr:8001'),
        'timeout' => (int) env('OCR_SERVICE_TIMEOUT', 180),
    ],
];
