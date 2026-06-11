<?php

declare(strict_types=1);

use App\Models\User;

it('registers a new user and returns an API token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']])
        ->assertJsonPath('user.email', 'ada@example.com');

    $this->assertDatabaseHas('users', ['email' => 'ada@example.com']);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'ada@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422)->assertJsonValidationErrors('email');
});

it('requires a confirmed password', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ])->assertStatus(422)->assertJsonValidationErrors('password');
});

it('never exposes the password hash', function () {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertCreated()->assertJsonMissingPath('user.password');
});
