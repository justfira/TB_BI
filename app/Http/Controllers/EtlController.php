<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEtlJob;
use App\Models\EtlLog;
use App\Models\StagingWorkorder;
use App\Services\EtlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur:
 *   GET  /import              → showImport() : form upload
 *   POST /import/preview      → preview()    : baca header + 20 baris pertama
 *   POST /import/process      → process()    : dispatch ETL job ke queue
 *   GET  /import/result/{id}  → result()     : tampilkan hasil ETL
 *   GET  /import/status/{id}  → status()     : cek status via AJAX polling
 */
class EtlController extends Controller
{
    public function __construct(protected EtlService $etlService) {}

    // ── 1. Halaman upload ─────────────────────────────────────────────────────

    public function showImport()
    {
        $pendingCount   = StagingWorkorder::where('status', 'pending')->count();
        $processedCount = StagingWorkorder::where('status', 'processed')->count();
        $failedCount    = StagingWorkorder::where('status', 'failed')->count();

        return view('import.index', compact('pendingCount', 'processedCount', 'failedCount'));
    }

    // ── 2. Preview header + sampel baris ─────────────────────────────────────

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:102400'],
        ]);

        $file     = $request->file('file');
        $fileName = 'import_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('imports', $fileName);

        $fullPath        = Storage::path($path);
        $analysis        = $this->etlService->preview($fullPath);
        $canonicalLabels = $this->etlService->getCanonicalHeaderLabels();

        return view('import.preview', [
            'rows'            => $analysis['rows'],
            'filePath'        => $path,
            'headerAnalysis'  => $analysis,
            'canonicalLabels' => $canonicalLabels,
            'originalName'    => $file->getClientOriginalName(),
            'fileSize'        => round($file->getSize() / 1024 / 1024, 2),
        ]);
    }

    // ── 3. Proses ETL ─────────────────────────────────────────────────────────

    public function process(Request $request)
    {
        $request->validate([
            'file_path'         => ['required', 'string'],
            'manual_raw_header' => ['array'],
            'manual_mapping'    => ['array'],
        ]);

        // Bangun manual mapping dari input form
        $manualMapping = [];
        foreach ($request->input('manual_raw_header', []) as $idx => $rawHeader) {
            $canonical = $request->input("manual_mapping.{$idx}");
            if (!empty($rawHeader) && !empty($canonical)) {
                $manualMapping[$rawHeader] = $canonical;
            }
        }

        $filePath = $request->input('file_path');
        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            return redirect()->route('import.index')
                ->with('error', 'File tidak ditemukan. Silakan upload ulang.');
        }

        // Buat log awal dengan status 'queued'
        $log = EtlLog::create([
            'imported_at'     => now(),
            'status'          => 'running', // ← sementara, sampai migrasi dijalankan
            'total_rows'      => 0,
            'success_count'   => 0,
            'failed_count'    => 0,
            'duplicate_count' => 0,
        ]);

        // Dispatch ke background queue
        // File TIDAK dihapus di sini — ProcessEtlJob yang hapus setelah selesai
        ProcessEtlJob::dispatch($fullPath, $manualMapping, $log->id)
            ->onQueue('etl');

        return redirect()->route('import.result', $log->id);
    }

    // ── 4. Halaman hasil ETL ─────────────────────────────────────────────────

    public function result(int $logId)
    {
        $log = EtlLog::findOrFail($logId);

        $failedRows = StagingWorkorder::where('status', 'failed')
            ->where('created_at', '>=', $log->imported_at)
            ->limit(50)
            ->get();

        return view('import.result', compact('log', 'failedRows'));
    }

    // ── 5. Cek status ETL via AJAX polling ───────────────────────────────────

    public function status(int $logId)
    {
        $log = EtlLog::findOrFail($logId);

        return response()->json([
            'status'          => $log->status,
            'total_rows'      => $log->total_rows,
            'success_count'   => $log->success_count,
            'failed_count'    => $log->failed_count,
            'duplicate_count' => $log->duplicate_count,
            'error_message'   => $log->error_message,
            'is_done'         => in_array($log->status, ['done', 'error']),
        ]);
    }
}
