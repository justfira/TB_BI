@extends('layouts.app')

@section('title', 'Hasil Proses ETL')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold mb-1">Import Work Order</div>
        <h1 class="page-title mb-0">Hasil Proses ETL</h1>
    </div>
    <div class="text-muted text-end small">
        <div>{{ $log->imported_at->format('d F Y H:i:s') }}</div>
        <div>Batch #{{ $log->id }}</div>
    </div>
</div>

{{-- ── Status badge ─────────────────────────────────────────────────────── --}}
<div class="mb-4">
    @if($log->status === 'done')
        <div class="alert alert-success d-flex align-items-center gap-3 mb-0">
            <span style="font-size:2rem">✅</span>
            <div>
                <strong>ETL selesai!</strong> Data berhasil diproses dan disimpan ke database.
                <div class="small mt-1">Sekarang hasil dapat dilihat di <a href="{{ route('dashboard') }}">Dashboard BI</a>.</div>
            </div>
        </div>
    @elseif($log->status === 'error')
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-0">
            <span style="font-size:2rem">❌</span>
            <div>
                <strong>ETL gagal total.</strong> Terjadi kesalahan sistem.
                @if($log->error_message)
                    <div class="small mt-1"><code>{{ $log->error_message }}</code></div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning">ETL masih berjalan atau status tidak diketahui.</div>
    @endif
</div>

{{-- ── Kartu ringkasan ──────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card card-metric p-4 text-center">
            <div class="metric-label text-muted">Total Baris Dibaca</div>
            <div class="metric-value">{{ number_format($log->total_rows) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-metric p-4 text-center border-success">
            <div class="metric-label text-success">Berhasil Diproses</div>
            <div class="metric-value text-success">{{ number_format($log->success_count) }}</div>
            <div class="small text-muted">{{ $log->success_rate }}%</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-metric p-4 text-center border-warning">
            <div class="metric-label text-warning">Duplikat (Dilewati)</div>
            <div class="metric-value text-warning">{{ number_format($log->duplicate_count) }}</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card card-metric p-4 text-center border-danger">
            <div class="metric-label text-danger">Gagal / Error</div>
            <div class="metric-value text-danger">{{ number_format($log->failed_count) }}</div>
        </div>
    </div>
</div>

{{-- ── Baris yang gagal ─────────────────────────────────────────────────── --}}
@if($failedRows->isNotEmpty())
    <div class="card panel-card mb-4">
        <div class="card-header">
            Detail Baris Gagal
            <span class="badge bg-danger ms-2">{{ $failedRows->count() }}</span>
            <span class="text-muted small ms-2">(ditampilkan maks. 50 baris terakhir)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:320px;overflow-y:auto">
                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.78rem">
                    <thead class="table-danger sticky-top">
                        <tr>
                            <th>#</th>
                            <th>Waktu</th>
                            <th>Data JSON</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedRows as $i => $fr)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td class="text-nowrap">{{ $fr->created_at->format('H:i:s') }}</td>
                                <td style="max-width:600px;word-break:break-all;font-size:0.72rem">
                                    {{ Str::limit($fr->data_json, 200) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ── Tombol aksi ─────────────────────────────────────────────────────── --}}
<div class="d-flex gap-3">
    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg">
        📊 Lihat Dashboard BI
    </a>
    <a href="{{ route('import.index') }}" class="btn btn-outline-secondary">
        ⬆️ Upload File Lain
    </a>
</div>
@endsection