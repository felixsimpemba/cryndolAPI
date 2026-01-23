<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Carbon\Carbon;
use OpenApi\Attributes as OA;

class CapitalController extends Controller
{
    /**
     * Add Working Capital
     * POST /capital/add
     */
    #[OA\Post(
        path: '/capital/add',
        summary: 'Add Working Capital',
        description: 'Inject additional capital into the business',
        tags: ['Capital'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 10000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Capital added successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Capital added successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'working_capital', type: 'number', format: 'float', example: 60000.00)
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')
            )
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->user();
        $amount = $request->amount;

        // Update User Capital
        $user->working_capital += $amount;
        $user->save();

        // Create Transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'inflow',
            'category' => 'capital_injection',
            'amount' => $amount,
            'description' => 'Additional Capital Injection',
            'occurred_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Capital added successfully',
            'data' => [
                'working_capital' => (float) $user->working_capital,
            ]
        ], 200);
    }

    /**
     * Update Working Capital
     * PUT /capital/update
     */
    #[OA\Put(
        path: '/capital/update',
        summary: 'Update Working Capital',
        description: 'Update the total working capital amount. Adjusts via transaction automatically.',
        tags: ['Capital'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 75000.00)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Capital updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Capital updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'working_capital', type: 'number', format: 'float', example: 75000.00)
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function update(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $user = $request->user();
        $newAmount = $request->amount;
        $currentAmount = $user->working_capital;
        $diff = $newAmount - $currentAmount;

        if ($diff == 0) {
            return response()->json([
                'success' => true,
                'message' => 'No changes made',
                'data' => ['working_capital' => (float) $currentAmount]
            ]);
        }

        // Update User Capital
        $user->working_capital = $newAmount;
        $user->save();

        // Create transaction for the difference
        Transaction::create([
            'user_id' => $user->id,
            'type' => $diff > 0 ? 'inflow' : 'outflow',
            'category' => $diff > 0 ? 'capital_injection' : 'capital_withdrawal',
            'amount' => abs($diff),
            'description' => $diff > 0 ? 'Capital Adjustment (Addition)' : 'Capital Adjustment (Reduction)',
            'occurred_at' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Capital updated successfully',
            'data' => [
                'working_capital' => (float) $user->working_capital,
            ]
        ], 200);
    }
}
