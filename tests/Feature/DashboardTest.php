<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
        $response->assertViewHasAll([
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
        ]);
    }
}
