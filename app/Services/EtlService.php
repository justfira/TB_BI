<?php

namespace App\Services;

use App\Imports\WorkOrderImport;
use App\Models\DimInfrastruktur;
use App\Models\DimKendala;
use App\Models\DimPelanggan;
use App\Models\DimStatus;
use App\Models\DimSto;
use App\Models\DimTeknisi;
use App\Models\DimWaktu;
use App\Models\EtlLog;
use App\Models\FactKendalateknis;
use App\Models\FactWorkorder;
use App\Models\StagingWorkorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EtlService
{
    public function __construct(protected WorkOrderImport $importer) {}

    protected array $cacheSto = [];
    protected array $cacheStatus = [];
    protected array $cacheKendala = [];
    protected array $cacheWaktu = [];
    protected array $cacheTeknisi = [];
    protected array $cachePelanggan = [];

    protected function warmCaches(): void
    {
        $this->cacheSto = DimSto::all()->keyBy(fn($item) => trim($item->nama_sto))->all();
        $this->cacheStatus = DimStatus::all()->keyBy(fn($item) => trim($item->status_wo))->all();
        $this->cacheKendala = DimKendala::all()->keyBy(fn($item) => trim($item->kendala_pt1))->all();
        $this->cacheWaktu = DimWaktu::all()->keyBy('tanggal')->all();
        $this->cacheTeknisi = DimTeknisi::all()->keyBy(fn($item) => trim($item->nik_teknisi))->all();

        $pelangganCount = DimPelanggan::count();
        if ($pelangganCount < 50000) {
            $this->cachePelanggan = DimPelanggan::all()->keyBy(fn($item) => trim($item->kode_tracking))->all();
        } else {
            $this->cachePelanggan = [];
        }
    }

    protected function upsertWaktuCached(?string $tanggal): DimWaktu
    {
        if (empty($tanggal)) {
            $tanggal = now()->toDateString();
        }

        $dt = Carbon::parse($tanggal);
        $dateStr = $dt->toDateString();

        if (isset($this->cacheWaktu[$dateStr])) {
            return $this->cacheWaktu[$dateStr];
        }

        $dimWaktu = DimWaktu::firstOrCreate(
            ['tanggal' => $dateStr],
            [
                'bulan'         => $dt->month,
                'nama_bulan'    => $dt->locale('id')->isoFormat('MMMM'),
                'tahun'         => $dt->year,
                'kuartal'       => (int) ceil($dt->month / 3),
                'nama_hari'     => $dt->locale('id')->isoFormat('dddd'),
                'nomor_minggu'  => $dt->weekOfYear,
                'is_weekend'    => $dt->isWeekend() ? 1 : 0,
                'periode_laporan' => $dt->format('Y-m'),
            ]
        );

        $this->cacheWaktu[$dateStr] = $dimWaktu;
        return $dimWaktu;
    }

    // ─── Preview ─────────────────────────────────────────────────────────────

    public function preview(string $filePath): array
    {
        return $this->importer->analyzeHeaders($filePath);
    }

    public function getCanonicalHeaderLabels(): array
    {
        return $this->importer->getCanonicalHeaderLabels();
    }

    // ─── ETL utama ───────────────────────────────────────────────────────────

    public function processUploadedFile(string $filePath, array $manualMapping = []): EtlLog
    {
        $log = EtlLog::create([
            'imported_at'     => now(),
            'total_rows'      => 0,
            'success_count'   => 0,
            'failed_count'    => 0,
            'duplicate_count' => 0,
            'status'          => 'running',
        ]);

        try {
            // Load all rows into memory
            $rows = $this->importer->loadRows($filePath, $manualMapping);
            $total = count($rows);

            if ($total === 0) {
                $log->update([
                    'total_rows'      => 0,
                    'success_count'   => 0,
                    'failed_count'    => 0,
                    'duplicate_count' => 0,
                    'status'          => 'done',
                ]);
                return $log->fresh();
            }

            // Warm up caches
            $this->warmCaches();

            // Extract all wo_sc_id values from rows to query duplicates in one batch
            $woIds = [];
            foreach ($rows as $row) {
                $woId = $row['wo_sc_id'] ?? null;
                if (!empty($woId)) {
                    $woIds[] = trim($woId);
                }
            }

            $existingWoIds = [];
            if (!empty($woIds)) {
                $woIdChunks = array_chunk(array_unique($woIds), 1000);
                foreach ($woIdChunks as $chunk) {
                    $existing = FactWorkorder::whereIn('wo_id', $chunk)->pluck('wo_id')->toArray();
                    foreach ($existing as $id) {
                        $existingWoIds[trim($id)] = true;
                    }
                }
            }

            $success = $failed = $duplicate = 0;
            $batchSize = 200;
            $chunks = array_chunk($rows, $batchSize);

            foreach ($chunks as $chunk) {
                try {
                    DB::beginTransaction();

                    $batchSuccess = 0;
                    $batchDuplicate = 0;

                    foreach ($chunk as $row) {
                        $woId = trim($row['wo_sc_id'] ?? '');

                        if (empty($woId)) {
                            throw new \RuntimeException('wo_sc_id kosong');
                        }

                        // Duplicate check in-memory
                        if (isset($existingWoIds[$woId])) {
                            $batchDuplicate++;
                            continue;
                        }

                        // Process row
                        $this->processRowNoTransaction($row, $woId);

                        $existingWoIds[$woId] = true;
                        $batchSuccess++;
                    }

                    DB::commit();
                    $success += $batchSuccess;
                    $duplicate += $batchDuplicate;

                } catch (\Throwable $e) {
                    DB::rollBack();

                    Log::warning('ETL batch failed, falling back to row-by-row processing', ['error' => $e->getMessage()]);

                    foreach ($chunk as $row) {
                        try {
                            $woId = trim($row['wo_sc_id'] ?? '');

                            if (empty($woId)) {
                                throw new \RuntimeException('wo_sc_id kosong');
                            }

                            if (isset($existingWoIds[$woId])) {
                                $duplicate++;
                                continue;
                            }

                            DB::transaction(function () use ($row, $woId) {
                                $this->processRowNoTransaction($row, $woId);
                            });

                            $existingWoIds[$woId] = true;
                            $success++;
                        } catch (\Throwable $rowEx) {
                            $failed++;
                            Log::warning('ETL fallback row failed', ['row' => $row, 'error' => $rowEx->getMessage()]);

                            // Save to staging with failed status
                            StagingWorkorder::create([
                                'data_json'  => json_encode($row),
                                'status_etl' => 'failed',
                            ]);
                        }
                    }
                }
            }

            $log->update([
                'total_rows'      => $total,
                'success_count'   => $success,
                'failed_count'    => $failed,
                'duplicate_count' => $duplicate,
                'status'          => 'done',
            ]);

        } catch (\Throwable $e) {
            $log->update([
                'status'          => 'error',
                'error_message'   => $e->getMessage(),
            ]);
            Log::error('ETL gagal total', ['error' => $e->getMessage()]);
        }

        return $log->fresh();
    }

    // ─── Proses satu baris ───────────────────────────────────────────────────

    protected function processRow(array $row): string
    {
        $woId = $row['wo_sc_id'] ?? null;

        if (empty($woId)) {
            throw new \RuntimeException('wo_sc_id kosong');
        }

        // Duplikat cek
        if (FactWorkorder::where('wo_id', $woId)->exists()) {
            return 'duplicate';
        }

        DB::transaction(function () use ($row, $woId) {
            $this->processRowNoTransaction($row, $woId);
        });

        return 'ok';
    }

    protected function processRowNoTransaction(array $row, string $woId): void
    {
        // ── 1. dim_waktu ─────────────────────────────────────────────────
        $tanggal   = $this->parseDate($row['tanggal'] ?? null);
        $dimWaktu  = $this->upsertWaktuCached($tanggal);

        // ── 2. dim_sto ───────────────────────────────────────────────────
        $stoName = trim($row['sto'] ?? 'UNKNOWN');
        if (isset($this->cacheSto[$stoName])) {
            $dimSto = $this->cacheSto[$stoName];
        } else {
            $dimSto = DimSto::create([
                'nama_sto'    => $stoName,
                'branch'      => $row['branch']  ?? null,
                'sektor'      => $row['sektor']  ?? null,
                'hsa'         => $row['hsa']     ?? null,
                'wilayah_sto' => null,
            ]);
            $this->cacheSto[$stoName] = $dimSto;
        }

        // ── 3. dim_teknisi ───────────────────────────────────────────────
        $nikTeknisi = trim($row['nik_teknisi'] ?? '');
        if (empty($nikTeknisi)) {
            $nikTeknisi = 'UNKNOWN';
        }
        if (isset($this->cacheTeknisi[$nikTeknisi])) {
            $dimTeknisi = $this->cacheTeknisi[$nikTeknisi];
        } else {
            $dimTeknisi = DimTeknisi::create([
                'nik_teknisi'   => $nikTeknisi,
                'nama_teknisi'  => $row['korlap']         ?? $row['nama_mitra'] ?? '-',
                'nama_mitra'    => $row['mitra']          ?? $row['nama_mitra'] ?? null,
                'korlap'        => $row['korlap']         ?? null,
                'komandan_team' => $row['komandan_team']  ?? null,
                'unit_kerja'    => $row['spv']            ?? null,
                'status_aktif'  => 1,
            ]);
            $this->cacheTeknisi[$nikTeknisi] = $dimTeknisi;
        }

        // ── 4. dim_pelanggan ─────────────────────────────────────────────
        $trackIdVal = trim($row['track_id'] ?? $row['wo_sc_id']);
        if (isset($this->cachePelanggan[$trackIdVal])) {
            $dimPelanggan = $this->cachePelanggan[$trackIdVal];
        } else {
            $dimPelanggan = DimPelanggan::create([
                'kode_tracking'    => $trackIdVal,
                'nama_pelanggan'   => $row['nama_pelanggan']   ?? null,
                'nama_contact'     => $row['nama_contact']     ?? null,
                'segment'          => $row['segment']          ?? null,
                'layanan'          => $row['layanan']          ?? null,
                'alamat_instalasi' => $row['alamat_instalasi'] ?? null,
                'uic'              => $row['uic']              ?? null,
                'koordinat_lat'    => $this->parseCoord($row['koordinat_lat'] ?? null),
                'koordinat_lon'    => $this->parseCoord($row['koordinat_lon'] ?? null),
            ]);
            $this->cachePelanggan[$trackIdVal] = $dimPelanggan;
        }

        // ── 5. dim_kendala ───────────────────────────────────────────────
        $kendalaName = trim($row['kendala_pt1'] ?? 'UNKNOWN');
        if (isset($this->cacheKendala[$kendalaName])) {
            $dimKendala = $this->cacheKendala[$kendalaName];
        } else {
            $dimKendala = DimKendala::create([
                'kendala_pt1'     => $kendalaName,
                'kategori_roc'    => $row['kategori_roc']   ?? null,
                'kategori_solusi' => $row['kategori_solusi'] ?? null,
                'solusi_kendala'  => $row['solusi_kendala']  ?? null,
                'keterangan'      => $row['keterangan']      ?? null,
            ]);
            $this->cacheKendala[$kendalaName] = $dimKendala;
        }

        // ── 6. dim_status ────────────────────────────────────────────────
        $statusWo = trim($row['status'] ?? 'UNKNOWN');
        if (isset($this->cacheStatus[$statusWo])) {
            $dimStatus = $this->cacheStatus[$statusWo];
        } else {
            $dimStatus = DimStatus::create([
                'status_wo'       => $statusWo,
                'status_sc'       => $row['status_sc']    ?? null,
                'kategori_status' => null,
                'status_final'    => $statusWo,
                'status_group'    => DimStatus::resolveGroup($statusWo),
            ]);
            $this->cacheStatus[$statusWo] = $dimStatus;
        }

        // ── 7. dim_infrastruktur ─────────────────────────────────────────
        $dimInfra = DimInfrastruktur::create([
            'odp'              => $row['odp']              ?? null,
            'odc'              => $row['odc']              ?? null,
            'gpon'             => $row['gpon']             ?? null,
            'feeder'           => $row['feeder']           ?? null,
            'distribusi'       => $row['distribusi']       ?? null,
            'datek1'           => $row['datek1']           ?? null,
            'datek_inputan'    => $row['datek_inputan']    ?? null,
            'datek_real'       => $row['datek_real']       ?? null,
            'base_tray_odc'    => $row['base_tray_odc']    ?? null,
            'port_base_tray_odc' => $row['port_base_tray_odc'] ?? null,
            'status_aktif'     => 1,
        ]);

        // ── 8. Hitung SLA & workfail ─────────────────────────────────────
        $durMenit       = $this->parseDurasi($row);
        $isWorkfail     = $this->parseFlag($row['unsc'] ?? $row['is_unsc'] ?? null);
        $isSla          = $this->computeSla($statusWo, $dimStatus->status_group);

        // ── 9. fact_workorder ────────────────────────────────────────────
        FactWorkorder::create([
            'wo_id'                   => $woId,
            'date_id'                 => $dimWaktu->date_id,
            'dim_waktu_id'            => $dimWaktu->date_id,
            'sto_id'                  => $dimSto->sto_id,
            'dim_sto_id'              => $dimSto->sto_id,
            'teknisi_id'              => $dimTeknisi->teknisi_id,
            'dim_teknisi_id'          => $dimTeknisi->teknisi_id,
            'pelanggan_id'            => $dimPelanggan->pelanggan_id,
            'dim_pelanggan_id'        => $dimPelanggan->pelanggan_id,
            'kendala_id'              => $dimKendala->kendala_id,
            'dim_kendala_id'          => $dimKendala->kendala_id,
            'status_id'               => $dimStatus->status_id,
            'dim_status_id'           => $dimStatus->status_id,
            'tanggal_order'           => $this->parseDate($row['tanggal_order'] ?? null),
            'tanggal_komitmen'        => $this->parseDate($row['tanggal_komitmen_ps_completed'] ?? null),
            'status_wo'               => $statusWo,
            'durasi_hari'             => $row['durasi_hari'] ?? null,
            'durasi_pengerjaan_menit' => $durMenit,
            'durasi_grup'             => $row['durasi_grup'] ?? null,
            'durasi_manja'            => $this->parseDecimal($row['durasi_manja'] ?? null),
            'tgl_input_hd_gdocs'      => $this->parseDatetime($row['tgl_input_hd_gdocs'] ?? null),
            'is_sla_tercapai'         => $isSla,
            'is_workfail'             => $isWorkfail,
            'sc_id'                   => $row['sc_id']    ?? null,
            'track_id'                => $row['track_id'] ?? null,
            'track_id_baru'           => null,
        ]);

        // ── 10. fact_kendalateknis ───────────────────────────────────────
        FactKendalateknis::create([
            'wo_id'                          => $woId,
            'date_id'                        => $dimWaktu->date_id,
            'dim_waktu_id'                   => $dimWaktu->date_id,
            'sto_id'                         => $dimSto->sto_id,
            'dim_sto_id'                     => $dimSto->sto_id,
            'kendala_id'                     => $dimKendala->kendala_id,
            'dim_kendala_id'                 => $dimKendala->kendala_id,
            'infra_id'                       => $dimInfra->infra_id,
            'dim_infrastruktur_id'           => $dimInfra->infra_id,
            'jumlah_kendala'                 => (int) ($row['jumlah_kendala']    ?? 1),
            'hasil_solusi_maintenance'       => $row['hasil_solusi_maintenance'] ?? null,
            'hasil_solusi_optima'            => $row['hasil_solusi_optima']      ?? null,
            'hasil_solusi_sdi'               => $row['hasil_solusi_sdi']         ?? null,
            'total_eskalasi'                 => (int) ($row['total_eskalasi']    ?? 0),
            'durasi_grup_pengerjaan'         => $this->parseDecimal($row['durasi_grup_pengerjaan'] ?? null),
        ]);
    }

    // ─── Helper parsing ──────────────────────────────────────────────────────

    protected function parseDate(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        try {
            return Carbon::parse($val)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseDatetime(?string $val): ?string
    {
        if (empty($val)) {
            return null;
        }

        try {
            return Carbon::parse($val)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseCoord(?string $val): ?float
    {
        if (empty($val)) {
            return null;
        }

        $val = str_replace(',', '.', $val);

        return is_numeric($val) ? (float) $val : null;
    }

    protected function parseDecimal(?string $val): ?float
    {
        if (empty($val)) {
            return null;
        }

        $val = str_replace(',', '.', preg_replace('/[^\d.,\-]/', '', $val));

        return is_numeric($val) ? (float) $val : null;
    }

    protected function parseFlag(?string $val): bool
    {
        if (empty($val)) {
            return false;
        }

        return in_array(strtolower(trim($val)), ['1', 'yes', 'ya', 'true', 'y'], true);
    }

    /**
     * Konversi durasi dari berbagai format ke menit (decimal).
     * Field bisa berupa durasi_hari, durasi (menit), atau durasi_grup_pengerjaan.
     */
    protected function parseDurasi(array $row): ?float
    {
        // Coba ambil durasi langsung (dalam menit)
        if (! empty($row['durasi'])) {
            $val = $this->parseDecimal($row['durasi']);
            if ($val !== null) {
                return $val;
            }
        }

        // Fallback: durasi_hari × 60 × 24
        if (! empty($row['durasi_hari'])) {
            $hari = $this->parseDecimal($row['durasi_hari']);
            if ($hari !== null) {
                return $hari * 24 * 60;
            }
        }

        return null;
    }

    /**
     * SLA tercapai jika status group = Selesai.
     * Logika ini bisa dikembangkan dengan membandingkan tanggal komitmen.
     */
    protected function computeSla(string $statusWo, string $statusGroup): bool
    {
        return $statusGroup === DimStatus::GROUP_SELESAI;
    }

    /**
     * Upsert dim_waktu berdasarkan tanggal.
     */
    protected function upsertWaktu(?string $tanggal): DimWaktu
    {
        if (empty($tanggal)) {
            $tanggal = now()->toDateString();
        }

        $dt = Carbon::parse($tanggal);

        return DimWaktu::firstOrCreate(
            ['tanggal' => $dt->toDateString()],
            [
                'bulan'         => $dt->month,
                'nama_bulan'    => $dt->locale('id')->isoFormat('MMMM'),
                'tahun'         => $dt->year,
                'kuartal'       => (int) ceil($dt->month / 3),
                'nama_hari'     => $dt->locale('id')->isoFormat('dddd'),
                'nomor_minggu'  => $dt->weekOfYear,
                'is_weekend'    => $dt->isWeekend() ? 1 : 0,
                'periode_laporan' => $dt->format('Y-m'),
            ],
        );
    }
}