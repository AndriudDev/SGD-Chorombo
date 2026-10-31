@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="w-100">
            {{-- Encabezado --}}
            <div class="text-center mb-5">
                <div class="d-inline-flex align-items-center gap-3 mb-3">
                    <div class="sgd-avatar-lg">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
                <h1 class="display-6 fw-semibold text-primary">
                    Bienvenido, <span class="text-break-word d-inline-block">{{ auth()->user()->name }}</span>
                </h1>
                <p class="lead text-muted fs-6 mb-0">Panel de administración — SGD-Chorombo</p>
            </div>

            {{-- Tarjetas de módulos --}}
            <div class="row g-4 justify-content-center">

                @if (auth()->user()->hasRole('administracion'))
                    <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                        <div class="card sgd-module-card border-0 h-100">
                            <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                                <div class="sgd-module-icon mb-3">
                                    <i class="bi bi-people-fill"></i>
                                </div>
                                <h4 class="card-title fw-semibold mb-1">Usuarios</h4>
                                <p class="card-text text-muted small mb-4">Administra las cuentas y accesos del personal.</p>
                                <a href="{{ route('users.index') }}" class="btn sgd-btn-module mt-auto">
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    Gestionar usuarios
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                    <div class="card sgd-module-card border-0 h-100">
                        <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                            <div class="sgd-module-icon mb-3">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <h4 class="card-title fw-semibold mb-1">Memos y Oficios</h4>
                            <p class="card-text text-muted small mb-4">Registra, consulta y administra la correspondencia.</p>
                            <a href="{{ route('memos.index') }}" class="btn sgd-btn-module mt-auto">
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Gestionar documentos
                            </a>
                        </div>
                    </div>
                </div>

                @if (auth()->user()->hasAnyRole(['administracion', 'directora']))
                    <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                        <div class="card sgd-module-card border-0 h-100">
                            <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                                <div class="sgd-module-icon mb-3">
                                    <i class="bi bi-calendar-check"></i>
                                </div>
                                <h4 class="card-title fw-semibold mb-1">Permisos</h4>
                                <p class="card-text text-muted small mb-4">Gestiona solicitudes y el historial de permisos.</p>
                                <a href="{{ route('permisos.index') }}" class="btn sgd-btn-module mt-auto">
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    Gestionar permisos
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                    <div class="card sgd-module-card border-0 h-100">
                        <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                            <div class="sgd-module-icon mb-3">
                                <i class="bi bi-person-lines-fill"></i>
                            </div>
                            <h4 class="card-title fw-semibold mb-1">Citaciones</h4>
                            <p class="card-text text-muted small mb-4">Emite y da seguimiento a citaciones a apoderados.</p>
                            <a href="{{ route('citaciones.index') }}" class="btn sgd-btn-module mt-auto">
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Ver citaciones
                            </a>
                        </div>
                    </div>
                </div>

                @if (auth()->user()->hasAnyRole(['convivencia', 'directora', 'utp']))
                    <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                        <div class="card sgd-module-card border-0 h-100">
                            <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                                <div class="sgd-module-icon mb-3">
                                    <i class="bi bi-lock-fill"></i>
                                </div>
                                <h4 class="card-title fw-semibold mb-1">Actas Confidenciales</h4>
                                <p class="card-text text-muted small mb-4">Resguardo de acuerdos y compromisos con apoderados.</p>
                                <a href="{{ route('actas.index') }}" class="btn sgd-btn-module mt-auto">
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    Ver actas
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-sm-6 col-lg-4 d-flex align-items-stretch">
                    <div class="card sgd-module-card border-0 h-100">
                        <div class="card-body d-flex flex-column align-items-center text-center py-5 px-4">
                            <div class="sgd-module-icon mb-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <h4 class="card-title fw-semibold mb-1">Reuniones Comunales</h4>
                            <p class="card-text text-muted small mb-4">Minutas comunales, directrices y compromisos con plazo.</p>
                            <a href="{{ route('comunales.index') }}" class="btn sgd-btn-module mt-auto">
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Ver reuniones
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection