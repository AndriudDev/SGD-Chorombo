<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Correspondencia\MemoController;
use App\Http\Controllers\RRHH\PermisoAdminController;
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

// Permisos Administrativos
Route::middleware('auth')->group(function () {
    Route::get('permisos-administrativos', [PermisoAdminController::class, 'index'])->name('permisos.index');
    Route::get('permisos-administrativos/create', [PermisoAdminController::class, 'create'])->name('permisos.create');
    Route::post('permisos-administrativos', [PermisoAdminController::class, 'store'])->name('permisos.store');
    Route::get('permisos-administrativos/{permiso}/edit', [PermisoAdminController::class, 'edit'])->name('permisos.edit');
    Route::put('permisos-administrativos/{permiso}', [PermisoAdminController::class, 'update'])->name('permisos.update');
    Route::delete('permisos-administrativos/{permiso}', [PermisoAdminController::class, 'destroy'])->name('permisos.destroy');
    Route::post('permisos-administrativos/{permiso}/aprobar', [PermisoAdminController::class, 'aprobar'])->name('permisos.aprobar');
    Route::post('permisos-administrativos/{permiso}/rechazar', [PermisoAdminController::class, 'rechazar'])->name('permisos.rechazar');
});
