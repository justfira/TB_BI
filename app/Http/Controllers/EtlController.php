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
        $pendingCount   = StagingWorkorder::where('status_etl', 'pending')->count();
        $processedCount = StagingWorkorder::where('status_etl', 'processed')->count();
        $failedCount    = StagingWorkorder::where('status_etl', 'failed')->count();

        $etlLogs = EtlLog::latest('imported_at')->paginate(10);

        return view('import.index', compact('pendingCount', 'processedCount', 'failedCount', 'etlLogs'));
    }

    public function stop(int $logId)
    {
        $log = EtlLog::findOrFail($logId);

        if (!in_array($log->status, ['running', 'queued'], true)) {
            return back()->with('error', 'Batch ETL ini tidak sedang berjalan.');
        }

        $log->update([
            'stop_requested' => 1,
            'status'         => 'running',
        ]);

        return back()->with('success', "Stop request dikirim untuk batch #{$log->id}. ");
    }

    public function destroy(int $logId)
    {
        $all = (bool) request()->input('all');

        try {
            if ($all) {
                // Hapus semua data hasil ETL (gunakan etl_log_id = semua yang ada)
                $deleted = $this->etlService->deleteAllEtlData();

                // Hapus semua histori
                EtlLog::query()->delete();

                return back()->with('success', 'Semua data hasil ETL berhasil dihapus.');
            }

            $log = EtlLog::findOrFail($logId);

            if (in_array($log->status, ['running', 'queued'], true)) {
                return back()->with('error', 'Batch ETL masih berjalan dan tidak bisa dihapus.');
            }

            $this->etlService->deleteEtlBatch($log->id);
            $log->delete();

            return back()->with('success', "Histori ETL #{$log->id} dan data terkait berhasil dihapus.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menghapus histori: ' . $e->getMessage());
        }
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

        try {
            if (app()->environment('local')) {
                ProcessEtlJob::dispatchSync($fullPath, $manualMapping, $log->id);
            } else {
                ProcessEtlJob::dispatch($fullPath, $manualMapping, $log->id)
                    ->onQueue('etl');
            }
        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'error',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);

            return redirect()->route('import.result', $log->id)
                ->with('error', 'ETL gagal: ' . $e->getMessage());
        }

        return redirect()->route('import.result', $log->id);
    }

    // ── 4. Halaman hasil ETL ─────────────────────────────────────────────────

    public function result(int $logId)
    {
        $log = EtlLog::findOrFail($logId);

        $failedRows = StagingWorkorder::where('status_etl', 'failed')
            ->where('created_at', '>=', $log->imported_at)
            ->limit(50)
            ->get();

        return view('import.result', compact('log', 'failedRows'));
    }

    // ── 5. Cek status ETL via AJAX polling ───────────────────────────────────

    public function status(int $logId)
    {
        // Cache respon status untuk meredam polling (tiap request bisa ~500ms)
        // Cache 2 detik cukup untuk UI polling tanpa menahan ETL.
        $cacheKey = "etl_status_{$logId}";

        $payload = cache($cacheKey);
        if ($payload) {
            return response()->json($payload);
        }

        $log = EtlLog::select([
            'status',
            'total_rows',
            'success_count',
            'failed_count',
            'duplicate_count',
            'error_message',
            'updated_at',
        ])->findOrFail($logId);

        $payload = [
            'status'          => $log->status,
            'total_rows'      => $log->total_rows,
            'success_count'   => $log->success_count,
            'failed_count'    => $log->failed_count,
            'duplicate_count' => $log->duplicate_count,
            'error_message'   => $log->error_message,
            'is_done'         => in_array($log->status, ['done', 'error']),
        ];

        cache()->put($cacheKey, $payload, now()->addSeconds(5));

        return response()->json($payload);
    }

}
