@extends('layouts.app')

@section('title', 'Dashboard BI Ridar')

@section('content')

{{-- ── Header ──────────────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold mb-1">Dashboard Pimpinan</div>
        <h1 class="page-title mb-0">Monitoring Pengawalan Order Kendala Teknik</h1>
    </div>
    <div class="text-end text-muted small">
        <div>{{ now()->format('d F Y') }}</div>
        <div>Administrator</div>
    </div>
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────────────── --}}
<div class="card panel-card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('dashboard.index') }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-1">Tahun</label>
                <select name="tahun" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(range(now()->year, now()->year - 3) as $y)
                        <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Bulan</label>
                <select name="bulan" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    @foreach(range(1,12) as $m)
                        <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                            {{ Carbon\Carbon::create()->month($m)->locale('id')->isoFormat('MMMM') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">STO</label>
                <select name="sto" class="form-select form-select-sm">
                    <option value="">Semua STO</option>
                    @foreach($stoOptions as $s)
                        <option value="{{ $s->sto_id }}" {{ request('sto') == $s->sto_id ? 'selected' : '' }}>
                            {{ $s->nama_sto }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Teknisi</label>
                <select name="teknisi" class="form-select form-select-sm">
                    <option value="">Semua Teknisi</option>
                    @foreach($teknisiOptions as $t)
                        <option value="{{ $t->teknisi_id }}" {{ request('teknisi') == $t->teknisi_id ? 'selected' : '' }}>
                            {{ $t->nama_teknisi }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="{{ route('dashboard.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- ── Kartu Metrik ────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-primary">Total Work Order</div>
            <div class="metric-value">{{ number_format($totalWorkorder ?? 0) }}</div>
            <div class="text-muted small mt-2">Semua status</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-success">WO Selesai</div>
            <div class="metric-value">{{ number_format($totalSelesai ?? 0) }}</div>
            <div class="text-muted small mt-2">Status group: Selesai</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-warning">WO Pending</div>
            <div class="metric-value">{{ number_format($totalPending ?? 0) }}</div>
            <div class="text-muted small mt-2">Belum selesai</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-success">SLA Achievement</div>
            <div class="metric-value">{{ number_format($slaAchievement ?? 0, 1) }}%</div>
            <div class="text-muted small mt-2">is_sla_tercapai = 1</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-danger">Avg Resolution</div>
            <div class="metric-value">{{ number_format($averageResolution ?? 0, 1) }} <small class="fs-6">jam</small></div>
            <div class="text-muted small mt-2">Rata-rata durasi</div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-6">
        <div class="card card-metric bg-white p-4">
            <div class="metric-label text-info">Total Workfail</div>
            <div class="metric-value">{{ number_format($totalWorkfail ?? 0) }}</div>
            <div class="text-muted small mt-2">is_workfail = 1</div>
        </div>
    </div>
</div>

{{-- ── Baris 2: Tren + Upload + ETL ──────────────────────────────────────── --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card panel-card h-100">
            <div class="card-header">Tren Work Order Bulanan</div>
            <div class="card-body">
                <canvas id="chartTrend" style="max-height:280px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4 d-flex flex-column gap-3">

        {{-- Upload cepat --}}
        <div class="card panel-card">
            <div class="card-header">Upload Excel</div>
            <div class="card-body">
                <form action="{{ route('import.preview') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="upload-drop position-relative mb-3" style="min-height:80px;padding:1rem">
                        <div class="text-center">
                            <div class="small fw-semibold">Drag &amp; drop atau klik pilih file</div>
                            <div class="text-muted" style="font-size:.75rem">.xlsx / .xls · Maks 100 MB</div>
                        </div>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                               class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                               style="cursor:pointer">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm">Preview &amp; Import</button>
                </form>
            </div>
        </div>

        {{-- Status ETL --}}
        <div class="card panel-card">
            <div class="card-header">Status ETL</div>
            <div class="card-body status-box">
                <div class="status-item">
                    <div>Pending</div>
                    <div class="fw-bold text-warning">{{ number_format($pendingCount ?? 0) }}</div>
                </div>
                <div class="status-item">
                    <div>Processed</div>
                    <div class="fw-bold text-success">{{ number_format($processedCount ?? 0) }}</div>
                </div>
                <div class="status-item">
                    <div>Failed</div>
                    <div class="fw-bold text-danger">{{ number_format($failedCount ?? 0) }}</div>
                </div>
            </div>
        </div>

        {{-- Ringkasan ETL terakhir --}}
        <div class="card panel-card">
            <div class="card-header">Batch ETL Terakhir</div>
            <div class="card-body">
                @if(isset($latestEtlLog))
                    <div class="text-muted small">Diproses pada:</div>
                    <div class="fw-bold mb-2">{{ $latestEtlLog->imported_at->format('d M Y H:i') }}</div>
                    <div class="d-flex justify-content-between small mb-1"><span>Total Baris</span><span>{{ number_format($latestEtlLog->total_rows) }}</span></div>
                    <div class="d-flex justify-content-between small mb-1"><span>Sukses</span><span class="text-success">{{ number_format($latestEtlLog->success_count) }}</span></div>
                    <div class="d-flex justify-content-between small mb-1"><span>Gagal</span><span class="text-danger">{{ number_format($latestEtlLog->failed_count) }}</span></div>
                    <div class="d-flex justify-content-between small"><span>Duplikat</span><span class="text-warning">{{ number_format($latestEtlLog->duplicate_count) }}</span></div>
                    <a href="{{ route('import.result', $latestEtlLog->id) }}"
                       class="btn btn-outline-primary btn-sm w-100 mt-3">Lihat Detail Hasil</a>
                @else
                    <div class="text-muted small">Belum ada batch ETL.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ── Baris 3: Chart bawah ────────────────────────────────────────────────── --}}
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card panel-card">
            <div class="card-header">Top 8 STO Kendala Terbanyak</div>
            <div class="card-body">
                <canvas id="chartTopSto" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel-card">
            <div class="card-header">Top 8 Kendala</div>
            <div class="card-body">
                <canvas id="chartTopKendala" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card panel-card">
            <div class="card-header">Distribusi Status WO</div>
            <div class="card-body">
                <canvas id="chartStatus" style="max-height:260px"></canvas>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Data dari controller (kolom sudah benar) ────────────────────────────────
const chartTrendLabels   = {!! json_encode($chartTrend->pluck('label')) !!};
const chartTrendData     = {!! json_encode($chartTrend->pluck('total')) !!};
const statusLabels       = {!! json_encode($statusDistribution->pluck('status')) !!};
const statusData         = {!! json_encode($statusDistribution->pluck('total')) !!};
const topStoLabels       = {!! json_encode($topSto->pluck('sto')) !!};
const topStoData         = {!! json_encode($topSto->pluck('total')) !!};
const topKendalaLabels   = {!! json_encode($topKendala->pluck('kendala')) !!};
const topKendalaData     = {!! json_encode($topKendala->pluck('total')) !!};

// ── Tren ────────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: chartTrendLabels,
        datasets: [{
            label: 'Work Order',
            data: chartTrendData,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            tension: 0.4,
            fill: true,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// ── Status pie ───────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusData,
            backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#6c757d','#0dcaf0','#6f42c1'],
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});

// ── Top STO ──────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartTopSto'), {
    type: 'bar',
    data: {
        labels: topStoLabels,
        datasets: [{ label: 'Jumlah WO', data: topStoData, backgroundColor: '#0d6efd' }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});

// ── Top Kendala ──────────────────────────────────────────────────────────────
new Chart(document.getElementById('chartTopKendala'), {
    type: 'bar',
    data: {
        labels: topKendalaLabels,
        datasets: [{ label: 'Jumlah', data: topKendalaData, backgroundColor: '#ffc107' }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>
@endpush