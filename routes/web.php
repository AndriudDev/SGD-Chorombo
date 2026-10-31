<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Convivencia\ActaConfidencialController;
use App\Http\Controllers\Convivencia\CitacionController;
use App\Http\Controllers\Correspondencia\MemoController;
use App\Http\Controllers\RRHH\PermisoAdminController;
use App\Http\Controllers\UTP\CompromisoController;
use App\Http\Controllers\UTP\ReunionComunalController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::post('logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/', function () {
    return redirect()->route('login');
});

// Admin panel — solo rol administración
Route::middleware(['auth', 'role:administracion'])->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('users.index'))->name('admin.index');

    Route::resource('users', UserController::class)->except('show');
});

// Correspondencia — Memos y Oficios
Route::middleware('auth')->prefix('correspondencia')->group(function () {
    Route::get('memos', [MemoController::class, 'index'])->name('memos.index');
    Route::get('memos/create', [MemoController::class, 'create'])->name('memos.create');
    Route::post('memos', [MemoController::class, 'store'])->name('memos.store');
    Route::get('memos/{memo}', [MemoController::class, 'show'])->name('memos.show');
    Route::get('memos/{memo}/edit', [MemoController::class, 'edit'])->name('memos.edit');
    Route::put('memos/{memo}', [MemoController::class, 'update'])->name('memos.update');
    Route::delete('memos/{memo}', [MemoController::class, 'destroy'])->name('memos.destroy');
    Route::get('memos/{memo}/download', [MemoController::class, 'download'])->name('memos.download');
});

// Permisos Administrativos — todo el equipo directivo (HU-03)
Route::middleware(['auth', 'role:administracion|directora|secretaria|utp|convivencia'])->group(function () {
    Route::get('permisos-administrativos', [PermisoAdminController::class, 'index'])->name('permisos.index');
    Route::get('permisos-administrativos/create', [PermisoAdminController::class, 'create'])->name('permisos.create');
    Route::post('permisos-administrativos', [PermisoAdminController::class, 'store'])->name('permisos.store');
});

// Editar/Eliminar + Aprobar/Rechazar — solo Dirección y Administración
Route::middleware(['auth', 'role:administracion|directora'])->group(function () {
    Route::get('permisos-administrativos/{permiso}/edit', [PermisoAdminController::class, 'edit'])->name('permisos.edit');
    Route::put('permisos-administrativos/{permiso}', [PermisoAdminController::class, 'update'])->name('permisos.update');
    Route::delete('permisos-administrativos/{permiso}', [PermisoAdminController::class, 'destroy'])->name('permisos.destroy');
    Route::post('permisos-administrativos/{permiso}/aprobar', [PermisoAdminController::class, 'aprobar'])->name('permisos.aprobar');
    Route::post('permisos-administrativos/{permiso}/rechazar', [PermisoAdminController::class, 'rechazar'])->name('permisos.rechazar');
});

// Citaciones a Apoderados (HU-04)
Route::middleware('auth')->prefix('citaciones')->group(function () {
    // Emisión y seguimiento — Convivencia, Secretaría y Dirección
    Route::middleware('role:convivencia|secretaria|directora')->group(function () {
        Route::get('create', [CitacionController::class, 'create'])->name('citaciones.create');
        Route::post('/', [CitacionController::class, 'store'])->name('citaciones.store');
        Route::get('{citacion}/edit', [CitacionController::class, 'edit'])->name('citaciones.edit');
        Route::put('{citacion}', [CitacionController::class, 'update'])->name('citaciones.update');
        Route::patch('{citacion}/estado', [CitacionController::class, 'actualizarEstado'])->name('citaciones.estado');
        Route::delete('{citacion}', [CitacionController::class, 'destroy'])->name('citaciones.destroy');
    });

    // Consulta y descarga — todo el equipo directivo
    Route::middleware('role:administracion|directora|secretaria|utp|convivencia')->group(function () {
        Route::get('/', [CitacionController::class, 'index'])->name('citaciones.index');
        Route::get('{citacion}', [CitacionController::class, 'show'])->name('citaciones.show');
        Route::get('{citacion}/download', [CitacionController::class, 'download'])->name('citaciones.download');
    });
});

// Actas Confidenciales de Acuerdos con Apoderados (HU-05) — Convivencia y Dirección (UTP solo consulta ámbito pedagógico)
Route::middleware('auth')->prefix('actas-confidenciales')->group(function () {
    // Carga y gestión — Convivencia y Dirección
    Route::middleware('role:convivencia|directora')->group(function () {
        Route::get('create', [ActaConfidencialController::class, 'create'])->name('actas.create');
        Route::post('/', [ActaConfidencialController::class, 'store'])->name('actas.store');
        Route::get('{acta}/edit', [ActaConfidencialController::class, 'edit'])->name('actas.edit');
        Route::put('{acta}', [ActaConfidencialController::class, 'update'])->name('actas.update');
        Route::delete('{acta}', [ActaConfidencialController::class, 'destroy'])->name('actas.destroy');
    });

    // Consulta y descarga — Convivencia, Dirección y UTP (acotado en controlador)
    Route::middleware('role:convivencia|directora|utp')->group(function () {
        Route::get('/', [ActaConfidencialController::class, 'index'])->name('actas.index');
        Route::get('{acta}/download', [ActaConfidencialController::class, 'download'])->name('actas.download');
    });
});

// Reuniones Comunales e Interinstitucionales (HU-06)
Route::middleware('auth')->prefix('reuniones-comunales')->group(function () {
    // Carga y gestión de reuniones y compromisos — UTP y Dirección
    Route::middleware('role:utp|directora')->group(function () {
        Route::get('create', [ReunionComunalController::class, 'create'])->name('comunales.create');
        Route::post('/', [ReunionComunalController::class, 'store'])->name('comunales.store');
        Route::get('{reunion}/edit', [ReunionComunalController::class, 'edit'])->name('comunales.edit');
        Route::put('{reunion}', [ReunionComunalController::class, 'update'])->name('comunales.update');
        Route::delete('{reunion}', [ReunionComunalController::class, 'destroy'])->name('comunales.destroy');
        Route::post('{reunion}/compromisos', [CompromisoController::class, 'store'])->name('comunales.compromisos.store');
        Route::get('{reunion}/compromisos/{compromiso}/edit', [CompromisoController::class, 'edit'])->name('comunales.compromisos.edit');
        Route::put('{reunion}/compromisos/{compromiso}', [CompromisoController::class, 'update'])->name('comunales.compromisos.update');
        Route::delete('{reunion}/compromisos/{compromiso}', [CompromisoController::class, 'destroy'])->name('comunales.compromisos.destroy');
    });

    // Consulta y descarga — todo el equipo directivo
    Route::middleware('role:administracion|directora|secretaria|utp|convivencia')->group(function () {
        Route::get('/', [ReunionComunalController::class, 'index'])->name('comunales.index');
        Route::get('{reunion}', [ReunionComunalController::class, 'show'])->name('comunales.show');
        Route::get('{reunion}/download', [ReunionComunalController::class, 'download'])->name('comunales.download');
    });
});
