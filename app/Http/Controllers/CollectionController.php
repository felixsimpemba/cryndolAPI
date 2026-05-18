<?php

namespace App\Http\Controllers;

use App\Models\LoanPayment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    /**
     * Display a listing of all payments (Collections).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = LoanPayment::query()
            ->where('business_id', $user->business_id)
            ->with(['loan.customer']);

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // General search (Loan number or Customer name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('loan', function($lq) use ($search) {
                    $lq->where('loan_number', 'like', "%{$search}%")
                       ->orWhereHas('customer', function($cq) use ($search) {
                           $cq->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                       });
                })->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->paginate($request->query('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Get summary statistics for collections.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $businessId = $user->business_id;

        $today = now()->toDateString();
        $startOfWeek = now()->startOfWeek()->toDateString();

        $collectedToday = LoanPayment::where('business_id', $businessId)
            ->whereDate('payment_date', $today)
            ->sum('amount_paid');

        $collectedThisWeek = LoanPayment::where('business_id', $businessId)
            ->whereDate('payment_date', '>=', $startOfWeek)
            ->sum('amount_paid');

        $totalCollected = LoanPayment::where('business_id', $businessId)
            ->sum('amount_paid');

        return response()->json([
            'success' => true,
            'data' => [
                'collected_today' => (float)$collectedToday,
                'collected_this_week' => (float)$collectedThisWeek,
                'total_collected' => (float)$totalCollected,
            ]
        ]);
    }
}
