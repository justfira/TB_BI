<?php

namespace App\Jobs;

use App\Imports\WorkOrderImport;
use App\Models\EtlLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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

    public function handle(WorkOrderImport $importer): void
    {
        EtlLog::where('id', $this->logId)->update(['status' => 'running']);

        $mappingPath = storage_path('app/etl_mapping_' . uniqid('', true) . '.json');

        try {
            $headerMap = $importer->buildHeaderMap($this->filePath, $this->manualMapping);
            file_put_contents(
                $mappingPath,
                json_encode(['columns' => $headerMap], JSON_UNESCAPED_UNICODE),
            );

            $pythonBin = env('ETL_PYTHON_PATH', 'python');
            $pythonEnv = [
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
                'ComSpec' => getenv('ComSpec') ?: 'C:\\Windows\\system32\\cmd.exe',
            ];

            $check = new Process([$pythonBin, '-c', 'import mysql.connector']);
            $check->setWorkingDirectory(base_path());
            $check->setEnv($pythonEnv);
            $check->setTimeout(30);
            $check->run();
            if (!$check->isSuccessful()) {
                $checkOutput = trim($check->getErrorOutput() . "\n" . $check->getOutput());
                throw new \RuntimeException(
                    "Python ETL tidak siap. Install dulu: {$pythonBin} -m pip install -r requirements-etl.txt"
                    . ($checkOutput ? "\n\nDetail: {$checkOutput}" : '')
                );
            }

            $process = new Process([
                $pythonBin,
                '-u',
                base_path('python_staging_loader.py'),
                '--file',
                $this->filePath,
                '--log-id',
                (string) $this->logId,
                '--mapping-file',
                $mappingPath,
                '--batch-size',
                '2000',
            ]);
            $process->setWorkingDirectory(base_path());
            $process->setEnv($pythonEnv);
            $process->setTimeout($this->timeout);
            $process->mustRun(function ($type, $buffer) {
                if ($type === Process::ERR) {
                    Log::error('ETL python stderr', ['log_id' => $this->logId, 'output' => $buffer]);
                } else {
                    Log::info('ETL python stdout', ['log_id' => $this->logId, 'output' => $buffer]);
                }
            });

            EtlLog::where('id', $this->logId)->update(['status' => 'done']);
        } catch (ProcessFailedException $e) {
            $output = trim($e->getProcess()->getErrorOutput() . "\n" . $e->getProcess()->getOutput());
            EtlLog::where('id', $this->logId)->update([
                'status'        => 'error',
                'error_message' => substr($output ?: $e->getMessage(), 0, 1000),
            ]);
            throw $e;
        } catch (\Throwable $e) {
            EtlLog::where('id', $this->logId)->update([
                'status'        => 'error',
                'error_message' => substr($e->getMessage(), 0, 1000),
            ]);
            throw $e;
        } finally {
            if (file_exists($mappingPath)) {
                @unlink($mappingPath);
            }
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
