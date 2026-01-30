<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPersonalRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpVerificationMail;
use Carbon\Carbon;
use App\Models\Transaction;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Register Personal Profile
     * POST /auth/register/personal
     */
    #[OA\Post(
        path: '/auth/register/personal',
        summary: 'Register Personal Profile',
        description: 'Creates a new user account with personal information',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['fullName', 'email', 'phoneNumber', 'password', 'acceptTerms'],
                properties: [
                    new OA\Property(property: 'fullName', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'phoneNumber', type: 'string', example: '+1234567890'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePassword123!'),
                    new OA\Property(property: 'acceptTerms', type: 'boolean', example: true),
                    new OA\Property(property: 'working_capital', type: 'number', format: 'float', example: 50000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Personal profile created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Personal profile created successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                                new OA\Property(property: 'tokens', ref: '#/components/schemas/TokenPair')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function registerPersonal(RegisterPersonalRequest $request): JsonResponse
    {
        try {
            $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $user = User::create([
                'fullName' => $request->fullName,
                'email' => $request->email,
                'phoneNumber' => $request->phoneNumber,
                'password' => $request->password,
                'acceptTerms' => $request->acceptTerms,
                'otp_code' => $otp,
                'otp_expires_at' => Carbon::now()->addMinutes(10),
                'working_capital' => $request->working_capital ?? 0,
            ]);

            if ($request->filled('working_capital') && $request->working_capital > 0) {
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'inflow',
                    'category' => 'capital_injection',
                    'amount' => $request->working_capital,
                    'description' => 'Initial Working Capital',
                    'occurred_at' => Carbon::now(),
                ]);
            }

            // Send OTP Email
            Mail::to($user->email)->send(new OtpVerificationMail($otp));

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Please check your email for the verification code.',
                'data' => [
                    'verification_required' => true,
                    'email' => $user->email,
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Registration failed');
        }
    }

    #[OA\Post(
        path: '/auth/verify-otp',
        summary: 'Verify OTP',
        description: 'Verify the email address using the code sent via email',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'code', type: 'string', example: '123456')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Verification successful',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenPair')
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid code or expired',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        if (!$user->otp_code || $user->otp_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code expired'], 400);
        }

        // Verify user
        $user->email_verified_at = Carbon::now();
        $user->last_login = Carbon::now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        // Login user
        $tokens = $this->tokenService->createTokens($user);

        // Generate personalized greeting for first login
        $greeting = $this->getTimeBasedGreeting();
        $welcomeMessage = $greeting . ', ' . $user->fullName . '! Welcome to Cryndol.';

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'greeting' => $welcomeMessage,
            'data' => [
                'user' => [
                    'id' => 'user_' . $user->id,
                    'fullName' => $user->fullName,
                    'email' => $user->email,
                    'is_verified' => true,
                    'hasBusinessProfile' => $user->hasBusinessProfile,
                ],
                'tokens' => $tokens,
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/auth/resend-otp',
        summary: 'Resend OTP',
        description: 'Resend a new verification code',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OTP resent',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'success', type: 'boolean', example: true)]
                )
            )
        ]
    )]
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new OtpVerificationMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'Verification code resent',
        ], 200);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login',
        description: 'Authenticate user with email and password',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePassword123!')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                                new OA\Property(property: 'tokens', ref: '#/components/schemas/TokenPair')
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = $this->tokenService->authenticateUser($request->email, $request->password);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Update last login timestamp
            $user->last_login = Carbon::now();
            $user->save();

            $tokens = $this->tokenService->createTokens($user);

            // Generate personalized greeting
            $greeting = $this->getTimeBasedGreeting();
            $welcomeMessage = $greeting . ', ' . $user->fullName . '! Welcome back.';

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'greeting' => $welcomeMessage,
                'data' => [
                    'user' => [
                        'id' => 'user_' . $user->id,
                        'fullName' => $user->fullName,
                        'email' => $user->email,
                        'phoneNumber' => $user->phoneNumber,
                        'hasBusinessProfile' => $user->hasBusinessProfile,
                        'createdAt' => $user->created_at->toISOString(),
                        'updatedAt' => $user->updated_at->toISOString(),
                    ],
                    'tokens' => $tokens,
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Login failed');
        }
    }

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Refresh access token',
        description: 'Refresh access token using a valid refresh token',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['refreshToken'],
                properties: [
                    new OA\Property(property: 'refreshToken', type: 'string', example: 'eyJhbGciOi...')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tokens refreshed',
                content: new OA\JsonContent(ref: '#/components/schemas/TokenPair')
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid refresh token',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $tokens = $this->tokenService->refreshAccessToken($request->refreshToken);

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, $e->getMessage(), 401);
        }
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Logout',
        description: 'Revoke all tokens for the authenticated user',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Logout successful')
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->tokenService->revokeAllTokens($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Logout failed');
        }
    }

    #[OA\Get(
        path: '/auth/profile',
        summary: 'Get user profile',
        description: 'Returns the authenticated user profile (and business profile if available)',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User profile',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                        new OA\Property(property: 'businessProfile', ref: '#/components/schemas/BusinessProfile', nullable: true)
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load('businessProfile');

            $responseData = [
                'user' => [
                    'id' => 'user_' . $user->id,
                    'fullName' => $user->fullName,
                    'email' => $user->email,
                    'phoneNumber' => $user->phoneNumber,
                    'hasBusinessProfile' => $user->hasBusinessProfile,
                    'createdAt' => $user->created_at->toISOString(),
                    'updatedAt' => $user->updated_at->toISOString(),
                    'working_capital' => (float) $user->working_capital,
                ],
            ];

            if ($user->businessProfile) {
                $responseData['businessProfile'] = [
                    'id' => 'business_' . $user->businessProfile->id,
                    'businessName' => $user->businessProfile->businessName,
                    'createdAt' => $user->businessProfile->created_at->toISOString(),
                    'updatedAt' => $user->businessProfile->updated_at->toISOString(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to retrieve profile');
        }
    }
    #[OA\Post(
        path: '/auth/forgot-password',
        summary: 'Forgot Password',
        description: 'Initiate password reset process by sending an OTP to the email',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OTP sent',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'success', type: 'boolean', example: true)]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new \App\Mail\PasswordResetMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'Password reset code sent to your email',
        ], 200);
    }

    #[OA\Post(
        path: '/auth/reset-password',
        summary: 'Reset Password',
        description: 'Reset password using the OTP',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'code', type: 'string', example: '123456'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePassword123!')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password reset successful',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'success', type: 'boolean', example: true)]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid code or expired',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->otp_code || $user->otp_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code expired'], 400);
        }

        // Password is automatically hashed by the 'hashed' cast in the User model
        $user->password = $request->password;
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now login with your new password.',
        ], 200);
    }

    #[OA\Post(
        path: '/auth/request-deletion-otp',
        summary: 'Request Account Deletion OTP',
        description: 'Send an OTP to the user email for account deletion verification',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'OTP sent',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'success', type: 'boolean', example: true)]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'User not found',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function requestDeletionOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->otp_code = $otp;
        $user->otp_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        Mail::to($user->email)->send(new OtpVerificationMail($otp));

        return response()->json([
            'success' => true,
            'message' => 'Verification code sent to your email',
        ], 200);
    }

    #[OA\Post(
        path: '/auth/confirm-deletion',
        summary: 'Confirm Account Deletion',
        description: 'Permanently delete account using the OTP',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john.doe@example.com'),
                    new OA\Property(property: 'code', type: 'string', example: '123456')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account deleted successfully',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'success', type: 'boolean', example: true)]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid code or expired',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function confirmDeletion(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if (!$user->otp_code || $user->otp_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code expired'], 400);
        }

        // Revoke all tokens
        if ($this->tokenService) {
            $this->tokenService->revokeAllTokens($user);
        }

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ], 200);
    }

    /**
     * Get time-based greeting
     */
    private function getTimeBasedGreeting(): string
    {
        $hour = Carbon::now()->hour;

        if ($hour >= 5 && $hour < 12) {
            return 'Good morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Good afternoon';
        } elseif ($hour >= 17 && $hour < 21) {
            return 'Good evening';
        } else {
            return 'Good night';
        }
    }
}
