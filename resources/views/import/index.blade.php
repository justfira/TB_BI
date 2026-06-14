@extends('layouts.app')

@section('title', 'Upload Excel Work Order')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold mb-1">Data Management</div>
        <h1 class="page-title mb-0">Import Work Order</h1>
    </div>
    <div class="text-muted text-end small">
        <div>{{ now()->format('d F Y') }}</div>
        <div>Upload Excel</div>
    </div>
</div>

{{-- Flash message dari proses sebelumnya --}}
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="row g-4">
    {{-- Sidebar Info --}}
    <div class="col-lg-5 d-flex flex-column gap-4">
        {{-- Panduan --}}
        <div class="card panel-card">
            <div class="card-header">Panduan Upload</div>
            <div class="card-body">
                <ol class="ps-3 mb-0 small text-muted" style="line-height:2">
                    <li>Pilih atau drag file Excel (.xlsx/.xls)</li>
                    <li>Klik <strong>Preview Data</strong> untuk cek header</li>
                    <li>Perbaiki mapping jika ada header tidak dikenali</li>
                    <li>Klik <strong>Proses ETL</strong> untuk simpan ke database</li>
                    <li>Lihat hasil ringkasan &amp; error di halaman hasil</li>
                </ol>
            </div>
        </div>

        {{-- Status ETL --}}
        <div class="card panel-card">
            <div class="card-header">Status Staging ETL</div>
            <div class="card-body status-box">
                <div class="status-item">
                    <span>Pending</span>
                    <strong class="text-warning">{{ number_format($pendingCount ?? 0) }}</strong>
                </div>
                <div class="status-item">
                    <span>Processed</span>
                    <strong class="text-success">{{ number_format($processedCount ?? 0) }}</strong>
                </div>
                <div class="status-item">
                    <span>Failed</span>
                    <strong class="text-danger">{{ number_format($failedCount ?? 0) }}</strong>
                </div>
            </div>
        </div>
    </div>
    {{-- ── Form Upload ─────────────────────────────────────────────────── --}}
    <div class="col-lg-7">
        <div class="card panel-card h-100">
            <div class="card-header">Upload File Excel</div>
            <div class="card-body d-flex flex-column">
                <form id="importForm"
                      action="{{ route('import.preview') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    {{-- Progress indicator --}}
                    <div id="uploadStatus" class="alert alert-info d-none align-items-center mb-3" role="status">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Membaca file... harap tunggu, file besar membutuhkan waktu lebih lama.
                    </div>

                    @error('file')
                        <div class="alert alert-danger mb-3">{{ $message }}</div>
                    @enderror

                    {{-- Drop zone --}}
                    <div class="upload-drop position-relative mb-4" id="dropZone">
                        <div id="dropContent">
                            <div class="fs-2 mb-2">📂</div>
                            <div class="fw-semibold mb-1">Drag &amp; drop file Excel di sini</div>
                            <div class="text-muted small">Format: .xlsx, .xls &nbsp;|&nbsp; Maks. ukuran: 100 MB</div>
                        </div>
                        <div id="fileSelected" class="d-none">
                            <div class="fs-2 mb-2">✅</div>
                            <div class="fw-semibold" id="selectedFileName">—</div>
                            <div class="text-muted small" id="selectedFileSize">—</div>
                        </div>
                        <input type="file"
                               id="fileInput"
                               name="file"
                               accept=".xlsx,.xls,.csv"
                               required
                               class="position-absolute top-0 start-0 w-100 h-100 opacity-0"
                               style="cursor:pointer">
                    </div>

                    <button id="uploadButton" type="submit" class="btn btn-primary w-100">
                        Preview Data &amp; Cek Header
                    </button>
                </form>

                <div class="mt-4 text-muted small">
                    <strong>Catatan:</strong> Setelah upload, sistem akan membaca 20 baris pertama untuk
                    memvalidasi header kolom sebelum proses ETL dimulai.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Histori Proses ETL ───────────────────────────────────────── --}}
<div class="card panel-card mt-4">
    <div class="card-header d-flex align-items-center justify-content-between gap-2">
        <span>Histori Proses ETL</span>
        <div class="d-flex align-items-center gap-2">
            @if(isset($etlLogs) && $etlLogs->total() > 0)
                <span class="badge bg-secondary">{{ $etlLogs->total() }} batch</span>
            @endif
            {{-- Hapus full database hasil ETL (works hanya jika endpoint tersedia) --}}
            <form action="{{ route('import.history.destroy', 0) }}" method="POST" onsubmit="return confirm('Hapus ALL data hasil ETL dari database? Ini tidak bisa dibatalkan.');">
                @csrf
                @method('DELETE')
                <input type="hidden" name="all" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Semua</button>
            </form>
        </div>
    </div>

    <div class="card-body p-0">
        @if(!isset($etlLogs) || $etlLogs->isEmpty())
            <div class="p-4 text-muted small text-center">Belum ada batch ETL.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Waktu</th>
                        <th>Status</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Sukses</th>
                        <th class="text-end">Duplikat</th>
                        <th class="text-end">Gagal</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($etlLogs as $log)
                        <tr>
                            <td>#{{ $log->id }}</td>
                            <td class="text-nowrap small">{{ optional($log->imported_at)->format('d M Y H:i') }}</td>
                            <td>
                                @php
                                    $badgeClass = match($log->status) {
                                        'done'    => 'bg-success',
                                        'error'   => 'bg-danger',
                                        'running' => 'bg-info',
                                        'queued'  => 'bg-secondary',
                                        default   => 'bg-light text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $log->status }}</span>
                            </td>
                            <td class="text-end">{{ number_format($log->total_rows) }}</td>
                            <td class="text-end text-success">{{ number_format($log->success_count) }}</td>
                            <td class="text-end text-warning">{{ number_format($log->duplicate_count) }}</td>
                            <td class="text-end text-danger">{{ number_format($log->failed_count) }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('import.result', $log->id) }}" class="btn btn-sm btn-outline-primary">Detail</a>
                                @if(!in_array($log->status, ['queued', 'running'], true))
                                    <form action="{{ route('import.history.destroy', $log->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus histori ETL #{{ $log->id }}? Data hasil batch juga akan dihapus.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Pagination (pakai default Laravel). Jika tampilannya mengganggu, bisa diganti manual di sini. --}}
            <div class="p-3">
                {{ $etlLogs->links('pagination::bootstrap-5') }}
            </div>

        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form        = document.getElementById('importForm');
    const uploadStatus= document.getElementById('uploadStatus');
    const uploadBtn   = document.getElementById('uploadButton');
    const fileInput   = document.getElementById('fileInput');
    const dropContent = document.getElementById('dropContent');
    const fileSelected= document.getElementById('fileSelected');
    const fileNameEl  = document.getElementById('selectedFileName');
    const fileSizeEl  = document.getElementById('selectedFileSize');

    // Tampilkan nama file saat dipilih
    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const f    = this.files[0];
            const sizeMB = (f.size / 1024 / 1024).toFixed(2);
            fileNameEl.textContent = f.name;
            fileSizeEl.textContent = sizeMB + ' MB';
            dropContent.classList.add('d-none');
            fileSelected.classList.remove('d-none');
        }
    });

    // Submit → tampilkan loading
    form.addEventListener('submit', function () {
        uploadStatus.classList.remove('d-none');
        uploadStatus.classList.add('d-flex');
        uploadBtn.disabled    = true;
        uploadBtn.textContent = 'Mengunggah & Membaca File...';
    });
});
</script>
@endpush
