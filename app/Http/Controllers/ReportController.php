<?php

namespace App\Http\Controllers;

use App\Models\FactWorkorder;
use Illuminate\Http\Request;
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = FactWorkorder::with(['status', 'sto', 'teknisi', 'kendala', 'waktu', 'pelanggan']);

        $this->applyFilters($query, $request);

        $totalRows = (clone $query)->count();
        $reports   = $query->latest('wo_id')->paginate(20)->withQueryString();

        return view('reports.index', compact('reports', 'totalRows'));
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->buildReportRows($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(array_merge([
            ['WO ID', 'SC ID', 'Track ID', 'Tanggal', 'STO', 'Teknisi', 'Status', 'Kendala', 'Durasi (Jam)', 'SLA Tercapai', 'Workfail'],
        ], $rows));

        // Auto-width kolom header
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $fileName = 'report_workorder_' . now()->format('Ymd_His') . '.xlsx';

        return new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->buildReportRows($request);
        $html = view('reports.pdf', compact('rows'))->render();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="report_workorder_' . now()->format('Ymd_His') . '.pdf"',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    protected function applyFilters($query, Request $request): void
    {
        if ($request->filled('tahun')) {
            $query->whereHas('waktu', fn ($q) => $q->where('tahun', $request->tahun));
        }
        if ($request->filled('bulan')) {
            $query->whereHas('waktu', fn ($q) => $q->where('bulan', $request->bulan));
        }
        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('status_wo', $request->status));
        }
        if ($request->filled('sto')) {
            $query->where('sto_id', $request->sto);
        }
        if ($request->filled('teknisi')) {
            $query->where('teknisi_id', $request->teknisi);
        }
    }

    protected function buildReportRows(Request $request): array
    {
        $query = FactWorkorder::with(['status', 'sto', 'teknisi', 'kendala', 'waktu']);
        $this->applyFilters($query, $request);

        return $query->get()->map(function (FactWorkorder $item) {
            return [
                $item->wo_id,
                $item->sc_id,
                $item->track_id,
                optional($item->waktu)->tanggal,
                optional($item->sto)->nama_sto,
                optional($item->teknisi)->nama_teknisi,
                optional($item->status)->status_wo,
                optional($item->kendala)->kendala_pt1,
                $item->durasi_jam,                          // accessor di model
                $item->is_sla_tercapai ? 'Ya' : 'Tidak',   // kolom asli DB
                $item->is_workfail     ? 'Ya' : 'Tidak',   // kolom asli DB
            ];
        })->toArray();
    }
}