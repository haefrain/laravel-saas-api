<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('throttles login per email after repeated failures', function () {
    User::factory()->create(['email' => 'victim@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'victim@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'victim@example.com',
        'password' => 'wrong-password',
        'device_name' => 'test',
    ])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJsonPath('error.code', 'rate_limited');
});

it('does not let one email lock out a different account', function () {
    User::factory()->create(['email' => 'victim@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'victim@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test',
        ]);
    }

    // The per-email key is exhausted; a different email still has headroom.
    $this->postJson('/api/v1/auth/login', [
        'email' => 'other@example.com',
        'password' => 'password',
        'device_name' => 'test',
    ])->assertOk();
});

it('rate limits registration by ip', function () {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/register', [])->assertStatus(429);
});

it('exposes per-user rate limit headers on authenticated routes', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertHeader('X-RateLimit-Limit', 120);
});
