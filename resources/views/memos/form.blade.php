@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($memo) && $memo !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">{{ $isEdit ? 'Editar Memo/Oficio' : 'Nuevo Memo/Oficio' }}</h1>
        <a href="{{ route('memos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('memos.update', $memo->id) : route('memos.store') }}"
                  method="POST"
                  enctype="multipart/form-data">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                {{-- Folio --}}
                <div class="mb-3">
                    <label for="folio" class="form-label">
                        Folio
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('folio') is-invalid @enderror"
                           id="folio"
                           name="folio"
                           value="{{ old('folio', $isEdit ? $memo->folio : '') }}"
                           placeholder="Ej: MEMO-2025-001"
                           required>
                    @error('folio')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Tipo --}}
                <div class="mb-3">
                    <label for="tipo" class="form-label">
                        Tipo
                        <span class="required-asterisk">*</span>
                    </label>
                    <select class="form-select @error('tipo') is-invalid @enderror"
                            id="tipo"
                            name="tipo"
                            required>
                        <option value="">Seleccione...</option>
                        <option value="memo" {{ old('tipo', $isEdit ? $memo->tipo : '') === 'memo' ? 'selected' : '' }}>Memo</option>
                        <option value="oficio" {{ old('tipo', $isEdit ? $memo->tipo : '') === 'oficio' ? 'selected' : '' }}>Oficio</option>
                    </select>
                    @error('tipo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Fecha --}}
                <div class="mb-3">
                    <label for="fecha" class="form-label">
                        Fecha
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="date"
                           class="form-control @error('fecha') is-invalid @enderror"
                           id="fecha"
                           name="fecha"
                           value="{{ old('fecha', $isEdit ? $memo->fecha->format('Y-m-d') : '') }}"
                           required>
                    @error('fecha')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Destinatario --}}
                <div class="mb-3">
                    <label for="destinatario" class="form-label">
                        Destinatario
                        <span class="required-asterisk">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('destinatario') is-invalid @enderror"
                           id="destinatario"
                           name="destinatario"
                           value="{{ old('destinatario', $isEdit ? $memo->destinatario : '') }}"
                           placeholder="Nombre del destinatario"
                           required>
                    @error('destinatario')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Asunto --}}
                <div class="mb-3">
                    <label for="asunto" class="form-label">
                        Asunto
                        <span class="required-asterisk">*</span>
                    </label>
                    <textarea class="form-control @error('asunto') is-invalid @enderror"
                              id="asunto"
                              name="asunto"
                              rows="4"
                              placeholder="Describa el asunto del memo u oficio"
                              required>{{ old('asunto', $isEdit ? $memo->asunto : '') }}</textarea>
                    @error('asunto')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Archivo --}}
                <div class="mb-3">
                    <label for="archivo" class="form-label">Archivo adjunto</label>
                    @if ($isEdit && $memo->archivo)
                        <div class="mb-2">
                            <small class="text-muted">Archivo actual:</small>
                            <a href="{{ route('memos.download', $memo->id) }}"
                               class="text-decoration-none ms-2">
                                <i class="bi bi-file-earmark-text-fill me-1"></i>
                                {{ basename($memo->archivo) }}
                            </a>
                        </div>
                    @endif
                    <input type="file"
                           class="form-control @error('archivo') is-invalid @enderror"
                           id="archivo"
                           name="archivo"
                           accept=".pdf,.doc,.docx">
                    @error('archivo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Formatos permitidos: PDF, DOC, DOCX. Tamaño máximo: 15 MB.</div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('memos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-check-lg me-1"></i>
                        {{ $isEdit ? 'Actualizar Memo/Oficio' : 'Crear Memo/Oficio' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection