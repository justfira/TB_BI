# TODO - Optimasi performa ETL

## Rencana
- [x] 1) Naikkan chunk INSERT untuk `fact_workorder` dan `fact_kendalateknis` (500 -> 5000 atau 2000).

- [x] 2) Verifikasi skema `fact_kendalateknis` (butuh FK `fact_workorder_id`) dan perbaiki mapping di `flushFactsBatch()`.


- [ ] 3) (Jika masih lambat) cek & rapikan indeks pada kolom FK / unique yang sering dipakai.


## Status
- (baru dibuat)

