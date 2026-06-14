@extends('layouts.app')

@section('title', 'Analisis dan Rekomendasi')

@push('styles')
<style>
    .analysis-hero {
        background: linear-gradient(135deg, #ffffff 0%, #fff1f3 55%, #fff7f8 100%);
        border: 1px solid rgba(22, 43, 77, 0.08);
        border-radius: 18px;
        padding: 24px;
        box-shadow: 0 16px 28px rgba(4, 45, 91, 0.06);
    }
    .analysis-kicker {
        color: #d94f63;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .insight-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }
    .insight-card {
        overflow: hidden;
        border: 1px solid rgba(22, 43, 77, 0.08);
        border-radius: 16px;
        background: #ffffff;
        box-shadow: 0 16px 28px rgba(4, 45, 91, 0.06);
    }
    .insight-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        min-height: 76px;
        padding: 16px;
        background: #f8fbff;
        border-bottom: 1px solid rgba(22, 43, 77, 0.08);
    }
    .insight-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: #ffe1e6;
        color: #d94f63;
        font-size: 0.8rem;
        font-weight: 800;
    }
    .insight-title {
        margin: 0;
        color: #162b4d;
        font-size: 0.98rem;
        font-weight: 800;
        line-height: 1.35;
    }
    .priority-pill {
        border-radius: 999px;
        padding: 0.25rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 800;
        white-space: nowrap;
    }
    .priority-high {
        background: #fde8ee;
        color: #c92a3b;
    }
    .priority-medium {
        background: #fff4e0;
        color: #c66a00;
    }
    .insight-block {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(22, 43, 77, 0.07);
    }
    .insight-block:last-child {
        border-bottom: 0;
    }
    .insight-block.finding {
        background: #fff3f3;
    }
    .insight-block.reason {
        background: #fff8eb;
    }
    .insight-block.action {
        background: #fff1f3;
    }
    .insight-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 6px;
        font-size: 0.76rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }
    .insight-dot {
        width: 7px;
        height: 7px;
        border-radius: 999px;
    }
    .finding .insight-label {
        color: #d73535;
    }
    .reason .insight-label {
        color: #f07b13;
    }
    .action .insight-label {
        color: #b83246;
    }
    .finding .insight-dot {
        background: #d73535;
    }
    .reason .insight-dot {
        background: #f07b13;
    }
    .action .insight-dot {
        background: #b83246;
    }
    .insight-text {
        margin: 0;
        color: #46536a;
        font-size: 0.84rem;
        line-height: 1.48;
    }
    .summary-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 16px 28px rgba(4, 45, 91, 0.06);
        overflow: hidden;
    }
    .summary-card .table thead th {
        background: #8f2435;
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.16);
    }
    @media (max-width: 1399px) {
        .insight-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 767px) {
        .insight-grid {
            grid-template-columns: 1fr;
        }
        .analysis-hero {
            padding: 18px;
        }
    }
</style>
@endpush

@section('content')
@php
    $insights = [
        [
            'no' => '01',
            'title' => 'WO Pending Masih Tinggi - 337 Belum Selesai',
            'priority' => 'Tinggi',
            'owner' => 'Unit Confirmation',
            'finding' => 'Sebanyak 337 work order atau 3,5% dari total 9.686 WO masih berstatus proses atau pending. Analisis histogram durasi menunjukkan 12% WO melebihi batas SLA 30 hari tanpa eskalasi yang tercatat di sistem.',
            'reason' => 'Tidak ada mekanisme eskalasi otomatis pada sistem. Work order yang mendekati atau melewati batas waktu tidak diprioritaskan ulang, dan distribusi penugasan ke teknisi masih dilakukan manual oleh unit confirmation.',
            'action' => 'Terapkan notifikasi otomatis dashboard untuk WO yang belum selesai lebih dari 3 hari. Buat SOP eskalasi berjenjang: WO lebih dari 7 hari wajib dilaporkan ke supervisor, WO lebih dari 14 hari wajib naik ke manajer wilayah.',
        ],
        [
            'no' => '02',
            'title' => 'Puncak Kendala Januari 2026 - 2.479 WO dalam Satu Bulan',
            'priority' => 'Tinggi',
            'owner' => 'Manajemen / Pimpinan',
            'finding' => 'Terjadi lonjakan drastis volume work order pada periode Desember 2025 sampai Januari 2026. Puncak tertinggi terjadi pada Januari 2026 dengan 2.479 WO, jauh melampaui rata-rata bulanan normal.',
            'reason' => 'Pola musiman akhir atau awal tahun memicu lonjakan gangguan jaringan secara bersamaan di banyak titik. Tidak ada rencana kapasitas teknisi yang disiapkan untuk menghadapi periode puncak ini.',
            'action' => 'Siapkan rencana kapasitas teknisi cadangan minimal 2 bulan sebelum periode puncak. Aktifkan sistem piket khusus dan rekrut teknisi mitra tambahan pada bulan Desember-Januari setiap tahun.',
        ],
        [
            'no' => '03',
            'title' => 'Kendala Tekanan Tinggi Mendominasi - Lebih dari 2.500 Kejadian',
            'priority' => 'Tinggi',
            'owner' => 'Tim Teknik Lapangan',
            'finding' => 'Jenis kendala Tekanan Tinggi menjadi yang paling sering terjadi dengan lebih dari 2.500 kejadian, diikuti PT2 ODP Full NOK dan PT3 Takten BI. Ketiga jenis ini konsisten muncul di berbagai STO aktif.',
            'reason' => 'Infrastruktur jaringan optik dengan kepadatan tinggi tidak mendapatkan maintenance preventif secara terjadwal. Akibatnya, kendala tekanan pada jaringan terus berulang di titik yang sama.',
            'action' => 'Lakukan audit infrastruktur berkala pada ODP dengan frekuensi kendala tertinggi. Buat program maintenance preventif triwulanan dan prioritaskan pengadaan stok splitter serta komponen ODP.',
        ],
        [
            'no' => '04',
            'title' => '12% WO Melebihi SLA 30 Hari',
            'priority' => 'Tinggi',
            'owner' => 'Unit Confirmation + Supervisor',
            'finding' => 'Analisis distribusi durasi menunjukkan 68% WO selesai kurang dari 1 hari, namun 12% melewati batas SLA 30 hari. Ketimpangan ini menunjukkan adanya hambatan sistem pada sebagian kecil kasus.',
            'reason' => 'Tidak ada sistem peringatan dini untuk WO yang mendekati batas SLA. Prosedur eskalasi belum terdokumentasi dalam SOP tertulis, dan unit confirmation tidak memiliki tampilan khusus untuk memantau durasi WO secara real-time.',
            'action' => 'Aktifkan peringatan otomatis H-7 sebelum batas SLA tercapai untuk setiap WO. Buat laporan mingguan khusus WO berisiko over-SLA untuk ditinjau supervisor setiap Senin.',
        ],
        [
            'no' => '05',
            'title' => '218 Record Tanpa WO/SC ID - Kualitas Data Rendah',
            'priority' => 'Sedang',
            'owner' => 'Tim Operasional + IT',
            'finding' => 'Terdapat 218 record tanpa WO/SC ID, TRACK ID BARU 100% NULL, dan Hasil Ukur Feeder seluruhnya NULL pada dataset aktual. Kondisi ini membuat analisis SLA dan monitoring kendala tidak akurat.',
            'reason' => 'Tidak ada validasi wajib pada sistem input. Teknisi lapangan mengisi data secara manual tanpa panduan format yang jelas, sehingga kolom kritis sering kosong saat menutup work order.',
            'action' => 'Terapkan validasi wajib di sistem input: WO/SC ID, NIK teknisi, dan hasil ukur tidak boleh kosong saat WO ditutup. Buat SOP pengelolaan data tertulis dan pelatihan input data.',
        ],
        [
            'no' => '06',
            'title' => 'Beban Mitra Tidak Merata - Pekanbaru vs Dumai',
            'priority' => 'Sedang',
            'owner' => 'Manajemen Mitra',
            'finding' => 'Mitra Pekanbaru menangani volume WO jauh lebih besar dibandingkan mitra Dumai. Completion rate Pekanbaru tercatat 55,7% sedangkan Dumai 44,1%, keduanya masih di bawah target ideal.',
            'reason' => 'Distribusi WO ke mitra belum berbasis data kapasitas real-time. Tidak ada sistem monitoring beban kerja mitra yang dapat memicu redistribusi otomatis ketika salah satu mitra mengalami overload.',
            'action' => 'Terapkan distribusi WO berbasis kapasitas. Batasi jumlah WO aktif per mitra sesuai jumlah teknisi tersedia, lalu evaluasi performa mitra secara bulanan menggunakan dashboard.',
        ],
    ];
@endphp

<div class="analysis-hero mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <div class="analysis-kicker mb-2">Customer Intelligence Dashboard</div>
            <h1 class="page-title mb-2">Analisis dan Rekomendasi</h1>
            <p class="text-muted mb-0">Kartu insight untuk membantu prioritas tindakan operasional berdasarkan pola work order, SLA, kualitas data, dan beban mitra.</p>
        </div>
        <div class="text-muted text-end small">
            <div>{{ now()->format('d F Y') }}</div>
            <div>Insight Tindakan</div>
        </div>
    </div>
</div>

<div class="insight-grid mb-4">
    @foreach($insights as $insight)
        <article class="insight-card">
            <div class="insight-card-header">
                <div class="d-flex align-items-start gap-3">
                    <span class="insight-number">{{ $insight['no'] }}</span>
                    <h2 class="insight-title">{{ $insight['title'] }}</h2>
                </div>
                <span class="priority-pill {{ $insight['priority'] === 'Tinggi' ? 'priority-high' : 'priority-medium' }}">
                    {{ $insight['priority'] }}
                </span>
            </div>

            <div class="insight-block finding">
                <div class="insight-label"><span class="insight-dot"></span>Temuan Data</div>
                <p class="insight-text">{{ $insight['finding'] }}</p>
            </div>

            <div class="insight-block reason">
                <div class="insight-label"><span class="insight-dot"></span>Mengapa Ini Terjadi</div>
                <p class="insight-text">{{ $insight['reason'] }}</p>
            </div>

            <div class="insight-block action">
                <div class="insight-label"><span class="insight-dot"></span>Rekomendasi Tindakan</div>
                <p class="insight-text">{{ $insight['action'] }}</p>
            </div>
        </article>
    @endforeach
</div>

<div class="card summary-card">
    <div class="card-header bg-white fw-bold text-dark">Ringkasan Prioritas Tindakan</div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 70px;">No</th>
                    <th>Masalah Utama</th>
                    <th style="width: 120px;">Prioritas</th>
                    <th style="width: 260px;">Penanggung Jawab</th>
                </tr>
            </thead>
            <tbody>
                @foreach($insights as $insight)
                    <tr>
                        <td>{{ $insight['no'] }}</td>
                        <td>{{ $insight['title'] }}</td>
                        <td>
                            <span class="{{ $insight['priority'] === 'Tinggi' ? 'text-danger' : 'text-warning' }} fw-bold">
                                {{ $insight['priority'] }}
                            </span>
                        </td>
                        <td>{{ $insight['owner'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
