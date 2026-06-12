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

    {{-- ── Sidebar Info ────────────────────────────────────────────────── --}}
    <div class="col-lg-5 d-flex flex-column gap-4">

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