<?php
// app/Services/EtlService.php

namespace App\Services;

use App\Imports\WorkOrderImport;
use App\Models\DimStatus;
use App\Models\EtlLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EtlService
{
    public function __construct(protected WorkOrderImport $importer) {}

    protected array $cacheSto       = [];
    protected array $cacheStatus    = [];
    protected array $cacheKendala   = [];
    protected array $cacheWaktu     = [];
    protected array $cacheTeknisi   = [];
    protected array $cachePelanggan = [];

    public function preview(string $filePath): array
    {
        return $this->importer->analyzeHeaders($filePath);
    }

    public function getCanonicalHeaderLabels(): array
    {
        return $this->importer->getCanonicalHeaderLabels();
    }

    // ─── ETL Utama ───────────────────────────────────────────────────────────

    public function processUploadedFile(
        string $filePath,
        array  $manualMapping = [],
        ?int   $existingLogId = null,
    ): EtlLog {
        ini_set('memory_limit', '1G');
        set_time_limit(0);

        if ($existingLogId !== null) {
            $log = EtlLog::findOrFail($existingLogId);
            DB::table('etl_logs')->where('id', $log->id)->update([
                'status'          => 'running',
                'total_rows'      => 0,
                'success_count'   => 0,
                'failed_count'    => 0,
                'duplicate_count' => 0,
                'imported_at'     => now(),
                'updated_at'      => now(),
            ]);
        } else {
            $log = EtlLog::create([
                'imported_at'     => now(),
                'status'          => 'running',
                'total_rows'      => 0,
                'success_count'   => 0,
                'failed_count'    => 0,
                'duplicate_count' => 0,
            ]);
        }

        $total = $success = $failed = $duplicate = 0;

        try {
            // ── Load existing WO IDs sekali ──────────────────────────────────
            $existingWoIds = DB::table('fact_workorder')
                ->pluck('wo_id')
                ->flip()
                ->all();

            // ── Load cache dimensi yang sudah ada ────────────────────────────
            $this->warmCachesLite();

            // ── Baca file 1x — kumpulkan semua baris dulu per batch ──────────
            // Setiap batch: resolve dimensi baru → insert → insert facts
            $batch          = [];
            $batchSize      = 500;
            $lastUpdateTotal= 0;

            foreach ($this->importer->rowGenerator($filePath, $manualMapping) as $row) {
                $total++;
                $batch[] = $row;

                if (count($batch) >= $batchSize) {
                    // Upsert dimensi baru yang muncul di batch ini
                    $this->upsertBatchDimensions($batch);
                    // Refresh cache dengan dimensi baru
                    $this->refreshCaches();

                    [$bS, $fS, $dS] = $this->flushFactsBatch($batch, $existingWoIds);
                    $success   += $bS;
                    $failed    += $fS;
                    $duplicate += $dS;
                    $batch      = [];

                    // Update progress setiap 2500 baris (bukan setiap batch)
                    // supaya tidak spam DB update
                    if ($total - $lastUpdateTotal >= 2500) {
                        DB::table('etl_logs')->where('id', $log->id)->update([
                            'total_rows'      => $total,
                            'success_count'   => $success,
                            'failed_count'    => $failed,
                            'duplicate_count' => $duplicate,
                            'updated_at'      => now(),
                        ]);
                        $lastUpdateTotal = $total;
                    }
                }
            }

            // Flush sisa
            if (!empty($batch)) {
                $this->upsertBatchDimensions($batch);
                $this->refreshCaches();
                [$bS, $fS, $dS] = $this->flushFactsBatch($batch, $existingWoIds);
                $success   += $bS;
                $failed    += $fS;
                $duplicate += $dS;
            }

            DB::table('etl_logs')->where('id', $log->id)->update([
                'total_rows'      => $total,
                'success_count'   => $success,
                'failed_count'    => $failed,
                'duplicate_count' => $duplicate,
                'status'          => 'done',
                'updated_at'      => now(),
            ]);

        } catch (\Throwable $e) {
            DB::table('etl_logs')->where('id', $log->id)->update([
                'total_rows'      => $total,
                'success_count'   => $success,
                'failed_count'    => $failed,
                'duplicate_count' => $duplicate,
                'status'          => 'error',
                'error_message'   => substr($e->getMessage(), 0, 1000),
                'updated_at'      => now(),
            ]);
            Log::error('ETL gagal', ['log_id' => $log->id, 'error' => $e->getMessage()]);
        }

        return $log->fresh();
    }

    // ─── Upsert dimensi baru dari 1 batch ────────────────────────────────────
    // Hanya insert nilai yang BELUM ada di cache (skip yang sudah ada)

    protected function upsertBatchDimensions(array $batch): void
    {
        $now = now()->toDateTimeString();

        // Kumpulkan nilai unik dari batch — hanya yang belum di cache
        $newSto      = [];
        $newStatus   = [];
        $newKendala  = [];
        $newTeknisi  = [];
        $newPelanggan= [];
        $newTanggal  = [];

        foreach ($batch as $row) {
            $sto     = trim($row['sto'] ?? 'UNKNOWN');
            $status  = trim($row['status_wo'] ?? $row['status'] ?? 'UNKNOWN');
            $kendala = trim($row['kendala_pt1'] ?? 'UNKNOWN');
            $nik     = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
            $tid     = trim($row['track_id'] ?? $row['wo_sc_id'] ?? '');
            $tgl     = $this->parseDate($row['tanggal'] ?? null) ?? now()->toDateString();

            if (!isset($this->cacheSto[$sto]))           $newSto[$sto]          = true;
            if (!isset($this->cacheStatus[$status]))     $newStatus[$status]    = true;
            if (!isset($this->cacheKendala[$kendala]))   $newKendala[$kendala]  = true;
            if (!isset($this->cacheTeknisi[$nik]))       $newTeknisi[$nik]      = true;
            if ($tid && !isset($this->cachePelanggan[$tid])) {
                $newPelanggan[$tid] = $row; // simpan row untuk ambil detail pelanggan
            }
            if (!isset($this->cacheWaktu[$tgl]))         $newTanggal[$tgl]      = true;
        }

        // ── dim_waktu ────────────────────────────────────────────────────────
        if (!empty($newTanggal)) {
            $existing = DB::table('dim_waktu')
                ->whereIn('tanggal', array_keys($newTanggal))
                ->pluck('tanggal')->flip()->all();

            $toInsert = [];
            foreach (array_keys($newTanggal) as $tgl) {
                if (!isset($existing[$tgl])) {
                    $dt = Carbon::parse($tgl);
                    $toInsert[] = [
                        'tanggal'         => $tgl,
                        'bulan'           => $dt->month,
                        'nama_bulan'      => $dt->locale('id')->isoFormat('MMMM'),
                        'tahun'           => $dt->year,
                        'kuartal'         => (int) ceil($dt->month / 3),
                        'nama_hari'       => $dt->locale('id')->isoFormat('dddd'),
                        'nomor_minggu'    => $dt->weekOfYear,
                        'is_weekend'      => $dt->isWeekend() ? 1 : 0,
                        'periode_laporan' => $dt->format('Y-m'),
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_waktu')->insertOrIgnore($toInsert);
            }
        }

        // ── dim_sto ──────────────────────────────────────────────────────────
        if (!empty($newSto)) {
            $existing = DB::table('dim_sto')
                ->whereIn('nama_sto', array_keys($newSto))
                ->pluck('nama_sto')->flip()->all();

            $toInsert = [];
            foreach (array_keys($newSto) as $sto) {
                if (!isset($existing[$sto])) {
                    $toInsert[] = ['nama_sto' => $sto, 'created_at' => $now, 'updated_at' => $now];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_sto')->insertOrIgnore($toInsert);
            }
        }

        // ── dim_status ───────────────────────────────────────────────────────
        if (!empty($newStatus)) {
            $existing = DB::table('dim_status')
                ->whereIn('status_wo', array_keys($newStatus))
                ->pluck('status_wo')->flip()->all();

            $toInsert = [];
            foreach (array_keys($newStatus) as $sw) {
                if (!isset($existing[$sw])) {
                    $toInsert[] = [
                        'status_wo'    => $sw,
                        'status_final' => $sw,
                        'status_group' => DimStatus::resolveGroup($sw),
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_status')->insertOrIgnore($toInsert);
            }
        }

        // ── dim_kendala ──────────────────────────────────────────────────────
        if (!empty($newKendala)) {
            $existing = DB::table('dim_kendala')
                ->whereIn('kendala_pt1', array_keys($newKendala))
                ->pluck('kendala_pt1')->flip()->all();

            $toInsert = [];
            foreach ($batch as $row) {
                $k = trim($row['kendala_pt1'] ?? 'UNKNOWN');
                if (isset($newKendala[$k]) && !isset($existing[$k]) && !isset($toInsert[$k])) {
                    $toInsert[$k] = [
                        'kendala_pt1'     => $k,
                        'kategori_roc'    => $row['kategori_roc']    ?? null,
                        'kategori_solusi' => $row['kategori_solusi'] ?? null,
                        'solusi_kendala'  => $row['solusi_kendala']  ?? null,
                        'keterangan'      => $row['keterangan']      ?? null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_kendala')->insertOrIgnore(array_values($toInsert));
            }
        }

        // ── dim_teknisi ──────────────────────────────────────────────────────
        if (!empty($newTeknisi)) {
            $existing = DB::table('dim_teknisi')
                ->whereIn('nik_teknisi', array_keys($newTeknisi))
                ->pluck('nik_teknisi')->flip()->all();

            $toInsert = [];
            foreach ($batch as $row) {
                $nik = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
                if (isset($newTeknisi[$nik]) && !isset($existing[$nik]) && !isset($toInsert[$nik])) {
                    $toInsert[$nik] = [
                        'nik_teknisi'   => $nik,
                        'nama_teknisi'  => $row['korlap']        ?? $row['mitra'] ?? '-',
                        'nama_mitra'    => $row['mitra']         ?? null,
                        'korlap'        => $row['korlap']        ?? null,
                        'komandan_team' => $row['komandan_team'] ?? null,
                        'unit_kerja'    => $row['spv']           ?? null,
                        'spv'           => $row['spv']           ?? null,
                        'cp'            => $row['cp']            ?? null,
                        'status_aktif'  => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_teknisi')->insertOrIgnore(array_values($toInsert));
            }
        }

        // ── dim_pelanggan ────────────────────────────────────────────────────
        if (!empty($newPelanggan)) {
            $existing = DB::table('dim_pelanggan')
                ->whereIn('kode_tracking', array_keys($newPelanggan))
                ->pluck('kode_tracking')->flip()->all();

            $toInsert = [];
            foreach ($newPelanggan as $tid => $row) {
                if (!isset($existing[$tid]) && !isset($toInsert[$tid])) {
                    $lat = $this->parseCoord($row['koordinat_lat'] ?? null);
                    $lon = $this->parseCoord($row['koordinat_lon'] ?? null);
                    if ($lat === null && !empty($row['koordinat_pelanggan'])) {
                        [$lat, $lon] = $this->parseKoordinat($row['koordinat_pelanggan']);
                    }
                    $toInsert[$tid] = [
                        'kode_tracking'    => $tid,
                        'nama_pelanggan'   => $row['nama_pelanggan']   ?? null,
                        'nama_contact'     => $row['nama_contact']     ?? null,
                        'segment'          => $row['segment']          ?? null,
                        'layanan'          => $row['layanan']          ?? null,
                        'alamat_instalasi' => $row['alamat_instalasi'] ?? null,
                        'uic'              => $row['uic']              ?? null,
                        'koordinat_lat'    => $lat,
                        'koordinat_lon'    => $lon,
                        'created_at'       => $now,
                        'updated_at'       => $now,
                    ];
                }
            }
            if (!empty($toInsert)) {
                DB::table('dim_pelanggan')->insertOrIgnore(array_values($toInsert));
            }
        }
    }

    // ─── Load cache ID dari DB ────────────────────────────────────────────────

    protected function warmCachesLite(): void
    {
        $this->cacheSto = DB::table('dim_sto')
            ->pluck('sto_id', 'nama_sto')->all();

        $this->cacheStatus = DB::table('dim_status')
            ->get(['status_id', 'status_wo', 'status_group'])
            ->keyBy('status_wo')
            ->map(fn($r) => ['id' => $r->status_id, 'group' => $r->status_group])
            ->all();

        $this->cacheKendala = DB::table('dim_kendala')
            ->pluck('kendala_id', 'kendala_pt1')->all();

        $this->cacheWaktu = DB::table('dim_waktu')
            ->pluck('date_id', 'tanggal')->all();

        $this->cacheTeknisi = DB::table('dim_teknisi')
            ->pluck('teknisi_id', 'nik_teknisi')->all();

        if (DB::table('dim_pelanggan')->count() < 100_000) {
            $this->cachePelanggan = DB::table('dim_pelanggan')
                ->pluck('pelanggan_id', 'kode_tracking')->all();
        }
    }

    // ─── Refresh hanya bagian cache yang berubah ──────────────────────────────
    // Lebih cepat dari warmCachesLite() penuh karena skip tabel yang tidak berubah

    protected function refreshCaches(): void
    {
        $this->cacheSto = DB::table('dim_sto')
            ->pluck('sto_id', 'nama_sto')->all();

        $this->cacheStatus = DB::table('dim_status')
            ->get(['status_id', 'status_wo', 'status_group'])
            ->keyBy('status_wo')
            ->map(fn($r) => ['id' => $r->status_id, 'group' => $r->status_group])
            ->all();

        $this->cacheKendala = DB::table('dim_kendala')
            ->pluck('kendala_id', 'kendala_pt1')->all();

        $this->cacheWaktu = DB::table('dim_waktu')
            ->pluck('date_id', 'tanggal')->all();

        $this->cacheTeknisi = DB::table('dim_teknisi')
            ->pluck('teknisi_id', 'nik_teknisi')->all();

        if (DB::table('dim_pelanggan')->count() < 100_000) {
            $this->cachePelanggan = DB::table('dim_pelanggan')
                ->pluck('pelanggan_id', 'kode_tracking')->all();
        }
    }

    // ─── Flush batch ke fact tables ───────────────────────────────────────────

    protected function flushFactsBatch(array $batch, array &$existingWoIds): array
    {
        $success = $failed = $duplicate = 0;
        $now     = now()->toDateTimeString();

        $factWoRows      = [];
        $factKendalaRows = [];
        $infraInserts    = [];

        foreach ($batch as $row) {
            $woId = trim($row['wo_sc_id'] ?? '');
            if (empty($woId)) continue;
            if (isset($existingWoIds[$woId])) { $duplicate++; continue; }

            $tanggal     = $this->parseDate($row['tanggal'] ?? null) ?? now()->toDateString();
            $stoName     = trim($row['sto'] ?? 'UNKNOWN');
            $nikTeknisi  = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
            $trackId     = trim($row['track_id'] ?? $woId);
            $kendalaName = trim($row['kendala_pt1'] ?? 'UNKNOWN');
            $statusWo    = trim($row['status_wo'] ?? $row['status'] ?? 'UNKNOWN');

            $dateId      = $this->cacheWaktu[$tanggal]      ?? null;
            $stoId       = $this->cacheSto[$stoName]         ?? null;
            $teknisiId   = $this->cacheTeknisi[$nikTeknisi]  ?? null;
            $pelangganId = $this->cachePelanggan[$trackId]   ?? null;
            $kendalaId   = $this->cacheKendala[$kendalaName] ?? null;
            $statusInfo  = $this->cacheStatus[$statusWo]     ?? null;
            $statusId    = $statusInfo['id']                 ?? null;

            if (!$dateId || !$stoId || !$teknisiId || !$pelangganId || !$kendalaId || !$statusId) {
                $failed++;
                Log::debug('ETL FK miss', compact(
                    'woId', 'tanggal', 'stoName', 'nikTeknisi',
                    'trackId', 'kendalaName', 'statusWo'
                ));
                continue;
            }

            $isSla      = ($statusInfo['group'] ?? '') === DimStatus::GROUP_SELESAI;
            $isWorkfail = $this->parseFlag($row['unsc'] ?? $row['is_unsc'] ?? null);
            $durMenit   = $this->parseDurasi($row);

            $infraInserts[] = [
                '_wo_id'                 => $woId,
                'wo_id'                  => $woId,
                'odp'                    => $row['odp']                   ?? null,
                'odc'                    => $row['odc']                   ?? null,
                'gpon'                   => $row['gpon']                  ?? null,
                'feeder'                 => $row['feeder']                ?? null,
                'distribusi'             => $row['distribusi']            ?? null,
                'datek1'                 => $row['datek1']                ?? null,
                'datek_inputan'          => $row['datek_inputan']         ?? null,
                'datek_real'             => $row['datek_real']            ?? null,
                'base_tray_odc'          => $row['base_tray_odc']         ?? null,
                'port_base_tray_odc'     => $row['port_base_tray_odc']    ?? null,
                'hasil_ukur_odp'         => $row['hasil_ukur_odp']        ?? null,
                'hasil_ukur_distribusi'  => $row['hasil_ukur_distribusi'] ?? null,
                'hasil_ukur_feeder'      => $row['hasil_ukur_feeder']     ?? null,
                'status_aktif'           => 1,
                'created_at'             => $now,
                'updated_at'             => $now,
            ];

            $factWoRows[] = [
                'wo_id'                   => $woId,
                'date_id'                 => $dateId,
                'dim_waktu_id'            => $dateId,
                'sto_id'                  => $stoId,
                'dim_sto_id'              => $stoId,
                'teknisi_id'              => $teknisiId,
                'dim_teknisi_id'          => $teknisiId,
                'pelanggan_id'            => $pelangganId,
                'dim_pelanggan_id'        => $pelangganId,
                'kendala_id'              => $kendalaId,
                'dim_kendala_id'          => $kendalaId,
                'status_id'               => $statusId,
                'dim_status_id'           => $statusId,
                'tanggal_order'           => $this->parseDate($row['tanggal_order'] ?? null),
                'tanggal_komitmen'        => $this->parseDate($row['tanggal_komitmen_ps_completed'] ?? null),
                'status_wo'               => $statusWo,
                'status_sc'               => $row['status_sc']  ?? null,
                'durasi_hari'             => $this->parseDecimal($row['durasi_hari'] ?? null),
                'durasi_pengerjaan_menit' => $durMenit,
                'durasi_grup'             => $this->parseDecimal($row['durasi_grup'] ?? null),
                'durasi_manja'            => $this->parseDecimal($row['durasi_manja'] ?? null),
                'tgl_input_hd_gdocs'      => $this->parseDatetime($row['tgl_input_hd_gdocs'] ?? null),
                'is_sla_tercapai'         => $isSla ? 1 : 0,
                'is_workfail'             => $isWorkfail ? 1 : 0,
                'is_unsc'                 => $isWorkfail ? 1 : 0,
                'sc_id'                   => $row['sc_id']    ?? null,
                'track_id'                => $row['track_id'] ?? null,
                'track_id_baru'           => null,
                'keterangan'              => $row['keterangan'] ?? null,
                'created_at'              => $now,
                'updated_at'              => $now,
            ];

            $factKendalaRows[] = [
                '_wo_id'                   => $woId,
                'wo_id'                    => $woId,
                'date_id'                  => $dateId,
                'sto_id'                   => $stoId,
                'kendala_id'               => $kendalaId,
                'dim_kendala_id'           => $kendalaId,
                'jumlah_kendala'           => (int) ($row['jumlah_kendala'] ?? 1),
                'hasil_solusi_maintenance' => $row['hasil_solusi_maintenance'] ?? null,
                'hasil_solusi_optima'      => $row['hasil_solusi_optima']      ?? null,
                'hasil_solusi_sdi'         => $row['hasil_solusi_sdi']         ?? null,
                'total_eskalasi'           => (int) ($row['total_eskalasi']    ?? 0),
                'durasi_grup_pengerjaan'   => $this->parseDecimal($row['durasi_grup_pengerjaan'] ?? null),
                'created_at'               => $now,
                'updated_at'               => $now,
            ];

            $existingWoIds[$woId] = true;
            $success++;
        }

        if (empty($infraInserts)) {
            return [$success, $failed, $duplicate];
        }

        try {
            DB::beginTransaction();

            // Insert dim_infrastruktur (strip temp _wo_id)
            $infraData = array_map(function ($r) {
                unset($r['_wo_id']); return $r;
            }, $infraInserts);

            foreach (array_chunk($infraData, 200) as $chunk) {
                DB::table('dim_infrastruktur')->insert($chunk);
            }

            // Ambil infra_id hasil insert
            $woIds    = array_column($infraInserts, '_wo_id');
            $infraMap = DB::table('dim_infrastruktur')
                ->whereIn('wo_id', $woIds)
                ->pluck('infra_id', 'wo_id')
                ->all();

            // Inject infra_id ke fact_kendalateknis
            foreach ($factKendalaRows as &$fk) {
                $fk['infra_id']             = $infraMap[$fk['_wo_id']] ?? null;
                $fk['dim_infrastruktur_id'] = $fk['infra_id'];
                unset($fk['_wo_id']);
            }
            unset($fk);

            foreach (array_chunk($factWoRows, 200) as $chunk) {
                DB::table('fact_workorder')->insert($chunk);
            }
            foreach (array_chunk($factKendalaRows, 200) as $chunk) {
                DB::table('fact_kendalateknis')->insert($chunk);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ETL flushFactsBatch error', ['error' => $e->getMessage()]);
            $failed  += $success;
            $success  = 0;

            foreach ($infraInserts as $r) {
                try {
                    DB::table('staging_workorder')->insert([
                        'data_json'  => json_encode(array_diff_key($r, ['_wo_id' => true])),
                        'status'     => 'failed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Throwable) {}
            }
        }

        return [$success, $failed, $duplicate];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function parseDate(?string $val): ?string
    {
        if (empty($val)) return null;
        try { return Carbon::parse($val)->toDateString(); }
        catch (\Throwable) { return null; }
    }

    protected function parseDatetime(?string $val): ?string
    {
        if (empty($val)) return null;
        try { return Carbon::parse($val)->toDateTimeString(); }
        catch (\Throwable) { return null; }
    }

    protected function parseCoord(?string $val): ?float
    {
        if (empty($val)) return null;
        $val = str_replace(',', '.', trim($val));
        return is_numeric($val) ? (float) $val : null;
    }

    protected function parseKoordinat(?string $val): array
    {
        if (empty($val)) return [null, null];
        $parts = preg_split('/[\s,;]+/', trim($val));
        return [
            isset($parts[0]) ? $this->parseCoord($parts[0]) : null,
            isset($parts[1]) ? $this->parseCoord($parts[1]) : null,
        ];
    }

    protected function parseDecimal(?string $val): ?float
    {
        if (empty($val)) return null;
        $val = str_replace(',', '.', preg_replace('/[^\d.\-]/', '', $val));
        return is_numeric($val) ? (float) $val : null;
    }

    protected function parseFlag(?string $val): bool
    {
        if (empty($val)) return false;
        return in_array(strtolower(trim($val)), ['1', 'yes', 'ya', 'true', 'y'], true);
    }

    protected function parseDurasi(array $row): ?float
    {
        if (!empty($row['durasi'])) {
            $val = $this->parseDecimal($row['durasi']);
            if ($val !== null) return $val;
        }
        if (!empty($row['durasi_hari'])) {
            $hari = $this->parseDecimal($row['durasi_hari']);
            if ($hari !== null) return $hari * 24 * 60;
        }
        return null;
    }
}