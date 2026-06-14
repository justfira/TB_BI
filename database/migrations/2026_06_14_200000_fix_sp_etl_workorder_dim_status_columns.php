<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan unique key pada status_wo agar INSERT IGNORE bekerja dengan benar
        // dan mencegah duplikasi baris dim_status di batch ETL berikutnya.
        try {
            if (! $this->indexExists('dim_status', 'uq_dim_status_status_wo')) {
                DB::statement('ALTER TABLE dim_status ADD UNIQUE KEY uq_dim_status_status_wo (status_wo)');
            }
        } catch (\Throwable $e) {
            // Lewati jika sudah ada duplikat status_wo; bisa di-dedup manual nanti.
        }

        $sql = <<<'SQL'
CREATE PROCEDURE sp_etl_workorder(IN p_log_id BIGINT)
BEGIN
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_success INT DEFAULT 0;
    DECLARE v_duplicate INT DEFAULT 0;
    DECLARE v_failed INT DEFAULT 0;
    DECLARE v_unique_wo INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET SESSION foreign_key_checks = 1;
        SET SESSION unique_checks = 1;
        ROLLBACK;
        RESIGNAL;
    END;

    SET SESSION foreign_key_checks = 0;
    SET SESSION unique_checks = 0;

    START TRANSACTION;

    SELECT COUNT(*) INTO v_total FROM staging_workorder_flat;

    DROP TEMPORARY TABLE IF EXISTS tmp_etl_deduped;
    CREATE TEMPORARY TABLE tmp_etl_deduped (
        staging_id BIGINT NOT NULL,
        wo_sc_id VARCHAR(100) NULL,
        sc_id VARCHAR(100) NULL,
        track_id VARCHAR(255) NULL,
        track_id_baru VARCHAR(255) NULL,
        tanggal DATE NULL,
        tanggal_order DATE NULL,
        tanggal_komitmen DATE NULL,
        tgl_input_hd_gdocs DATE NULL,
        sto VARCHAR(255) NULL,
        status_wo VARCHAR(255) NULL,
        status_sc VARCHAR(100) NULL,
        kendala_pt1 TEXT NULL,
        kategori_roc VARCHAR(100) NULL,
        kategori_solusi VARCHAR(150) NULL,
        solusi_kendala TEXT NULL,
        nik_teknisi VARCHAR(100) NULL,
        korlap VARCHAR(150) NULL,
        komandan_team VARCHAR(150) NULL,
        mitra VARCHAR(150) NULL,
        spv VARCHAR(150) NULL,
        cp VARCHAR(100) NULL,
        nama_pelanggan VARCHAR(255) NULL,
        nama_contact VARCHAR(255) NULL,
        segment VARCHAR(100) NULL,
        layanan VARCHAR(150) NULL,
        alamat_instalasi TEXT NULL,
        uic VARCHAR(100) NULL,
        koordinat_pelanggan VARCHAR(255) NULL,
        odp VARCHAR(255) NULL,
        odc VARCHAR(255) NULL,
        gpon VARCHAR(255) NULL,
        feeder VARCHAR(255) NULL,
        distribusi VARCHAR(255) NULL,
        datek1 VARCHAR(255) NULL,
        datek_inputan VARCHAR(255) NULL,
        datek_real VARCHAR(255) NULL,
        hasil_ukur_odp VARCHAR(255) NULL,
        hasil_ukur_distribusi VARCHAR(255) NULL,
        hasil_ukur_feeder VARCHAR(255) NULL,
        durasi_hari DECIMAL(10,2) NULL,
        durasi DECIMAL(10,2) NULL,
        durasi_manja DECIMAL(10,2) NULL,
        durasi_grup VARCHAR(100) NULL,
        durasi_grup_pengerjaan DECIMAL(8,2) NULL,
        keterangan TEXT NULL,
        keterangan_sm_provisioning TEXT NULL,
        keterangan_tl_provisioning TEXT NULL,
        hasil_solusi_maintenance VARCHAR(255) NULL,
        hasil_solusi_optima VARCHAR(255) NULL,
        hasil_solusi_sdi VARCHAR(255) NULL,
        total_eskalasi INT NULL,
        jumlah_kendala INT NULL,
        is_unsc TINYINT NOT NULL DEFAULT 0,
        PRIMARY KEY (staging_id),
        UNIQUE KEY uq_wo_sc_id (wo_sc_id),
        KEY idx_tanggal (tanggal),
        KEY idx_sto (sto(100)),
        KEY idx_status_wo (status_wo(100)),
        KEY idx_kendala_pt1 (kendala_pt1(191)),
        KEY idx_nik_teknisi (nik_teknisi),
        KEY idx_track_id (track_id(100))
    );

    INSERT INTO tmp_etl_deduped (
        staging_id, wo_sc_id, sc_id, track_id, track_id_baru,
        tanggal, tanggal_order, tanggal_komitmen, tgl_input_hd_gdocs,
        sto, status_wo, status_sc,
        kendala_pt1, kategori_roc, kategori_solusi, solusi_kendala,
        nik_teknisi, korlap, komandan_team, mitra, spv, cp,
        nama_pelanggan, nama_contact, segment, layanan, alamat_instalasi, uic, koordinat_pelanggan,
        odp, odc, gpon, feeder, distribusi, datek1, datek_inputan, datek_real,
        hasil_ukur_odp, hasil_ukur_distribusi, hasil_ukur_feeder,
        durasi_hari, durasi, durasi_manja, durasi_grup, durasi_grup_pengerjaan,
        keterangan, keterangan_sm_provisioning, keterangan_tl_provisioning,
        hasil_solusi_maintenance, hasil_solusi_optima, hasil_solusi_sdi,
        total_eskalasi, jumlah_kendala, is_unsc
    )
    SELECT
        s.id, s.wo_sc_id, s.sc_id, s.track_id, s.track_id_baru,
        CASE WHEN CAST(s.tanggal AS CHAR) IN ('0000-00-00','') THEN NULL ELSE s.tanggal END,
        CASE WHEN CAST(s.tanggal_order AS CHAR) IN ('0000-00-00','') THEN NULL ELSE s.tanggal_order END,
        CASE WHEN CAST(s.tanggal_komitmen AS CHAR) IN ('0000-00-00','') THEN NULL ELSE s.tanggal_komitmen END,
        CASE WHEN CAST(s.tgl_input_hd_gdocs AS CHAR) IN ('0000-00-00','') THEN NULL ELSE s.tgl_input_hd_gdocs END,
        s.sto, s.status_wo, s.status_sc,
        s.kendala_pt1, s.kategori_roc, s.kategori_solusi, s.solusi_kendala,
        s.nik_teknisi, s.korlap, s.komandan_team, s.mitra, s.spv, s.cp,
        s.nama_pelanggan, s.nama_contact, s.segment, s.layanan, s.alamat_instalasi, s.uic, s.koordinat_pelanggan,
        s.odp, s.odc, s.gpon, s.feeder, s.distribusi, s.datek1, s.datek_inputan, s.datek_real,
        s.hasil_ukur_odp, s.hasil_ukur_distribusi, s.hasil_ukur_feeder,
        s.durasi_hari, s.durasi, s.durasi_manja, s.durasi_grup, s.durasi_grup_pengerjaan,
        s.keterangan, s.keterangan_sm_provisioning, s.keterangan_tl_provisioning,
        s.hasil_solusi_maintenance, s.hasil_solusi_optima, s.hasil_solusi_sdi,
        s.total_eskalasi, s.jumlah_kendala, COALESCE(s.is_unsc, 0)
    FROM staging_workorder_flat s
    INNER JOIN (
        SELECT wo_sc_id, MIN(id) AS keep_id
        FROM staging_workorder_flat
        WHERE wo_sc_id IS NOT NULL AND wo_sc_id <> ''
        GROUP BY wo_sc_id
    ) d ON s.id = d.keep_id;

    SELECT COUNT(*) INTO v_unique_wo FROM tmp_etl_deduped;

    INSERT IGNORE INTO dim_status (status_wo, status_group, created_at, updated_at)
    SELECT DISTINCT
        t.status_wo,
        CASE WHEN t.status_wo LIKE '%Selesai%' THEN 'SELESAI' ELSE 'INPROGRESS' END,
        NOW(), NOW()
    FROM staging_workorder_flat t
    WHERE t.status_wo IS NOT NULL AND t.status_wo <> '';

    INSERT IGNORE INTO dim_waktu (tanggal, tahun, bulan, hari, nama_bulan, nama_hari, kuartal, hari_kerja, created_at, updated_at)
    SELECT DISTINCT
        t.tanggal,
        YEAR(t.tanggal), MONTH(t.tanggal), DAY(t.tanggal),
        DATE_FORMAT(t.tanggal, '%M'), DATE_FORMAT(t.tanggal, '%W'),
        CEIL(MONTH(t.tanggal) / 3),
        CASE WHEN DAYOFWEEK(t.tanggal) IN (1, 7) THEN 0 ELSE 1 END,
        NOW(), NOW()
    FROM tmp_etl_deduped t
    WHERE t.tanggal IS NOT NULL;

    INSERT IGNORE INTO dim_sto (kode_sto, nama_sto, created_at, updated_at)
    SELECT DISTINCT
        CONCAT('STO_', SUBSTRING(MD5(t.sto), 1, 8)),
        t.sto, NOW(), NOW()
    FROM staging_workorder_flat t
    WHERE t.sto IS NOT NULL AND t.sto <> '';

    INSERT INTO dim_kendala (kendala_pt1, kategori_roc, kategori_solusi, solusi_kendala, created_at, updated_at)
    SELECT t.kendala_pt1, t.kategori_roc, t.kategori_solusi, t.solusi_kendala, NOW(), NOW()
    FROM tmp_etl_deduped t
    LEFT JOIN dim_kendala dk ON dk.kendala_pt1 = t.kendala_pt1
    WHERE t.kendala_pt1 IS NOT NULL AND t.kendala_pt1 <> '' AND dk.id IS NULL;

    INSERT IGNORE INTO dim_teknisi (nik_teknisi, nama_teknisi, korlap, komandan_team, mitra, nama_mitra, spv, cp, created_at, updated_at)
    SELECT DISTINCT
        t.nik_teknisi, COALESCE(t.korlap, t.mitra, '-'),
        t.korlap, t.komandan_team, t.mitra, t.mitra, t.spv, t.cp,
        NOW(), NOW()
    FROM staging_workorder_flat t
    WHERE t.nik_teknisi IS NOT NULL AND t.nik_teknisi <> '';

    INSERT IGNORE INTO dim_pelanggan (k_contact, nama_pelanggan, nama_contact, segment, layanan, alamat_instalasi, uic, koordinat_pelanggan, created_at, updated_at)
    SELECT DISTINCT
        t.track_id, t.nama_pelanggan, t.nama_contact, t.segment, t.layanan,
        t.alamat_instalasi, t.uic, t.koordinat_pelanggan,
        NOW(), NOW()
    FROM staging_workorder_flat t
    WHERE t.track_id IS NOT NULL AND t.track_id <> '';

    INSERT INTO dim_infrastruktur (
        etl_log_id, wo_id, odp, odc, gpon, feeder, distribusi, core_distribusi,
        datek1, datek_inputan, datek_real,
        hasil_ukur_odp, hasil_ukur_distribusi, hasil_ukur_feeder,
        created_at, updated_at
    )
    SELECT
        p_log_id, t.wo_sc_id, t.odp, t.odc, t.gpon, t.feeder, t.distribusi, t.distribusi,
        t.datek1, t.datek_inputan, t.datek_real,
        t.hasil_ukur_odp, t.hasil_ukur_distribusi, t.hasil_ukur_feeder,
        NOW(), NOW()
    FROM tmp_etl_deduped t
    LEFT JOIN dim_infrastruktur di ON di.wo_id = t.wo_sc_id
    WHERE di.id IS NULL;

    INSERT IGNORE INTO fact_workorder (
        wo_sc_id, sc_id, track_id, track_id_baru,
        dim_waktu_id, dim_sto_id, dim_teknisi_id, dim_pelanggan_id, dim_kendala_id, dim_infrastruktur_id, dim_status_id,
        tanggal_order, tanggal_komitmen, tgl_input_hd_gdocs,
        status_wo, status_sc,
        durasi_hari, durasi, durasi_manja, durasi_pengerjaan_kendala, durasi_grup,
        is_sla_tercapai, is_workfail, is_unsc,
        etl_log_id,
        keterangan, keterangan_sm_provisioning, keterangan_tl_provisioning,
        created_at, updated_at
    )
    SELECT
        t.wo_sc_id, t.sc_id, t.track_id, t.track_id_baru,
        dw.id, ds.id, dt.id, dp.id, dk.id, di.id, dstatus.status_id,
        t.tanggal_order, t.tanggal_komitmen, t.tgl_input_hd_gdocs,
        t.status_wo, t.status_sc,
        t.durasi_hari, t.durasi, t.durasi_manja,
        COALESCE(t.durasi, t.durasi_hari * 24 * 60),
        t.durasi_grup,
        CASE WHEN dstatus.status_group = 'SELESAI' THEN 1 ELSE 0 END,
        t.is_unsc, t.is_unsc,
        p_log_id,
        t.keterangan, t.keterangan_sm_provisioning, t.keterangan_tl_provisioning,
        NOW(), NOW()
    FROM tmp_etl_deduped t
    INNER JOIN dim_waktu dw ON dw.tanggal = COALESCE(t.tanggal, CURDATE())
    INNER JOIN dim_sto ds ON ds.nama_sto = t.sto
    INNER JOIN dim_teknisi dt ON dt.nik_teknisi = t.nik_teknisi
    INNER JOIN dim_pelanggan dp ON dp.k_contact = t.track_id
    INNER JOIN dim_kendala dk ON dk.kendala_pt1 = t.kendala_pt1
    INNER JOIN dim_infrastruktur di ON di.wo_id = t.wo_sc_id
    INNER JOIN dim_status dstatus ON dstatus.status_wo = t.status_wo;

    SELECT COUNT(*) INTO v_success FROM fact_workorder WHERE etl_log_id = p_log_id;

    SELECT COUNT(*) INTO v_duplicate
    FROM staging_workorder_flat s
    INNER JOIN fact_workorder fw ON fw.wo_sc_id = s.wo_sc_id AND fw.etl_log_id <> p_log_id
    WHERE s.wo_sc_id IS NOT NULL AND s.wo_sc_id <> '';

    SET v_duplicate = v_duplicate + GREATEST(
        (SELECT COUNT(*) FROM staging_workorder_flat WHERE wo_sc_id IS NOT NULL AND wo_sc_id <> '') - v_unique_wo,
        0
    );

    SET v_failed = GREATEST(v_total - v_success - v_duplicate, 0);

    INSERT IGNORE INTO fact_kendalateknis (
        fact_workorder_id, dim_kendala_id, dim_teknisi_id, dim_status_id,
        keterangan, resolusi_jam, root_cause,
        durasi_grup_pengerjaan, hasil_solusi_maintenance, hasil_solusi_optima, hasil_solusi_sdi,
        total_eskalasi, jumlah_kendala,
        etl_log_id, created_at, updated_at
    )
    SELECT
        fw.id, dk.id, dt.id, dstatus.status_id,
        t.keterangan, t.durasi, t.kendala_pt1,
        t.durasi_grup_pengerjaan, t.hasil_solusi_maintenance, t.hasil_solusi_optima, t.hasil_solusi_sdi,
        t.total_eskalasi, t.jumlah_kendala,
        p_log_id, NOW(), NOW()
    FROM tmp_etl_deduped t
    INNER JOIN fact_workorder fw ON fw.wo_sc_id = t.wo_sc_id AND fw.etl_log_id = p_log_id
    INNER JOIN dim_kendala dk ON dk.kendala_pt1 = t.kendala_pt1
    INNER JOIN dim_teknisi dt ON dt.nik_teknisi = t.nik_teknisi
    INNER JOIN dim_status dstatus ON dstatus.status_wo = t.status_wo;

    UPDATE etl_logs
    SET total_rows = v_total,
        success_count = v_success,
        failed_count = v_failed,
        duplicate_count = v_duplicate,
        updated_at = NOW()
    WHERE id = p_log_id;

    TRUNCATE TABLE staging_workorder_flat;

    DROP TEMPORARY TABLE IF EXISTS tmp_etl_deduped;

    SET SESSION foreign_key_checks = 1;
    SET SESSION unique_checks = 1;

    COMMIT;
END
SQL;

        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');
        DB::unprepared($sql);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');
    }

    /**
     * Cek apakah index dengan nama tertentu sudah ada pada tabel.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $result = DB::select(
            'SELECT COUNT(1) as cnt FROM information_schema.statistics 
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$table, $indexName]
        );

        return ((int) $result[0]->cnt) > 0;
    }
};