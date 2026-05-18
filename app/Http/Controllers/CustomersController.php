<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class CustomersController extends Controller
{
    #[OA\Get(path: '/customers', summary: 'List customers', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Customer::query()->where('business_id', $user->business_id);
        
        if ($search = $request->query('search')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('first_name', 'like', "%$search%")
                   ->orWhere('last_name', 'like', "%$search%")
                   ->orWhere('email', 'like', "%$search%");
            });
        }
        
        $customers = $q->orderByDesc('created_at')->paginate($request->query('per_page', 15));
        return response()->json(['success' => true, 'data' => $customers]);
    }

    #[OA\Post(path: '/customers', summary: 'Create customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $data['business_id'] = $user->business_id;
        $data['created_by'] = $user->id;
        
        $customer = Customer::create($data);
        return response()->json(['success' => true, 'message' => 'Customer created', 'data' => $customer], 201);
    }

    #[OA\Get(path: '/customers/{id}', summary: 'Get customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::where('business_id', $user->business_id)
            ->findOrFail($id);
        return response()->json(['success' => true, 'data' => $customer]);
    }

    #[OA\Put(path: '/customers/{id}', summary: 'Update customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function update(CustomerUpdateRequest $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::where('business_id', $user->business_id)->findOrFail($id);
        $data = $request->validated();
        $customer->update($data);
        return response()->json(['success' => true, 'message' => 'Customer updated', 'data' => $customer]);
    }

    #[OA\Get(path: '/customers/verify-nrc/{nrc}', summary: 'Verify NRC with ZRA', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function verifyNrc(Request $request, $nrc): JsonResponse
    {
        try {
            $response = Http::asMultipart()->post('https://portal.zra.org.zm/registration/isNrcValid', [
                'nrcNumber' => $nrc
            ]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to ZRA portal'
                ], 502);
            }

            $data = $response->json();
            $message = $data['message'] ?? '';
            
            // Extract Name and TPIN from message
            // Example: "This NRC is already in use by another Taxpayer: Felix Simpemba Simpemba with TPIN: 2001232982"
            $pattern = '/Taxpayer:\s*(.*?)\s*with\s*TPIN:\s*(\d+)/i';
            
            if (preg_match($pattern, $message, $matches)) {
                return response()->json([
                    'success' => true,
                    'verified' => true,
                    'name' => trim($matches[1]),
                    'tpin' => $matches[2],
                    'raw_message' => $message
                ]);
            }

            return response()->json([
                'success' => true,
                'verified' => false,
                'message' => 'NRC not found or no associated taxpayer yet.',
                'raw_message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error during NRC verification: ' . $e->getMessage()
            ], 500);
        }
    }

    #[OA\Delete(path: '/customers/{id}', summary: 'Delete customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Customer::where('business_id', $user->business_id)->findOrFail($id);
        $customer->delete();
        return response()->json(['success' => true, 'message' => 'Customer deleted']);
    }
}
