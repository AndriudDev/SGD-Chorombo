@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-center align-items-center" style="min-height: 60vh;">
        <div class="text-center">
            <h1 class="display-5 fw-bold text-primary mb-3">
                <i class="bi bi-building me-2"></i>Bienvenido, {{ auth()->user()->name }}
            </h1>
            <p class="lead text-muted mb-4">Estás en el panel de administración de SGD-Chorombo.</p>
            <a href="{{ route('users.index') }}" class="btn btn-primary btn-lg">
                <i class="bi bi-people-fill me-2"></i>Ir a Gestión de Usuarios
            </a>
        </div>
    </div>
@endsection