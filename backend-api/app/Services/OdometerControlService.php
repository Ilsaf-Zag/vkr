<?php

namespace App\Services;

use App\Models\File;
use App\Models\GpsPoint;
use App\Models\User;
use App\Models\Waybill;
use App\Models\WaybillOdometerCapture;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OdometerControlService
{
    public function __construct(private readonly OcrService $ocr)
    {
    }

    public function storeCapture(Waybill $waybill, UploadedFile $image, string $captureType, User $user): WaybillOdometerCapture
    {
        $path = $image->store('odometer-captures/' . now()->format('Y/m'), 'public');

        $file = File::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $image->getClientOriginalName() ?: basename($path),
            'mime_type' => $image->getClientMimeType() ?: 'application/octet-stream',
            'size' => $image->getSize() ?: 0,
            'type' => 'odometer_photo',
            'uploaded_by' => $user->id,
        ]);

        $capture = WaybillOdometerCapture::query()->updateOrCreate(
            [
                'waybill_id' => $waybill->id,
                'capture_type' => $captureType,
            ],
            [
                'file_id' => $file->id,
                'ocr_raw_text' => [],
                'ocr_candidates' => [],
                'ocr_value' => null,
                'ocr_confidence' => null,
                'confirmed_value' => null,
                'confirmed_by_user_id' => null,
                'confirmed_at' => null,
                'recognition_status' => 'pending',
                'recognition_error' => null,
            ],
        );

        try {
            $result = $this->ocr->recognize(Storage::disk('public')->path($path));

            $capture->update([
                'ocr_raw_text' => $result['raw_text'] ?? [],
                'ocr_candidates' => $result['candidates'] ?? [],
                'ocr_value' => $result['suggested_value'] ?? null,
                'ocr_confidence' => $result['confidence'] ?? null,
                'recognition_status' => ($result['success'] ?? false) ? 'recognized' : 'failed',
                'recognition_error' => ($result['success'] ?? false) ? null : ($result['error'] ?? 'Не удалось распознать показания одометра'),
            ]);
        } catch (\Throwable $exception) {
            $capture->update([
                'recognition_status' => 'failed',
                'recognition_error' => $exception->getMessage() ?: 'Не удалось распознать показания одометра',
            ]);
        }

        return $capture->fresh(['file', 'confirmedBy']);
    }

    public function confirmCapture(Waybill $waybill, WaybillOdometerCapture $capture, int $confirmedValue, User $user): WaybillOdometerCapture
    {
        if ($capture->waybill_id !== $waybill->id) {
            throw ValidationException::withMessages([
                'capture' => 'Фиксация одометра не относится к выбранному путевому листу.',
            ]);
        }

        $this->validateConfirmedValue($waybill, $capture->capture_type, $confirmedValue);

        $status = $capture->ocr_value !== null && (int) $capture->ocr_value === $confirmedValue
            ? 'confirmed'
            : 'corrected';

        $capture->update([
            'confirmed_value' => $confirmedValue,
            'confirmed_by_user_id' => $user->id,
            'confirmed_at' => now(),
            'recognition_status' => $status,
        ]);

        $waybill->update([
            $capture->capture_type === 'start' ? 'odometer_start' : 'odometer_end' => $confirmedValue,
        ]);

        return $capture->fresh(['file', 'confirmedBy']);
    }

    public function controlPayload(Waybill $waybill): array
    {
        $waybill = $waybill->loadMissing(['odometerCaptures.file', 'gpsPoints']);

        $start = $waybill->odometerCaptures->firstWhere('capture_type', 'start');
        $finish = $waybill->odometerCaptures->firstWhere('capture_type', 'finish');

        $startValue = $start?->confirmed_value;
        $finishValue = $finish?->confirmed_value;
        $odometerDistance = $startValue !== null && $finishValue !== null
            ? $finishValue - $startValue
            : null;

        $gpsDistance = $this->gpsDistanceKm($waybill);
        $difference = $odometerDistance !== null && $gpsDistance !== null
            ? abs($odometerDistance - $gpsDistance)
            : null;

        return [
            'start' => $this->capturePayload($start),
            'finish' => $this->capturePayload($finish),
            'start_odometer_confirmed' => $startValue,
            'finish_odometer_confirmed' => $finishValue,
            'odometer_distance_km' => $odometerDistance,
            'gps_distance_km' => $gpsDistance,
            'distance_difference_km' => $difference,
            'control_status' => $this->controlStatus($start, $finish, $gpsDistance, $difference),
            'threshold_km' => (float) config('odometer.gps_review_threshold_km', 5),
        ];
    }

    public function hasConfirmedCapture(Waybill $waybill, string $captureType): bool
    {
        return $waybill->odometerCaptures()
            ->where('capture_type', $captureType)
            ->whereNotNull('confirmed_value')
            ->exists();
    }

    public function ensureCanClose(Waybill $waybill): void
    {
        $start = $waybill->odometerCaptures()->where('capture_type', 'start')->whereNotNull('confirmed_value')->first();
        $finish = $waybill->odometerCaptures()->where('capture_type', 'finish')->whereNotNull('confirmed_value')->first();

        if (! $start || ! $finish) {
            throw ValidationException::withMessages([
                'odometer' => 'Перед закрытием смены необходимо подтвердить начальный и конечный одометр.',
            ]);
        }

        if ($finish->confirmed_value < $start->confirmed_value) {
            throw ValidationException::withMessages([
                'odometer' => 'Конечное значение одометра не может быть меньше начального.',
            ]);
        }
    }

    private function validateConfirmedValue(Waybill $waybill, string $captureType, int $confirmedValue): void
    {
        if ($captureType === 'finish') {
            $start = $waybill->odometerCaptures()
                ->where('capture_type', 'start')
                ->whereNotNull('confirmed_value')
                ->first();

            if (! $start) {
                throw ValidationException::withMessages([
                    'confirmed_value' => 'Сначала подтвердите начальный одометр.',
                ]);
            }

            if ($confirmedValue < $start->confirmed_value) {
                throw ValidationException::withMessages([
                    'confirmed_value' => 'Конечное значение одометра не может быть меньше начального.',
                ]);
            }
        }

        if ($captureType === 'start') {
            $finish = $waybill->odometerCaptures()
                ->where('capture_type', 'finish')
                ->whereNotNull('confirmed_value')
                ->first();

            if ($finish && $finish->confirmed_value < $confirmedValue) {
                throw ValidationException::withMessages([
                    'confirmed_value' => 'Начальное значение одометра не может быть больше конечного.',
                ]);
            }
        }
    }

    private function capturePayload(?WaybillOdometerCapture $capture): ?array
    {
        if (! $capture) {
            return null;
        }

        return [
            'id' => $capture->id,
            'capture_type' => $capture->capture_type,
            'image_url' => $capture->file ? Storage::disk($capture->file->disk)->url($capture->file->path) : null,
            'ocr_raw_text' => $capture->ocr_raw_text ?? [],
            'ocr_candidates' => $capture->ocr_candidates ?? [],
            'ocr_value' => $capture->ocr_value,
            'ocr_confidence' => $capture->ocr_confidence !== null ? (float) $capture->ocr_confidence : null,
            'confirmed_value' => $capture->confirmed_value,
            'confirmed_at' => $capture->confirmed_at?->toISOString(),
            'confirmed_by' => $capture->confirmedBy?->full_name,
            'recognition_status' => $capture->recognition_status,
            'recognition_error' => $capture->recognition_error,
            'was_corrected' => $capture->recognition_status === 'corrected',
        ];
    }

    private function controlStatus(?WaybillOdometerCapture $start, ?WaybillOdometerCapture $finish, ?float $gpsDistance, ?float $difference): string
    {
        if ($start?->confirmed_value === null || $finish?->confirmed_value === null || $gpsDistance === null || $difference === null) {
            return 'not_available';
        }

        $threshold = (float) config('odometer.gps_review_threshold_km', 5);

        if ($difference > $threshold || $start->recognition_status === 'corrected' || $finish->recognition_status === 'corrected') {
            return 'requires_review';
        }

        return 'normal';
    }

    private function gpsDistanceKm(Waybill $waybill): ?float
    {
        $points = GpsPoint::query()
            ->where('waybill_id', $waybill->id)
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude']);

        if ($points->count() < 2) {
            return null;
        }

        $distance = 0.0;
        $previous = null;

        foreach ($points as $point) {
            if ($previous) {
                $distance += $this->haversine(
                    (float) $previous->latitude,
                    (float) $previous->longitude,
                    (float) $point->latitude,
                    (float) $point->longitude,
                );
            }

            $previous = $point;
        }

        return round($distance, 2);
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
