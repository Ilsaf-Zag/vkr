<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('plate_number')->unique();
            $table->string('vin')->nullable()->unique();
            $table->unsignedSmallInteger('year')->nullable();
            $table->enum('fuel_type', ['petrol', 'gas', 'diesel']);
            $table->unsignedInteger('current_mileage')->default(0);
            $table->enum('status', ['available', 'on_line', 'maintenance', 'inactive'])->default('available')->index();
            $table->foreignId('photo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['fuel_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

