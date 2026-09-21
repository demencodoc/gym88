<?php

use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('media download increments download counter atomically', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => 'member']);
    $token = $user->createToken('test')->plainTextToken;

    Storage::disk('public')->put('media/exercise_video/2026-09/guide.mp4', 'dummy video content');

    $media = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => User::class,
        'mediable_id' => $user->id,
        'collection' => 'exercise_video',
        'file_name' => 'guide.mp4',
        'file_path' => 'media/exercise_video/2026-09/guide.mp4',
        'disk' => 'public',
        'mime_type' => 'video/mp4',
        'file_size' => 1024,
        'file_type' => 'video',
        'download_count' => 0,
    ]);

    expect($media->download_count)->toBe(0);

    // Thực hiện 5 lượt tải
    for ($i = 1; $i <= 5; $i++) {
        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->get("/api/v1/media/{$media->id}/download");

        $response->assertStatus(200);
    }

    // Kiểm tra bộ đếm chính xác là 5
    expect($media->fresh()->download_count)->toBe(5);
});
