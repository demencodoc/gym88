<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_id')->constrained('gym_classes')->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->string('room'); // Studio 1, Studio 2, etc.
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->unsignedInteger('max_capacity')->default(20);
            $table->unsignedInteger('current_bookings_count')->default(0); // Counter cache
            $table->string('status')->default('scheduled')->index(); // scheduled, ongoing, completed, cancelled
            $table->timestamps();

            $table->index(['start_time', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_class_schedules');
    }
};
