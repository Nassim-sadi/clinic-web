<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    public function exportAppointments(array $data, string $fromDate, string $toDate): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $this->setupHeader($sheet, 'Appointments Report', $fromDate, $toDate);

        $headers = ['Date', 'Time', 'Patient', 'Doctor', 'Service', 'Status', 'Reason'];
        $this->writeHeaders($sheet, $headers, 4);

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['date'] ?? '');
            $sheet->setCellValue("B{$row}", $item['start_time'] ?? '');
            $sheet->setCellValue("C{$row}", $item['patient'] ?? '');
            $sheet->setCellValue("D{$row}", $item['doctor'] ?? '');
            $sheet->setCellValue("E{$row}", $item['service'] ?? '');
            $sheet->setCellValue("F{$row}", ucfirst($item['status'] ?? ''));
            $sheet->setCellValue("G{$row}", $item['reason'] ?? '');
            $this->styleRow($sheet, $row);
            $row++;
        }

        $this->autoSizeColumns($sheet, count($headers));

        return $this->save($spreadsheet, "appointments_{$fromDate}_{$toDate}.xlsx");
    }

    public function exportPatients(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $this->setupHeader($sheet, 'Patients Report', date('Y-m-01'), date('Y-m-d'));

        $headers = ['Name', 'Email', 'Phone', 'Gender', 'Blood Group', 'Clinic', 'Registered'];
        $this->writeHeaders($sheet, $headers, 4);

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['name'] ?? '');
            $sheet->setCellValue("B{$row}", $item['email'] ?? '');
            $sheet->setCellValue("C{$row}", $item['phone'] ?? '');
            $sheet->setCellValue("D{$row}", ucfirst($item['gender'] ?? ''));
            $sheet->setCellValue("E{$row}", $item['blood_group'] ?? '');
            $sheet->setCellValue("F{$row}", $item['clinic'] ?? '');
            $sheet->setCellValue("G{$row}", $item['created_at'] ?? '');
            $this->styleRow($sheet, $row);
            $row++;
        }

        $this->autoSizeColumns($sheet, count($headers));

        return $this->save($spreadsheet, 'patients_'.date('Y-m-d').'.xlsx');
    }

    public function exportBilling(array $data, string $fromDate, string $toDate): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $this->setupHeader($sheet, 'Billing Report', $fromDate, $toDate);

        $headers = ['Invoice #', 'Date', 'Patient', 'Doctor', 'Total', 'Paid', 'Due', 'Status'];
        $this->writeHeaders($sheet, $headers, 4);

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['invoice_number'] ?? '');
            $sheet->setCellValue("B{$row}", $item['created_at'] ?? '');
            $sheet->setCellValue("C{$row}", $item['patient'] ?? '');
            $sheet->setCellValue("D{$row}", $item['doctor'] ?? '');
            $sheet->setCellValue("E{$row}", $item['total_amount'] ?? 0);
            $sheet->setCellValue("F{$row}", $item['amount_paid'] ?? 0);
            $sheet->setCellValue("G{$row}", ($item['total_amount'] ?? 0) - ($item['amount_paid'] ?? 0));
            $sheet->setCellValue("H{$row}", ucfirst($item['payment_status'] ?? ''));
            $this->styleRow($sheet, $row);
            $row++;
        }

        $this->addSummary($sheet, $row, $data);
        $this->autoSizeColumns($sheet, count($headers));

        return $this->save($spreadsheet, "billing_{$fromDate}_{$toDate}.xlsx");
    }

    public function exportPrescriptions(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        $this->setupHeader($sheet, 'Prescriptions Report', date('Y-m-01'), date('Y-m-d'));

        $headers = ['Date', 'Patient', 'Doctor', 'Medicine', 'Dosage', 'Frequency', 'Duration', 'Instructions'];
        $this->writeHeaders($sheet, $headers, 4);

        $row = 5;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $item['created_at'] ?? '');
            $sheet->setCellValue("B{$row}", $item['patient'] ?? '');
            $sheet->setCellValue("C{$row}", $item['doctor'] ?? '');
            $sheet->setCellValue("D{$row}", $item['medicine'] ?? '');
            $sheet->setCellValue("E{$row}", $item['dosage'] ?? '');
            $sheet->setCellValue("F{$row}", $item['frequency'] ?? '');
            $sheet->setCellValue("G{$row}", $item['duration'] ?? '');
            $sheet->setCellValue("H{$row}", $item['instructions'] ?? '');
            $this->styleRow($sheet, $row);
            $row++;
        }

        $this->autoSizeColumns($sheet, count($headers));

        return $this->save($spreadsheet, 'prescriptions_'.date('Y-m-d').'.xlsx');
    }

    private function setupHeader($sheet, string $title, string $fromDate, string $toDate): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);

        $sheet->setCellValue('A2', "Period: {$fromDate} to {$toDate}");
        $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);

        $sheet->setCellValue('A3', 'Generated: '.date('Y-m-d H:i:s'));
        $sheet->getStyle('A3')->getFont()->setSize(10)->setItalic(true);
    }

    private function writeHeaders($sheet, array $headers, int $startRow): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$startRow}", $header);
            $sheet->getStyle("{$col}{$startRow}")->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $col++;
        }
    }

    private function styleRow($sheet, int $row): void
    {
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private function addSummary($sheet, int $startRow, array $data): void
    {
        $totalAmount = array_sum(array_column($data, 'total_amount'));
        $totalPaid = array_sum(array_column($data, 'amount_paid'));
        $totalDue = $totalAmount - $totalPaid;

        $sheet->setCellValue("A{$startRow}", 'Summary');
        $sheet->getStyle("A{$startRow}")->getFont()->setBold(true);

        $sheet->setCellValue('E'.($startRow + 1), 'Total:');
        $sheet->getStyle('E'.($startRow + 1))->getFont()->setBold(true);
        $sheet->setCellValue('F'.($startRow + 1), $totalAmount);

        $sheet->setCellValue('E'.($startRow + 2), 'Total Paid:');
        $sheet->getStyle('E'.($startRow + 2))->getFont()->setBold(true);
        $sheet->setCellValue('F'.($startRow + 2), $totalPaid);

        $sheet->setCellValue('E'.($startRow + 3), 'Total Due:');
        $sheet->getStyle('E'.($startRow + 3))->getFont()->setBold(true);
        $sheet->setCellValue('F'.($startRow + 3), $totalDue);
    }

    private function autoSizeColumns($sheet, int $count): void
    {
        $columns = range('A', chr(65 + $count - 1));
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function save(Spreadsheet $spreadsheet, string $filename): string
    {
        $path = storage_path("app/exports/{$filename}");
        $dir = dirname($path);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }
}
