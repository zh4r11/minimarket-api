<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        /** @var User $user */
        $user = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->sendEmailVerificationNotification();

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function verifyEmail(User $user): string
    {
        if ($user->hasVerifiedEmail()) {
            return 'already_verified';
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return 'verified';
    }

    public function resendVerificationEmail(string $email): string
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user) {
            return 'not_found';
        }

        if ($user->hasVerifiedEmail()) {
            return 'already_verified';
        }

        $user->sendEmailVerificationNotification();

        return 'sent';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function forgotPassword(array $data): bool
    {
        $status = Password::sendResetLink($data);

        return $status === Password::RESET_LINK_SENT;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        return match ($status) {
            Password::PASSWORD_RESET => 'reset',
            Password::INVALID_TOKEN => 'invalid_token',
            Password::INVALID_USER => 'invalid_user',
            default => 'error',
        };
    }
}
