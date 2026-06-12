<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtlController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Root redirect ke dashboard
Route::get('/', fn() => redirect()->route('dashboard.index'));

// ── Dashboard BI ──────────────────────────────────────────────────────────────
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

// ── Import / ETL ──────────────────────────────────────────────────────────────
Route::prefix('import')->name('import.')->group(function () {
    Route::get('/',            [EtlController::class, 'showImport'])->name('index');
    Route::post('/preview',    [EtlController::class, 'preview'])->name('preview');
    Route::post('/process',    [EtlController::class, 'process'])->name('process');
    Route::get('/result/{id}', [EtlController::class, 'result'])->name('result');
});

// ── Laporan ───────────────────────────────────────────────────────────────────
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/',             [ReportController::class, 'index'])->name('index');
    Route::get('/export/excel', [ReportController::class, 'exportExcel'])->name('export.excel');
    Route::get('/export/pdf',   [ReportController::class, 'exportPdf'])->name('export.pdf');
});