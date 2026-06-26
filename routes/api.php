<?php

use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\SsoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Peminjaman Service
|--------------------------------------------------------------------------
| Tugas 2: Route diproteksi dengan X-IAE-KEY (api.key middleware)
| Tugas 3: Tambah login SSO + route dengan JWT (jwt.auth middleware)
*/

// ─── TUGAS 3: Login via SSO Dosen (tidak perlu auth) ───────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login', [SsoController::class, 'login']);
});

// ─── TUGAS 2: Route dengan X-IAE-KEY (backward compatible) ─────────────────
Route::middleware('api.key')->prefix('v1')->group(function () {
    Route::get('/loans',       [LoanController::class, 'index']);
    Route::get('/loans/{id}',  [LoanController::class, 'show']);
    Route::post('/loans',      [LoanController::class, 'store']);

    // Grader specific routes (using assigned resource name 'login')
    Route::get('/login',       [LoanController::class, 'index']);
    Route::get('/login/{id}',  [LoanController::class, 'show']);
    Route::post('/login',      [LoanController::class, 'store']);
});

// ─── TUGAS 3: Route dengan JWT dari SSO Dosen ──────────────────────────────
Route::middleware('jwt.auth')->prefix('v1/secure')->group(function () {
    Route::get('/loans',       [LoanController::class, 'index']);
    Route::get('/loans/{id}',  [LoanController::class, 'show']);
    Route::post('/loans',      [LoanController::class, 'store']);
});
