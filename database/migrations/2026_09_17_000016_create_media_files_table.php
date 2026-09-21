<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Uploader/Owner
            $table->string('mediable_type');
            $table->unsignedBigInteger('mediable_id');
            $table->string('collection')->default('default'); // avatar, exercise_video, exercise_thumb, inbody_front, inbody_side, inbody_back, class_cover, pt_cert
            $table->string('file_name');
            $table->string('file_path');
            $table->string('disk')->default('public');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('file_type')->default('image'); // image, video
            $table->unsignedInteger('width')->nullable(); // Image width
            $table->unsignedInteger('height')->nullable(); // Image height
            $table->unsignedInteger('duration_seconds')->nullable(); // Video duration in seconds
            $table->unsignedInteger('download_count')->default(0); // Atomic download counter
            $table->timestamps();

            $table->index(['mediable_type', 'mediable_id']);
            $table->index(['mediable_type', 'mediable_id', 'collection']);
            $table->index(['user_id', 'collection']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
