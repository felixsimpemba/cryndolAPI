<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPersonalRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\User;
use App\Models\Business;
use App\Models\Transaction; // I'll assume Transaction model exists for capital tracking
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\OtpVerificationMail;
use Carbon\Carbon;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    #[OA\Post(
        path: '/auth/register/personal',
        summary: 'Register Personal Profile',
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
            new OA\Response(response: 201, description: 'Personal profile created successfully'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    public function registerPersonal(RegisterPersonalRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if ($user) {
                if ($user->email_verified_at) {
                    return response()->json([
                        'message' => 'The email has already been taken.',
                        'errors' => ['email' => ['The email has already been taken.']]
                    ], 422);
                }

                $phoneExists = User::where('phone', $request->phoneNumber)
                    ->where('id', '!=', $user->id)
                    ->exists();

                if ($phoneExists) {
                    return response()->json([
                        'message' => 'The phone number has already been taken.',
                        'errors' => ['phoneNumber' => ['The phone number has already been taken.']]
                    ], 422);
                }

                $businessId = $user->business_id;
                if (!$businessId) {
                    $business = Business::create([
                        'name' => $request->fullName . ' Business',
                        'email' => $request->email,
                        'is_active' => true,
                        'registration_number' => 'REG-' . strtoupper(Str::random(6)),
                        'working_capital' => $request->working_capital ?? 0,
                    ]);
                    $businessId = $business->id;
                }

                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

                $user->update([
                    'full_name' => $request->fullName,
                    'phone' => $request->phoneNumber,
                    'password' => $request->password, 
                    'otp_code' => $otp,
                    'otp_expires_at' => Carbon::now()->addMinutes(10),
                    'business_id' => $businessId,
                    'role' => 'SUPER_ADMIN',
                ]);

            } else {
                $phoneExists = User::where('phone', $request->phoneNumber)->exists();
                if ($phoneExists) {
                    return response()->json([
                        'message' => 'The phone number has already been taken.',
                        'errors' => ['phoneNumber' => ['The phone number has already been taken.']]
                    ], 422);
                }

                $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

                $business = Business::create([
                    'name' => $request->fullName . ' Business',
                    'email' => $request->email,
                    'is_active' => true,
                    'registration_number' => 'REG-' . strtoupper(Str::random(6)),
                    'working_capital' => $request->working_capital ?? 0,
                ]);

                $user = User::create([
                    'full_name' => $request->fullName,
                    'email' => $request->email,
                    'phone' => $request->phoneNumber,
                    'password' => $request->password,
                    'otp_code' => $otp,
                    'otp_expires_at' => Carbon::now()->addMinutes(10),
                    'business_id' => $business->id,
                    'role' => 'SUPER_ADMIN',
                ]);
            }

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
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'code'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'code', type: 'string', example: '123456')
                ]
            )
        )
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

        $user->load('business');

        if ($user->email_verified_at) {
            return response()->json(['success' => false, 'message' => 'Email already verified'], 400);
        }

        if (!$user->otp_code || $user->otp_code !== $request->code) {
            return response()->json(['success' => false, 'message' => 'Invalid verification code'], 400);
        }

        if (Carbon::now()->gt($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Verification code expired'], 400);
        }

        $user->email_verified_at = Carbon::now();
        $user->last_login = Carbon::now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        $tokens = $this->tokenService->createTokens($user, false);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_verified' => true,
                    'business_id' => $user->business_id,
                    'permissions' => $user->permissions ?? $user->getDefaultPermissions(),
                    'hasBusinessProfile' => (bool)($user->business?->address),
                ],
                'tokens' => $tokens,
            ],
        ], 200);
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Login',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password')
                ]
            )
        )
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        Log::info('User found: ' . $request->email);
        try {
            $user = $this->tokenService->authenticateUser($request->email, $request->password);
            

            if (!$user) {
                Log::info('User not found: ' . $request->email);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            $user->load('business');
            $user->last_login = Carbon::now();
            $user->save();
            Log::info('User found: ' . $user->full_name);

            $tokens = $this->tokenService->createTokens($user, (bool) $request->remember);
            Log::info('User found: ' . $user->full_name);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'fullName' => $user->full_name,
                        'email' => $user->email,
                        'phoneNumber' => $user->phone,
                        'role' => $user->role,
                        'business_id' => $user->business_id,
                        'createdAt' => $user->created_at->toISOString(),
                        'updatedAt' => $user->updated_at->toISOString(),
                        'working_capital' => (float) ($user->business?->working_capital ?? 0),
                        'permissions' => $user->permissions ?? $user->getDefaultPermissions(),
                        'hasBusinessProfile' => (bool)($user->business?->address),
                    ],
                    'tokens' => $tokens,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return $this->logAndResponseError($e, 'Login failed');

        }
    }

    #[OA\Get(
        path: '/auth/profile',
        summary: 'Get user profile',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]]
    )]
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->load('business');

            $responseData = [
                'user' => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'phoneNumber' => $user->phone,
                    'role' => $user->role,
                    'createdAt' => $user->created_at?->toISOString(),
                    'updatedAt' => $user->updated_at?->toISOString(),
                    'permissions' => $user->permissions ?? $user->getDefaultPermissions(),
                    'hasBusinessProfile' => (bool)($user->business?->address),
                ],
            ];

            if ($user->business) {
                $responseData['businessProfile'] = [
                    'id' => $user->business->id,
                    'businessName' => $user->business->name,
                    'createdAt' => $user->business->created_at?->toISOString(),
                    'updatedAt' => $user->business->updated_at?->toISOString(),
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

    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['otp_code' => $otp, 'otp_expires_at' => Carbon::now()->addMinutes(10)]);
        Mail::to($user->email)->send(new OtpVerificationMail($otp));

        return response()->json(['success' => true, 'message' => 'Verification code resent'], 200);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);

        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['otp_code' => $otp, 'otp_expires_at' => Carbon::now()->addMinutes(10)]);
        Mail::to($user->email)->send(new \App\Mail\PasswordResetMail($otp));

        return response()->json(['success' => true, 'message' => 'Password reset code sent to your email'], 200);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) return response()->json(['success' => false, 'message' => 'User not found'], 404);
        if (!$user->otp_code || $user->otp_code !== $request->code) return response()->json(['success' => false, 'message' => 'Invalid code'], 400);

        $user->update(['password' => $request->password, 'otp_code' => null, 'otp_expires_at' => null]);
        return response()->json(['success' => true, 'message' => 'Password reset successful'], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();
        return response()->json(['success' => true, 'message' => 'Logout successful'], 200);
    }

}
