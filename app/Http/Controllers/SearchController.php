<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Perform a global search across customers and loans
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $search = $request->query('query');
        $user = $request->user();
        $businessId = $user->business_id;

        if (!$search || strlen($search) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => [],
                    'loans' => [],
                ]
            ]);
        }

        // 1. Search Customers
        $customers = Customer::where('business_id', $businessId)
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                  ->orWhere('last_name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            })
            ->limit(5)
            ->get(['id', 'first_name', 'last_name', 'email']);

        // 2. Search Loans
        $loans = Loan::where('business_id', $businessId)
            ->where('loan_number', 'like', "%$search%")
            ->with('customer:id,first_name,last_name')
            ->limit(5)
            ->get(['id', 'loan_number', 'customer_id', 'status', 'principal_amount']);

        return response()->json([
            'success' => true,
            'data' => [
                'customers' => $customers,
                'loans' => $loans,
            ]
        ]);
    }
}
