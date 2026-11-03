<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PengaduController;
use App\Http\Controllers\Api\PengaduanController as ApiPengaduanController;
use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\LampiranController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\InstansiController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\StatistikController;
use App\Http\Controllers\Api\AdminPengaduanController;
use App\Http\Controllers\Api\AdminInstansiController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminLaporanController;
use App\Http\Controllers\Api\InstansiPengaduanController;

// publik
Route::post('/pengadu/login', [PengaduController::class, 'login'])
     ->middleware('throttle:60,60,pengadu-login-ip');
Route::post('/admin/login', [AdminAuthController::class, 'login'])
    ->middleware('throttle:3,5,admin-login');
Route::get('/instansi', [InstansiController::class, 'index']);

// masyarakat
Route::middleware('auth:pengadu')->group(function () {
    Route::post('/pengadu/logout', [PengaduController::class, 'logout']);
    Route::get('/pengadu/me', [PengaduController::class, 'me']);
    Route::post('/pengadu/push-subscribe', [PushSubscriptionController::class, 'store']);
    Route::delete('/pengadu/push-subscribe', [PushSubscriptionController::class, 'destroy']);

    Route::post('/pengaduan', [ApiPengaduanController::class, 'store']);
    Route::get('/pengaduan', [ApiPengaduanController::class, 'index']);
    Route::get('/pengaduan/{pengaduan:no_tiket}', [ApiPengaduanController::class, 'show']);
    Route::post('/pengaduan/{pengaduan:no_tiket}/evaluasi', [ApiPengaduanController::class, 'evaluasi']);
    Route::get('/pengaduan/{pengaduan:no_tiket}/bukti', [LampiranController::class, 'bukti']);
    Route::get('/pengaduan/{pengaduan:no_tiket}/lampiran/{lampiran}', [LampiranController::class, 'show']);

    Route::get('/pengadu/notifikasi', [NotifikasiController::class, 'index']);
    Route::post('/pengadu/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua']);
    Route::post('/pengadu/notifikasi/{id}/baca', [NotifikasiController::class, 'baca']);

    Route::get('/statistik', [StatistikController::class, 'index']);

    Route::get('/pengadu/push-key', [PushSubscriptionController::class, 'kunci']);
});

// admin
Route::middleware('auth:web')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/me', [AdminAuthController::class, 'me']);
    Route::post('/admin/ganti-password', [AdminAuthController::class, 'gantiPassword'])
        ->middleware('throttle:5,10,ganti-password');
});

Route::middleware(['auth:web', 'role:super_admin'])->group(function () {
    Route::get('/admin/pengaduan', [AdminPengaduanController::class, 'index']);
    Route::get('/admin/pengaduan/{pengaduan:no_tiket}', [AdminPengaduanController::class, 'show']);
    Route::post('/admin/pengaduan/{pengaduan:no_tiket}/verifikasi', [AdminPengaduanController::class, 'verifikasi']);
    Route::post('/admin/pengaduan/{pengaduan:no_tiket}/tolak', [AdminPengaduanController::class, 'tolak']);
    Route::post('/admin/pengaduan/{pengaduan:no_tiket}/teruskan', [AdminPengaduanController::class, 'teruskan']);
    Route::post('/admin/pengaduan/{pengaduan:no_tiket}/balasan', [AdminPengaduanController::class, 'balasan']);
    Route::get('/admin/pengaduan/{pengaduan:no_tiket}/bukti', [AdminPengaduanController::class, 'bukti']);
    Route::get('/admin/pengaduan/{pengaduan:no_tiket}/lampiran/{lampiran}', [AdminPengaduanController::class, 'lampiran']);

    Route::get('/admin/instansi', [AdminInstansiController::class, 'index']);
    Route::post('/admin/instansi', [AdminInstansiController::class, 'store']);
    Route::put('/admin/instansi/{instansi}', [AdminInstansiController::class, 'update']);
    Route::delete('/admin/instansi/{instansi}', [AdminInstansiController::class, 'destroy']);

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update']);
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);

    Route::get('/admin/dashboard', [AdminLaporanController::class, 'dashboard']);
    Route::get('/admin/export', [AdminLaporanController::class, 'export']);
});

Route::middleware(['auth:web', 'role:admin_instansi'])->group(function () {
    Route::get('/instansi/ringkasan', [InstansiPengaduanController::class, 'ringkasan']);
    Route::get('/instansi/pengaduan', [InstansiPengaduanController::class, 'index']);
    Route::get('/instansi/pengaduan/{pengaduan:no_tiket}', [InstansiPengaduanController::class, 'show']);
    Route::post('/instansi/pengaduan/{pengaduan:no_tiket}/balasan', [InstansiPengaduanController::class, 'balasan']);
    Route::post('/instansi/pengaduan/{pengaduan:no_tiket}/lampiran', [InstansiPengaduanController::class, 'unggahLampiran']);
    Route::delete('/instansi/pengaduan/{pengaduan:no_tiket}/lampiran/{lampiran}', [InstansiPengaduanController::class, 'hapusLampiran']);
    Route::get('/instansi/pengaduan/{pengaduan:no_tiket}/bukti', [InstansiPengaduanController::class, 'bukti']);
    Route::get('/instansi/pengaduan/{pengaduan:no_tiket}/lampiran/{lampiran}', [InstansiPengaduanController::class, 'lampiran']);
});