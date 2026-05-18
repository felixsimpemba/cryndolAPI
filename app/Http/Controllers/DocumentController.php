<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Transaction;
use App\Models\LoanPayment;
use App\Services\ExcelExportService;
use App\Services\LoanAgreementPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

class DocumentController extends Controller
{
    protected $excelService;
    protected $pdfService;

    public function __construct(ExcelExportService $excelService, LoanAgreementPdfService $pdfService)
    {
        $this->excelService = $excelService;
        $this->pdfService = $pdfService;
    }

    public function index(Request $request, $customerId)
    {
        $documents = Document::where('entity_type', 'CUSTOMER')
            ->where('entity_id', $customerId)
            ->get();
        return response()->json(['status' => 'success', 'data' => $documents]);
    }

    public function store(Request $request, $customerId)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['status' => 'error', 'message' => 'Customer not found'], 404);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
            $path = $file->store('documents/' . $customerId, 'public');

            $document = Document::create([
                'business_id' => $request->user()->business_id,
                'entity_type' => 'CUSTOMER',
                'entity_id' => $customerId,
                'document_type' => $request->document_type,
                'file_name' => $originalName,
                'file_url' => $path,
                'file_size' => $fileSize,
                'uploaded_by' => $request->user()->id,
                'expiry_date' => $request->expiry_date,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Document uploaded successfully',
                'data' => $document
            ], 201);
        }

        return response()->json(['status' => 'error', 'message' => 'No file uploaded'], 400);
    }

    public function destroy($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['status' => 'error', 'message' => 'Document not found'], 404);
        }

        // Delete file from storage
        Storage::disk('public')->delete($document->file_url);

        $document->delete();

        return response()->json(['status' => 'success', 'message' => 'Document deleted successfully']);
    }

    /**
     * Export loans to Excel
     */
    public function exportLoans(Request $request)
    {
        try {
            $query = Loan::with(['customer', 'user'])->where('business_id', $request->user()->business_id);

            // Apply filters if provided
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('start_date')) {
                $query->whereDate('startDate', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('startDate', '<=', $request->end_date);
            }

            $loans = $query->get();

            $filepath = $this->excelService->exportLoans($loans);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export loans: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers to Excel
     */
    public function exportCustomers(Request $request)
    {
        try {
            $customers = Customer::where('business_id', $request->user()->business_id)->get();

            $filepath = $this->excelService->exportCustomers($customers);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export customers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export transactions to Excel
     */
    public function exportTransactions(Request $request)
    {
        try {
            $query = Transaction::where('business_id', $request->user()->business_id);

            // Apply filters if provided
            if ($request->has('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->has('start_date')) {
                $query->whereDate('occurred_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('occurred_at', '<=', $request->end_date);
            }

            $transactions = $query->get();

            $filepath = $this->excelService->exportTransactions($transactions);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export transactions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export loan payments to Excel
     */
    public function exportPayments(Request $request)
    {
        try {
            $query = LoanPayment::with(['loan.customer'])
                ->whereHas('loan', function ($q) use ($request) {
                    $q->where('business_id', $request->user()->business_id);
                });

            // Apply filters if provided
            if ($request->has('start_date')) {
                $query->whereDate('paymentDate', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('paymentDate', '<=', $request->end_date);
            }

            $payments = $query->get();

            $filepath = $this->excelService->exportPayments($payments);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export payments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate loan agreement PDF
     */
    public function generateLoanAgreement(Request $request, $loanId)
    {
        try {
            $loan = Loan::where('business_id', $request->user()->business_id)
                ->where('id', $loanId)
                ->firstOrFail();

            $filepath = $this->pdfService->generateLoanAgreement($loan);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Loan not found or access denied.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate loan agreement: ' . $e->getMessage()
            ], 500);
        }
    }
}
