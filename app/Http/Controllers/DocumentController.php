<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Borrower;
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

    public function index(Request $request, $borrowerId)
    {
        $documents = Document::where('borrower_id', $borrowerId)->get();
        return response()->json(['status' => 'success', 'data' => $documents]);
    }

    public function store(Request $request, $borrowerId)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $borrower = Borrower::find($borrowerId);
        if (!$borrower) {
            return response()->json(['status' => 'error', 'message' => 'Borrower not found'], 404);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $path = $file->store('documents/' . $borrowerId, 'public');

            $document = Document::create([
                'borrower_id' => $borrowerId,
                'document_type' => $request->document_type,
                'file_path' => $path,
                'original_name' => $originalName,
                'expiry_date' => $request->expiry_date,
                'verification_status' => 'pending',
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
        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return response()->json(['status' => 'success', 'message' => 'Document deleted successfully']);
    }

    /**
     * Export loans to Excel
     */
    public function exportLoans(Request $request)
    {
        try {
            $query = Loan::with(['borrower', 'user'])->where('user_id', $request->user()->id);

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
     * Export borrowers to Excel
     */
    public function exportBorrowers(Request $request)
    {
        try {
            $borrowers = Borrower::where('user_id', $request->user()->id)->get();

            $filepath = $this->excelService->exportBorrowers($borrowers);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export borrowers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export transactions to Excel
     */
    public function exportTransactions(Request $request)
    {
        try {
            $query = Transaction::where('user_id', $request->user()->id);

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
            $query = LoanPayment::with(['loan.borrower'])
                ->whereHas('loan', function ($q) use ($request) {
                    $q->where('user_id', $request->user()->id);
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
            $loan = Loan::where('user_id', $request->user()->id)
                ->where('id', $loanId)
                ->firstOrFail();

            $filepath = $this->pdfService->generateLoanAgreement($loan);

            return Response::download($filepath, basename($filepath), [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate loan agreement: ' . $e->getMessage()
            ], 500);
        }
    }
}
