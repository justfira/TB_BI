# TODO (BlackboxAI)

## ETL batch delete by `etl_log_id` + histori UI
- [ ] Update `app/Services/EtlService.php`: set `etl_log_id` pada insert `dim_infrastruktur`, `fact_workorder`, `fact_kendalateknis`
- [ ] Update `app/Services/EtlService.php`: pastikan `flushFactsBatch()` menerima `etlLogId`
- [ ] Update `app/Services/EtlService.php`: implement `deleteEtlBatch(int $etlLogId): array`
- [ ] Update stored procedure `database/migrations/2026_06_13_140000_create_sp_etl_workorder.php`: isi `etl_log_id` pada insert dim/fact
- [ ] Update UI `resources/views/import/index.blade.php`: tambah section tabel Histori Proses ETL + tombol hapus
- [ ] Update UI `app/Http/Controllers/DashboardController.php` + `resources/views/dashboard/index.blade.php`: hapus card “Batch ETL Terakhir” dan data `latestEtlLog`
- [ ] Jalankan `php artisan migrate`
- [ ] `php artisan optimize:clear`
- [ ] Smoke test: import → hasil masuk → delete histori → data terhapus
