<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('member_subscriptions')->nullOnDelete();
            $table->timestamp('check_in_time')->useCurrent();
            $table->timestamp('check_out_time')->nullable();
            $table->string('method')->default('dynamic_qr'); // dynamic_qr, membership_code, manual
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'check_in_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
