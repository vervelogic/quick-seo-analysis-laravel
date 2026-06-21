<?php

use App\Http\Controllers\LeadCaptureController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home.index'))->name('home');
Route::post('/scan', ScanController::class)->middleware('throttle:scan')->name('scan.store');
Route::get('/report/{scan:uuid}', ReportController::class)->name('report.show');
Route::post('/lead-capture', LeadCaptureController::class)->middleware('throttle:lead-capture')->name('lead.capture');
