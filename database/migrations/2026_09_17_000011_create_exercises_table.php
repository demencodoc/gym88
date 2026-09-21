<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('muscle_group')->index(); // chest, back, legs, shoulders, biceps, triceps, abs, cardio, full_body
            $table->string('difficulty')->default('beginner'); // beginner, intermediate, advanced
            $table->string('equipment')->default('dumbbell'); // barbell, dumbbell, machine, cable, bodyweight, other
            $table->text('instructions')->nullable();
            $table->decimal('calories_burn_per_minute', 5, 2)->default(5.00);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
