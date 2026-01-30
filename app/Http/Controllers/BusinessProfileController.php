<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileRequest;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use OpenApi\Attributes as OA;

class BusinessProfileController extends Controller
{
    #[OA\Post(
        path: '/auth/business-profile',
        summary: 'Create business profile',
        tags: ['Business Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['businessName'],
                properties: [
                    new OA\Property(property: 'businessName', type: 'string', example: 'Acme Inc.')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Business profile created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Business profile created successfully'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'businessProfile', ref: '#/components/schemas/BusinessProfile')
                        ])
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Already exists or validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
            )
        ]
    )]
    public function create(BusinessProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user already has a business profile
            if ($user->businessProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business profile already exists',
                ], 400);
            }

            $businessProfile = BusinessProfile::create([
                'user_id' => $user->id,
                'businessName' => $request->businessName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Business profile created successfully',
                'data' => [
                    'businessProfile' => [
                        'id' => 'business_' . $businessProfile->id,
                        'businessName' => $businessProfile->businessName,
                        'createdAt' => $businessProfile->created_at->toISOString(),
                        'updatedAt' => $businessProfile->updated_at->toISOString(),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to create business profile');
        }
    }

    #[OA\Put(
        path: '/auth/business-profile',
        summary: 'Update business profile',
        tags: ['Business Profile'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['businessName'],
                properties: [
                    new OA\Property(property: 'businessName', type: 'string', example: 'New Name LLC')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Business profile updated', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Business profile updated successfully'),
                    new OA\Property(property: 'data', properties: [
                        new OA\Property(property: 'businessProfile', ref: '#/components/schemas/BusinessProfile')
                    ])
                ]
            )),
            new OA\Response(response: 404, description: 'Business profile not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function update(BusinessProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $businessProfile = $user->businessProfile;

            if (!$businessProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business profile not found',
                ], 404);
            }

            $businessProfile->update([
                'businessName' => $request->businessName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Business profile updated successfully',
                'data' => [
                    'businessProfile' => [
                        'id' => 'business_' . $businessProfile->id,
                        'businessName' => $businessProfile->businessName,
                        'createdAt' => $businessProfile->created_at->toISOString(),
                        'updatedAt' => $businessProfile->updated_at->toISOString(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to update business profile');
        }
    }

    #[OA\Delete(
        path: '/auth/business-profile',
        summary: 'Delete business profile',
        tags: ['Business Profile'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Business profile deleted', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'success', type: 'boolean', example: true),
                    new OA\Property(property: 'message', type: 'string', example: 'Business profile deleted successfully')
                ]
            )),
            new OA\Response(response: 404, description: 'Business profile not found', content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse'))
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $businessProfile = $user->businessProfile;

            if (!$businessProfile) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business profile not found',
                ], 404);
            }

            $businessProfile->delete();

            return response()->json([
                'success' => true,
                'message' => 'Business profile deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to delete business profile');
        }
    }
}
