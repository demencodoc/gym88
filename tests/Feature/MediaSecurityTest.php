<?php

use App\Models\BodyMeasurement;
use App\Models\MediaFile;
use App\Models\PTBooking;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('media security policy enforces strict inbody privacy and public media access', function () {
    Storage::fake('public');

    $memberA = User::factory()->create(['role' => 'member']);
    $memberB = User::factory()->create(['role' => 'member']);
    $trainerWithBooking = User::factory()->create(['role' => 'trainer']);
    $trainerWithoutBooking = User::factory()->create(['role' => 'trainer']);
    $admin = User::factory()->create(['role' => 'admin']);

    // Thiết lập file ảo trong Storage
    Storage::disk('public')->put('media/inbody_front/2026-09/inbody_a.jpg', 'fake-content');
    Storage::disk('public')->put('media/avatar/2026-09/avatar_a.jpg', 'fake-avatar');

    // 1. Tạo InBody Media của Member A (Private Collection)
    $measurementA = BodyMeasurement::create([
        'user_id' => $memberA->id,
        'measured_at' => '2026-09-01',
        'weight_kg' => 70,
        'height_cm' => 175,
        'bmi' => 22.86,
    ]);

    $inbodyMediaA = MediaFile::create([
        'user_id' => $memberA->id,
        'mediable_type' => BodyMeasurement::class,
        'mediable_id' => $measurementA->id,
        'collection' => 'inbody_front',
        'file_name' => 'inbody_a.jpg',
        'file_path' => 'media/inbody_front/2026-09/inbody_a.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 50000,
        'file_type' => 'image',
    ]);

    // 2. Tạo Avatar Media của Member A (Public Collection)
    $avatarMediaA = MediaFile::create([
        'user_id' => $memberA->id,
        'mediable_type' => User::class,
        'mediable_id' => $memberA->id,
        'collection' => 'avatar',
        'file_name' => 'avatar_a.jpg',
        'file_path' => 'media/avatar/2026-09/avatar_a.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 30000,
        'file_type' => 'image',
    ]);

    // Tạo lịch PT giữa trainerWithBooking và memberA
    PTBooking::create([
        'user_id' => $memberA->id,
        'trainer_id' => $trainerWithBooking->id,
        'start_time' => Carbon::tomorrow()->setTime(9, 0),
        'end_time' => Carbon::tomorrow()->setTime(10, 0),
        'status' => 'confirmed',
    ]);

    // CASE 1: Member B truy cập ảnh InBody của Member A -> 403 Forbidden
    $responseCrossUser = $this->actingAs($memberB, 'sanctum')
        ->get("/api/v1/media/{$inbodyMediaA->id}/download");
    $responseCrossUser->assertStatus(403);

    // CASE 2: Member A truy cập ảnh InBody của chính mình -> 200 OK
    $responseOwner = $this->actingAs($memberA, 'sanctum')
        ->get("/api/v1/media/{$inbodyMediaA->id}/download");
    $responseOwner->assertStatus(200);

    // CASE 3: Trainer KHÔNG có lịch dạy Member A -> 403 Forbidden
    $responseTrainerNo = $this->actingAs($trainerWithoutBooking, 'sanctum')
        ->get("/api/v1/media/{$inbodyMediaA->id}/download");
    $responseTrainerNo->assertStatus(403);

    // CASE 4: Trainer CÓ lịch dạy kèm Member A -> 200 OK
    $responseTrainerYes = $this->actingAs($trainerWithBooking, 'sanctum')
        ->get("/api/v1/media/{$inbodyMediaA->id}/download");
    $responseTrainerYes->assertStatus(200);

    // CASE 5: Admin xem bất kỳ ảnh InBody nào -> 200 OK
    $responseAdmin = $this->actingAs($admin, 'sanctum')
        ->get("/api/v1/media/{$inbodyMediaA->id}/download");
    $responseAdmin->assertStatus(200);

    // CASE 6: Member B truy cập Public Media (avatar của Member A) -> 200 OK
    $responsePublic = $this->actingAs($memberB, 'sanctum')
        ->get("/api/v1/media/{$avatarMediaA->id}/download");
    $responsePublic->assertStatus(200);
});
