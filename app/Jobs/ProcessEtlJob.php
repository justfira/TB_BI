<?php

namespace App\Jobs;

use App\Models\EtlLog;
use App\Services\EtlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessEtlJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 7200; // max 2 jam
    public int $tries   = 1;    // jangan retry — bisa duplikat data

    public function __construct(
        public string $filePath,
        public array  $manualMapping,
        public int    $logId,
    ) {}

    public function handle(EtlService $etlService): void
{
    EtlLog::where('id', $this->logId)->update(['status' => 'running']);

    try {
        // Kirim logId agar EtlService update log yang sama, bukan buat baru
        $etlService->processUploadedFile($this->filePath, $this->manualMapping, $this->logId);
    } finally {
        $relativePath = ltrim(
            str_replace(storage_path('app'), '', $this->filePath),
            DIRECTORY_SEPARATOR
        );
        Storage::delete($relativePath);
    }
}

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessEtlJob failed', [
            'log_id' => $this->logId,
            'error'  => $exception->getMessage(),
        ]);

        EtlLog::where('id', $this->logId)->update([
            'status'        => 'error',
            'error_message' => $exception->getMessage(),
        ]);
    }
}