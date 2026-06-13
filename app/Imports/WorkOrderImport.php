<?php

namespace App\Imports;

use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Reader\XLS\Reader as XlsReader;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Options as XlsxOptions;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Common\Entity\Row;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class WorkOrderImport
{
    protected array $fieldAliases = [

        // =========================
        // WORK ORDER
        // =========================

        'wo_sc_id' => [
            'wo_sc_id',
            'wo_sc',
            'wo_scid',
            'wo / sc id',
        ],

        'sc_id' => [
            'sc_id',
            'sc id',
        ],

        'track_id' => [
            'track_id',
            'track id',
        ],

        'track_id_baru' => [
            'track_id_baru',
            'track id baru',
        ],

        // =========================
        // TANGGAL
        // =========================

        'bulan' => ['bulan'],

        'tanggal' => ['tanggal'],

        'tanggal_order' => [
            'tanggal_order',
            'tanggal order',
        ],

        'tanggal_komitmen_ps_completed' => [
            'tanggal_komitmen_ps_completed',
            'tanggal komitmen ps completed',
        ],

        'tgl_input_hd_gdocs' => [
            'tgl_input_hd_gdocs',
            'tgl input hd gdocs',
        ],

        'tgl_jam_manja' => [
            'tgl_jam_manja',
            'tgl jam manja',
            'tgl & jam manja',
        ],

        // =========================
        // STO
        // =========================

        'sto'       => ['sto'],
        'sto_input' => ['sto_input', 'sto input'],
        'branch'    => ['branch'],
        'sektor'    => ['sektor'],
        'hsa'       => ['hsa'],

        // =========================
        // TEKNISI
        // =========================

        'mitra'   => ['mitra'],
        'korlap'  => ['korlap'],

        'nik_teknisi' => [
            'nik_teknisi',
            'nik teknisi',
        ],

        'komandan_team' => [
            'komandan_team',
            'komandan team/pic team',
        ],

        'spv' => ['spv'],
        'cp'  => ['cp'],
        'uic' => ['uic'],

        // =========================
        // PELANGGAN
        // =========================

        'nama_pelanggan' => [
            'nama_pelanggan',
            'nama pelanggan',
        ],

        'nama_contact' => [
            'nama_contact',
            'k_contact',
            'k-contact',
        ],

        'segment'          => ['segment'],
        'layanan'          => ['layanan'],

        'alamat_instalasi' => [
            'alamat_instalasi',
            'alamat instalasi',
        ],

        'koordinat_pelanggan' => [
            'koordinat_pelanggan',
            'koordinat pelanggan',
        ],

        // =========================
        // KENDALA
        // =========================

        'kendala_pt1' => [
            'kendala_pt1',
            'kendala pt1',
        ],

        'kategori_roc' => [
            'kategori_roc',
            'kategori roc',
        ],

        'kategori_solusi' => [
            'kategori_solusi',
            'kategori solusi',
        ],

        'solusi_kendala' => [
            'solusi_kendala',
            'solusi kendala',
        ],

        'info_detail_sc_baru' => [
            'info detail / sc baru ganti datek',
        ],

        // =========================
        // INFRASTRUKTUR
        // =========================

        'odp'        => ['odp'],
        'odc'        => ['odc'],
        'gpon'       => ['gpon'],
        'feeder'     => ['feeder'],

        'distribusi' => [
            'distribusi',
            'core distribusi',
        ],

        'datek1'        => ['datek1'],
        'datek_inputan' => ['datek inputan', 'datek_inputan'],
        'datek_real'    => ['datek real', 'datek_real'],

        'hasil_ukur_odp'         => ['hasil ukur odp'],
        'hasil_ukur_distribusi'  => ['hasil ukur distribusi'],
        'hasil_ukur_feeder'      => ['hasil ukur feeder'],

        // =========================
        // STATUS
        // =========================

        'status_wo' => ['status', 'status wo'],
        'status_sc' => ['status sc'],

        // =========================
        // DURASI
        // =========================

        'durasi_hari' => [
            'durasi_hari',
            'durasi hari',
            'durasi (hari)',
        ],

        'durasi' => [
            'durasi',
            'durasi pengerjaan kendala',
        ],

        'durasi_grup'            => ['durasi grup'],
        'durasi_manja'           => ['durasi_manja', 'durasi manja'],
        'durasi_grup_pengerjaan' => ['durasi grup pengerjaan kendala teknis'],

        // =========================
        // SOLUSI
        // =========================

        'hasil_solusi_maintenance' => ['solusi maintenance'],
        'hasil_solusi_optima'      => ['solusi optima'],

        'hasil_solusi_sdi' => [
            'solusi sdi daman',
            'solusi sdi & daman',
        ],

        // =========================
        // KETERANGAN
        // =========================

        'keterangan' => ['keterangan'],

        'keterangan_sm_provisioning' => [
            'keterangan sm provisioning',
            'keterangan (sm provisioning)',
            'keterangan_n_sm_provisioning',
            'keterangan\n(sm provisioning)',
        ],

        'keterangan_tl_provisioning' => [
            'keterangan tl provisioning',
            'keterangan (tl provisioning)',
            'keterangan_n_tl_provisioning',
            'keterangan \n(tl provisioning)',
        ],

        'core_distribusi' => [
            'core distribusi',
            'core_distribusi',
        ],
    ];

    protected array $requiredHeaders = [
        'wo_sc_id',
        'tanggal',
        'sto',
        'nama_pelanggan',
    ];

    // ─── Public API ───────────────────────────────────────────────────────────

    /**
     * ✅ FIX UTAMA: baca file 1x saja untuk header + preview rows sekaligus
     * Versi lama baca 2x → sekarang 1x → 2x lebih cepat untuk preview
     */
    public function analyzeHeaders(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        // Satu pass: ambil header + 20 baris preview sekaligus
        [$headerMap, $rawHeaders, $previewRows] = $this->readHeaderAndPreview($path, [], 20);

        $mappedCanonicals = array_values(array_filter($headerMap));

        return [
            'raw_headers'      => $rawHeaders,
            'header_map'       => $headerMap,
            'missing_headers'  => array_values(array_diff($this->requiredHeaders, $mappedCanonicals)),
            'unmapped_headers' => $this->getUnmappedHeaders($rawHeaders, $headerMap),
            'rows'             => $previewRows,
        ];
    }

    /**
     * Generator: yield satu baris per iterasi — true streaming, memori konstan.
     */
    public function rowGenerator(UploadedFile|string $file, array $manualMapping = []): \Generator
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        // Untuk streaming, baca header saja dulu (1 baris pertama)
        [$headerMap] = $this->readHeaderAndPreview($path, $manualMapping, 0);

        yield from $this->streamRows($path, $headerMap);
    }

    public function loadRows(UploadedFile|string $file, array $manualMapping = [], int $limit = 0): array
    {
        $rows = [];
        foreach ($this->rowGenerator($file, $manualMapping) as $row) {
            $rows[] = $row;
            if ($limit > 0 && count($rows) >= $limit) break;
        }
        return $rows;
    }

    public function getCanonicalHeaderLabels(): array
    {
        $headers = array_keys($this->fieldAliases);
        return array_combine($headers, array_map('strtoupper', $headers));
    }

    public function getRequiredHeaders(): array
    {
        return $this->requiredHeaders;
    }

    // ─── Core: baca header + preview dalam 1x buka file ──────────────────────

    /**
     * Buka file SEKALI — ambil header baris 1 + preview $previewLimit baris.
     * Kalau $previewLimit = 0, hanya ambil header saja (untuk rowGenerator).
     *
     * @return array{0: array, 1: array, 2: array}
     *   [0] headerMap    — colIndex => canonical|null
     *   [1] rawHeaders   — colIndex => raw string
     *   [2] previewRows  — array of mapped rows
     */
    protected function readHeaderAndPreview(
        string $path,
        array  $manualMapping = [],
        int    $previewLimit  = 20
    ): array {
        $rawHeaders = [];
        $headerMap  = [];
        $previewRows= [];
        $rowNum     = 0;

        // Normalisasi manual mapping
        $manualNorm = [];
        foreach ($manualMapping as $raw => $canonical) {
            $manualNorm[$this->normalizeHeader($raw)] = $canonical;
        }

        $reader = $this->makeReader($path);
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            $iterator = $sheet->getRowIterator();
            $iterator->rewind();

            while ($iterator->valid()) {
                try {
                    $row = $iterator->current();
                    $rowNum++;

                    if ($rowNum === 1) {
                        // ── Baris header ─────────────────────────────────
                        foreach ($row->getCells() as $colIndex => $cell) {
                            try {
                                $raw = trim((string) $cell->getValue());
                            } catch (\Throwable) {
                                $raw = '';
                            }
                            $rawHeaders[$colIndex] = $raw;
                            $normalized            = $this->normalizeHeader($raw);
                            $headerMap[$colIndex]  = $manualNorm[$normalized]
                                ?? $this->getCanonicalByNormalized($normalized);
                        }
                    } else {
                        // ── Baris data (preview) ──────────────────────────
                        if ($previewLimit > 0 && count($previewRows) < $previewLimit) {
                            $rowData = $this->mapRowToFieldsSafe($row, $headerMap);
                            if (array_filter($rowData)) {
                                $previewRows[] = $rowData;
                            }
                        }
                    }

                    // Berhenti kalau preview sudah cukup
                    if ($previewLimit > 0 && count($previewRows) >= $previewLimit) {
                        break;
                    }

                    // Kalau hanya butuh header (previewLimit=0), berhenti setelah baris 1
                    if ($previewLimit === 0 && $rowNum >= 1) {
                        break;
                    }

                } catch (\Throwable $e) {
                    Log::warning('WorkOrderImport: skip baris di readHeaderAndPreview', [
                        'row'   => $rowNum,
                        'error' => $e->getMessage(),
                    ]);
                }

                try {
                    $iterator->next();
                } catch (\Throwable $e) {
                    Log::warning('WorkOrderImport: iterator->next() error', [
                        'row'   => $rowNum,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            }
            break; // hanya sheet pertama
        }

        $reader->close();

        return [$headerMap, $rawHeaders, $previewRows];
    }

    // ─── Generator streaming ─────────────────────────────────────────────────

    protected function streamRows(string $path, array $headerMap): \Generator
    {
        $rowNum = 0;

        $reader = $this->makeReader($path);
        $reader->open($path);

        foreach ($reader->getSheetIterator() as $sheet) {
            $iterator = $sheet->getRowIterator();
            $iterator->rewind();

            while ($iterator->valid()) {
                try {
                    $row = $iterator->current();
                    $rowNum++;

                    if ($rowNum > 1) {
                        $rowData = $this->mapRowToFieldsSafe($row, $headerMap);
                        if (array_filter($rowData)) {
                            yield $rowData;
                        }
                    }

                } catch (\Throwable $e) {
                    Log::warning('WorkOrderImport: skip baris stream', [
                        'row'   => $rowNum,
                        'error' => $e->getMessage(),
                    ]);
                }

                try {
                    $iterator->next();
                } catch (\Throwable $e) {
                    Log::warning('WorkOrderImport: iterator->next() error di stream', [
                        'row'   => $rowNum,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            }
            break;
        }

        $reader->close();
    }

    // ─── Buat reader ─────────────────────────────────────────────────────────

    protected function makeReader(string $path): XlsxReader|XlsReader|CsvReader
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $options                  = new CsvOptions();
            $options->FIELD_DELIMITER = ',';
            $options->FIELD_ENCLOSURE = '"';
            return new CsvReader($options);
        }

        if ($ext === 'xls') {
            return new XlsReader();
        }

        // xlsx default
        $options = new XlsxOptions();
        $options->SHOULD_FORMAT_DATES        = false;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = false;
        return new XlsxReader($options);
    }

    // ─── Map cells ke fields ──────────────────────────────────────────────────

    protected function mapRowToFieldsSafe(Row $row, array $headerMap): array
    {
        $rowData = [];
        foreach ($row->getCells() as $colIndex => $cell) {
            $key = $headerMap[$colIndex] ?? null;
            if ($key === null) continue;
            try {
                $val           = $cell->getValue();
                $rowData[$key] = trim((string) ($val ?? ''));
            } catch (\Throwable) {
                $rowData[$key] = '';
            }
        }
        return $rowData;
    }

    // ─── Header mapping helpers ───────────────────────────────────────────────

    protected function getUnmappedHeaders(array $rawHeaders, array $headerMap): array
    {
        $unmapped = [];
        foreach ($rawHeaders as $colIndex => $header) {
            if (!empty($header) && empty($headerMap[$colIndex])) {
                $unmapped[$colIndex] = $header;
            }
        }
        return $unmapped;
    }

    protected function getCanonicalByNormalized(string $normalized): ?string
    {
        if (empty($normalized)) return null;

        foreach ($this->fieldAliases as $canonical => $aliases) {
            // Cek exact match dengan canonical name-nya dulu
            if ($normalized === $this->normalizeHeader($canonical)) {
                return $canonical;
            }
            // Cek aliases
            foreach ($aliases as $alias) {
                if ($normalized === $this->normalizeHeader($alias)) {
                    return $canonical;
                }
            }
        }
        return null;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = preg_replace('/_+/', '_', $header);
        return trim($header, '_');
    }

    protected function splitWoScId(?string $value): array
    {
        preg_match('/WO[0-9]+/i', $value ?? '', $wo);
        preg_match('/SC[0-9]+/i', $value ?? '', $sc);
        return [
            'wo_id' => $wo[0] ?? null,
            'sc_id' => $sc[0] ?? null,
        ];
    }
}