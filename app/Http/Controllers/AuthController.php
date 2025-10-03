<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterPersonalRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Models\User;
use App\Services\TokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
                    new OA\Property(property: 'acceptTerms', type: 'boolean', example: true)
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
            $user = User::create([
                'fullName' => $request->fullName,
                'email' => $request->email,
                'phoneNumber' => $request->phoneNumber,
                'password' => $request->password,
                'acceptTerms' => $request->acceptTerms,
            ]);

            $tokens = $this->tokenService->createTokens($user);

            return response()->json([
                'success' => true,
                'message' => 'Personal profile created successfully',
                'data' => [
                    'user' => [
                        'id' => 'user_' . $user->id,
                        'fullName' => $user->fullName,
                        'email' => $user->email,
                        'phoneNumber' => $user->phoneNumber,
                        'createdAt' => $user->created_at->toISOString(),
                        'updatedAt' => $user->updated_at->toISOString(),
                    ],
                    'tokens' => $tokens,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login
     * POST /auth/login
     */
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

            $tokens = $this->tokenService->createTokens($user);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
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
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Token
     * POST /auth/refresh
     */
    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $tokens = $this->tokenService->refreshAccessToken($request->refreshToken);

            return response()->json([
                'success' => true,
                'data' => $tokens,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * Logout
     * POST /auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->tokenService->revokeAllTokens($request->user());

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get User Profile
     * GET /auth/profile
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
