<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');

        DB::unprepared(<<<'SQL'
CREATE PROCEDURE sp_etl_workorder(IN p_log_id BIGINT)
BEGIN
    DECLARE v_total INT DEFAULT 0;
    DECLARE v_unique_wo INT DEFAULT 0;
    DECLARE v_in_file_duplicate INT DEFAULT 0;
    DECLARE v_duplicate INT DEFAULT 0;
    DECLARE v_success INT DEFAULT 0;
    DECLARE v_failed INT DEFAULT 0;

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

    -- Databases that previously accepted zero dates can still contain them
    -- in staging. Normalize before MySQL materializes the temporary table.
    UPDATE staging_workorder_flat
    SET tanggal = NULL
    WHERE CAST(tanggal AS CHAR) = '0000-00-00';

    UPDATE staging_workorder_flat
    SET tanggal_order = NULL
    WHERE CAST(tanggal_order AS CHAR) = '0000-00-00';

    UPDATE staging_workorder_flat
    SET tanggal_komitmen = NULL
    WHERE CAST(tanggal_komitmen AS CHAR) = '0000-00-00';

    UPDATE staging_workorder_flat
    SET tgl_input_hd_gdocs = NULL
    WHERE CAST(tgl_input_hd_gdocs AS CHAR) = '0000-00-00';

    DROP TEMPORARY TABLE IF EXISTS tmp_etl_deduped;
    CREATE TEMPORARY TABLE tmp_etl_deduped AS
    SELECT s.*
    FROM staging_workorder_flat s
    INNER JOIN (
        SELECT TRIM(wo_sc_id) AS wo_sc_id, MIN(id) AS keep_id
        FROM staging_workorder_flat
        WHERE NULLIF(TRIM(wo_sc_id), '') IS NOT NULL
        GROUP BY TRIM(wo_sc_id)
    ) d ON s.id = d.keep_id;

    ALTER TABLE tmp_etl_deduped
        ADD PRIMARY KEY (id),
        ADD KEY idx_tmp_wo_sc_id (wo_sc_id);

    SELECT COUNT(*) INTO v_unique_wo FROM tmp_etl_deduped;
    SET v_in_file_duplicate = GREATEST(v_total - v_unique_wo, 0);

    INSERT IGNORE INTO dim_waktu (
        date_id, tanggal, bulan, nama_bulan, tahun, kuartal,
        nama_hari, nomor_minggu, is_weekend, periode_laporan
    )
    SELECT DISTINCT
        CAST(DATE_FORMAT(tgl, '%Y%m%d') AS UNSIGNED),
        tgl,
        MONTH(tgl),
        DATE_FORMAT(tgl, '%M'),
        YEAR(tgl),
        QUARTER(tgl),
        DATE_FORMAT(tgl, '%W'),
        WEEK(tgl, 1),
        IF(DAYOFWEEK(tgl) IN (1, 7), 1, 0),
        CONCAT(YEAR(tgl), '-Q', QUARTER(tgl))
    FROM (
        SELECT tanggal AS tgl FROM staging_workorder_flat WHERE tanggal IS NOT NULL
        UNION
        SELECT tanggal_order FROM staging_workorder_flat WHERE tanggal_order IS NOT NULL
        UNION
        SELECT tanggal_komitmen FROM staging_workorder_flat WHERE tanggal_komitmen IS NOT NULL
        UNION
        SELECT CURDATE()
    ) all_dates;

    INSERT IGNORE INTO dim_sto (nama_sto, created_at, updated_at)
    SELECT DISTINCT TRIM(sto), NOW(), NOW()
    FROM staging_workorder_flat
    WHERE NULLIF(TRIM(sto), '') IS NOT NULL;

    INSERT IGNORE INTO dim_teknisi (
        nik_teknisi, nama_mitra, korlap, komandan_team,
        spv, cp, status_aktif, created_at, updated_at
    )
    SELECT DISTINCT
        TRIM(nik_teknisi), TRIM(mitra), TRIM(korlap), TRIM(komandan_team),
        TRIM(spv), TRIM(cp), 1, NOW(), NOW()
    FROM staging_workorder_flat
    WHERE NULLIF(TRIM(nik_teknisi), '') IS NOT NULL;

    INSERT IGNORE INTO dim_pelanggan (
        kode_tracking, nama_pelanggan, nama_contact,
        segment, layanan, uic, alamat_instalasi,
        created_at, updated_at
    )
    SELECT DISTINCT
        TRIM(wo_sc_id), TRIM(nama_pelanggan), TRIM(nama_contact),
        TRIM(segment), TRIM(layanan), TRIM(uic), TRIM(alamat_instalasi),
        NOW(), NOW()
    FROM staging_workorder_flat
    WHERE NULLIF(TRIM(wo_sc_id), '') IS NOT NULL;

    INSERT IGNORE INTO dim_kendala (
        kendala_pt1, kategori_roc, kategori_solusi, solusi_kendala,
        solusi_maintenance, solusi_optima, solusi_sdi_daman,
        created_at, updated_at
    )
    SELECT DISTINCT
        TRIM(kendala_pt1), TRIM(kategori_roc), TRIM(kategori_solusi), TRIM(solusi_kendala),
        TRIM(hasil_solusi_maintenance), TRIM(hasil_solusi_optima), TRIM(hasil_solusi_sdi),
        NOW(), NOW()
    FROM staging_workorder_flat
    WHERE NULLIF(TRIM(kendala_pt1), '') IS NOT NULL;

    INSERT IGNORE INTO dim_status (
        status_wo, status_sc, status_group, created_at, updated_at
    )
    SELECT DISTINCT
        TRIM(status_wo), TRIM(status_sc),
        CASE WHEN TRIM(status_wo) LIKE '%Selesai%' THEN 'SELESAI' ELSE 'INPROGRESS' END,
        NOW(), NOW()
    FROM staging_workorder_flat
    WHERE NULLIF(TRIM(status_wo), '') IS NOT NULL;

    INSERT IGNORE INTO dim_infrastruktur (
        etl_log_id, wo_id,
        odp, odc, gpon, feeder, distribusi, core_distribusi,
        datek1, datek_inputan, datek_real,
        hasil_ukur_odp, hasil_ukur_distribusi, hasil_ukur_feeder,
        status_aktif, created_at, updated_at
    )
    SELECT
        p_log_id, TRIM(t.wo_sc_id),
        TRIM(t.odp), TRIM(t.odc), TRIM(t.gpon), TRIM(t.feeder),
        TRIM(t.distribusi), TRIM(t.distribusi),
        TRIM(t.datek1), TRIM(t.datek_inputan), TRIM(t.datek_real),
        TRIM(t.hasil_ukur_odp), TRIM(t.hasil_ukur_distribusi), TRIM(t.hasil_ukur_feeder),
        1, NOW(), NOW()
    FROM tmp_etl_deduped t;

    INSERT IGNORE INTO fact_workorder (
        etl_log_id, wo_sc_id,
        date_id, sto_id, teknisi_id, pelanggan_id, kendala_id, status_id,
        tanggal_order, tanggal_komitmen, status_wo,
        durasi_hari, durasi_pengerjaan_menit, durasi_grup, durasi_manja,
        tgl_input_hd_gdocs, is_sla_tercapai, is_workfail, is_unsc,
        sc_id, track_id, track_id_baru,
        created_at, updated_at
    )
    SELECT
        p_log_id,
        TRIM(t.wo_sc_id),
        CAST(DATE_FORMAT(COALESCE(t.tanggal, CURDATE()), '%Y%m%d') AS UNSIGNED),
        ds.sto_id,
        dt.teknisi_id,
        dp.pelanggan_id,
        dk.kendala_id,
        dstatus.status_id,
        t.tanggal_order,
        t.tanggal_komitmen,
        TRIM(t.status_wo),
        CASE
            WHEN t.durasi_hari BETWEEN -32768 AND 32767 THEN t.durasi_hari
            ELSE NULL
        END,
        CASE
            WHEN ABS(t.durasi * 60) <= 99999999.99 THEN t.durasi * 60
            ELSE NULL
        END,
        t.durasi_grup,
        t.durasi_manja,
        t.tgl_input_hd_gdocs,
        CASE WHEN dstatus.status_group = 'SELESAI' THEN 1 ELSE 0 END,
        CASE WHEN LOWER(t.status_wo) REGEXP 'gagal|fail|batal|cancel' THEN 1 ELSE 0 END,
        COALESCE(t.is_unsc, 0),
        t.sc_id,
        t.track_id,
        t.track_id_baru,
        NOW(), NOW()
    FROM tmp_etl_deduped t
    INNER JOIN dim_sto ds ON ds.nama_sto = TRIM(t.sto)
    INNER JOIN dim_teknisi dt ON dt.nik_teknisi = TRIM(t.nik_teknisi)
    INNER JOIN dim_pelanggan dp ON dp.kode_tracking = TRIM(t.wo_sc_id)
    INNER JOIN dim_kendala dk ON dk.kendala_pt1 = TRIM(t.kendala_pt1)
    INNER JOIN dim_status dstatus ON dstatus.status_wo = TRIM(t.status_wo)
    WHERE NULLIF(TRIM(t.sto), '') IS NOT NULL
      AND NULLIF(TRIM(t.nik_teknisi), '') IS NOT NULL
      AND NULLIF(TRIM(t.kendala_pt1), '') IS NOT NULL
      AND NULLIF(TRIM(t.status_wo), '') IS NOT NULL
    ON DUPLICATE KEY UPDATE
        date_id = VALUES(date_id),
        sto_id = VALUES(sto_id),
        teknisi_id = VALUES(teknisi_id),
        pelanggan_id = VALUES(pelanggan_id),
        kendala_id = VALUES(kendala_id),
        status_id = VALUES(status_id),
        tanggal_order = VALUES(tanggal_order),
        tanggal_komitmen = VALUES(tanggal_komitmen),
        status_wo = VALUES(status_wo),
        durasi_hari = VALUES(durasi_hari),
        durasi_pengerjaan_menit = VALUES(durasi_pengerjaan_menit),
        durasi_grup = VALUES(durasi_grup),
        durasi_manja = VALUES(durasi_manja),
        tgl_input_hd_gdocs = VALUES(tgl_input_hd_gdocs),
        is_sla_tercapai = VALUES(is_sla_tercapai),
        is_workfail = VALUES(is_workfail),
        is_unsc = VALUES(is_unsc),
        sc_id = VALUES(sc_id),
        track_id = VALUES(track_id),
        track_id_baru = VALUES(track_id_baru),
        updated_at = NOW();

    SELECT COUNT(*) INTO v_success
    FROM tmp_etl_deduped t
    INNER JOIN fact_workorder fw ON fw.wo_sc_id = TRIM(t.wo_sc_id);

    INSERT IGNORE INTO fact_kendalateknis (
        etl_log_id, wo_id, date_id, sto_id, kendala_id, infra_id,
        jumlah_kendala, hasil_solusi_maintenance,
        hasil_solusi_optima, hasil_solusi_sdi,
        total_eskalasi, durasi_grup_pengerjaan,
        created_at, updated_at
    )
    SELECT
        p_log_id,
        fw.wo_id,
        fw.date_id,
        fw.sto_id,
        fw.kendala_id,
        di.infra_id,
        COALESCE(t.jumlah_kendala, 1),
        t.hasil_solusi_maintenance,
        t.hasil_solusi_optima,
        t.hasil_solusi_sdi,
        COALESCE(t.total_eskalasi, 0),
        t.durasi_grup_pengerjaan,
        NOW(), NOW()
    FROM tmp_etl_deduped t
    INNER JOIN fact_workorder fw
        ON fw.wo_sc_id = TRIM(t.wo_sc_id) AND fw.etl_log_id = p_log_id
    INNER JOIN dim_infrastruktur di ON di.wo_id = TRIM(t.wo_sc_id);

    SET v_duplicate = v_in_file_duplicate;
    SET v_failed = GREATEST(v_unique_wo - v_success, 0);

    UPDATE etl_logs
    SET total_rows = v_total,
        success_count = v_success,
        failed_count = v_failed,
        duplicate_count = v_duplicate,
        status = IF(v_failed = 0, 'done', 'error'),
        error_message = NULL,
        errors = JSON_OBJECT(
            'duplicate_in_file', v_in_file_duplicate,
            'missing_sto', (
                SELECT COUNT(*) FROM tmp_etl_deduped
                WHERE NULLIF(TRIM(sto), '') IS NULL
            ),
            'missing_teknisi', (
                SELECT COUNT(*) FROM tmp_etl_deduped
                WHERE NULLIF(TRIM(nik_teknisi), '') IS NULL
            ),
            'missing_kendala', (
                SELECT COUNT(*) FROM tmp_etl_deduped
                WHERE NULLIF(TRIM(kendala_pt1), '') IS NULL
            ),
            'missing_status', (
                SELECT COUNT(*) FROM tmp_etl_deduped
                WHERE NULLIF(TRIM(status_wo), '') IS NULL
            )
        ),
        updated_at = NOW()
    WHERE id = p_log_id;

    TRUNCATE TABLE staging_workorder_flat;
    DROP TEMPORARY TABLE IF EXISTS tmp_etl_deduped;

    SET SESSION foreign_key_checks = 1;
    SET SESSION unique_checks = 1;
    COMMIT;
END
SQL);
    }

    public function down(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS sp_etl_workorder');
    }
};
