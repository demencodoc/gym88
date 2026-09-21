<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pt_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Member
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete(); // Trainer
            $table->foreignId('subscription_id')->nullable()->constrained('member_subscriptions')->nullOnDelete();
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('status')->default('pending')->index(); // pending, confirmed, completed, cancelled, no_show
            $table->string('cancelled_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1 to 5 stars
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['trainer_id', 'start_time']);
            $table->index(['user_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pt_bookings');
    }
};
