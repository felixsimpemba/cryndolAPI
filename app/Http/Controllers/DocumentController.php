<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Borrower;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DocumentController extends Controller
{
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
}
