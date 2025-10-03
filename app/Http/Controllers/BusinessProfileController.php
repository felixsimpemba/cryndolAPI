<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileRequest;
use App\Models\BusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessProfileController extends Controller
{
    /**
     * Create Business Profile
     * POST /auth/business-profile
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to create business profile',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Business Profile
     * PUT /auth/business-profile
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to update business profile',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Business Profile
     * DELETE /auth/business-profile
     */
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete business profile',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
}
