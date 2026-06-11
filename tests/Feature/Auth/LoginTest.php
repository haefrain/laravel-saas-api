<?php

declare(strict_types=1);

use App\Models\User;

it('logs in with valid credentials and returns a token', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'email']]);
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'ada@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'ada@example.com',
        'password' => 'wrong-password',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('requires email and password', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});
