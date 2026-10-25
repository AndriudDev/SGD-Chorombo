@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Detalle del Memo/Oficio</h1>
        <a href="{{ route('memos.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-medium">{{ $memo->folio }}</span>
            @if ($memo->tipo === 'memo')
                <span class="badge bg-info rounded-pill">Memo</span>
            @else
                <span class="badge bg-secondary rounded-pill">Oficio</span>
            @endif
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-sm-3 text-muted">Fecha</div>
                <div class="col-sm-9">{{ $memo->fecha->format('d/m/Y') }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-3 text-muted">Destinatario</div>
                <div class="col-sm-9">{{ $memo->destinatario }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-3 text-muted">Asunto</div>
                <div class="col-sm-9">{{ $memo->asunto }}</div>
            </div>

            @if ($memo->archivo)
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">Archivo</div>
                    <div class="col-sm-9">
                        <a href="{{ route('memos.download', $memo->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i> Descargar archivo
                        </a>
                    </div>
                </div>
            @endif

            <hr>

            <div class="row mb-3">
                <div class="col-sm-3 text-muted">Creado por</div>
                <div class="col-sm-9">{{ $memo->user->name ?? '—' }}</div>
            </div>

            <div class="row mb-3">
                <div class="col-sm-3 text-muted">Creado el</div>
                <div class="col-sm-9">{{ $memo->created_at->format('d/m/Y H:i') }}</div>
            </div>

            @if ($memo->updated_at && $memo->updated_at != $memo->created_at)
                <div class="row mb-3">
                    <div class="col-sm-3 text-muted">Actualizado el</div>
                    <div class="col-sm-9">{{ $memo->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            @endif
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('memos.edit', $memo->id) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
                <form action="{{ route('memos.destroy', $memo->id) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('¿Está seguro de eliminar este memo/oficio?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection