<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_class_schedule_id')->constrained('gym_class_schedules')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('booked')->index(); // booked, attended, cancelled
            $table->timestamp('booked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['gym_class_schedule_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
