<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 (Monday) to 7 (Sunday)
            $table->time('start_time'); // e.g. 08:00:00
            $table->time('end_time'); // e.g. 12:00:00
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trainer_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_availabilities');
    }
};
