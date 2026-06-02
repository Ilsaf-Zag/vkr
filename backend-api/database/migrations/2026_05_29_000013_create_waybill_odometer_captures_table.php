<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('ALTER TABLE files DROP CONSTRAINT IF EXISTS files_type_check');
        DB::statement("
            ALTER TABLE files
            ADD CONSTRAINT files_type_check
            CHECK (type IN ('driver_photo', 'vehicle_photo', 'waybill_pdf', 'odometer_photo'))
        ");

        Schema::create('waybill_odometer_captures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained('waybills')->cascadeOnDelete();
            $table->enum('capture_type', ['start', 'finish'])->index();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete();
            $table->jsonb('ocr_raw_text')->nullable();
            $table->jsonb('ocr_candidates')->nullable();
            $table->unsignedInteger('ocr_value')->nullable();
            $table->decimal('ocr_confidence', 5, 4)->nullable();
            $table->unsignedInteger('confirmed_value')->nullable();
            $table->foreignId('confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->enum('recognition_status', ['pending', 'recognized', 'failed', 'confirmed', 'corrected'])->default('pending')->index();
            $table->text('recognition_error')->nullable();
            $table->timestamps();

            $table->unique(['waybill_id', 'capture_type']);
            $table->index(['waybill_id', 'recognition_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waybill_odometer_captures');

        DB::statement('ALTER TABLE files DROP CONSTRAINT IF EXISTS files_type_check');
        DB::statement("
            ALTER TABLE files
            ADD CONSTRAINT files_type_check
            CHECK (type IN ('driver_photo', 'vehicle_photo', 'waybill_pdf'))
        ");
    }
};
