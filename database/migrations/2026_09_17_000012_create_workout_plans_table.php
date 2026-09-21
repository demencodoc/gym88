<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('goal')->default('general_fitness'); // muscle_gain, weight_loss, endurance, general_fitness
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->unsignedInteger('duration_weeks')->default(4);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        Schema::create('workout_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number'); // 1, 2, 3...
            $table->string('title'); // e.g. "Ngày 1: Ngực & Tay sau"
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('workout_plan_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_day_id')->constrained('workout_plan_days')->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained('exercises')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->unsignedSmallInteger('target_sets')->default(3);
            $table->unsignedSmallInteger('target_reps')->default(12);
            $table->unsignedSmallInteger('rest_seconds')->default(60);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_plan_exercises');
        Schema::dropIfExists('workout_plan_days');
        Schema::dropIfExists('workout_plans');
    }
};
