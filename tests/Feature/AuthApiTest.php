<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can register successfully', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Nguyễn Văn Test',
        'email' => 'testuser@gym88.vn',
        'phone' => '0988112233',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'gender' => 'male',
        'date_of_birth' => '1995-05-10',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email', 'phone', 'role'],
            'token',
            'token_type',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'testuser@gym88.vn',
        'phone' => '0988112233',
    ]);
});

test('user can login with email or phone', function () {
    $user = User::factory()->create([
        'email' => 'member_login@gym88.vn',
        'phone' => '0977889900',
        'password' => bcrypt('secret123'),
        'is_active' => true,
    ]);

    // Login bằng email
    $responseEmail = $this->postJson('/api/v1/auth/login', [
        'login' => 'member_login@gym88.vn',
        'password' => 'secret123',
    ]);
    $responseEmail->assertStatus(200)->assertJsonStructure(['token', 'user']);

    // Login bằng số điện thoại
    $responsePhone = $this->postJson('/api/v1/auth/login', [
        'login' => '0977889900',
        'password' => 'secret123',
    ]);
    $responsePhone->assertStatus(200)->assertJsonStructure(['token', 'user']);
});

test('authenticated user can view profile and change password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('oldpassword'),
        'is_active' => true,
    ]);

    $token = $user->createToken('test')->plainTextToken;

    // View Profile
    $meResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->getJson('/api/v1/auth/me');
    $meResponse->assertStatus(200)
        ->assertJsonPath('user.id', $user->id);

    // Change Password
    $changePassResponse = $this->withHeader('Authorization', 'Bearer '.$token)
        ->putJson('/api/v1/auth/password', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);
    $changePassResponse->assertStatus(200);
});
