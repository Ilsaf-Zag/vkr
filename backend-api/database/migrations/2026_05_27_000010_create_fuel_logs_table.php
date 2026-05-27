<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained('waybills')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->enum('fuel_type', ['petrol', 'gas', 'diesel']);
            $table->decimal('liters', 10, 2);
            $table->decimal('cost', 12, 2);
            $table->unsignedInteger('odometer');
            $table->timestamp('fueled_at');
            $table->text('comment')->nullable();

            $table->index(['waybill_id', 'fueled_at']);
            $table->index(['vehicle_id', 'fueled_at']);
            $table->index(['driver_id', 'fueled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_logs');
    }
};

