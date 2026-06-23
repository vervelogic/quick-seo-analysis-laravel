<?php

use App\Http\Controllers\KeywordFocusAuditController;
use App\Http\Controllers\KeywordFocusReportSectionController;
use App\Http\Controllers\LeadCaptureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home.index'))->name('home');
Route::post('/scan', ScanController::class)->middleware('throttle:scan')->name('scan.store');
Route::get('/keyword-focus-audit', [KeywordFocusAuditController::class, 'create'])->name('keyword-focus.create');
Route::post('/keyword-focus-audit', [KeywordFocusAuditController::class, 'store'])->middleware('throttle:scan')->name('keyword-focus.store');
Route::get('/report/{scan:uuid}', ReportController::class)->name('report.show');
Route::get('/report/{scan:uuid}/keyword-focus', KeywordFocusReportSectionController::class)->name('report.keyword-focus');
Route::post('/lead-capture', LeadCaptureController::class)->middleware('throttle:lead-capture')->name('lead.capture');
