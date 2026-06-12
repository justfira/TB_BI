<?php

namespace App\Imports;

use App\Imports\ChunkReadFilter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Http\UploadedFile;

class WorkOrderImport
{
    protected int $chunkSize = 1000;

    protected array $fieldAliases = [
        'bulan' => ['bulan', 'month', 'bln'],
        'tanggal' => ['tanggal', 'tgl', 'date', 'date_work', 'tanggal_work'],
        'tanggal_order' => ['tanggal_order', 'tgl_order', 'tanggal order', 'order date', 'date order'],
        'tanggal_komitmen_ps_completed' => ['tanggal_komitmen_ps_completed', 'tgl_komitmen_ps_completed', 'tanggal komitmen ps completed', 'tgl komitmen ps completed', 'komitmen ps completed', 'tanggal komitmen'],
        'wo_sc_id' => ['wo_sc_id', 'wo/sc_id', 'wo_sc', 'wo / sc id', 'wo / sc', 'wo-sc-id', 'wo sc id', 'work order', 'workorder', 'wo'],
        'sc_id' => ['sc_id', 'sc id'],
        'track_id' => ['track_id', 'tracking_id', 'track id', 'tracking id', 'track_id_baru', 'track id baru'],
        'status' => ['status', 'status_wo', 'status wo', 'status_workorder'],
        'status_sc' => ['status_sc', 'status sc'],
        'sto' => ['sto', 'nama_sto', 'sto name'],
        'sto_input' => ['sto_input', 'sto input', 'sto_inputan', 'sto inputan'],
        'branch' => ['branch', 'cabang'],
        'sektor' => ['sektor', 'sector'],
        'hsa' => ['hsa'],
        'mitra' => ['mitra', 'vendor', 'partner', 'nama_mitra'],
        'nama_mitra' => ['nama_mitra', 'mitra'],
        'korlap' => ['korlap', 'pic_team', 'komandan_team_pic_team', 'komandan team/pic team', 'pic team', 'komandan team', 'korlap.1'],
        'spv' => ['spv', 'supervisor'],
        'cp' => ['cp', 'contact person', 'cp person'],
        'nik_teknisi' => ['nik_teknisi', 'nik teknisi', 'nik'],
        'komandan_team' => ['komandan_team', 'komandan team', 'komandan team/pic team', 'pic team', 'komandan_team_pic_team'],
        'nama_pelanggan' => ['nama_pelanggan', 'customer_name', 'customer', 'pelanggan', 'nama pelanggan'],
        'nama_contact' => ['nama_contact', 'k_contact', 'k-contact', 'nama contact', 'contact name', 'contact'],
        'uic' => ['uic'],
        'segment' => ['segment'],
        'layanan' => ['layanan', 'service'],
        'alamat_instalasi' => ['alamat_instalasi', 'alamat instalasi', 'installation address', 'address'],
        'koordinat_pelanggan' => ['koordinat_pelanggan', 'koordinat pelanggan', 'koordinat_pelangan', 'koordinat'],
        'koordinat_lat' => ['koordinat_lat', 'latitude', 'lat'],
        'koordinat_lon' => ['koordinat_lon', 'longitude', 'lon'],
        'kendala_pt1' => ['kendala_pt1', 'kendala pt1', 'kendala', 'issue', 'problem'],
        'kategori_roc' => ['kategori_roc', 'kategori roc'],
        'kategori_solusi' => ['kategori_solusi', 'kategori solusi', 'subkategori', 'subcategory'],
        'solusi_kendala' => ['solusi_kendala', 'solusi kendala', 'solusi', 'solution'],
        'keterangan' => ['keterangan', 'description', 'catatan'],
        'odp' => ['odp'],
        'odc' => ['odc'],
        'gpon' => ['gpon'],
        'feeder' => ['feeder'],
        'distribusi' => ['distribusi', 'distribution'],
        'datek1' => ['datek1', 'datek 1'],
        'datek_inputan' => ['datek_inputan', 'datek inputan', 'datek_input'],
        'datek_real' => ['datek_real', 'datek real', 'datek_actual'],
        'base_tray_odc' => ['base_tray_odc', 'base tray odc', 'base_tray'],
        'port_base_tray_odc' => ['port_base_tray_odc', 'port base tray odc', 'port_base_tray'],
        'hasil_ukur_odp' => ['hasil_ukur_odp', 'hasil ukur odp'],
        'hasil_ukur_distribusi' => ['hasil_ukur_distribusi', 'hasil ukur distribusi'],
        'hasil_ukur_feeder' => ['hasil_ukur_feeder', 'hasil ukur feeder'],
        'durasi_hari' => ['durasi_hari', 'duration_day', 'durasi hari', 'durasi (hari)', 'durasi_day'],
        'durasi' => ['durasi', 'duration', 'durasi total', 'durasi_pengerjaan_menit'],
        'durasi_manja' => ['durasi_manja', 'durasi manja'],
        'durasi_grup' => ['durasi_grup', 'durasi grup'],
        'durasi_grup_pengerjaan' => ['durasi_grup_pengerjaan', 'durasi grup pengerjaan', 'durasi grup pengerjaan kendala teknis', 'durasi_grup_pengerjaan_kendala_teknis'],
        'tgl_input_hd_gdocs' => ['tgl_input_hd_gdocs', 'tgl input hd gdocs'],
        'unsc' => ['unsc', 'is_unsc', 'UNSC'],
        'is_unsc' => ['is_unsc', 'unsc'],
        'hasil_solusi_maintenance' => ['hasil_solusi_maintenance', 'solusi maintenance'],
        'hasil_solusi_optima' => ['hasil_solusi_optima', 'solusi optima'],
        'hasil_solusi_sdi' => ['hasil_solusi_sdi', 'solusi sdi', 'solusi sdi & daman', 'solusi sdi daman'],
        'total_eskalasi' => ['total_eskalasi', 'jumlah eskalasi', 'total eskalasi'],
        'jumlah_kendala' => ['jumlah_kendala', 'jumlah kendala', 'total kendala'],
    ];

    protected array $requiredHeaders = [
        'bulan',
        'tanggal',
        'tanggal_order',
        'tanggal_komitmen_ps_completed',
        'wo_sc_id',
        'sc_id',
        'track_id',
        'status',
        'status_sc',
        'sto',
        'nama_pelanggan',
        'kendala_pt1',
        'mitra',
        'nik_teknisi',
    ];

    public function analyzeHeaders(UploadedFile|string $file): array
    {
        [$rawHeaders, $normalizedHeaders] = $this->parseHeaderRow($file);
        $headerMap = $this->buildHeaderMap($normalizedHeaders);

        return [
            'raw_headers' => $rawHeaders,
            'normalized_headers' => $normalizedHeaders,
            'header_map' => $headerMap,
            'missing_headers' => $this->getMissingHeaders($headerMap),
            'unmapped_headers' => $this->getUnmappedHeaders($rawHeaders, $headerMap),
            'rows' => $this->loadRows($file, [], 20),
        ];
    }

    public function loadRows(UploadedFile|string $file, array $manualMapping = [], int $limit = 0): array
    {
        [$rawHeaders, $normalizedHeaders] = $this->parseHeaderRow($file);
        $headerMap = $this->buildHeaderMap($normalizedHeaders, $manualMapping);

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $rows = [];

        foreach ($this->yieldRowsInChunks($path, $headerMap) as $row) {
            $rows[] = $row;
            if ($limit > 0 && count($rows) >= $limit) {
                break;
            }
        }

        return $rows;
    }

    public function rowGenerator(UploadedFile|string $file, array $manualMapping = []): \Generator
    {
        [$rawHeaders, $normalizedHeaders] = $this->parseHeaderRow($file);
        $headerMap = $this->buildHeaderMap($normalizedHeaders, $manualMapping);

        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        yield from $this->yieldRowsInChunks($path, $headerMap);
    }

    protected function yieldRowsInChunks(string $path, array $headerMap): \Generator
    {
        ini_set('memory_limit', '512M');

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            if (($handle = fopen($path, 'r')) !== false) {
                // Skip the header row
                $headerRow = fgetcsv($handle);

                // Read row by row
                while (($data = fgetcsv($handle)) !== false) {
                    $rowData = [];
                    foreach ($data as $colIdx => $value) {
                        $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1);
                        $key = $headerMap[$columnLetter] ?? null;
                        if ($key) {
                            $rowData[$key] = trim((string) $value);
                        }
                    }
                    if (array_filter($rowData)) {
                        yield $rowData;
                    }
                }
                fclose($handle);
            }
            return;
        }

        // For Excel files, read in chunks of 5000 rows to optimize memory during preview and full import
        $chunkSize = 5000;
        $startRow = 2;

        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        while (true) {
            $filter = new ChunkReadFilter($startRow, $startRow + $chunkSize - 1);
            $reader->setReadFilter($filter);
            
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $rowCount = 0;
            $endRow = min($highestRow, $startRow + $chunkSize - 1);

            for ($row = $startRow; $row <= $endRow; $row++) {
                $rowData = [];
                $hasData = false;

                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $key = $headerMap[$columnLetter] ?? null;
                    if ($key) {
                        $value = trim((string) $sheet->getCellByColumnAndRow($col, $row)->getValue());
                        $rowData[$key] = $value;
                        if ($value !== '') {
                            $hasData = true;
                        }
                    }
                }

                if ($hasData && array_filter($rowData)) {
                    yield $rowData;
                    $rowCount++;
                }
            }

            $sheet->disconnectCells();
            $spreadsheet->disconnectWorksheets();
            unset($sheet, $spreadsheet);
            gc_collect_cycles();

            if ($rowCount === 0 || $startRow + $chunkSize - 1 >= $highestRow) {
                break;
            }

            $startRow += $chunkSize;
        }
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

    protected function parseHeaderRow(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new ChunkReadFilter(1, 1));
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $header = [];
        $normalized = [];

        $firstRow = $sheet->getRowIterator(1)->current();
        $cellIterator = $firstRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        foreach ($cellIterator as $cellIndex => $cell) {
            $value = trim((string) $cell->getValue());
            $header[$cellIndex] = $value;
            $normalized[$cellIndex] = $this->normalizeHeader($value);
        }

        return [$header, $normalized];
    }

    protected function buildHeaderMap(array $normalizedHeaders, array $manualMapping = []): array
    {
        $headerMap = [];
        $manualMappingNormalized = [];

        foreach ($manualMapping as $rawHeader => $canonical) {
            $manualMappingNormalized[$this->normalizeHeader($rawHeader)] = $canonical;
        }

        foreach ($normalizedHeaders as $index => $normalized) {
            $headerMap[$index] = $manualMappingNormalized[$normalized] ?? $this->getCanonicalByNormalized($normalized);
        }

        return $headerMap;
    }

    protected function getCanonicalByNormalized(string $normalized): ?string
    {
        foreach ($this->fieldAliases as $canonical => $aliases) {
            if (in_array($normalized, $aliases, true)) {
                return $canonical;
            }
        }

        return null;
    }

    protected function getMissingHeaders(array $headerMap): array
    {
        $mapped = array_values(array_filter($headerMap));

        return array_values(array_diff($this->requiredHeaders, $mapped));
    }

    protected function getUnmappedHeaders(array $rawHeaders, array $headerMap): array
    {
        $unmapped = [];

        foreach ($rawHeaders as $index => $header) {
            if (empty($headerMap[$index])) {
                $unmapped[$index] = $header;
            }
        }

        return $unmapped;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = preg_replace('/[^a-z0-9]+/', '_', $header);
        $header = preg_replace('/_+/', '_', $header);
        return trim($header, '_');
    }
}
