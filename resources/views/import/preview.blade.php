@extends('layouts.app')

@section('title', 'Preview Import Data WO')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold mb-1">Import Work Order</div>
        <h1 class="page-title mb-0">Preview Header &amp; Data</h1>
    </div>
    <div class="text-muted text-end small">
        <div>{{ now()->format('d F Y') }}</div>
        @isset($originalName)
            <div class="text-primary">{{ $originalName }} ({{ $fileSize }} MB)</div>
        @endisset
    </div>
</div>

<form id="processForm" action="{{ route('import.process') }}" method="POST">
    @csrf
    <input type="hidden" name="file_path" value="{{ $filePath }}">

    {{-- Loading overlay --}}
    <div id="processStatus" class="alert alert-info d-none align-items-center mb-4">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Proses ETL sedang berjalan... Jangan tutup halaman ini. File besar bisa memakan beberapa menit.
    </div>

    <div class="row g-4 mb-4">
        {{-- ── Status kartu ─────────────────────────────────────────────── --}}
        <div class="col-md-4">
            <div class="card card-metric p-3 h-100">
                <div class="metric-label text-muted">Total Kolom Terdeteksi</div>
                <div class="metric-value">{{ count($headerAnalysis['raw_headers'] ?? []) }}</div>
                <div class="small text-muted mt-1">kolom di file Excel</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric p-3 h-100
                {{ count($headerAnalysis['missing_headers'] ?? []) ? 'border-danger' : 'border-success' }}">
                <div class="metric-label {{ count($headerAnalysis['missing_headers'] ?? []) ? 'text-danger' : 'text-success' }}">
                    Header Wajib
                </div>
                <div class="metric-value">
                    {{ count($headerAnalysis['missing_headers'] ?? []) ? 'Tidak Lengkap' : 'Lengkap ✓' }}
                </div>
                <div class="small text-muted mt-1">
                    {{ count($headerAnalysis['missing_headers'] ?? []) }} header hilang
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric p-3 h-100">
                <div class="metric-label text-warning">Header Tidak Dikenali</div>
                <div class="metric-value">{{ count($headerAnalysis['unmapped_headers'] ?? []) }}</div>
                <div class="small text-muted mt-1">akan diabaikan / bisa di-mapping</div>
            </div>
        </div>
    </div>

    {{-- ── Alert header hilang ─────────────────────────────────────────── --}}
    @if(count($headerAnalysis['missing_headers'] ?? []))
        <div class="alert alert-danger mb-4">
            <strong>⛔ Header wajib tidak ditemukan:</strong>
            <ul class="mb-0 mt-2">
                @foreach($headerAnalysis['missing_headers'] as $h)
                    <li><code>{{ strtoupper(str_replace('_', ' ', $h)) }}</code></li>
                @endforeach
            </ul>
            <div class="mt-2 small">
                Pastikan file Excel memiliki kolom tersebut, atau gunakan <strong>mapping manual</strong> di bawah.
            </div>
        </div>
    @else
        <div class="alert alert-success mb-4">
            ✅ Semua header wajib ditemukan. Sistem siap memproses ETL.
        </div>
    @endif

    {{-- ── Mapping manual header tidak dikenali ─────────────────────────── --}}
    @if(count($headerAnalysis['unmapped_headers'] ?? []))
        <div class="card panel-card mb-4">
            <div class="card-header">
                Mapping Manual Header Tidak Dikenali
                <span class="badge bg-warning text-dark ms-2">{{ count($headerAnalysis['unmapped_headers']) }}</span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Header berikut tidak dikenali secara otomatis. Pilih kolom tujuan jika relevan,
                    atau biarkan kosong untuk diabaikan.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Header di File</th>
                                <th>Petakan ke Kolom</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($headerAnalysis['unmapped_headers'] as $idx => $rawHeader)
                                <tr>
                                    <td><code>{{ $rawHeader }}</code></td>
                                    <td>
                                        <input type="hidden"
                                               name="manual_raw_header[{{ $loop->index }}]"
                                               value="{{ $rawHeader }}">
                                        <select name="manual_mapping[{{ $loop->index }}]"
                                                class="form-select form-select-sm">
                                            <option value="">— Abaikan —</option>
                                            @foreach($canonicalLabels as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Preview tabel ───────────────────────────────────────────────── --}}
    <div class="card panel-card mb-4">
        <div class="card-header">
            Preview Data (20 Baris Pertama)
        </div>
        <div class="card-body p-0">
            @if(count($rows ?? []))
                <div class="table-responsive" style="max-height:420px; overflow-y:auto">
                    <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.78rem">
                        <thead class="table-dark sticky-top">
                            <tr>
                                <th>#</th>
                                @foreach(array_keys((array) $rows[0]) as $col)
                                    <th class="text-nowrap">{{ Illuminate\Support\Str::headline($col) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $i => $row)
                                <tr>
                                    <td class="text-muted">{{ $i + 1 }}</td>
                                    @foreach($row as $val)
                                        <td class="text-nowrap" style="max-width:200px;overflow:hidden;text-overflow:ellipsis" title="{{ $val }}">
                                            {{ Str::limit($val, 40) }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-muted text-center">Tidak ada baris data untuk ditampilkan.</div>
            @endif
        </div>
    </div>

    {{-- ── Tombol aksi ─────────────────────────────────────────────────── --}}
    <div class="d-flex gap-3 align-items-center">
        @if(empty($headerAnalysis['missing_headers']))
            <button id="processButton" type="submit" class="btn btn-success btn-lg px-5">
                🚀 Proses ETL Sekarang
            </button>
        @else
            <button type="submit" class="btn btn-success btn-lg px-5" disabled title="Perbaiki header dulu">
                🚀 Proses ETL Sekarang
            </button>
            <span class="text-danger small">Lengkapi header wajib sebelum memproses.</span>
        @endif
        <a href="{{ route('import.index') }}" class="btn btn-outline-secondary">
            ← Upload Ulang
        </a>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form   = document.getElementById('processForm');
    const status = document.getElementById('processStatus');
    const btn    = document.getElementById('processButton');

    if (form && btn) {
        form.addEventListener('submit', function () {
            status.classList.remove('d-none');
            status.classList.add('d-flex');
            btn.disabled    = true;
            btn.textContent = '⏳ Memproses ETL...';
            btn.classList.remove('btn-success');
            btn.classList.add('btn-secondary');
        });
    }
});
</script>
@endpush