<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BI SUPPORT Telkom Ridar')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: #fff6f7;
        }
        .app-shell {
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #7f1d2d 0%, #9f2d3f 54%, #5f1724 100%);
            color: #ffffff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 24px 20px;
            overflow-y: auto;
        }
        .sidebar-brand {
            text-align: center;
            padding: 10px 8px 26px;
            margin-bottom: 22px;
            border-bottom: 1px solid rgba(255,255,255,0.16);
        }
        .sidebar-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            margin-bottom: 14px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ffd6dc, #f06b7d 58%, #b83246);
            color: #ffffff;
            font-size: 1.35rem;
            font-weight: 800;
            box-shadow: 0 14px 28px rgba(95,23,36,0.28);
        }
        .sidebar-title {
            font-weight: 700;
            letter-spacing: 0.04em;
            line-height: 1.2;
        }
        .sidebar-subtitle {
            margin-top: 6px;
            color: rgba(255,255,255,0.58);
            font-size: 0.82rem;
        }
        .sidebar-caption {
            color: rgba(255,255,255,0.68);
            font-size: 0.88rem;
            line-height: 1.55;
            text-align: center;
            margin-bottom: 22px;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            border-radius: 14px;
            padding: 12px 16px;
            margin-bottom: 10px;
            font-weight: 600;
            transition: background .18s ease, color .18s ease, transform .18s ease;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.14);
            color: #ffffff;
            transform: translateX(3px);
        }
        .sidebar .nav-link.active {
            box-shadow: inset 4px 0 0 #ffd6dc;
        }
        .content-area {
            margin-left: 280px;
            padding: 24px 30px 40px;
        }
        .page-title {
            font-size: 1.7rem;
            font-weight: 700;
            letter-spacing: .02em;
            margin-bottom: 0.75rem;
        }
        .card-metric {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 16px 30px rgba(159, 45, 63, 0.08);
        }
        .card-metric .metric-label {
            font-size: 0.95rem;
            color: #6c757d;
        }
        .card-metric .metric-value {
            font-size: 2rem;
            font-weight: 700;
            margin-top: 0.5rem;
        }
        .panel-card {
            border-radius: 18px;
            box-shadow: 0 16px 28px rgba(159, 45, 63, 0.07);
            border: 0;
        }
        .panel-card .card-header {
            background: transparent;
            border-bottom: 0;
            font-weight: 700;
            color: #7f1d2d;
        }
        .upload-drop {
            border: 2px dashed #f3b8c2;
            min-height: 210px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #7f3846;
            text-align: center;
        }
        .upload-drop input[type=file] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            left: 0;
            top: 0;
            cursor: pointer;
        }
        .status-box {
            border-radius: 16px;
            padding: 18px;
            background: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(159, 45, 63, 0.10);
        }
        .status-box .status-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(159, 45, 63, 0.10);
        }
        .status-box .status-item:last-child {
            border-bottom: none;
        }
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .status-chip.pending { background: #fff4e5; color: #b05a00; }
        .status-chip.processed { background: #e9f8ef; color: #0f7a33; }
        .status-chip.failed { background: #fde8ee; color: #b02f3a; }
        .table-sm th, .table-sm td {
            padding: 0.65rem;
        }
        .btn-primary {
            --bs-btn-bg: #d94f63;
            --bs-btn-border-color: #d94f63;
            --bs-btn-hover-bg: #bf3f51;
            --bs-btn-hover-border-color: #bf3f51;
            --bs-btn-active-bg: #a93546;
            --bs-btn-active-border-color: #a93546;
        }
        .btn-outline-primary {
            --bs-btn-color: #d94f63;
            --bs-btn-border-color: #d94f63;
            --bs-btn-hover-bg: #d94f63;
            --bs-btn-hover-border-color: #d94f63;
            --bs-btn-active-bg: #bf3f51;
            --bs-btn-active-border-color: #bf3f51;
        }
        @media (max-width: 1199px) {
            .sidebar { position: relative; width: 100%; height: auto; }
            .content-area { margin-left: 0; padding-top: 18px; }
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="sidebar-logo">BI</div>
            <div class="sidebar-title text-white">BI SUPPORT</div>
            <div class="sidebar-subtitle">Telkom Ridar</div>
        </div>
        <p class="sidebar-caption">Monitoring Pengawalan Order Kendala Teknik</p>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}" href="{{ route('import.index') }}">Upload Excel</a>
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Laporan</a>
            <a class="nav-link {{ request()->routeIs('analysis.*') ? 'active' : '' }}" href="{{ route('analysis.index') }}">Analisis dan Rekomendasi</a>
        </nav>
    </aside>
    <main class="content-area">
        @if(session('status'))
            <div class="alert alert-success rounded-4 shadow-sm">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
