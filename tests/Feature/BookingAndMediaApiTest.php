<?php

use App\Models\GymClass;
use App\Models\GymClassSchedule;
use App\Models\MembershipPackage;
use App\Models\MemberSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('user can book classes with counter-cache and book PT sessions', function () {
    Storage::fake('public');

    $member = User::factory()->create(['is_active' => true, 'role' => 'member']);
    $trainer = User::factory()->create(['is_active' => true, 'role' => 'trainer']);

    $package = MembershipPackage::create([
        'name' => 'Gói VIP PT',
        'slug' => 'goi-vip-pt',
        'price' => 2000000,
        'duration_days' => 30,
        'has_pt' => true,
        'pt_sessions' => 5,
        'is_active' => true,
    ]);

    $sub = MemberSubscription::create([
        'user_id' => $member->id,
        'package_id' => $package->id,
        'membership_code' => 'VIP-BOOK-11',
        'qr_token' => 'QR-BOOK-11',
        'start_date' => Carbon::today()->toDateString(),
        'end_date' => Carbon::today()->addDays(30)->toDateString(),
        'price_paid' => 2000000,
        'status' => 'active',
        'remaining_pt_sessions' => 5,
    ]);

    $token = $member->createToken('test')->plainTextToken;

    // 1. Tạo lớp học nhóm & lịch học
    $gymClass = GymClass::create([
        'name' => 'HIIT Cardio',
        'is_active' => true,
    ]);

    $schedule = GymClassSchedule::create([
        'gym_class_id' => $gymClass->id,
        'trainer_id' => $trainer->id,
        'room' => 'Phòng 101',
        'start_time' => Carbon::tomorrow()->setTime(18, 0),
        'end_time' => Carbon::tomorrow()->setTime(19, 0),
        'max_capacity' => 10,
        'current_bookings_count' => 0,
        'status' => 'scheduled',
    ]);

    // 2. Đặt chỗ lớp học -> Observer tự động tăng current_bookings_count
    $bookClassResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/class-bookings', [
            'gym_class_schedule_id' => $schedule->id,
        ]);

    $bookClassResponse->assertStatus(201);
    $this->assertEquals(1, $schedule->fresh()->current_bookings_count);

    // 3. Đặt lịch tập PT 1:1 -> trừ 1 buổi trong hợp đồng
    $bookPTResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/pt-bookings', [
            'trainer_id' => $trainer->id,
            'start_time' => Carbon::tomorrow()->setTime(8, 0)->toDateTimeString(),
            'end_time' => Carbon::tomorrow()->setTime(9, 0)->toDateTimeString(),
            'notes' => 'Hướng dẫn tập vai và ngực',
        ]);

    $bookPTResponse->assertStatus(201);
    $this->assertEquals(4, $sub->fresh()->remaining_pt_sessions);

    // 4. Upload ảnh Avatar (Media File)
    $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

    $uploadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/media/upload', [
            'file' => $file,
            'collection' => 'avatar',
        ]);

    $uploadResponse->assertStatus(201)
        ->assertJsonPath('media.collection', 'avatar');

    $mediaId = $uploadResponse->json('media.id');

    // 5. Download media file
    $downloadResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->get("/api/v1/media/{$mediaId}/download");

    $downloadResponse->assertStatus(200);
});
