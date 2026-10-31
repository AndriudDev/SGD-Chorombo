@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($acta) && $acta !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <div>
            <h1 class="h3 mb-1">{{ $isEdit ? 'Editar Acta Confidencial' : 'Nueva Acta Confidencial' }}</h1>
            <span class="badge bg-dark rounded-pill">
                <i class="bi bi-lock-fill me-1"></i> Documento Confidencial / Sensible
            </span>
        </div>
        <a href="{{ route('actas.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('actas.update', $acta->id) : route('actas.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="titulo" class="form-label">
                        Título del acta
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('titulo') is-invalid @enderror"
                           id="titulo"
                           name="titulo"
                           value="{{ old('titulo', $isEdit ? $acta->titulo : '') }}"
                           placeholder="Ej: Acta de acuerdo de convivencia — Estudiante X"
                           required>
                    @error('titulo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="categoria" class="form-label">
                            Categoría de resguardo
                            <span class="required-asterisk">*</span>
                        </label>
                        <select class="form-select @error('categoria') is-invalid @enderror"
                                id="categoria"
                                name="categoria"
                                required>
                            <option value="">Seleccione...</option>
                            @foreach (\App\Models\ActaConfidencial::CATEGORIAS as $clave => $etiqueta)
                                <option value="{{ $clave }}" {{ old('categoria', $isEdit ? $acta->categoria : '') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="ambito" class="form-label">
                            Ámbito del acuerdo
                            <span class="required-asterisk">*</span>
                        </label>
                        <select class="form-select @error('ambito') is-invalid @enderror"
                                id="ambito"
                                name="ambito"
                                required>
                            <option value="">Seleccione...</option>
                            @foreach (\App\Models\ActaConfidencial::AMBITOS as $clave => $etiqueta)
                                <option value="{{ $clave }}" {{ old('ambito', $isEdit ? $acta->ambito : '') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('ambito')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Las actas de ámbito pedagógico también son consultables por la Jefatura de UTP.</div>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="fecha" class="form-label">
                            Fecha del acta
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="date"
                               class="form-control @error('fecha') is-invalid @enderror"
                               id="fecha"
                               name="fecha"
                               value="{{ old('fecha', $isEdit ? $acta->fecha->format('Y-m-d') : '') }}"
                               required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="estudiante_nombre" class="form-label">
                            Nombre del estudiante
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('estudiante_nombre') is-invalid @enderror"
                               id="estudiante_nombre"
                               name="estudiante_nombre"
                               value="{{ old('estudiante_nombre', $isEdit ? $acta->estudiante_nombre : '') }}"
                               placeholder="Nombre completo del estudiante"
                               required>
                        @error('estudiante_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="estudiante_run" class="form-label">
                            RUN del estudiante
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('estudiante_run') is-invalid @enderror"
                               id="estudiante_run"
                               name="estudiante_run"
                               value="{{ old('estudiante_run', $isEdit ? $acta->estudiante_run : '') }}"
                               placeholder="Ej: 12.345.678-9"
                               maxlength="12"
                               required>
                        @error('estudiante_run')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="apoderado_nombre" class="form-label">
                            Nombre del apoderado
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="text"
                               class="form-control @error('apoderado_nombre') is-invalid @enderror"
                               id="apoderado_nombre"
                               name="apoderado_nombre"
                               value="{{ old('apoderado_nombre', $isEdit ? $acta->apoderado_nombre : '') }}"
                               placeholder="Nombre del apoderado"
                               required>
                        @error('apoderado_nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="archivo" class="form-label">
                        Archivo del acta firmada
                        @unless ($isEdit)
                            <span class="required-asterisk">*</span>
                        @endunless
                    </label>
                    @if ($isEdit && $acta->archivo)
                        <div class="mb-2">
                            <small class="text-muted">Archivo actual:</small>
                            <a href="{{ route('actas.download', $acta->id) }}"
                               class="text-decoration-none ms-2">
                                <i class="bi bi-file-earmark-text-fill me-1"></i>
                                {{ basename($acta->archivo) }}
                            </a>
                        </div>
                    @endif
                    <input type="file"
                           class="form-control @error('archivo') is-invalid @enderror"
                           id="archivo"
                           name="archivo"
                           accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                           @unless ($isEdit) required @endunless>
                    @error('archivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Formatos permitidos: PDF, JPG, PNG, DOC, DOCX. Tamaño máximo: 15 MB.</div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('actas.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEdit ? 'Actualizar Acta' : 'Crear Acta' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection