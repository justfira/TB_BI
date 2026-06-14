@extends('layouts.app')

@section('title', 'Laporan Work Order')

@push('styles')
<style>
    .report-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        white-space: nowrap;
        border-bottom: 1px solid #e2e8f0;
    }
    .report-table tbody td {
        font-size: 0.875rem;
        vertical-align: middle;
        color: #1e293b;
    }
    .report-table tbody tr:hover {
        background: #f8fbff;
    }
    .report-meta {
        color: #64748b;
        font-size: 0.9rem;
    }
    .report-pagination {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid #e2e8f0;
    }
    .report-pagination nav {
        margin-left: auto;
    }
    .report-pagination nav > div {
        justify-content: flex-end !important;
    }
    .report-pagination nav > div > div:first-child {
        display: none;
    }
    .report-pagination .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.25rem;
    }
    .report-pagination .page-link {
        min-width: 38px;
        padding: 0.45rem 0.7rem;
        font-size: 0.875rem;
        text-align: center;
        border-radius: 8px;
    }
    .report-pagination .page-item:first-child .page-link,
    .report-pagination .page-item:last-child .page-link {
        min-width: 34px;
    }
    .report-pagination .page-item:not(:first-child) .page-link {
        margin-left: 0;
    }
    @media (max-width: 767px) {
        .report-pagination {
            align-items: flex-start;
            flex-direction: column;
        }
        .report-pagination nav {
            margin-left: 0;
            width: 100%;
        }
        .report-pagination .pagination {
            justify-content: flex-start;
        }
    }
    .badge-sla-yes { background: #dcfce7; color: #166534; }
    .badge-sla-no  { background: #fee2e2; color: #991b1b; }
    .badge-wf-yes  { background: #ffedd5; color: #9a3412; }
    .text-empty    { color: #94a3b8; }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="text-uppercase text-muted small fw-semibold mb-1">Reporting</div>
        <h1 class="page-title mb-0">Laporan Work Order</h1>
        <div class="report-meta mt-1">
            Total data: <strong>{{ number_format($totalRows) }}</strong>
            @if($reports->total() > 0)
                &middot; Halaman {{ $reports->currentPage() }} dari {{ $reports->lastPage() }}
            @endif
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.export.excel', request()->query()) }}" class="btn btn-success btn-sm px-3">
            Export Excel
        </a>
        <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn btn-danger btn-sm px-3">
            Export PDF
        </a>
    </div>
</div>

<div class="card panel-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover report-table mb-0">
                <thead>
                    <tr>
                        <th>WO ID</th>
                        <th>SC ID</th>
                        <th>Tanggal</th>
                        <th>STO</th>
                        <th>Teknisi</th>
                        <th>Status</th>
                        <th>Kendala</th>
                        <th class="text-end">Durasi (Jam)</th>
                        <th class="text-center">SLA</th>
                        <th class="text-center">Workfail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        @php
                            $tanggal = optional($report->waktu)->tanggal ?? $report->tanggal_order;
                            $teknisi = optional($report->teknisi)->nama_teknisi
                                ?: optional($report->teknisi)->nik_teknisi;
                            $status  = $report->status_wo ?: optional($report->status)->status_wo;
                            $kendala = optional($report->kendala)->kendala_pt1;
                            $durasiJam = $report->durasi_pengerjaan_menit
                                ? round($report->durasi_pengerjaan_menit / 60, 2)
                                : ($report->durasi_hari ? round($report->durasi_hari * 24, 2) : null);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $report->wo_sc_id ?: $report->wo_id }}</td>
                            <td>{{ $report->sc_id ?: '—' }}</td>
                            <td>{{ $tanggal ?: '—' }}</td>
                            <td>{{ optional($report->sto)->nama_sto ?: '—' }}</td>
                            <td>{{ $teknisi ?: '—' }}</td>
                            <td>{{ $status ?: '—' }}</td>
                            <td>{{ $kendala ? Str::limit($kendala, 40) : '—' }}</td>
                            <td class="text-end">{{ $durasiJam !== null ? number_format($durasiJam, 2) : '—' }}</td>
                            <td class="text-center">
                                @if($report->is_sla_tercapai)
                                    <span class="badge rounded-pill badge-sla-yes">Ya</span>
                                @else
                                    <span class="badge rounded-pill badge-sla-no">Tidak</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($report->is_workfail)
                                    <span class="badge rounded-pill badge-wf-yes">Ya</span>
                                @else
                                    <span class="text-empty">Tidak</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                Belum ada data laporan. Silakan import work order terlebih dahulu.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($reports->total() > 0)
            <div class="report-pagination px-3 pb-3">
                <div class="report-meta">
                    Menampilkan {{ $reports->firstItem() }}–{{ $reports->lastItem() }}
                    dari {{ number_format($reports->total()) }} baris
                </div>
                @if($reports->hasPages())
                    {{ $reports->withQueryString()->links('pagination::bootstrap-5') }}
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
