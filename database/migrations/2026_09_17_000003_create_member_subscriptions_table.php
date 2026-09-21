<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('membership_packages')->restrictOnDelete();
            $table->string('membership_code')->unique();
            $table->string('qr_token')->nullable()->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('price_paid', 12, 2); // Snapshot price at purchase time
            $table->string('status')->default('active')->index(); // active, expired, suspended, cancelled
            $table->unsignedInteger('remaining_pt_sessions')->default(0);
            $table->date('suspended_at')->nullable();
            $table->date('suspended_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
    }
};
