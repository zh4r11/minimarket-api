<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\V1\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\ResendVerificationRequest;
use App\Http\Requests\Api\V1\ResetPasswordRequest;
use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new user.
     *
     * Creates a new user account and sends an email verification notification.
     * Returns the created user resource along with a Sanctum API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->created([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'User registered successfully. Please check your email to verify your account.');
    }

    /**
     * Login user.
     *
     * Authenticates the user with email and password.
     * Returns the authenticated user resource and a Sanctum API token on success.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        if (! $result) {
            return $this->unauthorized('Invalid credentials');
        }

        return $this->success([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 'Login successful');
    }

    /**
     * Logout user.
     *
     * Revokes the current user's access token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authService->logout($user);

        return $this->success(message: 'Logged out successfully');
    }

    /**
     * Get authenticated user.
     *
     * Returns the currently authenticated user's profile data.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * Verify email address.
     *
     * Marks the authenticated user's email as verified using the signed URL hash.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $status = $this->authService->verifyEmail($user);

        return match ($status) {
            'already_verified' => $this->success(message: 'Email already verified'),
            default => $this->success(message: 'Email verified successfully'),
        };
    }

    /**
     * Resend email verification.
     *
     * Sends a new email verification notification to the given email address.
     */
    public function resendVerificationEmail(ResendVerificationRequest $request): JsonResponse
    {
        $status = $this->authService->resendVerificationEmail($request->email);

        return match ($status) {
            'not_found' => $this->notFound('User not found'),
            'already_verified' => $this->error('Email already verified', 400),
            default => $this->success(message: 'Verification email sent successfully'),
        };
    }

    /**
     * Send password reset link.
     *
     * Sends a password reset link to the provided email address.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $sent = $this->authService->forgotPassword($request->only('email'));

        if ($sent) {
            return $this->success(message: 'Password reset link sent to your email');
        }

        return $this->error('Unable to send reset link', 500);
    }

    /**
     * Reset password.
     *
     * Resets the user's password using the token received via email.
     * All existing tokens are revoked upon a successful reset.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword(
            $request->only('email', 'password', 'password_confirmation', 'token')
        );

        return match ($status) {
            'reset' => $this->success(message: 'Password reset successfully'),
            'invalid_token' => $this->error('Invalid or expired reset token', 400),
            'invalid_user' => $this->error('User not found', 400),
            default => $this->error('Unable to reset password', 400),
        };
    }
}
