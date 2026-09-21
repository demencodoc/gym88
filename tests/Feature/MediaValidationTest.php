<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('media upload strictly validates file type via binary magic-byte sniffing and rejects spoofed files', function () {
    Storage::fake('public');

    $user = User::factory()->create(['role' => 'member']);
    $token = $user->createToken('test')->plainTextToken;

    // 1. Tải lên file PHP giả danh extension .jpg
    $fakeImagePhp = UploadedFile::fake()->createWithContent(
        'hacker.jpg',
        "echo 'malicious php content';"
    );

    $responseSpoofed = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/media/upload', [
            'file' => $fakeImagePhp,
            'collection' => 'avatar',
        ]);

    // Bị chặn 422 bởi finfo magic-byte sniffing
    $responseSpoofed->assertStatus(422)
        ->assertJsonValidationErrors(['file']);

    // 2. Tải lên collection không hợp lệ -> 422
    $realImage = UploadedFile::fake()->image('real.jpg', 200, 200);
    $responseInvalidCollection = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/media/upload', [
            'file' => $realImage,
            'collection' => 'hacked_collection',
        ]);

    $responseInvalidCollection->assertStatus(422)
        ->assertJsonValidationErrors(['collection']);

    // 3. Tải lên ảnh hợp lệ thật -> 201
    $responseValid = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/media/upload', [
            'file' => $realImage,
            'collection' => 'avatar',
        ]);

    $responseValid->assertStatus(201)
        ->assertJsonPath('media.collection', 'avatar')
        ->assertJsonPath('media.file_type', 'image');
});
