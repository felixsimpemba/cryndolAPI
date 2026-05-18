<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;

class ReportingController extends Controller
{
    protected $reportingService;

    public function __construct(ReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Get high-level BI metrics for the business.
     */
    public function overview(Request $request): JsonResponse
    {
        $metrics = $this->reportingService->getBusinessOverview($request->user()->business_id);
        return response()->json(['success' => true, 'data' => $metrics]);
    }

    /**
     * Get performance comparison between branches.
     */
    public function branchComparison(Request $request): JsonResponse
    {
        $comparison = $this->reportingService->getBranchPerformance($request->user()->business_id);
        return response()->json(['success' => true, 'data' => $comparison]);
    }
}
