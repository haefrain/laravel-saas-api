<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

/**
 * Creates a new user account. Business logic lives in single-purpose Actions
 * so controllers stay thin and the behaviour is unit-testable in isolation.
 */
class RegisterUser
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        // The User model casts `password` as `hashed`, so it is hashed on save.
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
    }
}
