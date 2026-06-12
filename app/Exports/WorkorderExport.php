<?php

namespace App\Exports;

use App\Models\FactWorkorder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WorkorderExport
{
    public function buildSpreadsheet($rows): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['WO ID', 'SC ID', 'Tanggal', 'STO', 'Teknisi', 'Status', 'Kendala', 'Durasi Jam', 'SLA Achievement', 'Workfail'],
        ], null, 'A1');

        $sheet->fromArray($rows, null, 'A2');

        return $spreadsheet;
    }

    public function downloadFilename(): string
    {
        return 'report_workorder_' . now()->format('Ymd_His') . '.xlsx';
    }
}
