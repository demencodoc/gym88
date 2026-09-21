<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('transaction_code')->unique();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_type')->nullable(); // subscription, store, etc.
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('VND');
            $table->string('payment_method')->default('vietqr'); // vietqr, vnpay, momo, cash, bank_transfer
            $table->string('status')->default('pending')->index(); // pending, success, failed, cancelled
            $table->string('payment_gateway_ref')->nullable();
            $table->string('signature_hash')->nullable(); // Webhook signature/checksum audit
            $table->json('webhook_payload')->nullable(); // Raw payload audit
            $table->timestamp('webhook_verified_at')->nullable();
            $table->timestamp('transaction_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
