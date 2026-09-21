<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('forgot password does not reveal whether an email exists', function () {
    Notification::fake();

    $knownEmailResponse = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'member@gym88.test',
    ]);

    $unknownEmailResponse = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'missing@gym88.test',
    ]);

    $knownEmailResponse->assertOk();
    $unknownEmailResponse->assertOk();
});

test('user can reset password and all old tokens are revoked', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'member@gym88.test',
        'password' => 'old-password',
    ]);

    $oldToken = $user->createToken('old-device')->plainTextToken;
    $resetToken = app('auth.password.broker')->createToken($user);

    $response = $this->postJson('/api/v1/auth/reset-password', [
        'email' => $user->email,
        'token' => $resetToken,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertOk();
    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
    expect($user->tokens()->count())->toBe(0);
    expect($this->withHeader('Authorization', 'Bearer '.$oldToken)
        ->getJson('/api/v1/auth/me')->status())->toBe(401);
});

test('logout all revokes every Sanctum token', function () {
    $user = User::factory()->create();
    $firstToken = $user->createToken('first-device')->plainTextToken;
    $secondToken = $user->createToken('second-device')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$firstToken)
        ->postJson('/api/v1/auth/logout-all');

    $response->assertOk();
    expect($user->fresh()->tokens()->count())->toBe(0);
    $this->app['auth']->forgetGuards();
    expect($this->withHeader('Authorization', 'Bearer '.$secondToken)
        ->getJson('/api/v1/auth/me')->status())->toBe(401);
});
