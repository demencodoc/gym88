<?php

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can track workout sessions and record InBody measurements', function () {
    $user = User::factory()->create(['is_active' => true]);
    $exercise = Exercise::create([
        'name' => 'Barbell Bench Press',
        'muscle_group' => 'Ngực',
        'difficulty' => 'intermediate',
        'calories_burn_per_minute' => 8.0,
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // 1. Bắt đầu buổi tập
    $startResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/workouts/start', [
            'title' => 'Buổi tập Ngực Thứ 2',
        ]);

    $startResponse->assertStatus(201);
    $sessionId = $startResponse->json('session.id');

    // 2. Ghi set tập
    $logResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/workouts/{$sessionId}/log-set", [
            'exercise_id' => $exercise->id,
            'set_number' => 1,
            'reps_completed' => 10,
            'weight_kg' => 60.0,
            'rpe' => 8,
        ]);

    $logResponse->assertStatus(201);

    // 3. Hoàn thành buổi tập
    $completeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson("/api/v1/workouts/{$sessionId}/complete", [
            'rating' => 5,
            'notes' => 'Tập rất sung, hoàn thành tốt mục tiêu',
        ]);

    $completeResponse->assertStatus(200)
        ->assertJsonPath('session.rating', 5);

    // 4. Đo chỉ số InBody
    $measurementResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/v1/measurements', [
            'weight_kg' => 75.0,
            'height_cm' => 175.0,
            'body_fat_percentage' => 18.5,
            'muscle_mass_kg' => 34.0,
        ]);

    $measurementResponse->assertStatus(201)
        ->assertJsonPath('measurement.bmi', 24.49); // 75 / (1.75^2) = 24.489... -> 24.49

    // 5. Xem biểu đồ tiến trình
    $chartResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/measurements/chart');

    $chartResponse->assertStatus(200)
        ->assertJsonStructure(['labels', 'weight', 'body_fat', 'bmi']);
});
