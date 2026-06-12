@extends('layouts.app')

@section('title', 'Reporting Work Order')

@section('content')
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Laporan Work Order</h1>
                <div>
                    <a href="{{ route('reports.export.excel', request()->query()) }}" class="btn btn-success">Export Excel</a>
                    <a href="{{ route('reports.export.pdf', request()->query()) }}" class="btn btn-danger">Export PDF</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>WO ID</th>
                            <th>SC ID</th>
                            <th>Tanggal</th>
                            <th>STO</th>
                            <th>Teknisi</th>
                            <th>Status</th>
                            <th>Kendala</th>
                            <th>Durasi Jam</th>
                            <th>SLA %</th>
                            <th>Workfail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                            <tr>
                                <td>{{ $report->wo_id }}</td>
                                <td>{{ $report->sc_id }}</td>
                                <td>{{ optional($report->waktu)->tanggal }}</td>
                                <td>{{ optional($report->sto)->nama_sto }}</td>
                                <td>{{ optional($report->teknisi)->nama_teknisi }}</td>
                                <td>{{ optional($report->status)->status_name }}</td>
                                <td>{{ optional($report->kendala)->nama_kendala }}</td>
                                <td>{{ $report->durasi_jam }}</td>
                                <td>{{ $report->sla_achievement }}</td>
                                <td>{{ $report->workfail_flag ? 'Ya' : 'Tidak' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reports->withQueryString()->links() }}</div>
        </div>
    </div>
@endsection
