<?php

namespace App\Http\Controllers;

use App\Models\LoanProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = LoanProduct::all();
        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'interest_type' => 'required|in:flat,reducing_balance,tiered',
            'interest_rate' => 'required|numeric|min:0',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'required|numeric|gt:min_amount',
            'min_term' => 'required|integer|min:1',
            'max_term' => 'required|integer|gte:min_term',
            'term_unit' => 'required|in:days,weeks,months',
            'repayment_frequency' => 'required|in:weekly,bi_weekly,monthly',
            'grace_period' => 'integer|min:0',
            'processing_fee_type' => 'in:fixed,percentage',
            'processing_fee_value' => 'numeric|min:0',
            'late_penalty_type' => 'in:fixed,percentage',
            'late_penalty_value' => 'numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = LoanProduct::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Loan product created successfully',
            'data' => $product
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = LoanProduct::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $product]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $product = LoanProduct::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'interest_type' => 'sometimes|in:flat,reducing_balance,tiered',
            'interest_rate' => 'sometimes|numeric|min:0',
            'min_amount' => 'sometimes|numeric|min:0',
            'max_amount' => 'sometimes|numeric|gt:min_amount',
            'min_term' => 'sometimes|integer|min:1',
            'max_term' => 'sometimes|integer|gte:min_term',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $product->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Loan product updated successfully',
            'data' => $product
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = LoanProduct::find($id);

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        // Ideally check for existing loans before deletion
        // For now, we will just deactivate
        // delete() would be: $product->delete();

        $product->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Loan product deleted successfully'
        ]);
    }
}
