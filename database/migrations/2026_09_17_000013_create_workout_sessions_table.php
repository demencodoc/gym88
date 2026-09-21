<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('workout_plan_day_id')->nullable()->constrained('workout_plan_days')->nullOnDelete();
            $table->string('title'); // e.g. "Buổi tập ngực thứ 2"
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->decimal('calories_burned', 7, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5 stars
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
        });

        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained('workout_sessions')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->unsignedSmallInteger('reps_completed');
            $table->decimal('weight_kg', 6, 2)->default(0.00);
            $table->unsignedTinyInteger('rpe')->nullable(); // 1 to 10
            $table->boolean('is_completed')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
        Schema::dropIfExists('workout_sessions');
    }
};
