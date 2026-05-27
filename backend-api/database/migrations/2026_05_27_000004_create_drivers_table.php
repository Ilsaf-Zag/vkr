<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone')->nullable();
            $table->string('license_number')->unique();
            $table->string('license_category', 50);
            $table->foreignId('photo_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active')->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};

