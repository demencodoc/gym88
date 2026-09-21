<?php

use App\Models\BodyMeasurement;
use App\Models\Exercise;
use App\Models\GymClass;
use App\Models\MediaFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('polymorphic media relations work correctly across User, GymClass, Exercise, and BodyMeasurement', function () {
    Storage::fake('public');

    // 1. User Avatar & PT Certificate
    $user = User::factory()->create(['role' => 'trainer']);

    $avatarMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => User::class,
        'mediable_id' => $user->id,
        'collection' => 'avatar',
        'file_name' => 'avatar.jpg',
        'file_path' => 'media/avatar/2026-09/avatar.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 50000,
        'file_type' => 'image',
    ]);

    $certMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => User::class,
        'mediable_id' => $user->id,
        'collection' => 'pt_cert',
        'file_name' => 'cert.jpg',
        'file_path' => 'media/pt_cert/2026-09/cert.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 120000,
        'file_type' => 'image',
    ]);

    expect($user->avatar)->not->toBeNull()
        ->and($user->avatar->id)->toBe($avatarMedia->id)
        ->and($user->avatar_url)->toBe(Storage::disk('public')->url('media/avatar/2026-09/avatar.jpg'))
        ->and($user->certificates)->toHaveCount(1)
        ->and($user->pt_cert_url)->toBe(Storage::disk('public')->url('media/pt_cert/2026-09/cert.jpg'));

    // 2. GymClass Cover Image
    $gymClass = GymClass::create([
        'name' => 'Pilates Core',
        'is_active' => true,
    ]);

    $coverMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => GymClass::class,
        'mediable_id' => $gymClass->id,
        'collection' => 'class_cover',
        'file_name' => 'cover.webp',
        'file_path' => 'media/class_cover/2026-09/cover.webp',
        'disk' => 'public',
        'mime_type' => 'image/webp',
        'file_size' => 85000,
        'file_type' => 'image',
    ]);

    expect($gymClass->coverImage)->not->toBeNull()
        ->and($gymClass->coverImage->id)->toBe($coverMedia->id)
        ->and($gymClass->cover_image_url)->toBe(Storage::disk('public')->url('media/class_cover/2026-09/cover.webp'));

    // 3. Exercise Thumbnail & Video
    $exercise = Exercise::create([
        'name' => 'Barbell Squat',
        'muscle_group' => 'Chân',
        'difficulty' => 'intermediate',
        'is_active' => true,
    ]);

    $thumbMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => Exercise::class,
        'mediable_id' => $exercise->id,
        'collection' => 'exercise_thumb',
        'file_name' => 'squat_thumb.jpg',
        'file_path' => 'media/exercise_thumb/2026-09/squat_thumb.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 60000,
        'file_type' => 'image',
    ]);

    $videoMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => Exercise::class,
        'mediable_id' => $exercise->id,
        'collection' => 'exercise_video',
        'file_name' => 'squat_guide.mp4',
        'file_path' => 'media/exercise_video/2026-09/squat_guide.mp4',
        'disk' => 'public',
        'mime_type' => 'video/mp4',
        'file_size' => 15000000,
        'file_type' => 'video',
    ]);

    expect($exercise->thumbnail)->not->toBeNull()
        ->and($exercise->thumbnail_url)->toBe(Storage::disk('public')->url('media/exercise_thumb/2026-09/squat_thumb.jpg'))
        ->and($exercise->video)->not->toBeNull()
        ->and($exercise->video_url)->toBe(Storage::disk('public')->url('media/exercise_video/2026-09/squat_guide.mp4'));

    // 4. BodyMeasurement Tri-angle Photos
    $measurement = BodyMeasurement::create([
        'user_id' => $user->id,
        'measured_at' => '2026-09-01',
        'weight_kg' => 70,
        'height_cm' => 175,
        'bmi' => 22.86,
    ]);

    $frontMedia = MediaFile::create([
        'user_id' => $user->id,
        'mediable_type' => BodyMeasurement::class,
        'mediable_id' => $measurement->id,
        'collection' => 'inbody_front',
        'file_name' => 'front.jpg',
        'file_path' => 'media/inbody_front/2026-09/front.jpg',
        'disk' => 'public',
        'mime_type' => 'image/jpeg',
        'file_size' => 90000,
        'file_type' => 'image',
    ]);

    expect($measurement->frontPhoto)->not->toBeNull()
        ->and($measurement->front_photo_url)->toBe(url('/api/v1/media/'.$frontMedia->id.'/download'));
});
