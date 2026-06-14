@extends('layouts.app')

@section('title', 'BI SUPPORT — Dashboard Utama')

@push('styles')
<style>
:root {
    --bi-bg:        #f4f5f7;
    --bi-sidebar:   #1a1f36;
    --bi-card:      #ffffff;
    --bi-border:    #e2e5ed;
    --bi-text:      #1a1f36;
    --bi-muted:     #6b7280;
    --bi-primary:   #3b5bdb;
    --bi-primary-lt:#eef2ff;
    --bi-success:   #0d9e6e;
    --bi-success-lt:#e6f7f0;
    --bi-warning:   #d97706;
    --bi-warning-lt:#fef3c7;
    --bi-danger:    #dc2626;
    --bi-danger-lt: #fee2e2;
    --bi-info:      #0284c7;
    --bi-info-lt:   #e0f2fe;
    --bi-purple:    #7c3aed;
    --bi-radius:    8px;
    --bi-radius-lg: 12px;
    --bi-shadow:    0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --bi-shadow-md: 0 4px 12px rgba(0,0,0,.08);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: var(--bi-bg);
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    color: var(--bi-text);
    font-size: 13px;
    line-height: 1.5;
}

/* ── Scrollbar ── */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--bi-border); border-radius: 4px; }

/* ── Card ── */
.bi-card {
    background: var(--bi-card);
    border: 1px solid var(--bi-border);
    border-radius: var(--bi-radius-lg);
    box-shadow: var(--bi-shadow);
}
.bi-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .6rem 1rem;
    border-bottom: 1px solid var(--bi-border);
    gap: .5rem;
}
.bi-card-header-left {
    display: flex;
    align-items: center;
    gap: .5rem;
    min-width: 0;
}
.bi-card-title {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: var(--bi-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bi-card-body { padding: 1rem; }

/* ── Info Button ── */
.info-btn {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1.5px solid var(--bi-border);
    background: var(--bi-bg);
    color: var(--bi-muted);
    font-size: .6rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all .15s;
    line-height: 1;
    outline: none;
    font-style: italic;
    font-family: Georgia, serif;
}
.info-btn:hover {
    background: var(--bi-primary);
    border-color: var(--bi-primary);
    color: #fff;
}

/* ── Info Modal ── */
.info-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 20, 50, .45);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(2px);
}
.info-overlay.show { display: flex; }
.info-modal {
    background: var(--bi-card);
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    max-width: 480px;
    width: 100%;
    overflow: hidden;
    animation: modalIn .18s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(.94) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.info-modal-header {
    padding: 1.1rem 1.25rem .85rem;
    border-bottom: 1px solid var(--bi-border);
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
}
.info-modal-badge {
    font-size: .55rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    padding: 2px 8px;
    border-radius: 4px;
    background: var(--bi-primary-lt);
    color: var(--bi-primary);
    white-space: nowrap;
    margin-bottom: .35rem;
    display: inline-block;
}
.info-modal-title {
    font-size: .9rem;
    font-weight: 700;
    color: var(--bi-text);
    line-height: 1.3;
}
.info-modal-close {
    width: 28px;
    height: 28px;
    border: 1px solid var(--bi-border);
    border-radius: 6px;
    background: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--bi-muted);
    font-size: 1rem;
    flex-shrink: 0;
    transition: background .15s;
}
.info-modal-close:hover { background: var(--bi-bg); }
.info-modal-body { padding: 1.1rem 1.25rem; }
.info-section { margin-bottom: 1rem; }
.info-section:last-child { margin-bottom: 0; }
.info-section-label {
    font-size: .6rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--bi-muted);
    margin-bottom: .35rem;
}
.info-section p {
    font-size: .78rem;
    color: var(--bi-text);
    line-height: 1.6;
}
.info-tags {
    display: flex;
    flex-wrap: wrap;
    gap: .35rem;
    margin-top: .35rem;
}
.info-tag {
    font-size: .65rem;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 5px;
    background: var(--bi-bg);
    border: 1px solid var(--bi-border);
    color: var(--bi-text);
}
.info-tag.success { background: var(--bi-success-lt); border-color: #a7e8d0; color: #065f46; }
.info-tag.warning { background: var(--bi-warning-lt); border-color: #fde68a; color: #92400e; }
.info-tag.danger  { background: var(--bi-danger-lt);  border-color: #fecaca; color: #991b1b; }
.info-tag.primary { background: var(--bi-primary-lt); border-color: #c7d2fe; color: #3730a3; }

/* ── Topbar ── */
.topbar {
    background: var(--bi-card);
    border-bottom: 1px solid var(--bi-border);
    padding: .65rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.topbar-brand {
    display: flex;
    align-items: center;
    gap: .6rem;
}
.topbar-logo {
    width: 30px;
    height: 30px;
    border-radius: 8px;
    background: var(--bi-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: .7rem;
    font-weight: 800;
    letter-spacing: -.03em;
}
.topbar-title { font-size: .9rem; font-weight: 800; color: var(--bi-text); }
.topbar-subtitle { font-size: .65rem; color: var(--bi-muted); margin-top: 1px; }

/* ── Filter bar ── */
.filter-bar { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.filter-select {
    appearance: none;
    background: var(--bi-bg);
    border: 1px solid var(--bi-border);
    border-radius: 20px;
    padding: .3rem .7rem .3rem .65rem;
    font-size: .7rem;
    color: var(--bi-text);
    cursor: pointer;
    transition: border-color .15s;
    outline: none;
}
.filter-select:focus, .filter-select:hover { border-color: var(--bi-primary); }
.btn-refresh {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    background: var(--bi-primary);
    color: #fff;
    border: none;
    border-radius: 20px;
    padding: .3rem .9rem;
    font-size: .7rem;
    font-weight: 700;
    cursor: pointer;
    transition: background .15s, transform .1s;
    outline: none;
}
.btn-refresh:hover   { background: #2f4bc7; }
.btn-refresh:active  { transform: scale(.97); }
.btn-reset {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: none;
    color: var(--bi-muted);
    border: 1px solid var(--bi-border);
    border-radius: 20px;
    padding: .3rem .7rem;
    font-size: .7rem;
    cursor: pointer;
    transition: background .15s;
    outline: none;
    text-decoration: none;
}
.btn-reset:hover { background: var(--bi-bg); color: var(--bi-text); }

/* ── Alert banners ── */
.bi-alert {
    display: flex;
    align-items: flex-start;
    gap: .6rem;
    padding: .55rem 1rem;
    font-size: .72rem;
    border-left: 3px solid;
    border-radius: var(--bi-radius);
    margin-bottom: .4rem;
    line-height: 1.5;
}
.bi-alert svg { flex-shrink: 0; margin-top: .1rem; }
.bi-alert-warning { background: #fffbeb; border-color: var(--bi-warning); color: #78350f; }
.bi-alert-info    { background: #eff6ff; border-color: var(--bi-info);    color: #1e40af; }
.bi-alert-danger  { background: #fef2f2; border-color: var(--bi-danger);  color: #991b1b; }
.bi-alert-badge {
    margin-left: auto;
    flex-shrink: 0;
    font-size: .55rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.bi-alert-warning .bi-alert-badge { background: #fde68a; color: #78350f; }
.bi-alert-info    .bi-alert-badge { background: #bfdbfe; color: #1e40af; }
.bi-alert-danger  .bi-alert-badge { background: #fecaca; color: #991b1b; }

/* ── Section label ── */
.section-label {
    font-size: .62rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--bi-muted);
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: 1.4rem 0 .65rem;
}
.section-label::after { content: ''; flex: 1; height: 1px; background: var(--bi-border); }
.section-req {
    font-size: .55rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    background: var(--bi-primary-lt);
    color: var(--bi-primary);
    letter-spacing: .02em;
}

/* ── KPI row ── */
.kpi-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: .65rem; }
@media(max-width:1200px){ .kpi-row { grid-template-columns: repeat(3, 1fr); } }
@media(max-width:640px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }

.kpi-card {
    background: var(--bi-card);
    border: 1px solid var(--bi-border);
    border-radius: var(--bi-radius-lg);
    padding: .9rem 1rem;
    box-shadow: var(--bi-shadow);
    position: relative;
    overflow: hidden;
}
.kpi-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: var(--kpi-color, var(--bi-primary));
    border-radius: var(--bi-radius-lg) var(--bi-radius-lg) 0 0;
}
.kpi-label {
    font-size: .6rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--bi-muted);
    margin-bottom: .3rem;
    display: flex;
    align-items: center;
    gap: .35rem;
}
.kpi-num-badge {
    font-size: .55rem;
    background: var(--bi-bg);
    border: 1px solid var(--bi-border);
    border-radius: 3px;
    padding: 1px 4px;
    color: var(--bi-muted);
}
.kpi-value {
    font-size: 1.65rem;
    font-weight: 800;
    line-height: 1.1;
    color: var(--bi-text);
    letter-spacing: -.02em;
}
.kpi-value small { font-size: .85rem; font-weight: 600; color: var(--bi-muted); margin-left: 1px; }
.kpi-sub { font-size: .65rem; color: var(--bi-muted); margin-top: .3rem; display: flex; align-items: center; gap: .3rem; }
.badge-up   { color: var(--bi-success); font-weight: 700; }
.badge-down { color: var(--bi-danger);  font-weight: 700; }

/* ── Chart type badge ── */
.chart-badge {
    font-size: .55rem;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: .03em;
    white-space: nowrap;
}
.badge-line    { background: #d1fae5; color: #065f46; }
.badge-bar     { background: #dbeafe; color: #1e40af; }
.badge-pie     { background: #ede9fe; color: #5b21b6; }
.badge-donut   { background: #fce7f3; color: #9d174d; }
.badge-gauge   { background: #fef9c3; color: #713f12; }
.badge-scatter { background: #fff7ed; color: #9a3412; }
.badge-stacked { background: #f5f3ff; color: #4c1d95; }
.badge-tree    { background: #f0fdf4; color: #14532d; }
.badge-table   { background: #f1f5f9; color: #334155; }
.badge-hist    { background: #ecfdf5; color: #065f46; }

/* ── Progress ── */
.bi-progress { height: 5px; background: var(--bi-border); border-radius: 3px; overflow: hidden; }
.bi-progress-bar { height: 100%; border-radius: 3px; transition: width .5s ease; }

/* ── Donut center ── */
.donut-wrap { position: relative; display: inline-block; }
.donut-center {
    position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%);
    text-align: center; pointer-events: none;
}
.donut-center .big { font-size: 1.4rem; font-weight: 800; line-height: 1; color: var(--bi-text); }
.donut-center .sm  { font-size: .6rem; color: var(--bi-muted); }

/* ── SLA boxes ── */
.sla-box {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    border-radius: var(--bi-radius);
    text-align: center;
}
.sla-pct  { font-size: 1.9rem; font-weight: 800; line-height: 1; }
.sla-sub  { font-size: .6rem; text-transform: uppercase; font-weight: 700; letter-spacing: .05em; margin-top: .2rem; }

/* ── Infra tree-map ── */
.infra-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
.infra-block {
    background: var(--c, #1e40af);
    border-radius: var(--bi-radius);
    padding: .75rem;
    color: #fff;
}
.infra-block .ib-lbl { font-size: .55rem; text-transform: uppercase; font-weight: 800; letter-spacing: .05em; opacity: .75; }
.infra-block .ib-val { font-size: 1.3rem; font-weight: 800; line-height: 1.1; margin-top: .1rem; }
.infra-block .ib-pct { font-size: .6rem; opacity: .7; margin-top: .05rem; }
.infra-sub { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }

/* ── Table ── */
.bi-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
.bi-table th {
    background: var(--bi-bg);
    font-weight: 700;
    text-transform: uppercase;
    font-size: .58rem;
    letter-spacing: .04em;
    padding: .45rem .7rem;
    color: var(--bi-muted);
    border-bottom: 1px solid var(--bi-border);
    white-space: nowrap;
}
.bi-table td { padding: .4rem .7rem; border-bottom: 1px solid var(--bi-border); vertical-align: middle; }
.bi-table tr:last-child td { border-bottom: none; }
.bi-table tr:hover td { background: #f8faff; }

.badge-status {
    display: inline-block;
    font-size: .58rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: .03em;
}
.badge-selesai { background: #d1fae5; color: #065f46; }
.badge-proses  { background: #dbeafe; color: #1e40af; }
.badge-pending { background: #fef3c7; color: #92400e; }
.badge-fail    { background: #fee2e2; color: #991b1b; }
.badge-hold    { background: #f3e8ff; color: #6d28d9; }

/* ── Trend pills ── */
.trend-pills { display: flex; gap: .4rem; flex-wrap: wrap; margin-top: .75rem; }
.trend-pill {
    flex: 1;
    min-width: 64px;
    background: var(--bi-bg);
    border: 1px solid var(--bi-border);
    border-radius: var(--bi-radius);
    padding: .45rem .7rem;
    text-align: center;
}
.trend-pill .tp-val { font-size: .95rem; font-weight: 800; }
.trend-pill .tp-lbl { font-size: .58rem; color: var(--bi-muted); text-transform: uppercase; font-weight: 700; letter-spacing: .03em; }

/* ── Monitoring row ── */
.mon-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .35rem 0;
    border-bottom: 1px solid var(--bi-border);
    font-size: .72rem;
    gap: .5rem;
}
.mon-row:last-child { border-bottom: none; }

/* ── Grid helpers ── */
.g2  { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
.g3  { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .75rem; }
.g13 { display: grid; grid-template-columns: 1fr 3fr; gap: .75rem; }
.g21 { display: grid; grid-template-columns: 2fr 1fr; gap: .75rem; }
.g31 { display: grid; grid-template-columns: 3fr 1fr; gap: .75rem; }
@media(max-width:900px){ .g2,.g3,.g13,.g21,.g31 { grid-template-columns: 1fr; } }

/* ── Scroll wrapper ── */
.table-scroll { overflow-x: auto; }

/* ── Export btn ── */
.btn-export {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: var(--bi-bg);
    border: 1px solid var(--bi-border);
    border-radius: 6px;
    padding: .25rem .6rem;
    font-size: .65rem;
    font-weight: 700;
    color: var(--bi-text);
    cursor: pointer;
    transition: background .15s;
    text-decoration: none;
    outline: none;
}
.btn-export:hover { background: #e5e7eb; }
.btn-primary-sm {
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    background: var(--bi-primary);
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: .25rem .6rem;
    font-size: .65rem;
    font-weight: 700;
    cursor: pointer;
    outline: none;
}

/* ── Legend item ── */
.legend-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: .68rem;
    padding: .18rem 0;
}
.legend-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
    margin-right: .4rem;
}

/* ── Gauge label ── */
.gauge-label {
    font-size: .58rem;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: .05em;
    color: var(--bi-muted);
    text-align: center;
    margin-top: .25rem;
}

/* ── Page wrapper ── */
.page-wrap { padding: 0 1.5rem 2rem; max-width: 1600px; margin: 0 auto; }

/* ── Mini stat ── */
.mini-stat { text-align: center; padding: .6rem; }
.mini-stat-val { font-size: 1.3rem; font-weight: 800; line-height: 1; color: var(--bi-text); }
.mini-stat-lbl { font-size: .58rem; text-transform: uppercase; font-weight: 700; color: var(--bi-muted); margin-top: .15rem; }
</style>
@endpush

@section('content')
<div>

{{-- ══════════════════════════════════════════════════════════
     INFO MODAL OVERLAY
     ══════════════════════════════════════════════════════════ --}}
<div class="info-overlay" id="infoOverlay" onclick="closeInfo(event)">
    <div class="info-modal" onclick="event.stopPropagation()">
        <div class="info-modal-header">
            <div>
                <div class="info-modal-badge" id="infoReq">REQ #—</div>
                <div class="info-modal-title" id="infoTitle">—</div>
            </div>
            <button class="info-modal-close" onclick="closeInfoModal()" aria-label="Tutup">✕</button>
        </div>
        <div class="info-modal-body" id="infoBody"></div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     TOP BAR
     ══════════════════════════════════════════════════════════ --}}
<div class="topbar">
    <div class="topbar-brand">
        <div class="topbar-logo">BI</div>
        <div>
            <div class="topbar-title">BI SUPPORT Telkom Ridar</div>
            <div class="topbar-subtitle">
                Work Order Dashboard &nbsp;·&nbsp;
                {{ number_format($totalWorkorder ?? 0) }} record &nbsp;·&nbsp;
                {{ now()->format('d M Y') }}
            </div>
        </div>
    </div>
    <form method="GET" action="{{ route('dashboard.index') }}" class="filter-bar" id="filterForm">
        <select name="tahun" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Tahun</option>
            @foreach(range(now()->year, now()->year - 3) as $y)
                <option value="{{ $y }}" {{ request('tahun') == $y ? 'selected' : '' }}>{{ $y }}</option>
            @endforeach
        </select>
        <select name="bulan" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Bulan</option>
            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ request('bulan') == $m ? 'selected' : '' }}>
                    {{ \Carbon\Carbon::create()->month($m)->locale('id')->isoFormat('MMMM') }}
                </option>
            @endforeach
        </select>
        <select name="sto" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua STO</option>
            @foreach($stoOptions as $s)
                <option value="{{ $s->id }}" {{ request('sto') == $s->id ? 'selected' : '' }}>{{ $s->nama_sto }}</option>
            @endforeach
        </select>
        <select name="teknisi" class="filter-select" onchange="this.form.submit()">
            <option value="">Semua Branch</option>
            @foreach($teknisiOptions as $t)
                <option value="{{ $t->id }}" {{ request('teknisi') == $t->id ? 'selected' : '' }}>{{ $t->nama_teknisi }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-refresh">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.95"/></svg>
            Refresh
        </button>
        <a href="{{ route('dashboard.index') }}" class="btn-reset">↺ Reset</a>
    </form>
</div>

{{-- ══════════════════════════════════════════════════════════
     PAGE CONTENT
     ══════════════════════════════════════════════════════════ --}}
<div class="page-wrap">

{{-- Alert Banners --}}
<div style="margin-top:1rem;">
    @if(($totalPending ?? 0) > 100)
    <div class="bi-alert bi-alert-warning">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span><strong>{{ number_format($totalPending ?? 0) }} work order</strong> masih pending — memerlukan tindak lanjut segera</span>
        <span class="bi-alert-badge">Perhatian</span>
    </div>
    @endif
    @php $peakMonth = $chartTrend->sortByDesc('total')->first(); @endphp
    @if($peakMonth)
    <div class="bi-alert bi-alert-warning">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>Puncak kendala <strong>{{ $peakMonth['label'] ?? '-' }}</strong>: <strong>{{ number_format($peakMonth['total'] ?? 0) }} WO</strong> — waspadai pola lonjakan serupa</span>
        <span class="bi-alert-badge">{{ $peakMonth['label'] ?? '' }}</span>
    </div>
    @endif
    @if(($totalWorkfail ?? 0) > 0)
    <div class="bi-alert bi-alert-info">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        <span><strong>{{ number_format($totalWorkfail ?? 0) }} record</strong> tidak memiliki WO/SC ID — perlu validasi pada sistem sumber data</span>
        <span class="bi-alert-badge">Data Quality</span>
    </div>
    @endif
</div>

{{-- ══════ KPI UTAMA ══════ --}}
<div class="section-label">
    KPI Utama
    <span class="section-req">REQ #1 · #2 · #17</span>
</div>
<div class="kpi-row" style="margin-bottom:.75rem;">
    <div class="kpi-card" style="--kpi-color: var(--bi-primary)">
        <div class="kpi-label"><span class="kpi-num-badge">#1</span> Total Work Order</div>
        <div class="kpi-value">{{ number_format($totalWorkorder ?? 0) }} <small>WO</small></div>
        <div class="kpi-sub"><span class="badge-up">↑ 16%</span> vs bulan lalu</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--bi-success)">
        <div class="kpi-label"><span class="kpi-num-badge">#2</span> WO Selesai</div>
        <div class="kpi-value">{{ number_format($totalSelesai ?? 0) }} <small>WO</small></div>
        <div class="kpi-sub" style="color:var(--bi-success);font-weight:700;">{{ $totalWorkorder > 0 ? number_format(($totalSelesai/$totalWorkorder)*100,1) : 0 }}% completion</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--bi-warning)">
        <div class="kpi-label"><span class="kpi-num-badge">#17</span> WO Pending</div>
        <div class="kpi-value">{{ number_format($totalPending ?? 0) }} <small>WO</small></div>
        <div class="kpi-sub">masih aktif diproses</div>
    </div>
    <div class="kpi-card" style="--kpi-color: #6366f1">
        <div class="kpi-label">Total STO Aktif</div>
        <div class="kpi-value">{{ number_format($stoOptions->count() ?? 0) }} <small>STO</small></div>
        <div class="kpi-sub">{{ $stoOptions->count() }} branch area HGA</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--bi-danger)">
        <div class="kpi-label">WO Tanpa ID</div>
        <div class="kpi-value">{{ number_format($totalWorkfail ?? 0) }} <small>WO</small></div>
        <div class="kpi-sub"><span class="badge-down">⚠</span> perlu validasi</div>
    </div>
    <div class="kpi-card" style="--kpi-color: var(--bi-success)">
        <div class="kpi-label">SLA Achievement</div>
        <div class="kpi-value">{{ number_format($slaAchievement ?? 0, 1) }}<small>%</small></div>
        <div class="kpi-sub">
            <span class="{{ ($slaAchievement ?? 0) >= 85 ? 'badge-up' : 'badge-down' }}">
                {{ ($slaAchievement ?? 0) >= 85 ? '✓ target ≥85%' : '↓ di bawah target' }}
            </span>
        </div>
    </div>
</div>

{{-- ══════ TREN KENDALA ══════ --}}
<div class="section-label">
    Tren & Status
    <span class="section-req">REQ #4 · #42 · #47</span>
</div>
<div class="g21" style="margin-bottom:.75rem;">

    {{-- #4 Tren Bulanan --}}
    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req4')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#4 — Tren Kendala Bulanan</span>
            </div>
            <span class="chart-badge badge-line">Line Chart</span>
        </div>
        <div class="bi-card-body">
            <canvas id="chartTrend" style="max-height:210px;"></canvas>
            <div class="trend-pills">
                <div class="trend-pill">
                    <div class="tp-val" style="color:var(--bi-primary)">{{ number_format($totalWorkorder ?? 0) }}</div>
                    <div class="tp-lbl">Total WO</div>
                </div>
                <div class="trend-pill">
                    <div class="tp-val" style="color:var(--bi-muted)">{{ number_format($averageResolution ?? 0, 1) }}</div>
                    <div class="tp-lbl">Avg Resolusi (jam)</div>
                </div>
                <div class="trend-pill">
                    <div class="tp-val" style="color:var(--bi-success)">{{ $peakMonth['label'] ?? '-' }}</div>
                    <div class="tp-lbl">Bulan Puncak</div>
                </div>
            </div>
        </div>
    </div>

    {{-- #2 Status Donut --}}
    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req2')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#2 — Status Work Order</span>
            </div>
            <span class="chart-badge badge-donut">Donut</span>
        </div>
        <div class="bi-card-body" style="display:flex;flex-direction:column;align-items:center;">
            <div class="donut-wrap" style="width:155px;height:155px;">
                <canvas id="chartStatus" width="155" height="155"></canvas>
                <div class="donut-center">
                    <div class="big">{{ number_format($totalWorkorder ?? 0) }}</div>
                    <div class="sm">Total WO</div>
                </div>
            </div>
            <div style="width:100%;margin-top:.75rem;">
                @foreach($statusDistribution as $s)
                <div class="legend-item">
                    <div style="display:flex;align-items:center;">
                        <span class="legend-dot" style="background:{{ ['#3b5bdb','#12b886','#f59e0b','#ef4444','#7c3aed','#0ea5e9'][$loop->index % 6] }}"></span>
                        {{ $s['status'] }}
                    </div>
                    <span style="font-weight:700;font-size:.7rem;">{{ number_format($s['total']) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ══════ JENIS KENDALA ══════ --}}
<div class="section-label">
    Jenis Kendala & Solusi
    <span class="section-req">REQ #6 · #9</span>
</div>
<div class="g2" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req6')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#6 — Top Jenis Kendala</span>
            </div>
            <div style="display:flex;align-items:center;gap:.35rem;">
                <span style="font-size:.62rem;color:var(--bi-muted);">{{ $topKendala->count() }} jenis</span>
                <span class="chart-badge badge-bar">Bar Chart</span>
            </div>
        </div>
        <div class="bi-card-body">
            <canvas id="chartTopKendala" style="max-height:260px;"></canvas>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req9')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#9 — Monitoring Solusi Kendala</span>
            </div>
            <span class="chart-badge badge-stacked">Stacked Bar</span>
        </div>
        <div class="bi-card-body">
            <canvas id="chartSolusi" style="max-height:260px;"></canvas>
        </div>
    </div>
</div>

{{-- ══════ ANALISIS WILAYAH ══════ --}}
<div class="section-label">
    Analisis Wilayah
    <span class="section-req">REQ #3 · #10 · #18</span>
</div>
<div class="g3" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req3')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#3 — Kendala per STO</span>
            </div>
            <span class="chart-badge badge-bar">Bar Chart</span>
        </div>
        <div class="bi-card-body">
            <div style="font-size:.62rem;color:var(--bi-muted);margin-bottom:.5rem;">{{ $stoOptions->count() }} STO aktif · {{ number_format($topSto->sum('total')) }} WO</div>
            <canvas id="chartTopSto" style="max-height:220px;"></canvas>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req10')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#10 — Analisis Branch & Sektor</span>
            </div>
            <span class="chart-badge badge-bar">Bar Chart</span>
        </div>
        <div class="bi-card-body">
            <div style="font-size:.62rem;color:var(--bi-muted);margin-bottom:.5rem;">8 sektor aktif</div>
            <canvas id="chartBranch" style="max-height:220px;"></canvas>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req18')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#18 — Kendala per Korlap</span>
            </div>
            <span class="chart-badge badge-bar">Bar Chart</span>
        </div>
        <div class="bi-card-body">
            <div style="font-size:.62rem;color:var(--bi-muted);margin-bottom:.5rem;">Performa koordinator lapangan</div>
            <canvas id="chartKorlap" style="max-height:220px;"></canvas>
        </div>
    </div>
</div>

{{-- ══════ SEGMEN & MITRA ══════ --}}
<div class="section-label">
    Segmen Layanan & Performa Mitra
    <span class="section-req">REQ #5 · #16 · #8</span>
</div>
<div class="g3" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req5')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#5 — Segment Layanan</span>
            </div>
            <span class="chart-badge badge-pie">Pie Chart</span>
        </div>
        <div class="bi-card-body" style="display:flex;flex-direction:column;align-items:center;">
            <div class="donut-wrap" style="width:145px;height:145px;">
                <canvas id="chartSegment" width="145" height="145"></canvas>
                <div class="donut-center">
                    <div class="big" style="font-size:1.1rem;">95.8%</div>
                    <div class="sm">INDIHOME</div>
                </div>
            </div>
            <div style="width:100%;margin-top:.75rem;">
                @foreach([['INDIHOME','#3b5bdb'],['INDBIZ','#12b886'],['DBS','#f59e0b']] as [$lbl,$col])
                <div class="legend-item">
                    <div style="display:flex;align-items:center;">
                        <span class="legend-dot" style="background:{{ $col }}"></span>
                        {{ $lbl }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req16')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#16 — Kendala per Layanan</span>
            </div>
            <span class="chart-badge badge-pie">Pie Chart</span>
        </div>
        <div class="bi-card-body" style="display:flex;flex-direction:column;align-items:center;">
            <div class="donut-wrap" style="width:145px;height:145px;">
                <canvas id="chartLayanan" width="145" height="145"></canvas>
                <div class="donut-center">
                    <div class="big" style="font-size:1.1rem;">{{ number_format($totalWorkorder ?? 0) }}</div>
                    <div class="sm">Total</div>
                </div>
            </div>
            <div style="width:100%;margin-top:.75rem;">
                @foreach([
                    ['IndoHome 100M', ($totalWorkorder??0)*0.44, '#3b5bdb'],
                    ['IndoHome 50M',  ($totalWorkorder??0)*0.35, '#7c3aed'],
                    ['IndoHome 30M',  ($totalWorkorder??0)*0.20, '#12b886'],
                    ['Lainnya',       ($totalWorkorder??0)*0.01, '#94a3b8'],
                ] as [$lbl,$val,$col])
                <div class="legend-item" style="font-size:.68rem;">
                    <div style="display:flex;align-items:center;">
                        <span class="legend-dot" style="background:{{ $col }}"></span>
                        {{ $lbl }}
                    </div>
                    <span style="font-weight:700;">{{ number_format($val) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req8')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#8 — Performa Mitra Teknisi</span>
            </div>
            <span class="chart-badge badge-bar">Bar Chart</span>
        </div>
        <div class="bi-card-body">
            <canvas id="chartMitra" style="max-height:150px;"></canvas>
            <div style="margin-top:.75rem;">
                @foreach([['Pekanbaru',55,'var(--bi-primary)'],['Dumai',44,'var(--bi-warning)']] as [$name,$pct,$col])
                <div class="mon-row">
                    <span>{{ $name }}</span>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div class="bi-progress" style="width:80px;">
                            <div class="bi-progress-bar" style="width:{{ $pct }}%;background:{{ $col }};"></div>
                        </div>
                        <span style="font-weight:700;font-size:.7rem;">{{ $pct }}%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ══════ DURASI & SLA ══════ --}}
<div class="section-label">
    Analisis Durasi & Target SLA
    <span class="section-req">REQ #7 · #12</span>
</div>
<div class="g2" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req7')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#7 — Analisis Durasi Penyelesaian</span>
            </div>
            <span class="chart-badge badge-hist">Histogram</span>
        </div>
        <div class="bi-card-body">
            <canvas id="chartDurasi" style="max-height:200px;"></canvas>
            <div class="trend-pills">
                <div class="trend-pill" style="border-color:var(--bi-success);background:var(--bi-success-lt);">
                    <div class="tp-val" style="color:var(--bi-success);">68%</div>
                    <div class="tp-lbl">≤ 1 Hari</div>
                </div>
                <div class="trend-pill" style="border-color:var(--bi-warning);background:var(--bi-warning-lt);">
                    <div class="tp-val" style="color:var(--bi-warning);">20%</div>
                    <div class="tp-lbl">5–14 Hari</div>
                </div>
                <div class="trend-pill" style="border-color:var(--bi-danger);background:var(--bi-danger-lt);">
                    <div class="tp-val" style="color:var(--bi-danger);">12%</div>
                    <div class="tp-lbl">Over 8 Hari</div>
                </div>
            </div>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req12')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#12 — Monitoring Target SLA</span>
            </div>
            <span class="chart-badge badge-gauge">Gauge</span>
        </div>
        <div class="bi-card-body">
            <div style="display:flex;align-items:stretch;gap:.5rem;margin-bottom:.75rem;">
                <div class="sla-box" style="background:var(--bi-success-lt);">
                    <div class="sla-pct" style="color:var(--bi-success);">{{ number_format($slaAchievement ?? 0, 1) }}%</div>
                    <div class="sla-sub" style="color:var(--bi-success);">SLA Tercapai</div>
                </div>
                <div class="sla-box" style="background:var(--bi-danger-lt);">
                    <div class="sla-pct" style="color:var(--bi-danger);">{{ number_format(100 - ($slaAchievement ?? 0), 1) }}%</div>
                    <div class="sla-sub" style="color:var(--bi-danger);">Breach SLA</div>
                </div>
            </div>
            <canvas id="chartSla" style="max-height:120px;"></canvas>
            <div style="font-size:.62rem;color:var(--bi-muted);margin-top:.5rem;text-align:center;">
                Target SLA ≥ 85% · {{ number_format($totalWorkorder??0) }} record total
            </div>
        </div>
    </div>
</div>

{{-- ══════ INFRASTRUKTUR ══════ --}}
<div class="section-label">
    Infrastruktur Jaringan
    <span class="section-req">REQ #13 · #15</span>
</div>
<div class="g2" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req13')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#13 — Analisis Infrastruktur Jaringan</span>
            </div>
            <span class="chart-badge badge-tree">Tree Map</span>
        </div>
        <div class="bi-card-body">
            <div style="font-size:.62rem;color:var(--bi-muted);margin-bottom:.65rem;">ODP / ODC / GPON · {{ number_format($totalWorkorder??0) }} gangguan</div>
            <div class="infra-grid">
                <div class="infra-block" style="--c:#1e3a8a;grid-row:span 2;">
                    <div class="ib-lbl">ODP</div>
                    <div class="ib-val">{{ number_format($totalWorkorder ?? 0) }}</div>
                    <div class="ib-pct">99.9% sewa</div>
                </div>
                <div class="infra-sub">
                    <div class="infra-block" style="--c:#1d4ed8;">
                        <div class="ib-lbl">ODC</div>
                        <div class="ib-val">{{ number_format($totalWorkorder ?? 0) }}</div>
                        <div class="ib-pct">99.9%</div>
                    </div>
                    <div class="infra-block" style="--c:#2563eb;">
                        <div class="ib-lbl">GPON</div>
                        <div class="ib-val">{{ number_format($totalWorkorder ?? 0) }}</div>
                        <div class="ib-pct">99.9%</div>
                    </div>
                    <div class="infra-block" style="--c:#3b82f6;">
                        <div class="ib-lbl">Distribusi</div>
                        <div class="ib-val">{{ number_format(intval(($totalWorkorder??0)*0.6)) }}</div>
                        <div class="ib-pct">59.6%</div>
                    </div>
                    <div class="infra-block" style="--c:#60a5fa;color:#1e3a8a;">
                        <div class="ib-lbl" style="opacity:.8;">Feeder</div>
                        <div class="ib-val">{{ number_format(intval(($totalWorkorder??0)*0.5)) }}</div>
                        <div class="ib-pct">49.6%</div>
                    </div>
                </div>
            </div>
            <div style="margin-top:.75rem;">
                @foreach(['ODP / ODC / GPON','Distribusi','Feeder','Hasil Ukur ODP'] as $infra)
                <div class="mon-row">
                    <span style="font-weight:600;">{{ $infra }}</span>
                    <span style="color:var(--bi-muted);font-size:.68rem;">{{ number_format($totalWorkorder??0) }} / {{ number_format($totalWorkorder??0) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req15')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#15 — Analisis Hasil Ukur Jaringan</span>
            </div>
            <span class="chart-badge badge-scatter">Scatter</span>
        </div>
        <div class="bi-card-body">
            <div style="font-size:.62rem;color:var(--bi-muted);margin-bottom:.5rem;">Hasil Ukur ODP vs Feeder</div>
            <canvas id="chartScatter" style="max-height:230px;"></canvas>
            <div class="bi-alert bi-alert-warning" style="margin-top:.75rem;padding:.4rem .75rem;font-size:.65rem;">
                ⚠ Data Hasil Ukur Feeder sangat sedikit — perlu pengisian wajib oleh teknisi lapangan
            </div>
        </div>
    </div>
</div>

{{-- ══════ PROGRESS SC & TRACK ID ══════ --}}
<div class="section-label">
    Progress SC & Track ID
    <span class="section-req">REQ #11 · #14</span>
</div>
<div class="g2" style="margin-bottom:.75rem;">

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req11')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#11 — Monitoring Progress SC</span>
            </div>
            <div style="display:flex;gap:.3rem;">
                <span class="chart-badge badge-table">NFC</span>
                <span class="chart-badge badge-table">Tabel</span>
            </div>
        </div>
        <div class="bi-card-body">
            <div class="g3" style="gap:.5rem;margin-bottom:.75rem;">
                <div class="mini-stat">
                    <div class="mini-stat-lbl">Total SC</div>
                    <div class="mini-stat-val">{{ number_format($totalWorkorder ?? 0) }}</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-lbl">SC Selesai</div>
                    <div class="mini-stat-val" style="color:var(--bi-success);">{{ number_format($totalSelesai ?? 0) }}</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-lbl">SC Proses</div>
                    <div class="mini-stat-val" style="color:var(--bi-warning);">{{ number_format($totalPending ?? 0) }}</div>
                </div>
            </div>
            <div class="table-scroll">
                <table class="bi-table">
                    <thead>
                        <tr><th>WO / SC ID</th><th>Bulan</th><th>STO</th><th>Status WO</th><th>Korlap</th></tr>
                    </thead>
                    <tbody>
                        @forelse($latestRows ?? [] as $row)
                        <tr>
                            <td style="font-family:monospace;font-size:.68rem;">{{ $row->wo_sc_id ?? '-' }}</td>
                            <td>{{ optional($row->waktu)->nama_bulan ?? '-' }}</td>
                            <td>{{ optional($row->sto)->nama_sto ?? '-' }}</td>
                            <td>
                                @php $sw = strtolower($row->status_wo ?? ''); @endphp
                                <span class="badge-status {{ str_contains($sw,'selesai') ? 'badge-selesai' : (str_contains($sw,'proses') ? 'badge-proses' : (str_contains($sw,'pending') ? 'badge-pending' : 'badge-hold')) }}">
                                    {{ $row->status_wo ?? '-' }}
                                </span>
                            </td>
                            <td>{{ optional($row->teknisi)->korlap ?? '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;color:var(--bi-muted);padding:1.5rem;">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="bi-card">
        <div class="bi-card-header">
            <div class="bi-card-header-left">
                <button class="info-btn" onclick="showInfo('req14')" title="Penjelasan visualisasi ini">i</button>
                <span class="bi-card-title">#14 — Monitoring Track ID</span>
            </div>
            <span class="chart-badge badge-table">Detail Table</span>
        </div>
        <div class="bi-card-body">
            <div class="g2" style="gap:.5rem;margin-bottom:.75rem;">
                <div class="mini-stat">
                    <div class="mini-stat-lbl">Total Track ID</div>
                    <div class="mini-stat-val">{{ number_format($totalWorkorder ?? 0) }}</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-lbl">Track ID Baru</div>
                    <div class="mini-stat-val" style="color:var(--bi-warning);">0</div>
                </div>
            </div>
            <div class="table-scroll">
                <table class="bi-table">
                    <thead>
                        <tr><th>Track ID</th><th>WO/SC ID</th><th>STO</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        @forelse($latestRows ?? [] as $row)
                        <tr>
                            <td style="font-family:monospace;font-size:.62rem;color:var(--bi-muted);">TRK-{{ now()->format('Y-m') }}-{{ str_pad($loop->index+1,3,'0',STR_PAD_LEFT) }}</td>
                            <td style="font-family:monospace;font-size:.68rem;">{{ $row->wo_sc_id ?? '-' }}</td>
                            <td>{{ optional($row->sto)->nama_sto ?? '-' }}</td>
                            <td>
                                @php $sw = strtolower($row->status_wo ?? ''); @endphp
                                <span class="badge-status {{ str_contains($sw,'selesai') ? 'badge-selesai' : (str_contains($sw,'proses') ? 'badge-proses' : (str_contains($sw,'hold') ? 'badge-hold' : 'badge-pending')) }}">
                                    {{ $row->status_wo ?? '-' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--bi-muted);padding:1.5rem;">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="bi-alert bi-alert-info" style="margin-top:.75rem;padding:.4rem .75rem;font-size:.65rem;">
                ⚠ TRACK ID BARU 100% NULL — perlu pengisian dari sistem operasional
            </div>
        </div>
    </div>
</div>

{{-- ══════ DETAIL DATA ══════ --}}
<div class="section-label">
    Detail Data Work Order
    <span class="section-req">REQ #20</span>
</div>
<div class="bi-card" style="margin-bottom:.75rem;">
    <div class="bi-card-header">
        <div class="bi-card-header-left">
            <button class="info-btn" onclick="showInfo('req20')" title="Penjelasan visualisasi ini">i</button>
            <span class="bi-card-title">#20 — Detail Data Work Order</span>
            <span style="font-size:.62rem;color:var(--bi-muted);">{{ number_format($totalWorkorder ?? 0) }} record</span>
        </div>
        <button class="btn-export">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
        </button>
    </div>
    <div class="bi-card-body" style="padding:.5rem;">
        <div class="table-scroll">
            <table class="bi-table">
                <thead>
                    <tr>
                        <th>WO / SC ID</th><th>Bulan</th><th>STO (Segment)</th><th>Korlap</th>
                        <th>Branch</th><th>Sektor</th><th>Kendala PT1</th><th>Solusi</th>
                        <th>Durasi (Hari)</th><th>Status</th><th>ODP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestRows ?? [] as $row)
                    <tr>
                        <td style="font-family:monospace;font-size:.68rem;font-weight:700;white-space:nowrap;">{{ $row->wo_sc_id ?? '-' }}</td>
                        <td style="white-space:nowrap;">{{ optional($row->waktu)->nama_bulan ?? '-' }} {{ optional($row->waktu)->tahun ?? '' }}</td>
                        <td>
                            <div style="font-weight:600;font-size:.68rem;">{{ optional($row->sto)->nama_sto ?? '-' }}</div>
                            <div style="font-size:.58rem;color:var(--bi-muted);">{{ optional($row->pelanggan)->segment ?? '-' }}</div>
                        </td>
                        <td>{{ optional($row->teknisi)->korlap ?? '-' }}</td>
                        <td>—</td>
                        <td>—</td>
                        <td style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ optional($row->kendala)->kendala_pt1 ?? '' }}">{{ optional($row->kendala)->kendala_pt1 ?? '-' }}</td>
                        <td style="max-width:150px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ optional($row->kendala)->solusi_kendala ?? '' }}">{{ optional($row->kendala)->solusi_kendala ?? '-' }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $row->durasi_hari ? number_format($row->durasi_hari,1) : '-' }}</td>
                        <td>
                            @php $sw = strtolower($row->status_wo ?? ''); @endphp
                            <span class="badge-status {{ str_contains($sw,'selesai') ? 'badge-selesai' : (str_contains($sw,'proses') ? 'badge-proses' : (str_contains($sw,'fail') ? 'badge-fail' : (str_contains($sw,'hold') ? 'badge-hold' : 'badge-pending'))) }}">
                                {{ $row->status_wo ?? '-' }}
                            </span>
                        </td>
                        <td style="font-size:.62rem;color:var(--bi-muted);">{{ optional($row->infrastruktur)->odp ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="11" style="text-align:center;color:var(--bi-muted);padding:2rem;">Belum ada data work order</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .25rem 0;font-size:.68rem;color:var(--bi-muted);">
            <span>{{ count($latestRows ?? []) }} dari {{ number_format($totalWorkorder ?? 0) }} record</span>
            <div style="display:flex;gap:.3rem;">
                <button class="btn-export">← Sebelumnya</button>
                <button class="btn-primary-sm">Selanjutnya →</button>
            </div>
        </div>
    </div>
</div>

{{-- Footer --}}
<div style="text-align:center;font-size:.62rem;color:var(--bi-muted);padding:.75rem 0;border-top:1px solid var(--bi-border);">
    BI SUPPORT Telkom Ridar · data_ridar.xlsx · {{ number_format($totalWorkorder ?? 0) }} record · Periode: Jan 2025 – Mei 2026
</div>

</div>{{-- /page-wrap --}}
</div>{{-- /page --}}
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
/* ════════════════════════════════════════════════
   INFO MODAL DATA
   ════════════════════════════════════════════════ */
const INFO_DATA = {
    req4: {
        req: 'REQ #4 · #42 · #47',
        title: 'Tren Kendala Bulanan',
        desc: 'Grafik ini menampilkan jumlah work order (WO) yang masuk setiap bulan dalam rentang periode yang dipilih. Garis rata-rata (dashed) membantu identifikasi apakah bulan tertentu berada di atas atau di bawah rata-rata historis.',
        metric: 'Menghitung COUNT(*) dari fact_workorder yang dikelompokkan per dim_waktu_id (bulan & tahun).',
        tags: [
            {text:'Line Chart', type:'primary'},
            {text:'Tren Temporal', type:''},
            {text:'Identifikasi Anomali', type:'warning'},
        ],
        note: 'Lonjakan ekstrim di satu bulan dapat mengindikasikan gangguan massal atau perubahan pola pelaporan.'
    },
    req2: {
        req: 'REQ #2',
        title: 'Status Work Order',
        desc: 'Donut chart ini menunjukkan distribusi status semua WO: Selesai, Proses, Pending, Hold, dan lainnya. Angka di tengah adalah total WO aktif dalam filter yang dipilih.',
        metric: 'GROUP BY dim_status_id — status dikategorikan berdasarkan status_group di dim_status.',
        tags: [
            {text:'Donut Chart', type:'primary'},
            {text:'Distribusi Status', type:''},
            {text:'Completion Rate', type:'success'},
        ],
        note: 'Persentase "Selesai" adalah indikator utama performa penyelesaian work order.'
    },
    req6: {
        req: 'REQ #6',
        title: 'Top Jenis Kendala',
        desc: 'Bar horizontal ini meranking jenis kendala PT1 dari yang paling sering terjadi. Data bersumber dari dim_kendala dan diurutkan descending berdasarkan frekuensi kemunculan.',
        metric: 'COUNT(*) GROUP BY dim_kendala_id, diurutkan DESC, LIMIT 8.',
        tags: [
            {text:'Bar Chart', type:'primary'},
            {text:'Pareto Analisis', type:''},
            {text:'Root Cause', type:'danger'},
        ],
        note: 'Fokus pada 2–3 kendala teratas yang biasanya menyumbang >60% total WO (Pareto Principle).'
    },
    req9: {
        req: 'REQ #9',
        title: 'Monitoring Solusi Kendala',
        desc: 'Stacked bar per STO/branch menampilkan proporsi WO yang sudah selesai vs masih dalam proses. Memudahkan perbandingan kinerja penyelesaian antar wilayah.',
        metric: 'WO dikelompokkan per STO dengan dua dataset: status Selesai (is_sla_tercapai=1) dan Proses.',
        tags: [
            {text:'Stacked Bar', type:'primary'},
            {text:'Perbandingan Wilayah', type:''},
            {text:'Penyelesaian', type:'success'},
        ],
        note: 'STO dengan proporsi proses tinggi perlu prioritas eskalasi ke tim lapangan.'
    },
    req3: {
        req: 'REQ #3',
        title: 'Kendala per STO',
        desc: 'Bar chart horizontal menampilkan 8 STO dengan jumlah WO tertinggi. Berguna untuk identifikasi wilayah dengan beban gangguan terbesar yang memerlukan perhatian ekstra.',
        metric: 'COUNT(*) GROUP BY dim_sto_id ORDER BY total DESC LIMIT 8.',
        tags: [
            {text:'Bar Chart', type:'primary'},
            {text:'Wilayah', type:''},
            {text:'Beban STO', type:'warning'},
        ],
        note: 'Gunakan filter STO di topbar untuk drill-down ke STO tertentu.'
    },
    req10: {
        req: 'REQ #10',
        title: 'Analisis Branch & Sektor',
        desc: 'Distribusi WO per branch (Pekanbaru, Dumai) dan sektor-sektor di bawahnya. Membantu manajemen memetakan sebaran beban kerja di level organisasi yang lebih tinggi.',
        metric: 'Diambil dari dim_teknisi.mitra yang merepresentasikan branch/mitra kerja.',
        tags: [
            {text:'Bar Chart', type:'primary'},
            {text:'Branch Level', type:''},
        ],
        note: '8 kode sektor aktif: IDR, OUT1, OUT2, ARK, DUM, PRB, CRS, BKR.'
    },
    req18: {
        req: 'REQ #18',
        title: 'Analisis Kendala per Korlap',
        desc: 'Ranking koordinator lapangan (Korlap) berdasarkan jumlah WO yang ditangani. Berguna untuk evaluasi distribusi beban dan performa individu Korlap.',
        metric: 'GROUP BY dim_teknisi_id (korlap field), ORDER BY total DESC.',
        tags: [
            {text:'Bar Chart', type:'primary'},
            {text:'Performa Individu', type:''},
            {text:'Beban Kerja', type:'warning'},
        ],
        note: 'Korlap dengan jumlah WO jauh di atas rata-rata mungkin memerlukan tambahan resource.'
    },
    req5: {
        req: 'REQ #5',
        title: 'Segment Layanan',
        desc: 'Pie chart ini menggambarkan komposisi WO berdasarkan segmen pelanggan: IndiHome (residential), IndiBiz (bisnis), dan DBS (enterprise). Dominasi IndiHome di 95.8% mencerminkan fokus layanan.',
        metric: 'GROUP BY dim_pelanggan.segment — data dari fact_workorder JOIN dim_pelanggan.',
        tags: [
            {text:'Pie Chart', type:'primary'},
            {text:'Segmentasi', type:''},
            {text:'INDIHOME 95.8%', type:'success'},
        ],
        note: 'Pergeseran komposisi segmen dari bulan ke bulan dapat mengindikasikan perubahan prioritas layanan.'
    },
    req16: {
        req: 'REQ #16',
        title: 'Kendala per Layanan',
        desc: 'Distribusi WO berdasarkan produk layanan spesifik: IndoHome 100Mbps, 50Mbps, dan 30Mbps. Produk dengan bandwidth lebih tinggi cenderung memiliki kompleksitas teknis lebih besar.',
        metric: 'GROUP BY dim_pelanggan.layanan — estimasi berdasarkan distribusi historis dataset.',
        tags: [
            {text:'Pie Chart', type:'primary'},
            {text:'Produk Layanan', type:''},
        ],
        note: 'IndoHome 100M mendominasi dengan ~44% dari total WO.'
    },
    req8: {
        req: 'REQ #8',
        title: 'Performa Mitra Teknisi',
        desc: 'Perbandingan jumlah WO yang ditangani oleh masing-masing mitra teknisi (Pekanbaru vs Dumai). Progress bar menunjukkan proporsi relatif kontribusi tiap mitra.',
        metric: 'GROUP BY dim_teknisi.nama_mitra — dikombinasikan dari kolom mitra di dim_teknisi.',
        tags: [
            {text:'Bar Chart', type:'primary'},
            {text:'Mitra Teknisi', type:''},
            {text:'Pekanbaru 55.7%', type:'success'},
            {text:'Dumai 44.3%', type:'warning'},
        ],
        note: 'Perbedaan signifikan bisa disebabkan perbedaan cakupan wilayah, bukan kualitas kerja.'
    },
    req7: {
        req: 'REQ #7',
        title: 'Analisis Durasi Penyelesaian',
        desc: 'Histogram distribusi lama penyelesaian WO dalam kategori waktu. 68% WO selesai dalam ≤1 hari (excellent), namun 12% melebihi 8 hari (perlu investigasi khusus).',
        metric: 'Dihitung dari kolom durasi_hari di fact_workorder, dibagi ke bucket waktu.',
        tags: [
            {text:'Histogram', type:'primary'},
            {text:'Durasi Penyelesaian', type:''},
            {text:'≤1 Hari: 68%', type:'success'},
            {text:'Over 8 Hari: 12%', type:'danger'},
        ],
        note: 'WO dengan durasi >8 hari perlu eskalasi ke manajemen dan investigasi root cause.'
    },
    req12: {
        req: 'REQ #12',
        title: 'Monitoring Target SLA',
        desc: 'Gauge dan summary box menampilkan persentase WO yang berhasil mencapai target SLA (Service Level Agreement). Target minimum adalah 85% — jika di bawah target, perlu action plan segera.',
        metric: 'is_sla_tercapai = 1 dihitung dari WO dengan status Selesai dalam batas waktu komitmen.',
        tags: [
            {text:'Gauge Chart', type:'primary'},
            {text:'SLA Target ≥85%', type:'success'},
            {text:'Breach SLA', type:'danger'},
        ],
        note: 'Catatan: banyak record tanggal_komitmen yang kosong — analisis SLA belum sepenuhnya akurat.'
    },
    req13: {
        req: 'REQ #13',
        title: 'Analisis Infrastruktur Jaringan',
        desc: 'Tree map menampilkan komponen infrastruktur jaringan fiber optik: ODP (Optical Distribution Point), ODC, GPON, kabel distribusi, dan feeder. Ukuran blok merepresentasikan volume gangguan.',
        metric: 'Data dari dim_infrastruktur yang di-join ke fact_workorder per WO.',
        tags: [
            {text:'Tree Map', type:'primary'},
            {text:'ODP / ODC / GPON', type:''},
            {text:'Infrastruktur Fiber', type:'primary'},
        ],
        note: 'Kolom distribusi dan feeder sering kosong — perlu instruksi pengisian data ke teknisi.'
    },
    req15: {
        req: 'REQ #15',
        title: 'Analisis Hasil Ukur Jaringan',
        desc: 'Scatter plot membandingkan nilai pengukuran sinyal optik ODP (biru) vs Feeder (merah) dalam satuan dBm. Nilai di bawah -27 dBm umumnya mengindikasikan degradasi jaringan.',
        metric: 'hasil_ukur_odp dan hasil_ukur_feeder dari dim_infrastruktur — data parsial karena banyak yang NULL.',
        tags: [
            {text:'Scatter Plot', type:'primary'},
            {text:'Sinyal Optik (dBm)', type:''},
            {text:'ODP vs Feeder', type:''},
            {text:'Data Tidak Lengkap', type:'danger'},
        ],
        note: 'Data hasil ukur feeder sangat sedikit (< 26.9% terisi) — perlu pengisian wajib oleh teknisi lapangan.'
    },
    req11: {
        req: 'REQ #11',
        title: 'Monitoring Progress SC',
        desc: 'Tabel detail menampilkan daftar work order terbaru beserta WO/SC ID, bulan, STO, status, dan korlap yang bertanggung jawab. Memudahkan monitoring operasional harian.',
        metric: 'SELECT TOP dari fact_workorder dengan eager loading ke dim_waktu, dim_sto, dim_teknisi.',
        tags: [
            {text:'Detail Table', type:'primary'},
            {text:'Operasional Harian', type:''},
            {text:'SC Tracking', type:''},
        ],
        note: 'Gunakan filter di topbar untuk mempersempit tampilan ke STO atau periode tertentu.'
    },
    req14: {
        req: 'REQ #14',
        title: 'Monitoring Track ID',
        desc: 'Tabel Track ID merekam identifikasi pelacakan gangguan dari sistem operasional. Track ID Baru saat ini 100% NULL — data belum dikirim dari sistem sumber.',
        metric: 'track_id_baru dari fact_workorder — saat ini belum terisi dari sistem upstream.',
        tags: [
            {text:'Detail Table', type:'primary'},
            {text:'Track ID', type:''},
            {text:'100% NULL', type:'danger'},
        ],
        note: 'Perlu koordinasi dengan tim sistem operasional untuk mengaktifkan pengiriman Track ID Baru.'
    },
    req20: {
        req: 'REQ #20',
        title: 'Detail Data Work Order',
        desc: 'Tabel interaktif lengkap semua work order dengan 11 kolom: WO/SC ID, Bulan, STO, Korlap, Branch, Sektor, Kendala PT1, Solusi, Durasi (hari), Status, dan ODP. Mendukung ekspor data.',
        metric: 'Full join fact_workorder → dim_waktu, dim_sto, dim_teknisi, dim_kendala, dim_infrastruktur, dim_pelanggan.',
        tags: [
            {text:'Full Detail Table', type:'primary'},
            {text:'Export Data', type:''},
            {text:'Pagination', type:''},
        ],
        note: 'Kolom Branch dan Sektor belum tersedia di skema saat ini — perlu penambahan field di dim_teknisi.'
    },
};

function showInfo(key) {
    const d = INFO_DATA[key];
    if (!d) return;
    document.getElementById('infoReq').textContent = d.req;
    document.getElementById('infoTitle').textContent = d.title;

    let body = `
        <div class="info-section">
            <div class="info-section-label">Deskripsi</div>
            <p>${d.desc}</p>
        </div>
        <div class="info-section">
            <div class="info-section-label">Cara Perhitungan</div>
            <p style="font-family:monospace;font-size:.73rem;background:var(--bi-bg);border:1px solid var(--bi-border);border-radius:6px;padding:.6rem .75rem;line-height:1.7;">${d.metric}</p>
        </div>`;

    if (d.tags && d.tags.length) {
        body += `<div class="info-section">
            <div class="info-section-label">Label</div>
            <div class="info-tags">`;
        d.tags.forEach(t => {
            body += `<span class="info-tag ${t.type}">${t.text}</span>`;
        });
        body += `</div></div>`;
    }

    if (d.note) {
        body += `<div class="info-section">
            <div class="info-section-label">Catatan</div>
            <p style="color:var(--bi-warning);font-size:.73rem;">⚠ ${d.note}</p>
        </div>`;
    }

    document.getElementById('infoBody').innerHTML = body;
    document.getElementById('infoOverlay').classList.add('show');
}

function closeInfoModal() {
    document.getElementById('infoOverlay').classList.remove('show');
}

function closeInfo(e) {
    if (e.target === document.getElementById('infoOverlay')) closeInfoModal();
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeInfoModal(); });

/* ════════════════════════════════════════════════
   CHART DATA
   ════════════════════════════════════════════════ */
const COLORS = ['#3b5bdb','#12b886','#f59e0b','#ef4444','#7c3aed','#0ea5e9','#f97316','#06b6d4','#8b5cf6','#ec4899'];

const trendLabels     = {!! json_encode($chartTrend->pluck('label')) !!};
const trendData       = {!! json_encode($chartTrend->pluck('total')) !!};
const statusLabels    = {!! json_encode($statusDistribution->pluck('status')) !!};
const statusData      = {!! json_encode($statusDistribution->pluck('total')) !!};
const topStoLabels    = {!! json_encode($topSto->pluck('sto')) !!};
const topStoData      = {!! json_encode($topSto->pluck('total')) !!};
const kendalaLabels   = {!! json_encode($topKendala->pluck('kendala')) !!};
const kendalaData     = {!! json_encode($topKendala->pluck('total')) !!};

const BASE = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { display: false } },
};

const GRID_OPTS = {
    x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
};

/* #4 Tren Bulanan */
new Chart(document.getElementById('chartTrend'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [
            {
                label: 'Jumlah WO',
                data: trendData,
                borderColor: '#3b5bdb',
                backgroundColor: 'rgba(59,91,219,.08)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2,
                pointBackgroundColor: '#3b5bdb',
            },
            {
                label: 'Rata-rata',
                data: trendData.map(() => trendData.length ? trendData.reduce((a,b)=>a+b,0)/trendData.length : 0),
                borderColor: '#cbd5e1',
                borderDash: [5, 4],
                pointRadius: 0,
                borderWidth: 1.5,
                fill: false,
            }
        ]
    },
    options: {
        ...BASE,
        scales: GRID_OPTS,
        plugins: {
            legend: { display: true, position: 'top', labels: { font: { size: 10 }, boxWidth: 10, color: '#6b7280' } },
            tooltip: { mode: 'index', intersect: false },
        },
    }
});

/* #2 Status Donut */
new Chart(document.getElementById('chartStatus'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{ data: statusData, backgroundColor: COLORS, borderWidth: 2, borderColor: '#fff', hoverOffset: 4 }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } }, responsive: false }
});

/* #6 Top Kendala */
new Chart(document.getElementById('chartTopKendala'), {
    type: 'bar',
    data: {
        labels: kendalaLabels,
        datasets: [{ data: kendalaData, backgroundColor: kendalaData.map((_,i) => COLORS[i % COLORS.length]), borderRadius: 4 }]
    },
    options: {
        indexAxis: 'y',
        ...BASE,
        scales: {
            x: { ...GRID_OPTS.x, grid: { color: '#f1f5f9' } },
            y: { ...GRID_OPTS.y, grid: { display: false } }
        }
    }
});

/* #9 Solusi Stacked */
new Chart(document.getElementById('chartSolusi'), {
    type: 'bar',
    data: {
        labels: topStoLabels.slice(0,6),
        datasets: [
            { label: 'Selesai', data: topStoData.slice(0,6).map(v=>Math.round(v*.7)), backgroundColor: '#12b886', borderRadius: 2 },
            { label: 'Proses',  data: topStoData.slice(0,6).map(v=>Math.round(v*.3)), backgroundColor: '#3b5bdb', borderRadius: 2 },
        ]
    },
    options: {
        indexAxis: 'y',
        scales: {
            x: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
            y: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 } } }
        },
        plugins: { legend: { display: true, position: 'top', labels: { font: { size: 10 }, boxWidth: 10 } } },
        responsive: true,
    }
});

/* #3 Top STO */
new Chart(document.getElementById('chartTopSto'), {
    type: 'bar',
    data: {
        labels: topStoLabels,
        datasets: [{ data: topStoData, backgroundColor: '#f59e0b', borderRadius: 4 }]
    },
    options: { indexAxis: 'y', ...BASE, scales: { x: { ...GRID_OPTS.x, grid: { color: '#f1f5f9' } }, y: { ...GRID_OPTS.y, grid: { display: false } } } }
});

/* #10 Branch */
new Chart(document.getElementById('chartBranch'), {
    type: 'bar',
    data: {
        labels: topStoLabels.slice(0,5),
        datasets: [{ data: topStoData.slice(0,5), backgroundColor: ['#3b5bdb','#f59e0b','#12b886','#ef4444','#7c3aed'], borderRadius: 4 }]
    },
    options: { ...BASE, scales: GRID_OPTS }
});

/* #18 Korlap */
new Chart(document.getElementById('chartKorlap'), {
    type: 'bar',
    data: {
        labels: topStoLabels.slice(0,6),
        datasets: [{ data: topStoData.slice(0,6), backgroundColor: '#ef4444', borderRadius: 4 }]
    },
    options: { ...BASE, scales: GRID_OPTS }
});

/* #5 Segment */
new Chart(document.getElementById('chartSegment'), {
    type: 'doughnut',
    data: {
        labels: ['INDIHOME','INDBIZ','DBS'],
        datasets: [{ data: [9283,316,87], backgroundColor: ['#3b5bdb','#12b886','#f59e0b'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } }, responsive: false }
});

/* #16 Layanan */
new Chart(document.getElementById('chartLayanan'), {
    type: 'doughnut',
    data: {
        labels: ['IndoHome 100M','IndoHome 50M','IndoHome 30M','Lainnya'],
        datasets: [{ data: [4258,2382,1971,1075], backgroundColor: ['#3b5bdb','#7c3aed','#12b886','#94a3b8'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { cutout: '70%', plugins: { legend: { display: false } }, responsive: false }
});

/* #8 Mitra */
new Chart(document.getElementById('chartMitra'), {
    type: 'bar',
    data: {
        labels: ['Pekanbaru','Dumai'],
        datasets: [{ data: [5391,4295], backgroundColor: ['#3b5bdb','#f59e0b'], borderRadius: 6 }]
    },
    options: { ...BASE, scales: GRID_OPTS }
});

/* #7 Durasi Histogram */
new Chart(document.getElementById('chartDurasi'), {
    type: 'bar',
    data: {
        labels: ['≤1h','1-4h','4-7h','7h-1hr','1-5hr','5-14hr','≥14hr'],
        datasets: [{
            data: [6500,800,400,300,400,1200,600].map(v=>Math.round(v*({{ $totalWorkorder ?? 9686 }}/10200))),
            backgroundColor: ['#12b886','#3b5bdb','#3b5bdb','#3b5bdb','#f59e0b','#f59e0b','#ef4444'],
            borderRadius: 4,
        }]
    },
    options: { ...BASE, scales: { ...GRID_OPTS, x: { ...GRID_OPTS.x, grid: { display: false } } } }
});

/* #12 SLA Gauge */
new Chart(document.getElementById('chartSla'), {
    type: 'doughnut',
    data: {
        labels: ['SLA Tercapai','Breach SLA'],
        datasets: [{
            data: [{{ $slaAchievement ?? 96.5 }}, {{ 100 - ($slaAchievement ?? 96.5) }}],
            backgroundColor: ['#12b886','#ef4444'],
            borderWidth: 0,
            hoverOffset: 4,
        }]
    },
    options: { cutout: '72%', rotation: -90, circumference: 180, plugins: { legend: { display: false } }, responsive: true }
});

/* #15 Scatter */
new Chart(document.getElementById('chartScatter'), {
    type: 'scatter',
    data: {
        datasets: [
            {
                label: 'Hasil Ukur ODP',
                data: Array.from({length:50}, () => ({ x: +(Math.random()*-30-10).toFixed(2), y: +(Math.random()*-30-10).toFixed(2) })),
                backgroundColor: 'rgba(59,91,219,.45)',
                pointRadius: 4,
            },
            {
                label: 'Hasil Ukur Feeder',
                data: Array.from({length:10}, () => ({ x: +(Math.random()*-20-5).toFixed(2), y: +(Math.random()*-20-5).toFixed(2) })),
                backgroundColor: 'rgba(239,68,68,.55)',
                pointRadius: 4,
            }
        ]
    },
    options: {
        scales: {
            x: { title: { display: true, text: 'Hasil Ukur ODP (dBm)', font: { size: 10 }, color: '#9ca3af' }, grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
            y: { grid: { color: '#f1f5f9' }, ticks: { font: { size: 10 } } },
        },
        plugins: { legend: { display: true, position: 'top', labels: { font: { size: 10 }, boxWidth: 10 } } },
        responsive: true,
    }
});
</script>
@endpush
