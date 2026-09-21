<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('role')->default('member')->index()->after('password'); // admin, manager, staff, trainer, member
            $table->string('gender')->nullable()->after('role'); // male, female, other
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('address')->nullable()->after('date_of_birth');
            $table->string('emergency_contact')->nullable()->after('address');
            $table->boolean('is_active')->default(true)->after('emergency_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role',
                'gender',
                'date_of_birth',
                'address',
                'emergency_contact',
                'is_active',
            ]);
        });
    }
};
