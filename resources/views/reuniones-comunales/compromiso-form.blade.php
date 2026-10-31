@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Editar Compromiso</h1>
        <a href="{{ route('comunales.show', $reunion->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted">
                Reunión: <strong>{{ $reunion->titulo }}</strong> ({{ $reunion->fecha->format('d/m/Y') }})
            </p>
            <form action="{{ route('comunales.compromisos.update', [$reunion->id, $compromiso->id]) }}"
                  method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="descripcion" class="form-label">
                        Descripción del compromiso
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('descripcion') is-invalid @enderror"
                           id="descripcion"
                           name="descripcion"
                           maxlength="500"
                           value="{{ old('descripcion', $compromiso->descripcion) }}"
                           required>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="fecha_limite" class="form-label">
                        Fecha límite de entrega institucional
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="date"
                           class="form-control @error('fecha_limite') is-invalid @enderror"
                           id="fecha_limite"
                           name="fecha_limite"
                           value="{{ old('fecha_limite', $compromiso->fecha_limite->format('Y-m-d')) }}"
                           required>
                    @error('fecha_limite')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('comunales.show', $reunion->id) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i> Actualizar Compromiso
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection