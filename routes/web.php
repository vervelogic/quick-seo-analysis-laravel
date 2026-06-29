<?php

use App\Http\Controllers\ClientAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\KeywordFocusAuditController;
use App\Http\Controllers\KeywordFocusReportSectionController;
use App\Http\Controllers\LeadCaptureController;
use App\Http\Controllers\LegacyAccountClaimController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\WhiteLabelReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home.index'))->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [ClientAuthController::class, 'create'])->name('login');
    Route::post('/login', [ClientAuthController::class, 'store'])->name('login.store');
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::post('/logout', [ClientAuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/scans', [DashboardController::class, 'scans'])->name('dashboard.scans');
    Route::get('/dashboard/reports', [DashboardController::class, 'reports'])->name('dashboard.reports');
    Route::get('/dashboard/reports/{scan:uuid}/white-label-pdf', [WhiteLabelReportController::class, 'show'])->name('dashboard.reports.white-label-pdf');
    Route::get('/dashboard/projects', [DashboardController::class, 'projects'])->name('dashboard.projects');
    Route::post('/dashboard/projects', [DashboardController::class, 'storeProject'])->name('dashboard.projects.store');
    Route::patch('/dashboard/projects/{project}', [DashboardController::class, 'updateProject'])->name('dashboard.projects.update');
    Route::get('/dashboard/branding', [DashboardController::class, 'branding'])->name('dashboard.branding');
    Route::patch('/dashboard/branding', [DashboardController::class, 'updateBranding'])->name('dashboard.branding.update');
    Route::get('/dashboard/usage', [DashboardController::class, 'usage'])->name('dashboard.usage');
    Route::post('/dashboard/legacy-accounts/{legacyAccount}/claim', LegacyAccountClaimController::class)->name('dashboard.legacy-accounts.claim');
});

Route::post('/scan', ScanController::class)->middleware('throttle:scan')->name('scan.store');
Route::get('/keyword-focus-audit', [KeywordFocusAuditController::class, 'create'])->name('keyword-focus.create');
Route::post('/keyword-focus-audit', [KeywordFocusAuditController::class, 'store'])->middleware('throttle:scan')->name('keyword-focus.store');
Route::get('/report/{scan:uuid}', ReportController::class)->name('report.show');
Route::get('/report/{scan:uuid}/keyword-focus', KeywordFocusReportSectionController::class)->name('report.keyword-focus');
Route::post('/lead-capture', LeadCaptureController::class)->middleware('throttle:lead-capture')->name('lead.capture');
