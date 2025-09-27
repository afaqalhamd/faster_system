<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\TrackingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Here are the public routes that don't require authentication
|
*/

Route::get('/tracking', [TrackingController::class, 'index'])->name('public.tracking.index');
Route::post('/tracking/search', [TrackingController::class, 'search'])->name('public.tracking.search');
Route::get('/tracking/document/{id}', [TrackingController::class, 'downloadDocument'])->name('public.tracking.document.download');
