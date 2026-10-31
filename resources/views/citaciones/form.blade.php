@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($citacion) && $citacion !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">{{ $isEdit ? 'Editar Citación' : 'Nueva Citación' }}</h1>
        <a href="{{ route('citaciones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('citaciones.update', $citacion->id) : route('citaciones.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="estudiante_nombre" class="form-label">
                            Nombre del estudiante
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('estudiante_nombre') is-invalid @enderror"
                               id="estudiante_nombre"
                               name="estudiante_nombre"
                               value="{{ old('estudiante_nombre', $isEdit ? $citacion->estudiante_nombre : '') }}"
                               placeholder="Nombre completo del estudiante"
                               required>
                        @error('estudiante_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="estudiante_run" class="form-label">
                            RUN del estudiante
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('estudiante_run') is-invalid @enderror"
                               id="estudiante_run"
                               name="estudiante_run"
                               value="{{ old('estudiante_run', $isEdit ? $citacion->estudiante_run : '') }}"
                               placeholder="Ej: 12.345.678-9"
                               maxlength="12"
                               required>
                        @error('estudiante_run')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="apoderado_nombre" class="form-label">
                            Nombre del apoderado
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('apoderado_nombre') is-invalid @enderror"
                               id="apoderado_nombre"
                               name="apoderado_nombre"
                               value="{{ old('apoderado_nombre', $isEdit ? $citacion->apoderado_nombre : '') }}"
                               placeholder="Nombre del apoderado citado"
                               required>
                        @error('apoderado_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="funcionario_cita" class="form-label">
                            Funcionario que cita
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('funcionario_cita') is-invalid @enderror"
                               id="funcionario_cita"
                               name="funcionario_cita"
                               value="{{ old('funcionario_cita', $funcionarioDefault ?? '') }}"
                               placeholder="Nombre del funcionario que realiza la citación"
                               required>
                        @error('funcionario_cita')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="fecha" class="form-label">
                            Fecha de citación
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="date"
                               class="form-control @error('fecha') is-invalid @enderror"
                               id="fecha"
                               name="fecha"
                               value="{{ old('fecha', $isEdit ? $citacion->fecha->format('Y-m-d') : '') }}"
                               required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="hora" class="form-label">
                            Hora de citación
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="time"
                               class="form-control @error('hora') is-invalid @enderror"
                               id="hora"
                               name="hora"
                               value="{{ old('hora', $isEdit ? $citacion->hora : '') }}"
                               required>
                        @error('hora')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="motivo" class="form-label">
                            Motivo
                            <span class="required-asterisk">*</span>
                        </label>
                        <select class="form-select @error('motivo') is-invalid @enderror"
                                id="motivo"
                                name="motivo"
                                required>
                            <option value="">Seleccione...</option>
                            @foreach (\App\Models\Citacion::MOTIVOS as $clave => $etiqueta)
                                <option value="{{ $clave }}" {{ old('motivo', $isEdit ? $citacion->motivo : '') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('motivo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="archivo" class="form-label">Adjunto (copia firmada o colilla de recepción)</label>
                    @if ($isEdit && $citacion->archivo)
                        <div class="mb-2">
                            <small class="text-muted">Archivo actual:</small>
                            <a href="{{ route('citaciones.download', $citacion->id) }}"
                               class="text-decoration-none ms-2">
                                <i class="bi bi-file-earmark-text-fill me-1"></i>
                                {{ basename($citacion->archivo) }}
                            </a>
                        </div>
                    @endif
                    <input type="file"
                           class="form-control @error('archivo') is-invalid @enderror"
                           id="archivo"
                           name="archivo"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                    @error('archivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Formatos permitidos: PDF, JPG, PNG, DOC, DOCX. Tamaño máximo: 15 MB. Opcional.</div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('citaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEdit ? 'Actualizar Citación' : 'Registrar Citación' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection