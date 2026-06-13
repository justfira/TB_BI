<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Stored procedure ETL entrypoint.
        // NOTE:
        // - Ini implementasi minimal untuk memindahkan data dari staging_workorder ke fact/dim.
        // - Transformasi kompleks (mapping dim) masih bisa diperluas.
        // - Prosedur ini hanya dijalankan dari aplikasi setelah staging terisi.

        $sql = <<<SQL
CREATE PROCEDURE sp_etl_workorder(IN p_log_id BIGINT)
BEGIN
    -- Untuk aman, jalankan transform berdasarkan staging yang terkait log
    -- Saat ini staging_workorder tidak menyimpan log_id, jadi dianggap semua rows staging.
    -- Jika diperlukan, tambahkan kolom log_id dan sesuaikan.

    START TRANSACTION;

    -- =====================
    -- 1) Insert dim_status
    -- =====================
    -- Ambil status_name dari JSON. Jika JSON tidak memiliki field, baris akan terlewat.

    INSERT INTO dim_status (status_name, status_group, aktif, created_at, updated_at)
    SELECT DISTINCT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo')) AS status_name,
        CASE
            WHEN JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo')) LIKE '%Selesai%'
                THEN 'SELESAI'
            ELSE 'INPROGRESS'
        END AS status_group,
        1,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.status_wo') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_status ds WHERE ds.status_name = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo'))
      );

    -- =====================
    -- 2) Insert dim_waktu
    -- =====================
    INSERT INTO dim_waktu (tanggal, tahun, bulan, hari, nama_bulan, nama_hari, kuartal, hari_kerja, created_at, updated_at)
    SELECT DISTINCT
        STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d') AS tanggal,
        YEAR(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')) AS tahun,
        MONTH(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')) AS bulan,
        DAY(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')) AS hari,
        DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d'), '%M') AS nama_bulan,
        DATE_FORMAT(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d'), '%W') AS nama_hari,
        CEIL(MONTH(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')) / 3) AS kuartal,
        CASE WHEN DAYOFWEEK(STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')) IN (1,7) THEN 0 ELSE 1 END AS hari_kerja,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.tanggal') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_waktu dw WHERE dw.tanggal = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')), '%Y-%m-%d')
      );

    -- =====================
    -- 3) Insert dim_sto
    -- =====================
    INSERT INTO dim_sto (kode_sto, nama_sto, created_at, updated_at)
    SELECT DISTINCT
        CONCAT('STO_', SUBSTRING(MD5(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.sto'))), 1, 8)) AS kode_sto,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.sto')) AS nama_sto,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.sto') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_sto ds WHERE ds.nama_sto = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.sto'))
      );

    -- =====================
    -- 4) Insert dim_kendala
    -- =====================
    INSERT INTO dim_kendala (kendala_pt1, kategori_roc, kategori_solusi, solusi_kendala, keterangan, created_at, updated_at)
    SELECT DISTINCT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kendala_pt1')) AS kendala_pt1,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kategori_roc')) AS kategori_roc,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kategori_solusi')) AS kategori_solusi,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.solusi_kendala')) AS solusi_kendala,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.keterangan')) AS keterangan,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.kendala_pt1') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_kendala dk WHERE dk.kendala_pt1 = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kendala_pt1'))
      );

    -- =====================
    -- 5) Insert dim_teknisi
    -- =====================
    INSERT INTO dim_teknisi (nik_teknisi, nama_teknisi, korlap, komandan_team, mitra, nama_mitra, spv, cp, created_at, updated_at)
    SELECT DISTINCT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nik_teknisi')) AS nik_teknisi,
        COALESCE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.korlap')),
                 JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.mitra')),
                 '-') AS nama_teknisi,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.korlap')) AS korlap,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.komandan_team')) AS komandan_team,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.mitra')) AS mitra,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.mitra')) AS nama_mitra,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.spv')) AS spv,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.cp')) AS cp,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.nik_teknisi') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_teknisi dt WHERE dt.nik_teknisi = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nik_teknisi'))
      );

    -- =====================
    -- 6) Insert dim_pelanggan
    -- =====================
    INSERT INTO dim_pelanggan (k_contact, nama_pelanggan, nama_contact, segment, layanan, alamat_instalasi, uic, koordinat_pelanggan, koordinat_lat, koordinat_lon, created_at, updated_at)
    SELECT DISTINCT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.track_id')) AS k_contact,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nama_pelanggan')) AS nama_pelanggan,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nama_contact')) AS nama_contact,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.segment')) AS segment,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.layanan')) AS layanan,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.alamat_instalasi')) AS alamat_instalasi,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.uic')) AS uic,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.koordinat_pelanggan')) AS koordinat_pelanggan,
        NULL AS koordinat_lat,
        NULL AS koordinat_lon,
        NOW(),
        NOW()
    FROM staging_workorder sw
    WHERE JSON_EXTRACT(sw.data_json, '$.track_id') IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_pelanggan dp WHERE dp.k_contact = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.track_id'))
      );

    -- =====================
    -- 7) Insert dim_infrastruktur (materialize JSON keys to avoid repeated JSON_EXTRACT)
    -- =====================

    CREATE TEMPORARY TABLE tmp_stage_infra (
        wo_sc_id           VARCHAR(100) NULL,
        odp                VARCHAR(255) NULL,
        odc                VARCHAR(255) NULL,
        gpon               VARCHAR(255) NULL,
        feeder             VARCHAR(255) NULL,
        distribusi         VARCHAR(255) NULL,
        datek1             VARCHAR(255) NULL,
        datek_inputan      VARCHAR(255) NULL,
        datek_real         VARCHAR(255) NULL,
        hasil_ukur_odp     VARCHAR(255) NULL,
        hasil_ukur_distribusi VARCHAR(255) NULL,
        hasil_ukur_feeder  VARCHAR(255) NULL
    ) ENGINE=MEMORY;

    INSERT INTO tmp_stage_infra (
        wo_sc_id,
        odp,
        odc,
        gpon,
        feeder,
        distribusi,
        datek1,
        datek_inputan,
        datek_real,
        hasil_ukur_odp,
        hasil_ukur_distribusi,
        hasil_ukur_feeder
    )
    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.wo_sc_id')) AS wo_sc_id,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.odp')) AS odp,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.odc')) AS odc,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.gpon')) AS gpon,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.feeder')) AS feeder,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.distribusi')) AS distribusi,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.datek1')) AS datek1,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.datek_inputan')) AS datek_inputan,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.datek_real')) AS datek_real,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.hasil_ukur_odp')) AS hasil_ukur_odp,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.hasil_ukur_distribusi')) AS hasil_ukur_distribusi,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.hasil_ukur_feeder')) AS hasil_ukur_feeder
    FROM staging_workorder sw;

    INSERT INTO dim_infrastruktur (etl_log_id, wo_id, odp, odc, gpon, feeder, distribusi, core_distribusi, datek1, datek_inputan, datek_real, hasil_ukur_odp, hasil_ukur_distribusi, hasil_ukur_feeder, created_at, updated_at)
    SELECT DISTINCT
        p_log_id AS etl_log_id,
        t.wo_sc_id AS wo_id,
        t.odp,
        t.odc,
        t.gpon,
        t.feeder,
        t.distribusi,
        t.distribusi AS core_distribusi,
        t.datek1,
        t.datek_inputan,
        t.datek_real,
        t.hasil_ukur_odp,
        t.hasil_ukur_distribusi,
        t.hasil_ukur_feeder,
        NOW(),
        NOW()
    FROM tmp_stage_infra t
    WHERE t.wo_sc_id IS NOT NULL
      AND NOT EXISTS (
        SELECT 1 FROM dim_infrastruktur di WHERE di.wo_id = t.wo_sc_id
      );

    DROP TEMPORARY TABLE IF EXISTS tmp_stage_infra;


    -- =====================
    -- 8) Insert fact_workorder
    -- =====================
    INSERT INTO fact_workorder (
        wo_sc_id, sc_id, track_id, track_id_baru,
        dim_waktu_id, dim_sto_id, dim_teknisi_id, dim_pelanggan_id, dim_kendala_id, dim_infrastruktur_id, dim_status_id,
        tanggal_order, tanggal_komitmen, tgl_input_hd_gdocs,
        status_wo, status_sc,
        durasi_hari, durasi, durasi_manja, durasi_pengerjaan_kendala, durasi_grup,
        is_sla_tercapai, is_workfail,
        etl_log_id,
        keterangan, keterangan_sm_provisioning, keterangan_tl_provisioning,
        created_at, updated_at
    )

    SELECT
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.wo_sc_id')) AS wo_sc_id,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.sc_id')) AS sc_id,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.track_id')) AS track_id,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.track_id_baru')) AS track_id_baru,
        dw.id,
        ds.id,
        dt.id,
        dp.id,
        dk.id,
        di.id,
        dstatus.id,
        NULL,
        NULL,
        NULL,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo')) AS status_wo,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_sc')) AS status_sc,
        NULL,
        NULL,
        NULL,
        NULL,
        NULL,
        0,
        0,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.keterangan')) AS keterangan,
        NULL,
        NULL,
        NOW(),
        NOW()
    FROM staging_workorder sw
    JOIN dim_waktu dw ON dw.tanggal = STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.tanggal')),'%Y-%m-%d')
    JOIN dim_sto ds ON ds.nama_sto = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.sto'))
    JOIN dim_teknisi dt ON dt.nik_teknisi = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nik_teknisi'))
    JOIN dim_pelanggan dp ON dp.k_contact = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.track_id'))
    JOIN dim_kendala dk ON dk.kendala_pt1 = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kendala_pt1'))
    JOIN dim_infrastruktur di ON di.wo_id = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.wo_sc_id'))
    JOIN dim_status dstatus ON dstatus.status_name = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo'))
    WHERE NOT EXISTS (
        SELECT 1 FROM fact_workorder fw WHERE fw.wo_sc_id = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.wo_sc_id'))
    );

    -- =====================
    -- 9) Insert fact_kendalateknis
    -- =====================
    INSERT INTO fact_kendalateknis (
        fact_workorder_id, dim_kendala_id, dim_teknisi_id, dim_status_id,
        keterangan, resolusi_jam, root_cause,
        etl_log_id,
        created_at, updated_at
    )

    SELECT
        fw.id,
        dk.id,
        dt.id,
        dstatus.id,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.keterangan')) AS keterangan,
        NULL,
        JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kendala_pt1')) AS root_cause,
        NOW(),
        NOW()
    FROM staging_workorder sw
    JOIN fact_workorder fw ON fw.wo_sc_id = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.wo_sc_id'))
    JOIN dim_kendala dk ON dk.kendala_pt1 = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.kendala_pt1'))
    JOIN dim_teknisi dt ON dt.nik_teknisi = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.nik_teknisi'))
    JOIN dim_status dstatus ON dstatus.status_name = JSON_UNQUOTE(JSON_EXTRACT(sw.data_json, '$.status_wo'))
    WHERE NOT EXISTS (
        SELECT 1 FROM fact_kendalateknis fkt WHERE fkt.fact_workorder_id = fw.id
    );

    -- Tandai staging sebagai processed
    UPDATE staging_workorder SET status = 'processed', updated_at = NOW();

    COMMIT;
END;
SQL;

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');
    }
};

