<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusinessProfileRequest;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class BusinessProfileController extends Controller
{
    public function create(BusinessProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business already exists',
                ], 400);
            }

            $business = Business::create([
                'name' => $request->businessName,
                'email' => $user->email,
            ]);

            $user->update(['business_id' => $business->id]);

            return response()->json([
                'success' => true,
                'message' => 'Business profile created successfully',
                'data' => [
                    'businessProfile' => [
                        'id' => $business->id,
                        'businessName' => $business->name,
                        'createdAt' => $business->created_at->toISOString(),
                        'updatedAt' => $business->updated_at->toISOString(),
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to create business profile');
        }
    }

    public function update(BusinessProfileRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $user->business;

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business profile not found',
                ], 404);
            }

            $business->update([
                'name' => $request->businessName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Business profile updated successfully',
                'data' => [
                    'businessProfile' => [
                        'id' => $business->id,
                        'businessName' => $business->name,
                        'createdAt' => $business->created_at->toISOString(),
                        'updatedAt' => $business->updated_at->toISOString(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to update business profile');
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $business = $user->business;

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business profile not found',
                ], 404);
            }

            $business->delete();

            return response()->json([
                'success' => true,
                'message' => 'Business profile deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return $this->logAndResponseError($e, 'Failed to delete business profile');
        }
    }
}
