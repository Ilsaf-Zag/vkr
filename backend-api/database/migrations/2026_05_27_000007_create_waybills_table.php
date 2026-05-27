<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('waybills', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->date('date')->index();
            $table->string('organization_name')->default('ООО «АЗЫК»');
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('work_order_id')->unique()->constrained('work_orders')->restrictOnDelete();
            $table->string('route_name');
            $table->string('status')->default('opened')->index();
            $table->unsignedInteger('odometer_start')->nullable();
            $table->unsignedInteger('odometer_end')->nullable();
            $table->decimal('fuel_start', 10, 2)->nullable();
            $table->decimal('fuel_end', 10, 2)->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('shift_started_at')->nullable();
            $table->timestamp('shift_finished_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('initial_printed_at')->nullable();
            $table->timestamp('final_printed_at')->nullable();
            $table->timestamps();

            $table->index(['driver_id', 'date']);
            $table->index(['vehicle_id', 'date']);
            $table->index(['status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waybills');
    }
};

