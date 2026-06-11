<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentication
 *
 * Register, log in and manage the current token session.
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        /** @var array{name: string, email: string, password: string} $data */
        $data = $request->validated();
        $user = $action->handle($data);

        return $this->tokenResponse($user, Response::HTTP_CREATED);
    }

    /**
     * Log in
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $email = (string) $request->validated('email');
        $password = (string) $request->validated('password');

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, (string) $user->password)) {
            // Same generic error whether the email exists or not (no user enumeration).
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return $this->tokenResponse($user, Response::HTTP_OK);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    public function logout(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        // The route is token-guarded, so the current access token is a real,
        // persisted PersonalAccessToken — revoking it logs out this device only.
        $user->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function tokenResponse(User $user, int $status): JsonResponse
    {
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ], $status);
    }
}
