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

{{-- ── Status: queued / running → tampilkan progress bar + polling ─────────── --}}
@if(in_array($log->status, ['queued', 'running']))
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4" id="processingAlert">
        <span class="spinner-border spinner-border-sm flex-shrink-0"></span>
        <div class="w-100">
            <strong id="processingTitle">
                {{ $log->status === 'queued' ? 'Menunggu worker memproses...' : 'ETL sedang berjalan...' }}
            </strong>
            <div class="small mt-1">Jangan tutup halaman ini. Halaman otomatis update tiap 3 detik.</div>
            <div class="progress mt-2" style="height:6px">
                <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
            </div>
        </div>
    </div>

    {{-- Kartu live counter — diupdate via JS ──────────────────────────────── --}}
    <div class="row g-3 mb-4" id="liveCards">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-metric p-4 text-center">
                <div class="metric-label text-muted">Total Dibaca</div>
                <div class="metric-value" id="liveTotalRows">–</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-metric p-4 text-center border-success">
                <div class="metric-label text-success">Berhasil</div>
                <div class="metric-value text-success" id="liveSuccess">–</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-metric p-4 text-center border-warning">
                <div class="metric-label text-warning">Duplikat</div>
                <div class="metric-value text-warning" id="liveDuplicate">–</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-metric p-4 text-center border-danger">
                <div class="metric-label text-danger">Gagal</div>
                <div class="metric-value text-danger" id="liveFailed">–</div>
            </div>
        </div>
    </div>

@else
    {{-- ── Status: done / error → tampilkan hasil final ──────────────────── --}}
    @if($log->status === 'done')
        <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
            <span style="font-size:2rem">✅</span>
            <div>
                <strong>ETL selesai!</strong> Data berhasil diproses dan disimpan ke database.
                <div class="small mt-1">
                Lihat hasilnya di <a href="{{ route('dashboard.index') }}">Dashboard BI</a>.
                </div>
            </div>
        </div>
    @elseif($log->status === 'error')
        <div class="alert alert-danger d-flex align-items-center gap-3 mb-4">
            <span style="font-size:2rem">❌</span>
            <div>
                <strong>ETL gagal.</strong> Terjadi kesalahan sistem.
                @if($log->error_message)
                    <div class="small mt-1"><code>{{ $log->error_message }}</code></div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-warning mb-4">Status tidak diketahui: {{ $log->status }}</div>
    @endif

    {{-- Kartu ringkasan final ─────────────────────────────────────────────── --}}
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
                @if($log->total_rows > 0)
                    <div class="small text-muted">
                        {{ round($log->success_count / $log->total_rows * 100, 1) }}%
                    </div>
                @endif
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

    {{-- Baris gagal ──────────────────────────────────────────────────────── --}}
    @if($failedRows->isNotEmpty())
        <div class="card panel-card mb-4">
            <div class="card-header">
                Detail Baris Gagal
                <span class="badge bg-danger ms-2">{{ $failedRows->count() }}</span>
                <span class="text-muted small ms-2">(maks. 50 baris)</span>
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

    {{-- Tombol aksi ──────────────────────────────────────────────────────── --}}
    <div class="d-flex gap-3">
        <a href="{{ route('dashboard.index') }}" class="btn btn-primary btn-lg">
            📊 Lihat Dashboard BI
        </a>
        <a href="{{ route('import.index') }}" class="btn btn-outline-secondary">
            ⬆️ Upload File Lain
        </a>
    </div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Hanya jalankan polling kalau status masih queued/running
    const isProcessing = {{ in_array($log->status, ['queued', 'running']) ? 'true' : 'false' }};
    if (!isProcessing) return;

    const statusUrl = '{{ route('import.status', $log->id) }}';
    let pollInterval;

    function formatNumber(n) {
        return (n ?? 0).toLocaleString('id-ID');
    }

    function updateLiveCards(data) {
        const total = document.getElementById('liveTotalRows');
        const succ  = document.getElementById('liveSuccess');
        const dupl  = document.getElementById('liveDuplicate');
        const fail  = document.getElementById('liveFailed');

        if (total) total.textContent = formatNumber(data.total_rows);
        if (succ)  succ.textContent  = formatNumber(data.success_count);
        if (dupl)  dupl.textContent  = formatNumber(data.duplicate_count);
        if (fail)  fail.textContent  = formatNumber(data.failed_count);

        const title = document.getElementById('processingTitle');
        if (title) {
            title.textContent = data.status === 'queued'
                ? 'Menunggu worker memproses...'
                : 'ETL berjalan...';
        }
    }

    async function poll() {
        try {
            const res  = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            updateLiveCards(data);

            // Kalau sudah selesai → reload halaman untuk tampilkan hasil final
            if (data.is_done) {
                clearInterval(pollInterval);
                window.location.reload();
            }
        } catch (err) {
            console.warn('Polling error:', err);
        }
    }

    // Poll pertama langsung, lalu tiap 8 detik (lebih jarang biar endpoint tidak dipukul terus)
    poll();
    pollInterval = setInterval(poll, 8000);
});
</script>
@endpush