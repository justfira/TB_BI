<?php

namespace App\Http\Controllers;

use App\Models\DimSto;
use App\Models\DimStatus;
use App\Models\DimTeknisi;
use App\Models\EtlLog;
use App\Models\FactWorkorder;
use App\Models\StagingWorkorder;
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

        // ── Chart & tabel ─────────────────────────────────────────────────────
        $statusDistribution = (clone $filtered)
            ->selectRaw('status_id, status_wo, count(*) as total')
            ->groupBy('status_id', 'status_wo')
            ->with('status')
            ->get()
            ->map(fn ($item) => [
                'status' => optional($item->status)->status_wo ?? $item->status_wo ?? 'Unknown',
                'total'  => $item->total,
            ]);

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

        // Tren bulanan — join ke dim_waktu
        $chartTrend = (clone $filtered)
            ->selectRaw('date_id, count(*) as total')
            ->groupBy('date_id')
            ->with('waktu')
            ->orderBy('date_id')
            ->get()
            ->map(fn ($item) => [
                'label' => optional($item->waktu)->nama_bulan . ' ' . optional($item->waktu)->tahun,
                'total' => $item->total,
            ]);

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
            'statusDistribution',
            'topSto',
            'topTeknisi',
            'topKendala',
            'chartTrend',
            'stoOptions',
            'teknisiOptions',
            'pendingCount',
            'processedCount',
            'failedCount',
            'latestEtlLog',
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
