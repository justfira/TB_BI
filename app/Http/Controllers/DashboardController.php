<?php

namespace App\Http\Controllers;

use App\Models\DimSto;
use App\Models\DimStatus;
use App\Models\DimTeknisi;
use App\Models\DimPelanggan;
use App\Models\DimInfrastruktur;
use App\Models\DimWaktu;
use App\Models\EtlLog;
use App\Models\FactWorkorder;
use App\Models\StagingWorkorder;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $filtered = $this->applyFilters(FactWorkorder::query(), $request);

        // ── Kartu metrik ──────────────────────────────────────────────────────
        $totalWorkorder = (clone $filtered)->count();

        // is_workfail  → kolom tinyint di fact_workorder (bukan workfail_flag)
        $totalWorkfail  = (clone $filtered)->where('is_workfail', 1)->count();

        // status_group ada di dim_status (diisi saat ETL)
        $totalSelesai   = (clone $filtered)
            ->whereHas('status', fn ($q) => $q->where('status_group', DimStatus::GROUP_SELESAI))
            ->count();

        $totalPending   = (clone $filtered)
            ->whereHas('status', fn ($q) => $q->where('status_group', DimStatus::GROUP_PENDING))
            ->count();

        // Rata-rata durasi dalam JAM (durasi_pengerjaan_menit / 60)
        $avgMenit        = (clone $filtered)->avg('durasi_pengerjaan_menit') ?? 0;
        $averageResolution = round($avgMenit / 60, 2);

        // SLA: persentase baris yang is_sla_tercapai = 1
        $slaCount        = (clone $filtered)->where('is_sla_tercapai', 1)->count();
        $slaAchievement  = $totalWorkorder > 0
            ? round(($slaCount / $totalWorkorder) * 100, 2)
            : 0;

        // ── #2: Distribusi Status WO (dengan persentase) ────────────────────────
        $statusDistribution = (clone $filtered)
            ->selectRaw('status_id, status_wo, count(*) as total')
            ->groupBy('status_id', 'status_wo')
            ->with('status')
            ->get()
            ->map(function ($item) use ($totalWorkorder) {
                return [
                    'status'  => optional($item->status)->status_wo ?? $item->status_wo ?? 'Unknown',
                    'total'   => $item->total,
                    'percent' => $totalWorkorder > 0
                        ? round(($item->total / $totalWorkorder) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('total')
            ->values();

        $topSto = (clone $filtered)
            ->selectRaw('sto_id, count(*) as total')
            ->groupBy('sto_id')
            ->with('sto')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'sto'   => optional($item->sto)->nama_sto ?? '-',
                'total' => $item->total,
            ]);

        $topTeknisi = (clone $filtered)
            ->selectRaw('teknisi_id, count(*) as total')
            ->groupBy('teknisi_id')
            ->with('teknisi')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'teknisi' => optional($item->teknisi)->nama_teknisi ?? '-',
                'total'   => $item->total,
            ]);

        $topKendala = (clone $filtered)
            ->selectRaw('kendala_id, count(*) as total')
            ->groupBy('kendala_id')
            ->with('kendala')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'kendala' => optional($item->kendala)->kendala_pt1 ?? '-',
                'total'   => $item->total,
            ]);

        // ── #4: Tren Kendala Bulanan ─────────────────────────────────────────────
        $chartTrend = (clone $filtered)
            ->join('dim_waktu', 'dim_waktu.date_id', '=', 'fact_workorder.date_id')
            ->selectRaw('dim_waktu.tahun, dim_waktu.bulan, dim_waktu.nama_bulan, count(*) as total')
            ->groupBy('dim_waktu.tahun', 'dim_waktu.bulan', 'dim_waktu.nama_bulan')
            ->orderBy('dim_waktu.tahun')
            ->orderBy('dim_waktu.bulan')
            ->get()
            ->map(fn ($item) => [
                'label' => $item->nama_bulan . ' ' . $item->tahun,
                'total' => $item->total,
            ]);

        $trendAverage = $chartTrend->count() > 0
            ? round($chartTrend->avg('total'), 1)
            : 0;

        // ── #19: Monitoring Harian ───────────────────────────────────────────────
        if ($request->filled('bulan') && $request->filled('tahun')) {
            $dailyBulan = (int) $request->bulan;
            $dailyTahun = (int) $request->tahun;
        } else {
            $latestDate = (clone $filtered)
                ->join('dim_waktu', 'dim_waktu.date_id', '=', 'fact_workorder.date_id')
                ->max('dim_waktu.tanggal');

            $dailyBulan = $latestDate ? Carbon::parse($latestDate)->month : now()->month;
            $dailyTahun = $latestDate ? Carbon::parse($latestDate)->year : now()->year;
        }

        $chartDaily = (clone $filtered)
            ->join('dim_waktu', 'dim_waktu.date_id', '=', 'fact_workorder.date_id')
            ->where('dim_waktu.tahun', $dailyTahun)
            ->where('dim_waktu.bulan', $dailyBulan)
            ->selectRaw('dim_waktu.tanggal, count(*) as total')
            ->groupBy('dim_waktu.tanggal')
            ->orderBy('dim_waktu.tanggal')
            ->get()
            ->map(fn ($item) => [
                'label' => Carbon::parse($item->tanggal)->format('d'),
                'total' => $item->total,
            ]);

        $dailyPeriodLabel = Carbon::createFromDate($dailyTahun, $dailyBulan, 1)
            ->locale('id')->isoFormat('MMMM YYYY');

        // ── KPI tambahan: completion rate, STO aktif, WO/SC ID, alert banner ────
        $completionRate = $totalWorkorder > 0
            ? round(($totalSelesai / $totalWorkorder) * 100, 1)
            : 0;

        $stoAktifIds = (clone $filtered)
            ->select('sto_id')
            ->distinct()
            ->pluck('sto_id');

        $totalStoAktif = $stoAktifIds->count();

        $stoAktifDetail   = DimSto::whereIn('sto_id', $stoAktifIds)->get();
        $totalBranchAktif = $stoAktifDetail->pluck('branch')->filter()->unique()->count();
        $totalHsaAktif    = $stoAktifDetail->pluck('hsa')->filter()->unique()->count();

        $totalDenganId = (clone $filtered)
            ->whereNotNull('wo_sc_id')
            ->where('wo_sc_id', '!=', '')
            ->count();

        $totalTanpaId = $totalWorkorder - $totalDenganId;

        $peakBulan = $chartTrend->sortByDesc('total')->first();

        // ── #9: Solusi Stacked per STO ──────────────────────────────────────────
        $solusiStoData = (clone $filtered)
            ->selectRaw('sto_id, count(*) as total,
                SUM(CASE WHEN is_sla_tercapai = 1 THEN 1 ELSE 0 END) as selesai,
                SUM(CASE WHEN is_sla_tercapai = 0 THEN 1 ELSE 0 END) as proses')
            ->groupBy('sto_id')
            ->orderByDesc('total')
            ->limit(6)
            ->with('sto')
            ->get()
            ->map(fn ($item) => [
                'sto' => optional($item->sto)->nama_sto ?? '-',
                'selesai' => (int) $item->selesai,
                'proses' => (int) $item->proses,
            ]);

        // ── #10: Analisis Branch & Sektor ────────────────────────────────────────
        $sektorDistribution = (clone $filtered)
            ->join('dim_sto', 'dim_sto.sto_id', '=', 'fact_workorder.sto_id')
            ->selectRaw('dim_sto.sektor, count(*) as total')
            ->whereNotNull('dim_sto.sektor')
            ->where('dim_sto.sektor', '!=', '')
            ->groupBy('dim_sto.sektor')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'sektor' => $item->sektor,
                'total' => $item->total,
            ]);

        // ── #18: Kendala per Korlap ──────────────────────────────────────────────
        $topKorlap = (clone $filtered)
            ->join('dim_teknisi', 'dim_teknisi.teknisi_id', '=', 'fact_workorder.teknisi_id')
            ->selectRaw('dim_teknisi.korlap, count(*) as total')
            ->whereNotNull('dim_teknisi.korlap')
            ->where('dim_teknisi.korlap', '!=', '')
            ->where('dim_teknisi.korlap', '!=', '-')
            ->groupBy('dim_teknisi.korlap')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn ($item) => [
                'korlap' => $item->korlap,
                'total' => $item->total,
            ]);

        // ── #5: Segment Layanan ──────────────────────────────────────────────────
        $segmentDistribution = (clone $filtered)
            ->join('dim_pelanggan', 'dim_pelanggan.pelanggan_id', '=', 'fact_workorder.pelanggan_id')
            ->selectRaw('dim_pelanggan.segment, count(*) as total')
            ->whereNotNull('dim_pelanggan.segment')
            ->where('dim_pelanggan.segment', '!=', '')
            ->groupBy('dim_pelanggan.segment')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'segment' => strtoupper($item->segment),
                'total' => $item->total,
            ]);

        $indihomeTotal = $segmentDistribution->firstWhere('segment', 'INDIHOME')['total'] ?? 0;
        $indihomePct = $totalWorkorder > 0 ? round(($indihomeTotal / $totalWorkorder) * 100, 1) : 0;

        // ── #16: Kendala per Layanan ─────────────────────────────────────────────
        $layananDistribution = (clone $filtered)
            ->join('dim_pelanggan', 'dim_pelanggan.pelanggan_id', '=', 'fact_workorder.pelanggan_id')
            ->selectRaw('dim_pelanggan.layanan, count(*) as total')
            ->whereNotNull('dim_pelanggan.layanan')
            ->where('dim_pelanggan.layanan', '!=', '')
            ->groupBy('dim_pelanggan.layanan')
            ->orderByDesc('total')
            ->get();

        $topLayanan = $layananDistribution->take(3);
        $lainnyaTotal = $layananDistribution->slice(3)->sum('total');

        $layananChartData = $topLayanan->map(fn($item) => [
            'layanan' => $item->layanan,
            'total' => $item->total,
        ])->concat($lainnyaTotal > 0 ? [[
            'layanan' => 'Lainnya',
            'total' => $lainnyaTotal,
        ]] : []);

        // ── #8: Performa Mitra ───────────────────────────────────────────────────
        $mitraDistribution = (clone $filtered)
            ->join('dim_teknisi', 'dim_teknisi.teknisi_id', '=', 'fact_workorder.teknisi_id')
            ->selectRaw('dim_teknisi.nama_mitra, count(*) as total')
            ->whereNotNull('dim_teknisi.nama_mitra')
            ->where('dim_teknisi.nama_mitra', '!=', '')
            ->where('dim_teknisi.nama_mitra', '!=', '-')
            ->groupBy('dim_teknisi.nama_mitra')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($totalWorkorder) {
                return [
                    'nama_mitra' => $item->nama_mitra,
                    'total' => $item->total,
                    'percent' => $totalWorkorder > 0 ? round(($item->total / $totalWorkorder) * 100, 1) : 0,
                ];
            });

        // ── #7: Analisis Durasi Penyelesaian (Histogram & Pills) ─────────────────
        $durasiStats = (clone $filtered)
            ->selectRaw("
                SUM(CASE WHEN durasi_pengerjaan_menit <= 60 THEN 1 ELSE 0 END) as h1,
                SUM(CASE WHEN durasi_pengerjaan_menit > 60 AND durasi_pengerjaan_menit <= 240 THEN 1 ELSE 0 END) as h4,
                SUM(CASE WHEN durasi_pengerjaan_menit > 240 AND durasi_pengerjaan_menit <= 420 THEN 1 ELSE 0 END) as h7,
                SUM(CASE WHEN durasi_pengerjaan_menit > 420 AND (durasi_hari <= 1 OR durasi_pengerjaan_menit <= 1440) THEN 1 ELSE 0 END) as d1,
                SUM(CASE WHEN durasi_hari > 1 AND durasi_hari <= 5 THEN 1 ELSE 0 END) as d5,
                SUM(CASE WHEN durasi_hari > 5 AND durasi_hari <= 14 THEN 1 ELSE 0 END) as d14,
                SUM(CASE WHEN durasi_hari > 14 THEN 1 ELSE 0 END) as d_max
            ")
            ->first();

        $histogramData = [
            (int) ($durasiStats->h1 ?? 0),
            (int) ($durasiStats->h4 ?? 0),
            (int) ($durasiStats->h7 ?? 0),
            (int) ($durasiStats->d1 ?? 0),
            (int) ($durasiStats->d5 ?? 0),
            (int) ($durasiStats->d14 ?? 0),
            (int) ($durasiStats->d_max ?? 0),
        ];

        $durasiPills = (clone $filtered)
            ->selectRaw("
                SUM(CASE WHEN durasi_hari <= 1 THEN 1 ELSE 0 END) as under_1,
                SUM(CASE WHEN durasi_hari > 5 AND durasi_hari <= 14 THEN 1 ELSE 0 END) as between_5_14,
                SUM(CASE WHEN durasi_hari > 8 THEN 1 ELSE 0 END) as over_8
            ")
            ->first();

        $pctUnder1Day = $totalWorkorder > 0 ? round(($durasiPills->under_1 / $totalWorkorder) * 100, 1) : 0;
        $pct5to14Days = $totalWorkorder > 0 ? round(($durasiPills->between_5_14 / $totalWorkorder) * 100, 1) : 0;
        $pctOver8Days = $totalWorkorder > 0 ? round(($durasiPills->over_8 / $totalWorkorder) * 100, 1) : 0;

        // ── #13: Analisis Infrastruktur Jaringan (Tree Map) ──────────────────────
        $infraStats = (clone $filtered)
            ->join('dim_infrastruktur', 'dim_infrastruktur.wo_id', '=', 'fact_workorder.wo_sc_id')
            ->selectRaw("
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.odp), '') IS NOT NULL THEN 1 ELSE 0 END) as odp,
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.odc), '') IS NOT NULL THEN 1 ELSE 0 END) as odc,
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.gpon), '') IS NOT NULL THEN 1 ELSE 0 END) as gpon,
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.distribusi), '') IS NOT NULL THEN 1 ELSE 0 END) as distribusi,
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.feeder), '') IS NOT NULL THEN 1 ELSE 0 END) as feeder,
                SUM(CASE WHEN NULLIF(TRIM(dim_infrastruktur.hasil_ukur_odp), '') IS NOT NULL THEN 1 ELSE 0 END) as hasil_ukur_odp
            ")
            ->first();

        $infraData = [
            'odp' => (int) ($infraStats->odp ?? 0),
            'odc' => (int) ($infraStats->odc ?? 0),
            'gpon' => (int) ($infraStats->gpon ?? 0),
            'distribusi' => (int) ($infraStats->distribusi ?? 0),
            'feeder' => (int) ($infraStats->feeder ?? 0),
            'hasil_ukur_odp' => (int) ($infraStats->hasil_ukur_odp ?? 0),
        ];

        // ── #15: Analisis Hasil Ukur Jaringan (Scatter Plot) ─────────────────────
        $scatterPoints = (clone $filtered)
            ->join('dim_infrastruktur', 'dim_infrastruktur.wo_id', '=', 'fact_workorder.wo_sc_id')
            ->selectRaw('dim_infrastruktur.hasil_ukur_odp, dim_infrastruktur.hasil_ukur_feeder')
            ->where(function ($query) {
                $query->whereNotNull('dim_infrastruktur.hasil_ukur_odp')
                      ->orWhereNotNull('dim_infrastruktur.hasil_ukur_feeder');
            })
            ->limit(100)
            ->get();

        $scatterOdp = [];
        $scatterFeeder = [];

        foreach ($scatterPoints as $pt) {
            $odpVal = $pt->hasil_ukur_odp;
            $feederVal = $pt->hasil_ukur_feeder;

            if ($odpVal !== null && $odpVal !== '') {
                $cleanOdp = preg_replace('/[^0-9.-]/', '', $odpVal);
                if (is_numeric($cleanOdp)) {
                    $val = (float) $cleanOdp;
                    if ($val < 0 && $val > -100) {
                        $scatterOdp[] = ['x' => $val, 'y' => $val];
                    }
                }
            }

            if ($feederVal !== null && $feederVal !== '') {
                $cleanFeeder = preg_replace('/[^0-9.-]/', '', $feederVal);
                if (is_numeric($cleanFeeder)) {
                    $val = (float) $cleanFeeder;
                    if ($val < 0 && $val > -100) {
                        $scatterFeeder[] = ['x' => $val, 'y' => $val];
                    }
                }
            }
        }

        if (empty($scatterOdp)) {
            $scatterOdp = [['x' => -20.0, 'y' => -20.0], ['x' => -22.5, 'y' => -22.5]];
        }
        if (empty($scatterFeeder)) {
            $scatterFeeder = [['x' => -18.0, 'y' => -18.0]];
        }

        // ── #14: Monitoring Track ID Chart ──────────────────────────────────────
        $trackIdStats = (clone $filtered)
            ->selectRaw("
                SUM(CASE WHEN NULLIF(TRIM(track_id), '') IS NOT NULL AND NULLIF(TRIM(track_id_baru), '') IS NULL THEN 1 ELSE 0 END) as track_lama,
                SUM(CASE WHEN NULLIF(TRIM(track_id_baru), '') IS NOT NULL THEN 1 ELSE 0 END) as track_baru,
                SUM(CASE WHEN NULLIF(TRIM(track_id), '') IS NULL AND NULLIF(TRIM(track_id_baru), '') IS NULL THEN 1 ELSE 0 END) as track_empty
            ")
            ->first();

        $trackIdData = [
            'lama' => (int) ($trackIdStats->track_lama ?? 0),
            'baru' => (int) ($trackIdStats->track_baru ?? 0),
            'empty' => (int) ($trackIdStats->track_empty ?? 0),
        ];

        // ── #11 & #14 & #20: Detail Data Work Order dengan pagination ────────────
        $latestRows = (clone $filtered)
            ->with(['waktu', 'sto', 'teknisi', 'kendala', 'infrastruktur', 'pelanggan'])
            ->latest('wo_id')
            ->paginate(10)
            ->withQueryString();

        // ── Filter options ────────────────────────────────────────────────────
        $stoOptions      = DimSto::orderBy('nama_sto')->get();
        $teknisiOptions  = DimTeknisi::orderBy('nama_teknisi')->get();

        // ── ETL status sidebar ────────────────────────────────────────────────
        $pendingCount   = StagingWorkorder::where('status_etl', 'pending')->count();
        $processedCount = StagingWorkorder::where('status_etl', 'processed')->count();
        $failedCount    = StagingWorkorder::where('status_etl', 'failed')->count();
        $latestEtlLog   = EtlLog::latest('imported_at')->first();

        return view('dashboard.index', compact(
            'totalWorkorder',
            'totalSelesai',
            'totalPending',
            'totalWorkfail',
            'averageResolution',
            'slaAchievement',
            'completionRate',
            'totalStoAktif',
            'totalBranchAktif',
            'totalHsaAktif',
            'totalDenganId',
            'totalTanpaId',
            'peakBulan',
            'statusDistribution',
            'topSto',
            'topTeknisi',
            'topKendala',
            'chartTrend',
            'trendAverage',
            'chartDaily',
            'dailyPeriodLabel',
            'stoOptions',
            'teknisiOptions',
            'pendingCount',
            'processedCount',
            'failedCount',
            'latestEtlLog',
            'solusiStoData',
            'sektorDistribution',
            'topKorlap',
            'segmentDistribution',
            'indihomePct',
            'layananChartData',
            'mitraDistribution',
            'histogramData',
            'pctUnder1Day',
            'pct5to14Days',
            'pctOver8Days',
            'infraData',
            'scatterOdp',
            'scatterFeeder',
            'latestRows',
            'trackIdData'
        ));
    }

    // ─────────────────────────────────────────────────────────────────────────

    protected function applyFilters($query, Request $request)
    {
        if ($request->filled('tahun')) {
            $query->whereHas('waktu', fn ($q) => $q->where('tahun', $request->tahun));
        }

        if ($request->filled('bulan')) {
            $query->whereHas('waktu', fn ($q) => $q->where('bulan', $request->bulan));
        }

        if ($request->filled('sto')) {
            $query->where('sto_id', $request->sto);
        }

        if ($request->filled('teknisi')) {
            $query->where('teknisi_id', $request->teknisi);
        }

        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('status_wo', $request->status));
        }

        if ($request->filled('kendala')) {
            $query->where('kendala_id', $request->kendala);
        }

        return $query;
    }
}
