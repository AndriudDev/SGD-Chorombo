@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($permiso) && $permiso !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">{{ $isEdit ? 'Editar Permiso' : 'Solicitar Permiso' }}</h1>
        <a href="{{ route('permisos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('permisos.update', $permiso->id) : route('permisos.store') }}"
                  method="POST">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                {{-- Fecha Inicio --}}
                <div class="mb-3">
                    <label for="fecha_inicio" class="form-label">
                        Fecha de inicio
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="date"
                           class="form-control @error('fecha_inicio') is-invalid @enderror"
                           id="fecha_inicio"
                           name="fecha_inicio"
                           value="{{ old('fecha_inicio', $isEdit ? $permiso->fecha_inicio->format('Y-m-d') : '') }}"
                           required>
                    @error('fecha_inicio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Fecha Fin --}}
                <div class="mb-3">
                    <label for="fecha_fin" class="form-label">
                        Fecha de término
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="date"
                           class="form-control @error('fecha_fin') is-invalid @enderror"
                           id="fecha_fin"
                           name="fecha_fin"
                           value="{{ old('fecha_fin', $isEdit ? $permiso->fecha_fin->format('Y-m-d') : '') }}"
                           required>
                    @error('fecha_fin')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Tipo Jornada --}}
                <div class="mb-3">
                    <label for="tipo_jornada" class="form-label">
                        Tipo de jornada
                        <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-select @error('tipo_jornada') is-invalid @enderror"
                            id="tipo_jornada"
                            name="tipo_jornada"
                            required>
                        <option value="">Seleccione...</option>
                        <option value="completa" {{ old('tipo_jornada', $isEdit ? $permiso->tipo_jornada : '') === 'completa' ? 'selected' : '' }}>Completa</option>
                        <option value="media" {{ old('tipo_jornada', $isEdit ? $permiso->tipo_jornada : '') === 'media' ? 'selected' : '' }}>Media</option>
                    </select>
                    @error('tipo_jornada')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Motivo --}}
                <div class="mb-3">
                    <label for="motivo" class="form-label">
                        Motivo
                        <span class="required-asterisk">*</span>
                    </label>
                    <textarea class="form-control @error('motivo') is-invalid @enderror"
                              id="motivo"
                              name="motivo"
                              rows="4"
                              placeholder="Describa el motivo del permiso"
                              required>{{ old('motivo', $isEdit ? $permiso->motivo : '') }}</textarea>
                    @error('motivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('permisos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEdit ? 'Actualizar Permiso' : 'Solicitar Permiso' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection