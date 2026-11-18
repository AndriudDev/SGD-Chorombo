@extends('layouts.admin')

@section('content')
    @php
        $user = auth()->user();
        $puedeModificar = $user->hasAnyRole(['administracion']) || $memo->user_id === $user->id;
        $puedeEliminar = $user->hasAnyRole(['administracion', 'directora', 'inspectoria']);
    @endphp
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
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
            <div class="detail-row">
                <div class="detail-label">Fecha</div>
                <div class="detail-value">{{ $memo->fecha->format('d/m/Y') }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Destinatario</div>
                <div class="detail-value">{{ $memo->destinatario }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Asunto</div>
                <div class="detail-value">{{ $memo->asunto }}</div>
            </div>

            @if ($memo->archivo)
                <div class="detail-row">
                    <div class="detail-label">Archivo</div>
                    <div class="detail-value">
                        <a href="{{ route('memos.download', $memo->id) }}" class="btn btn-sm btn-primary">
                            <i class="bi bi-download me-1"></i> Descargar archivo
                        </a>
                    </div>
                </div>
            @endif

            <hr>

            <div class="detail-row">
                <div class="detail-label">Creado por</div>
                <div class="detail-value">{{ $memo->user->name ?? '—' }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Creado el</div>
                <div class="detail-value">{{ $memo->created_at->format('d/m/Y H:i') }}</div>
            </div>

            @if ($memo->updated_at && $memo->updated_at != $memo->created_at)
                <div class="detail-row">
                    <div class="detail-label">Actualizado el</div>
                    <div class="detail-value">{{ $memo->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            @endif
        </div>
        {{-- Editar — autor o Administración; Eliminar (papelera HU-08) — Dirección, Inspectoría y Administración --}}
        @if ($puedeModificar || $puedeEliminar)
            <div class="card-footer">
                <div class="d-flex justify-content-end gap-2">
                    @if ($puedeModificar)
                        <a href="{{ route('memos.edit', $memo->id) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1"></i> Editar
                        </a>
                    @endif
                    @if ($puedeEliminar)
                        <form action="{{ route('memos.destroy', $memo->id) }}"
                              method="POST"
                              class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="button"
                                    class="btn btn-outline-danger"
                                    data-confirm-modal
                                    data-confirm-message="¿Está seguro de eliminar el memo/oficio «{{ $memo->folio }}»? Se enviará a la papelera y podrá recuperarse."
                                    data-confirm-btn="Eliminar"
                                    data-confirm-icon="bi-trash"
                                    data-confirm-btn-class="btn-danger">
                                <i class="bi bi-trash me-1"></i> Eliminar
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection