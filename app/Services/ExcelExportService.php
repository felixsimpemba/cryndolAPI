<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ExcelExportService
{
    /**
     * Export loans to Excel
     */
    public function exportLoans($loans)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'Loans Export - ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A1:H1');
        $this->styleHeader($sheet, 'A1');

        // Set column headers
        $headers = ['Loan ID', 'Customer Name', 'Principal', 'Interest Rate', 'Term', 'Start Date', 'Due Date', 'Status', 'Total Paid'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $this->styleColumnHeader($sheet, $column . '3');
            $column++;
        }

        // Add data
        $row = 4;
        foreach ($loans as $loan) {
            $sheet->setCellValue('A' . $row, $loan->id);
            $sheet->setCellValue('B' . $row, $loan->customer->first_name ?? 'N/A');
            $sheet->setCellValue('C' . $row, 'K ' . number_format($loan->principal, 2));
            $sheet->setCellValue('D' . $row, $loan->interestRate . '%');
            $sheet->setCellValue('E' . $row, $loan->termMonths . ' ' . ($loan->term_unit ?? 'months'));
            $sheet->setCellValue('F' . $row, $loan->startDate ? $loan->startDate->format('Y-m-d') : 'N/A');
            $sheet->setCellValue('G' . $row, $loan->dueDate ? $loan->dueDate->format('Y-m-d') : 'N/A');
            $sheet->setCellValue('H' . $row, ucfirst($loan->status));
            $sheet->setCellValue('I' . $row, 'K ' . number_format($loan->totalPaid ?? 0, 2));
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->outputSpreadsheet($spreadsheet, 'loans_export_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export customers to Excel
     */
    public function exportCustomers($customers)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'Customers Export - ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A1:J1');
        $this->styleHeader($sheet, 'A1');

        // Set column headers
        $headers = ['ID', 'Full Name', 'Email', 'Phone', 'NRC', 'Address', 'DOB', 'Gender', 'Employment', 'Monthly Income'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $this->styleColumnHeader($sheet, $column . '3');
            $column++;
        }

        // Add data
        $row = 4;
        foreach ($customers as $customer) {
            $sheet->setCellValue('A' . $row, $customer->id);
            $sheet->setCellValue('B' . $row, $customer->first_name);
            $sheet->setCellValue('C' . $row, $customer->email ?? 'N/A');
            $sheet->setCellValue('D' . $row, $customer->phoneNumber ?? 'N/A');
            $sheet->setCellValue('E' . $row, $customer->nrc_number ?? 'N/A');
            $sheet->setCellValue('F' . $row, $customer->address ?? 'N/A');
            $sheet->setCellValue('G' . $row, $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : 'N/A');
            $sheet->setCellValue('H' . $row, ucfirst($customer->gender ?? 'N/A'));
            $sheet->setCellValue('I' . $row, ucfirst($customer->employment_status ?? 'N/A'));
            $sheet->setCellValue('J' . $row, $customer->monthly_income ? 'K ' . number_format($customer->monthly_income, 2) : 'N/A');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->outputSpreadsheet($spreadsheet, 'customers_export_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export transactions to Excel
     */
    public function exportTransactions($transactions)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'Transactions Export - ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A1:F1');
        $this->styleHeader($sheet, 'A1');

        // Set column headers
        $headers = ['ID', 'Type', 'Category', 'Amount', 'Description', 'Date'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $this->styleColumnHeader($sheet, $column . '3');
            $column++;
        }

        // Add data
        $row = 4;
        foreach ($transactions as $transaction) {
            $sheet->setCellValue('A' . $row, $transaction->id);
            $sheet->setCellValue('B' . $row, ucfirst($transaction->type));
            $sheet->setCellValue('C' . $row, ucfirst(str_replace('_', ' ', $transaction->category)));
            $sheet->setCellValue('D' . $row, 'K ' . number_format($transaction->amount, 2));
            $sheet->setCellValue('E' . $row, $transaction->description ?? 'N/A');
            $sheet->setCellValue('F' . $row, $transaction->occurred_at ? $transaction->occurred_at->format('Y-m-d H:i') : 'N/A');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->outputSpreadsheet($spreadsheet, 'transactions_export_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Export loan payments to Excel
     */
    public function exportPayments($payments)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'Loan Payments Export - ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A1:F1');
        $this->styleHeader($sheet, 'A1');

        // Set column headers
        $headers = ['Payment ID', 'Loan ID', 'Customer', 'Amount', 'Payment Date', 'Notes'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '3', $header);
            $this->styleColumnHeader($sheet, $column . '3');
            $column++;
        }

        // Add data
        $row = 4;
        foreach ($payments as $payment) {
            $sheet->setCellValue('A' . $row, $payment->id);
            $sheet->setCellValue('B' . $row, $payment->loan_id);
            $sheet->setCellValue('C' . $row, $payment->loan->customer->first_name ?? 'N/A');
            $sheet->setCellValue('D' . $row, 'K ' . number_format($payment->amount, 2));
            $sheet->setCellValue('E' . $row, $payment->paymentDate ? $payment->paymentDate->format('Y-m-d') : 'N/A');
            $sheet->setCellValue('F' . $row, $payment->notes ?? 'N/A');
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->outputSpreadsheet($spreadsheet, 'payments_export_' . now()->format('Y-m-d') . '.xlsx');
    }

    /**
     * Style header cell
     */
    private function styleHeader($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        $sheet->getRowDimension(1)->setRowHeight(30);
    }

    /**
     * Style column header
     */
    private function styleColumnHeader($sheet, $cell)
    {
        $sheet->getStyle($cell)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER
            ]
        ]);
    }

    /**
     * Output spreadsheet and return file path
     */
    private function outputSpreadsheet($spreadsheet, $filename)
    {
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filepath = $tempDir . '/' . $filename;
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }
}
