<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('shift', 50)->index();
            $table->foreignId('driver_id')->constrained('drivers')->restrictOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->restrictOnDelete();
            $table->string('route_name');
            $table->text('dispatcher_comment')->nullable();
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['driver_id', 'date', 'shift']);
            $table->index(['vehicle_id', 'date', 'shift']);
        });

        DB::statement("
            CREATE UNIQUE INDEX work_orders_one_active_driver_shift
            ON work_orders (driver_id, date, shift)
            WHERE status IN ('planned', 'active')
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS work_orders_one_active_driver_shift');
        Schema::dropIfExists('work_orders');
    }
};

