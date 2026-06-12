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
            background: #f1f5fb;
        }
        .app-shell {
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background: #061d44;
            color: #ffffff;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            padding: 24px 20px;
            overflow-y: auto;
        }
        .sidebar .brand {
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 2rem;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.85);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 8px;
        }
        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: #ffffff;
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
            box-shadow: 0 16px 30px rgba(4, 45, 91, 0.08);
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
            box-shadow: 0 16px 28px rgba(4, 45, 91, 0.06);
            border: 0;
        }
        .panel-card .card-header {
            background: transparent;
            border-bottom: 0;
            font-weight: 700;
            color: #162b4d;
        }
        .upload-drop {
            border: 2px dashed #c5d6eb;
            min-height: 210px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            color: #3e4d6d;
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
            box-shadow: inset 0 0 0 1px rgba(56, 89, 152, 0.08);
        }
        .status-box .status-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(56, 89, 152, 0.08);
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
        <div class="brand text-white">BI SUPPORT<br><small class="text-muted">Telkom Ridar</small></div>
        <p class="text-white-50">Monitoring Pengawalan Order Kendala Teknik</p>
        <nav class="nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard.index') ? 'active' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a>
            <a class="nav-link {{ request()->routeIs('import.*') ? 'active' : '' }}" href="{{ route('import.index') }}">Upload Excel</a>
            <a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">Laporan</a>
            <a class="nav-link" href="#">Pengaturan</a>
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
