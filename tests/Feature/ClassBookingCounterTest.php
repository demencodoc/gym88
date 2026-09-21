<?php

use App\Models\GymClass;
use App\Models\GymClassSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('class booking observer syncs current_bookings_count accurately and blocks booking when full', function () {
    $trainer = User::factory()->create(['role' => 'trainer']);
    $member1 = User::factory()->create(['role' => 'member']);
    $member2 = User::factory()->create(['role' => 'member']);
    $member3 = User::factory()->create(['role' => 'member']);

    $gymClass = GymClass::create([
        'name' => 'Boxing Cardio',
        'is_active' => true,
    ]);

    // Tạo lịch học chỉ có đúng 2 chỗ (max_capacity = 2)
    $schedule = GymClassSchedule::create([
        'gym_class_id' => $gymClass->id,
        'trainer_id' => $trainer->id,
        'room' => 'Studio Boxing',
        'start_time' => Carbon::tomorrow()->setTime(18, 0),
        'end_time' => Carbon::tomorrow()->setTime(19, 0),
        'max_capacity' => 2,
        'current_bookings_count' => 0,
        'status' => 'scheduled',
    ]);

    $token1 = $member1->createToken('test')->plainTextToken;
    $token2 = $member2->createToken('test')->plainTextToken;
    $token3 = $member3->createToken('test')->plainTextToken;

    // 1. Member 1 đặt chỗ -> count tăng lên 1
    $res1 = $this->actingAs($member1, 'sanctum')
        ->postJson('/api/v1/class-bookings', [
            'gym_class_schedule_id' => $schedule->id,
        ]);
    $res1->assertStatus(201);
    expect($schedule->fresh()->current_bookings_count)->toBe(1);

    // 2. Member 2 đặt chỗ -> count tăng lên 2 (đầy lớp)
    $res2 = $this->actingAs($member2, 'sanctum')
        ->postJson('/api/v1/class-bookings', [
            'gym_class_schedule_id' => $schedule->id,
        ]);
    $res2->assertStatus(201);
    expect($schedule->fresh()->current_bookings_count)->toBe(2);

    // 3. Member 3 cố đặt chỗ -> Bị từ chối 422 do lớp đã đầy
    $res3 = $this->actingAs($member3, 'sanctum')
        ->postJson('/api/v1/class-bookings', [
            'gym_class_schedule_id' => $schedule->id,
        ]);
    $res3->assertStatus(422)
        ->assertJsonValidationErrors(['schedule']);

    // 4. Member 1 hủy chỗ -> Observer tự động giảm count về 1
    $cancelRes = $this->actingAs($member1, 'sanctum')
        ->postJson("/api/v1/class-bookings/{$schedule->id}/cancel");
    $cancelRes->assertStatus(200);
    expect($schedule->fresh()->current_bookings_count)->toBe(1);

    // 5. Member 3 đặt chỗ lại -> Thành công và count trở lại 2
    $res3Retry = $this->actingAs($member3, 'sanctum')
        ->postJson('/api/v1/class-bookings', [
            'gym_class_schedule_id' => $schedule->id,
        ]);
    $res3Retry->assertStatus(201);
    expect($schedule->fresh()->current_bookings_count)->toBe(2);
});
