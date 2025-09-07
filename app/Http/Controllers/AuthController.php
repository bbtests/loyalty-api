<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @group Authentication
 *
 * APIs for user authentication, registration, and account management.
 */
class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::where('email', $validated['email'])
                ->first();

            if (! $user || ! Hash::check($validated['password'], $user->password)) {
                return $this->validationError(
                    'The provided credentials are incorrect.',
                    'email'
                );
            }

            // Check if api key matches
            if (($api_key = $request->header('x-api-key')) && ! \in_array($request->header('x-api-key'), config('constants.api_keys'), true)) {
                return $this->errorResponse(
                    'Missing or invalid API key.',
                    400,
                    [
                        [
                            'field' => 'api_key',
                            'message' => 'Missing or invalid API key.',
                        ],
                    ]
                );
            }

            // Revoke all existing tokens
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $expiresIn = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

            return $this->successData(
                [
                    'item' => [
                        'user' => new UserResource($user),
                        'token' => $token,
                        'expires_in' => $expiresIn,
                    ],
                ],
                'Login successful.',
                200,
                [
                    'auth' => [
                        'token_type' => 'Bearer',
                        'expires_in_seconds' => $expiresIn,
                    ],
                ]
            );
        } catch (ValidationException $e) {
            return $this->validationError(
                'The provided credentials are incorrect.',
                'email',
                $e->errors()
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Login failed. Please try again later.',
                422,
                [
                    [
                        'field' => 'login',
                        'message' => 'Login failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return $this->successMessage('Logged out successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Logout failed. Please try again later.',
                422,
                [
                    [
                        'field' => 'logout',
                        'message' => 'Logout failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $tokensCount = $request->user()->tokens()->count();
            $request->user()->tokens()->delete();

            return $this->successMessage('Logged out from all devices successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Logout from all devices failed. Please try again later.',
                422,
                [
                    [
                        'field' => 'logout',
                        'message' => 'Logout from all devices failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            return $this->successItem(
                new UserResource($request->user()),
                'User profile retrieved successfully.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'User profile retrieval failed. Please try again later.',
                422,
                [
                    [
                        'field' => 'user',
                        'message' => 'User profile retrieval failed due to an internal error.',
                    ],
                ]
            );
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $user = User::where('email', $validated['email'])
                ->first();

            if (! $user) {
                return $this->successMessage(
                    'If an account with that email exists, we have sent a password reset link.'
                );
            }

            $code = \mt_rand(111111, 999999);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'token' => $code,
                    'created_at' => now(),
                ]
            );

            $user->notify(new PasswordResetNotification($code));

            return $this->successData(
                [
                    'item' => null,
                ],
                'Password reset code has been sent to your email.',
                200,
                [
                    'reset' => [
                        'check_spam_folder' => true,
                    ],
                ]
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to send password reset email. Please try again later.',
                422,
                [
                    [
                        'field' => 'email',
                        'message' => 'Email delivery failed.',
                    ],
                ]
            );
        }
    }

    public function validateResetCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_code' => 'required|int',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->where('token', $validated['reset_code'])
            ->first();

        if (! $resetRecord) {
            throw new BadRequestHttpException('The reset code is invalid.');
        }

        return $this->successData(
            [
                'item' => [
                    'reset_code' => $validated['reset_code'],
                ],
            ],
            'Code validated successfully.',
            200,
            [
                'pagination' => null,
            ]
        );
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {

        $validated = $request->validated();

        $this->validateResetCode($request);

        $user = User::where('email', $validated['email'])
            ->first();

        $user->update(['password' => Hash::make($validated['password'])]);

        DB::table('password_reset_tokens')
            ->where('email', $validated['email'])
            ->delete();

        return $this->successMessage('Password reset successfully. Please log in with your new password.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::user();

        try {
            $user = Auth::user();
            $validated = $request->validated();

            if (! Hash::check($validated['current_password'], $user->password)) {
                return $this->validationError(
                    'The current password provided is incorrect.',
                    'password'
                );
            }

            $user->update(['password' => Hash::make($validated['new_password'])]);

            return $this->successMessage('Password reset successfully. The next time you log in, use your new password.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to change password', 422, [
                $e->getMessage(),
            ]);
        }
    }

    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->currentAccessToken()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;
            $expiresIn = config('sanctum.expiration') ? config('sanctum.expiration') * 60 : null;

            return $this->successData(
                [
                    'item' => [
                        'token' => $token,
                        'expires_in' => $expiresIn,
                    ],
                ],
                'Token refreshed successfully.',
                200,
                [
                    'auth' => [
                        'token_type' => 'Bearer',
                        'expires_in_seconds' => $expiresIn,
                        'refreshed_at' => now()->toISOString(),
                    ],
                ]
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to refresh token. Please try again later.',
                422,
                [
                    [
                        'field' => 'token',
                        'message' => 'Token refresh failed.',
                    ],
                ]
            );
        }
    }
}
