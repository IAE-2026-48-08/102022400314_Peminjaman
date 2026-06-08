<?php

use App\Http\Controllers\Api\LoanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Peminjaman Service
|--------------------------------------------------------------------------
| Semua route diproteksi dengan ApiKeyMiddleware (X-IAE-KEY header)
| Sesuai Standard Integration Contract IAE-T2
*/

Route::middleware('api.key')->prefix('v1')->group(function () {
    // Loans Resource
    Route::get('/loans',       [LoanController::class, 'index']);
    Route::get('/loans/{id}',  [LoanController::class, 'show']);
    Route::post('/loans',      [LoanController::class, 'store']);
});
