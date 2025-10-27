<?php

namespace App\Http\Controllers;

use App\Models\Borrower;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

class CustomersController extends Controller
{
    #[OA\Get(path: '/customers', summary: 'List customers', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK')])]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Borrower::query()->where('user_id', $user->id);
        if ($search = $request->query('search')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('fullName', 'like', "%$search%")->orWhere('email', 'like', "%$search%");
            });
        }
        $customers = $q->orderByDesc('id')->paginate($request->query('per_page', 15));
        return response()->json(['success' => true, 'data' => $customers]);
    }

    #[OA\Post(path: '/customers', summary: 'Create customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 422, description: 'Validation error')])]
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'fullName' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('borrowers','email')->where('user_id',$user->id)],
            'phoneNumber' => ['nullable','string','max:50'],
        ]);
        $data['user_id'] = $user->id;
        $customer = Borrower::create($data);
        return response()->json(['success' => true, 'message' => 'Customer created', 'data' => $customer], 201);
    }

    #[OA\Get(path: '/customers/{id}', summary: 'Get customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Borrower::where('user_id', $user->id)->findOrFail($id);
        return response()->json(['success' => true, 'data' => $customer]);
    }

    #[OA\Put(path: '/customers/{id}', summary: 'Update customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found'), new OA\Response(response: 422, description: 'Validation error')])]
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Borrower::where('user_id', $user->id)->findOrFail($id);
        $data = $request->validate([
            'fullName' => ['sometimes','string','max:255'],
            'email' => ['sometimes','email','max:255', Rule::unique('borrowers','email')->ignore($customer->id)->where('user_id',$user->id)],
            'phoneNumber' => ['sometimes','nullable','string','max:50'],
        ]);
        $customer->update($data);
        return response()->json(['success' => true, 'message' => 'Customer updated', 'data' => $customer]);
    }

    #[OA\Delete(path: '/customers/{id}', summary: 'Delete customer', tags: ['Customers'], security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')])]
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $customer = Borrower::where('user_id', $user->id)->findOrFail($id);
        $customer->delete();
        return response()->json(['success' => true, 'message' => 'Customer deleted']);
    }
}
