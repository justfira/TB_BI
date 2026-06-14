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
    protected bool $cachePelangganFull = false;

    // ─── Hapus data ETL ──────────────────────────────────────────────────────

    /**
     * Hapus semua data hasil ETL (full) untuk mendukung tombol "Hapus Semua".
     */
    public function deleteAllEtlData(): array
    {
        return DB::transaction(function () {
            $factIds = DB::table('fact_workorder')->pluck('id')->all();

            $deletedKendala = 0;
            if (!empty($factIds)) {
                $deletedKendala = DB::table('fact_kendalateknis')
                    ->whereIn('fact_workorder_id', $factIds)
                    ->delete();
            }

            $deletedFact  = DB::table('fact_workorder')->delete();
            $deletedInfra = DB::table('dim_infrastruktur')->delete();

            return [
                'fact_workorder'     => (int) $deletedFact,
                'fact_kendalateknis' => (int) $deletedKendala,
                'dim_infrastruktur'  => (int) $deletedInfra,
            ];
        });
    }

    /**
     * Hapus seluruh data hasil ETL untuk sebuah log_id (fact + infra + kendalateknis).
     * Catatan: dim_* yang di-share tidak dihapus.
     */
    public function deleteEtlBatch(int $etlLogId): array
    {
        return DB::transaction(function () use ($etlLogId) {
            $factIds = DB::table('fact_workorder')
                ->where('etl_log_id', $etlLogId)
                ->pluck('id')
                ->all();

            $deletedKendala = 0;
            if (!empty($factIds)) {
                $deletedKendala = DB::table('fact_kendalateknis')
                    ->whereIn('fact_workorder_id', $factIds)
                    ->delete();
            }

            $deletedFact = DB::table('fact_workorder')
                ->where('etl_log_id', $etlLogId)
                ->delete();

            $deletedInfra = DB::table('dim_infrastruktur')
                ->where('etl_log_id', $etlLogId)
                ->delete();

            return [
                'fact_workorder'     => (int) $deletedFact,
                'fact_kendalateknis' => (int) $deletedKendala,
                'dim_infrastruktur'  => (int) $deletedInfra,
            ];
        });
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

    // ─── Proses utama ETL ───────────────────────────────────────────────────

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
        $batchSize = 500;
        $batch = [];
        $lastUpdateTotal = 0;
        $existingWoIds = [];
        $now = now()->toDateTimeString();

        try {
            $this->warmCachesLite();

            foreach ($this->importer->rowGenerator($filePath, $manualMapping) as $row) {
                $total++;
                $batch[] = $row;

                if (count($batch) >= $batchSize) {
                    [$bS, $fS, $dS] = $this->processBatch($batch, $existingWoIds, $log->id, $now);
                    $success   += $bS;
                    $failed    += $fS;
                    $duplicate += $dS;
                    $batch = [];

                    if ($total - $lastUpdateTotal >= 500) {
                        DB::table('etl_logs')->where('id', $log->id)->update([
                            'total_rows'    => $total,
                            'success_count' => $success,
                            'failed_count'  => $failed,
                            'duplicate_count' => $duplicate,
                            'updated_at'    => now(),
                        ]);
                        $lastUpdateTotal = $total;
                    }
                }
            }

            if (!empty($batch)) {
                [$bS, $fS, $dS] = $this->processBatch($batch, $existingWoIds, $log->id, $now);
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

            return $log->fresh();

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

    protected function processBatch(array $batch, array &$existingWoIds, int $etlLogId, string $now): array
    {
        $tanggals      = [];
        $stoNames      = [];
        $statusNames   = [];
        $kendalaNames  = [];
        $niks          = [];
        $kContacts     = [];
        $uniquePelanggan = [];

        foreach ($batch as $row) {
            $tanggal      = $this->parseDate($row['tanggal'] ?? null);
            $sto          = trim($row['sto'] ?? 'UNKNOWN');
            $status       = trim($row['status_wo'] ?? $row['status'] ?? 'UNKNOWN');
            $kendala      = trim($row['kendala_pt1'] ?? 'UNKNOWN');
            $nik          = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
            $kContact     = trim($row['track_id'] ?? $row['wo_sc_id'] ?? 'UNKNOWN');

            if ($tanggal) {
                $tanggals[$tanggal] = true;
            }
            if ($sto !== '') {
                $stoNames[$sto] = true;
            }
            if ($status !== '') {
                $statusNames[$status] = true;
            }
            if ($kendala !== '') {
                $kendalaNames[$kendala] = true;
            }
            if ($nik !== '') {
                $niks[$nik] = true;
            }
            if ($kContact !== '') {
                $kContacts[$kContact] = true;
                $uniquePelanggan[$kContact] = $row;
            }
        }

        $this->bulkUpsertWaktu(array_keys($tanggals), $now);
        $this->bulkUpsertSto(array_keys($stoNames), $now);
        $this->bulkUpsertStatus(array_keys($statusNames), $now);
        $this->bulkUpsertKendala($kendalaNames, $batch, $now);
        $this->bulkUpsertTeknisi($niks, $batch, $now);
        $this->bulkUpsertPelanggan($uniquePelanggan, $now);

        $this->prefetchPelangganCache($kContacts);
        $this->prefetchExistingWorkOrderIds($batch, $existingWoIds);

        return $this->flushFactsBatch($batch, $existingWoIds, $now, $etlLogId);
    }

    protected function prefetchExistingWorkOrderIds(array $batch, array &$existingWoIds): void
    {
        $woIds = [];
        foreach ($batch as $row) {
            $woScId = trim($row['wo_sc_id'] ?? '');
            if ($woScId !== '') {
                $woIds[$woScId] = true;
            }
        }

        if (empty($woIds)) {
            return;
        }

        $existing = $this->getExistingWorkOrderIds(array_keys($woIds));
        $existingWoIds += $existing;
    }

    protected function getExistingWorkOrderIds(array $woIds): array
    {
        if (empty($woIds)) {
            return [];
        }

        $existing = [];
        foreach (array_chunk(array_unique($woIds), 1000) as $chunk) {
            $existing += DB::table('fact_workorder')
                ->whereIn('wo_sc_id', $chunk)
                ->pluck('wo_sc_id')
                ->flip()
                ->all();
        }

        return $existing;
    }

    protected function prefetchPelangganCache(array $kContacts): void
    {
        if ($this->cachePelangganFull || empty($kContacts)) {
            return;
        }

        $missing = array_filter(array_unique($kContacts), fn($contact) => $contact !== '' && !isset($this->cachePelanggan[$contact]));
        if (empty($missing)) {
            return;
        }

        $found = [];
        foreach (array_chunk($missing, 1000) as $chunk) {
            $found += DB::table('dim_pelanggan')
                ->whereIn('k_contact', $chunk)
                ->pluck('id', 'k_contact')
                ->all();
        }

        $this->cachePelanggan += $found;
    }

    // ─── Bulk upsert dimensi ─────────────────────────────────────────────────

    protected function bulkUpsertWaktu(array $tanggals, string $now): void
    {
        if (empty($tanggals)) return;
        $existing = DB::table('dim_waktu')->whereIn('tanggal', $tanggals)->pluck('tanggal')->flip()->all();
        $toInsert = [];
        foreach ($tanggals as $tgl) {
            if (isset($existing[$tgl])) continue;
            $dt = Carbon::parse($tgl);
            $toInsert[] = [
                'tanggal'    => $tgl,
                'tahun'      => $dt->year,
                'bulan'      => $dt->month,
                'hari'       => $dt->day,
                'nama_bulan' => $dt->locale('id')->isoFormat('MMMM'),
                'nama_hari'  => $dt->locale('id')->isoFormat('dddd'),
                'kuartal'    => (int) ceil($dt->month / 3),
                'hari_kerja' => $dt->isWeekday() ? 1 : 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_waktu')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_waktu')->whereIn('tanggal', array_keys($tanggals))->pluck('id', 'tanggal')->all();
            $this->cacheWaktu += $newCache;
        }
    }

    protected function bulkUpsertSto(array $stoNames, string $now): void
    {
        if (empty($stoNames)) return;
        $existing = DB::table('dim_sto')->whereIn('nama_sto', $stoNames)->pluck('nama_sto')->flip()->all();
        $toInsert = [];
        foreach ($stoNames as $sto) {
            if (isset($existing[$sto])) continue;
            $kode = substr(strtoupper(preg_replace('/[^A-Z0-9]/', '', strtoupper($sto))) ?: 'UNK', 0, 40);
            $toInsert[] = [
                'kode_sto'   => $kode . '_' . substr(md5($sto), 0, 6),
                'nama_sto'   => $sto,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_sto')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_sto')->whereIn('nama_sto', array_keys($stoNames))->pluck('id', 'nama_sto')->all();
            $this->cacheSto += $newCache;
        }
    }

    protected function bulkUpsertStatus(array $statusNames, string $now): void
    {
        if (empty($statusNames)) return;
        $existing = DB::table('dim_status')->whereIn('status_name', $statusNames)->pluck('status_name')->flip()->all();
        $toInsert = [];
        foreach ($statusNames as $sw) {
            if (isset($existing[$sw])) continue;
            $toInsert[] = [
                'status_name'  => $sw,
                'status_group' => DimStatus::resolveGroup($sw),
                'aktif'        => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_status')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_status')->whereIn('status_name', array_keys($statusNames))->pluck('id', 'status_name')->all();
            foreach ($newCache as $statusName => $id) {
                $this->cacheStatus[$statusName] = [
                    'id' => $id,
                    'group' => DimStatus::resolveGroup($statusName),
                ];
            }
        }
    }

    protected function bulkUpsertKendala(array $uniqueKendala, array $allRows, string $now): void
    {
        if (empty($uniqueKendala)) return;
        $kendalaNames = array_keys($uniqueKendala);
        $existing     = DB::table('dim_kendala')->whereIn('kendala_pt1', $kendalaNames)->pluck('kendala_pt1')->flip()->all();

        $detailMap = [];
        foreach ($allRows as $row) {
            $k = trim($row['kendala_pt1'] ?? 'UNKNOWN');
            if (!isset($existing[$k]) && !isset($detailMap[$k])) {
                $detailMap[$k] = $row;
            }
        }

        $toInsert = [];
        foreach ($kendalaNames as $k) {
            if (isset($existing[$k])) continue;
            $row = $detailMap[$k] ?? [];
            $toInsert[] = [
                'kendala_pt1'     => $k,
                'kategori_roc'    => $row['kategori_roc']    ?? null,
                'kategori_solusi' => $row['kategori_solusi'] ?? null,
                'solusi_kendala'  => $row['solusi_kendala']  ?? null,
                'keterangan'      => $row['keterangan']      ?? null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_kendala')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_kendala')->whereIn('kendala_pt1', array_keys($uniqueKendala))->pluck('id', 'kendala_pt1')->all();
            $this->cacheKendala += $newCache;
        }
    }

    protected function bulkUpsertTeknisi(array $uniqueTeknisi, array $allRows, string $now): void
    {
        if (empty($uniqueTeknisi)) return;
        $niks     = array_keys($uniqueTeknisi);
        $existing = DB::table('dim_teknisi')->whereIn('nik_teknisi', $niks)->pluck('nik_teknisi')->flip()->all();

        $detailMap = [];
        foreach ($allRows as $row) {
            $nik = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
            if (!isset($existing[$nik]) && !isset($detailMap[$nik])) {
                $detailMap[$nik] = $row;
            }
        }

        $toInsert = [];
        foreach ($niks as $nik) {
            if (isset($existing[$nik])) continue;
            $row = $detailMap[$nik] ?? [];
            $toInsert[] = [
                'nik_teknisi'   => $nik,
                'nama_teknisi'  => $row['korlap']        ?? $row['mitra'] ?? '-',
                'korlap'        => $row['korlap']        ?? null,
                'komandan_team' => $row['komandan_team'] ?? null,
                'mitra'         => $row['mitra']         ?? null,
                'nama_mitra'    => $row['mitra']         ?? null,
                'spv'           => $row['spv']           ?? null,
                'cp'            => $row['cp']            ?? null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_teknisi')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_teknisi')->whereIn('nik_teknisi', array_keys($uniqueTeknisi))->pluck('id', 'nik_teknisi')->all();
            $this->cacheTeknisi += $newCache;
        }
    }

    protected function bulkUpsertPelanggan(array $uniquePelanggan, string $now): void
    {
        if (empty($uniquePelanggan)) return;
        $kContacts = array_keys($uniquePelanggan);
        $existing  = DB::table('dim_pelanggan')->whereIn('k_contact', $kContacts)->pluck('k_contact')->flip()->all();

        $toInsert = [];
        foreach ($uniquePelanggan as $kContact => $row) {
            if (isset($existing[$kContact])) continue;
            $lat = $this->parseCoord($row['koordinat_lat'] ?? null);
            $lon = $this->parseCoord($row['koordinat_lon'] ?? null);
            if ($lat === null && !empty($row['koordinat_pelanggan'])) {
                [$lat, $lon] = $this->parseKoordinat($row['koordinat_pelanggan']);
            }
            $toInsert[] = [
                'k_contact'           => $kContact,
                'nama_pelanggan'      => $row['nama_pelanggan']      ?? null,
                'nama_contact'        => $row['nama_contact']        ?? null,
                'segment'             => $row['segment']             ?? null,
                'layanan'             => $row['layanan']             ?? null,
                'alamat_instalasi'    => $row['alamat_instalasi']    ?? null,
                'uic'                 => $row['uic']                 ?? null,
                'koordinat_pelanggan' => $row['koordinat_pelanggan'] ?? null,
                'koordinat_lat'       => $lat,
                'koordinat_lon'       => $lon,
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }
        if (!empty($toInsert)) {
            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('dim_pelanggan')->insertOrIgnore($chunk);
            }
            $newCache = DB::table('dim_pelanggan')->whereIn('k_contact', array_keys($uniquePelanggan))->pluck('id', 'k_contact')->all();
            $this->cachePelanggan += $newCache;
        }
    }

    // ─── Load cache ID saja ───────────────────────────────────────────────────

    protected function warmCachesLite(): void
    {
        $this->cacheSto = DB::table('dim_sto')->pluck('id', 'nama_sto')->all();

        $this->cacheStatus = DB::table('dim_status')
            ->get(['id', 'status_name', 'status_group'])
            ->mapWithKeys(fn($r) => [
                $r->status_name => ['id' => $r->id, 'group' => $r->status_group]
            ])->all();

        $this->cacheKendala  = DB::table('dim_kendala')->pluck('id', 'kendala_pt1')->all();
        $this->cacheWaktu    = DB::table('dim_waktu')->pluck('id', 'tanggal')->all();
        $this->cacheTeknisi  = DB::table('dim_teknisi')->pluck('id', 'nik_teknisi')->all();

        $pelangganCount = DB::table('dim_pelanggan')->count();
        if ($pelangganCount <= 100_000) {
            $this->cachePelanggan = DB::table('dim_pelanggan')
                ->whereNotNull('k_contact')
                ->pluck('id', 'k_contact')
                ->all();
            $this->cachePelangganFull = true;
        } else {
            $this->cachePelanggan = [];
            $this->cachePelangganFull = false;
        }
    }

    // ─── Flush 1000 baris ke fact tables ─────────────────────────────────────

    protected function flushFactsBatch(array $batch, array &$existingWoIds, string $now, int $etlLogId): array
    {
        $success = $failed = $duplicate = 0;
        $validRows    = [];
        $infraInserts = [];

        $batchSeen = [];
        foreach ($batch as $row) {
            $woScId = trim($row['wo_sc_id'] ?? '');
            if (empty($woScId)) continue;
            if (isset($existingWoIds[$woScId])) { $duplicate++; continue; }
            if (isset($batchSeen[$woScId])) { $duplicate++; continue; }
            $batchSeen[$woScId] = true;

            $tanggal    = $this->parseDate($row['tanggal'] ?? null) ?? now()->toDateString();
            $stoName    = trim($row['sto'] ?? 'UNKNOWN');
            $nik        = trim($row['nik_teknisi'] ?? '') ?: 'UNKNOWN';
            $kContact   = trim($row['track_id'] ?? $woScId);
            $kendala    = trim($row['kendala_pt1'] ?? 'UNKNOWN');
            $statusName = trim($row['status_wo'] ?? $row['status'] ?? 'UNKNOWN');

            $waktuId    = $this->cacheWaktu[$tanggal]     ?? null;
            $stoId      = $this->cacheSto[$stoName]        ?? null;
            $teknisiId  = $this->cacheTeknisi[$nik]        ?? null;
            $pelId      = $this->cachePelanggan[$kContact] ?? null;
            $kendalaId  = $this->cacheKendala[$kendala]    ?? null;
            $statusInfo = $this->cacheStatus[$statusName]  ?? null;
            $statusId   = $statusInfo['id']                ?? null;

            if (!$waktuId || !$stoId || !$teknisiId || !$pelId || !$kendalaId || !$statusId) {
                $failed++;
                Log::debug('ETL FK miss', compact('woScId', 'tanggal', 'stoName', 'nik', 'kContact', 'kendala', 'statusName'));
                continue;
            }

            $isSla      = ($statusInfo['group'] ?? '') === DimStatus::GROUP_SELESAI;
            $isWorkfail = $this->parseFlag($row['unsc'] ?? $row['is_unsc'] ?? null);

            $validRows[] = compact('woScId', 'waktuId', 'stoId', 'teknisiId', 'pelId', 'kendalaId', 'statusId', 'isSla', 'isWorkfail', 'row');
            if (!isset($infraInserts[$woScId])) {
                $infraInserts[$woScId] = [
                    '_wo_sc_id'             => $woScId,
                    'wo_id'                 => $woScId,
                    'etl_log_id'            => $etlLogId,
                    'odp'                   => $row['odp']                   ?? null,
                    'odc'                   => $row['odc']                   ?? null,
                    'gpon'                  => $row['gpon']                  ?? null,
                    'feeder'                => $row['feeder']                ?? null,
                    'distribusi'            => $row['distribusi']            ?? null,
                    'core_distribusi'       => $row['distribusi']            ?? null,
                    'datek1'                => $row['datek1']                ?? null,
                    'datek_inputan'         => $row['datek_inputan']         ?? null,
                    'datek_real'            => $row['datek_real']            ?? null,
                    'base_tray_odc'         => $row['base_tray_odc']         ?? null,
                    'port_base_tray_odc'    => $row['port_base_tray_odc']    ?? null,
                    'hasil_ukur_odp'        => $row['hasil_ukur_odp']        ?? null,
                    'hasil_ukur_distribusi' => $row['hasil_ukur_distribusi'] ?? null,
                    'hasil_ukur_feeder'     => $row['hasil_ukur_feeder']     ?? null,
                    'created_at'            => $now,
                    'updated_at'            => $now,
                ];
            }

            $existingWoIds[$woScId] = true;
        }

        if (empty($validRows)) return [$success, $failed, $duplicate];

        try {
            DB::beginTransaction();

            // 1. Bulk insert infrastruktur
            $infraData = array_map(fn($r) => array_diff_key($r, ['_wo_sc_id' => 0]), $infraInserts);
            foreach (array_chunk($infraData, 1000) as $chunk) {
                DB::table('dim_infrastruktur')->insert($chunk);
            }

            // 2. Ambil infra_id
            $woScIds  = array_column($infraInserts, '_wo_sc_id');
            $infraMap = DB::table('dim_infrastruktur')
                ->whereIn('wo_id', $woScIds)
                ->pluck('id', 'wo_id')
                ->all();

            // 3. Build + insert fact_workorder
            $factWoRows = [];
            foreach ($validRows as $vr) {
                $infraId = $infraMap[$vr['woScId']] ?? null;
                if (!$infraId) { $failed++; continue; }
                $row = $vr['row'];
                $factWoRows[] = [
                    'wo_sc_id'                   => $vr['woScId'],
                    'sc_id'                      => $row['sc_id']         ?? null,
                    'track_id'                   => $row['track_id']      ?? null,
                    'track_id_baru'              => $row['track_id_baru'] ?? null,
                    'dim_waktu_id'               => $vr['waktuId'],
                    'dim_sto_id'                 => $vr['stoId'],
                    'dim_teknisi_id'             => $vr['teknisiId'],
                    'dim_pelanggan_id'           => $vr['pelId'],
                    'dim_kendala_id'             => $vr['kendalaId'],
                    'dim_infrastruktur_id'       => $infraId,
                    'dim_status_id'              => $vr['statusId'],
                    'tanggal_order'              => $this->parseDate($row['tanggal_order'] ?? null),
                    'tanggal_komitmen'           => $this->parseDate($row['tanggal_komitmen_ps_completed'] ?? null),
                    'tgl_input_hd_gdocs'         => $this->parseDate($row['tgl_input_hd_gdocs'] ?? null),
                    'status_wo'                  => $row['status_wo'] ?? $row['status'] ?? null,
                    'status_sc'                  => $row['status_sc']  ?? null,
                    'durasi_hari'                => $this->parseDecimal($row['durasi_hari'] ?? null),
                    'durasi'                     => $this->parseDecimal($row['durasi'] ?? null),
                    'durasi_manja'               => $this->parseDecimal($row['durasi_manja'] ?? null),
                    'durasi_pengerjaan_kendala'  => $this->parseDurasi($row),
                    'durasi_grup'                => $row['durasi_grup'] ?? null,
                    'is_sla_tercapai'            => $vr['isSla'] ? 1 : 0,
                    'is_workfail'                => $vr['isWorkfail'] ? 1 : 0,
                    'is_unsc'                    => $vr['isWorkfail'] ? 1 : 0,
                    'etl_log_id'                 => $etlLogId,
                    'keterangan'                 => $row['keterangan'] ?? null,
                    'keterangan_sm_provisioning' => $row['keterangan_sm_provisioning'] ?? null,
                    'keterangan_tl_provisioning' => $row['keterangan_tl_provisioning'] ?? null,
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ];
                $success++;
            }

            foreach (array_chunk($factWoRows, 1000) as $chunk) {
                DB::table('fact_workorder')->insertOrIgnore($chunk);
            }

            // 4. fact_kendalateknis
            $insertedIds = array_column($factWoRows, 'wo_sc_id');
            $factWoMap   = DB::table('fact_workorder')
                ->whereIn('wo_sc_id', $insertedIds)
                ->pluck('id', 'wo_sc_id')
                ->all();

            $factKendalaRows = [];
            foreach ($validRows as $vr) {
                $factWoId = $factWoMap[$vr['woScId']] ?? null;
                if (!$factWoId) continue;
                $row = $vr['row'];
                $factKendalaRows[] = [
                    'fact_workorder_id'        => $factWoId,
                    'dim_kendala_id'           => $vr['kendalaId'],
                    'dim_teknisi_id'           => $vr['teknisiId'],
                    'dim_status_id'            => $vr['statusId'],
                    'keterangan'               => $row['keterangan']      ?? null,
                    'resolusi_jam'             => $this->parseDecimal($row['durasi'] ?? null),
                    'root_cause'               => $row['kendala_pt1']     ?? null,
                    'durasi_grup_pengerjaan'   => $this->parseDecimal($row['durasi_grup_pengerjaan'] ?? null),
                    'hasil_solusi_maintenance' => $row['hasil_solusi_maintenance'] ?? null,
                    'hasil_solusi_optima'      => $row['hasil_solusi_optima']      ?? null,
                    'hasil_solusi_sdi'         => $row['hasil_solusi_sdi']         ?? null,
                    'total_eskalasi'           => (int) ($row['total_eskalasi'] ?? 0),
                    'jumlah_kendala'           => (int) ($row['jumlah_kendala'] ?? 1),
                    'created_at'               => $now,
                    'updated_at'               => $now,
                ];
            }

            foreach (array_chunk($factKendalaRows, 1000) as $chunk) {
                DB::table('fact_kendalateknis')->insert($chunk);
            }

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('ETL batch error', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            $failed  += $success;
            $success  = 0;
        }

        return [$success, $failed, $duplicate];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    protected function parseDate(?string $val): ?string
    {
        if (empty($val)) return null;
        if (is_numeric($val)) {
            $int = (int) $val;
            if ($int > 32874 && $int < 73050) {
                try {
                    return Carbon::createFromTimestamp(($int - 25569) * 86400)->toDateString();
                } catch (\Throwable) {}
            }
            return null;
        }
        try { return Carbon::parse($val)->toDateString(); }
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

