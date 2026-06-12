<?php

namespace App\Http\Controllers;

use App\Models\StagingWorkorder;
use App\Services\EtlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Alur:
 *   GET  /import              → showImport()   : form upload
 *   POST /import/preview      → preview()      : baca header + 20 baris pertama
 *   POST /import/process      → process()      : jalankan ETL, simpan ke DB
 *   GET  /import/result/{id}  → result()       : tampilkan hasil ETL
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

        return view('import.index', compact('pendingCount', 'processedCount', 'failedCount'));
    }

    // ── 2. Preview header + sampel baris ─────────────────────────────────────

    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:102400'], // max 100 MB
        ]);

        $file = $request->file('file');

        // Simpan sementara di storage/app/imports/
        $fileName = 'import_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('imports', $fileName);

        $fullPath        = Storage::path($path);
        $analysis        = $this->etlService->preview($fullPath);
        $canonicalLabels = $this->etlService->getCanonicalHeaderLabels();

        return view('import.preview', [
            'rows'            => $analysis['rows'],         // 20 baris sampel
            'filePath'        => $path,                     // relative path untuk disimpan di form
            'headerAnalysis'  => $analysis,
            'canonicalLabels' => $canonicalLabels,
            'originalName'    => $file->getClientOriginalName(),
            'fileSize'        => round($file->getSize() / 1024 / 1024, 2), // MB
        ]);
    }

    // ── 3. Proses ETL ─────────────────────────────────────────────────────────

    public function process(Request $request)
    {
        $request->validate([
            'file_path'        => ['required', 'string'],
            'manual_raw_header'=> ['array'],
            'manual_mapping'   => ['array'],
        ]);

        // Bangun manual mapping dari input form
        $manualMapping = [];
        foreach ($request->input('manual_raw_header', []) as $idx => $rawHeader) {
            $canonical = $request->input("manual_mapping.{$idx}");
            if (! empty($rawHeader) && ! empty($canonical)) {
                $manualMapping[$rawHeader] = $canonical;
            }
        }

        $fullPath = Storage::path($request->input('file_path'));

        if (! file_exists($fullPath)) {
            return redirect()->route('import.index')
                ->with('error', 'File tidak ditemukan. Silakan upload ulang.');
        }

        $log = $this->etlService->processUploadedFile($fullPath, $manualMapping);

        // Hapus file sementara
        Storage::delete($request->input('file_path'));

        return redirect()->route('import.result', $log->id);
    }

    // ── 4. Halaman hasil ETL ─────────────────────────────────────────────────

    public function result(int $logId)
    {
        $log = \App\Models\EtlLog::findOrFail($logId);

        // Ambil beberapa baris yang gagal untuk ditampilkan
        $failedRows = StagingWorkorder::where('status_etl', 'failed')
            ->where('created_at', '>=', $log->imported_at)
            ->limit(50)
            ->get();

        return view('import.result', compact('log', 'failedRows'));
    }
}