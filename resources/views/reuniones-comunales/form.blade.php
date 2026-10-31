@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($reunion) && $reunion !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">{{ $isEdit ? 'Editar Reunión Comunal' : 'Nueva Reunión Comunal' }}</h1>
        <a href="{{ route('comunales.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('comunales.update', $reunion->id) : route('comunales.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="titulo" class="form-label">
                        Título
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('titulo') is-invalid @enderror"
                           id="titulo"
                           name="titulo"
                           value="{{ old('titulo', $isEdit ? $reunion->titulo : '') }}"
                           placeholder="Ej: Reunión de directores — planificación trimestral"
                           required>
                    @error('titulo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="categoria" class="form-label">
                            Categoría
                            <span class="required-asterisk">*</span>
                        </label>
                        <select class="form-select @error('categoria') is-invalid @enderror"
                                id="categoria"
                                name="categoria"
                                required>
                            <option value="">Seleccione...</option>
                            @foreach (\App\Models\ReunionComunal::CATEGORIAS as $clave => $etiqueta)
                                <option value="{{ $clave }}" {{ old('categoria', $isEdit ? $reunion->categoria : '') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                        @error('categoria')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="fecha" class="form-label">
                            Fecha de la reunión
                            <span class="required-asterisk">*</span>
                        </label>
                        <input type="date"
                               class="form-control @error('fecha') is-invalid @enderror"
                               id="fecha"
                               name="fecha"
                               value="{{ old('fecha', $isEdit ? $reunion->fecha->format('Y-m-d') : '') }}"
                               required>
                        @error('fecha')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción / acuerdos generales</label>
                    <textarea class="form-control @error('descripcion') is-invalid @enderror"
                              id="descripcion"
                              name="descripcion"
                              rows="4"
                              placeholder="Resumen de la reunión, directrices comunicadas...">{{ old('descripcion', $isEdit ? $reunion->descripcion : '') }}</textarea>
                    @error('descripcion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="archivo" class="form-label">
                        Documento (minuta, orientación o presentación)
                        @unless ($isEdit)
                            <span class="required-asterisk">*</span>
                        @endunless
                    </label>
                    @if ($isEdit && $reunion->archivo)
                        <div class="mb-2">
                            <small class="text-muted">Archivo actual:</small>
                            <a href="{{ route('comunales.download', $reunion->id) }}"
                               class="text-decoration-none ms-2">
                                <i class="bi bi-file-earmark-text-fill me-1"></i>
                                {{ basename($reunion->archivo) }}
                            </a>
                        </div>
                    @endif
                    <input type="file"
                           class="form-control @error('archivo') is-invalid @enderror"
                           id="archivo"
                           name="archivo"
                           accept=".pdf,.ppt,.pptx,.doc,.docx"
                           @unless ($isEdit) required @endunless>
                    @error('archivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Formatos permitidos: PDF, PPT, PPTX, DOC, DOCX. Tamaño máximo: 15 MB.</div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('comunales.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEdit ? 'Actualizar Reunión' : 'Crear Reunión' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection