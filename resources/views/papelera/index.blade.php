@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3 mb-0">Papelera</h1>
        <a href="{{ route('auditoria.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-clock-history me-1"></i> Bitácora de Auditoría
        </a>
    </div>

    <p class="text-muted">
        Los documentos eliminados permanecen aquí hasta que un perfil autorizado los restaure
        o los elimine definitivamente.
    </p>

    @if (empty($secciones))
        <div class="alert alert-info" role="alert">
            <i class="bi bi-inbox me-1"></i> La papelera está vacía.
        </div>
    @else
        @foreach ($secciones as $modulo => $documentos)
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-trash me-1"></i> {{ $etiquetas[$modulo] ?? $modulo }}</span>
                    <span class="badge bg-secondary rounded-pill">{{ $documentos->total() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Documento</th>
                                    <th>Referencia</th>
                                    <th>Fecha del documento</th>
                                    <th>Eliminado el</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($documentos as $documento)
                                    @php
                                        $fecha = $documento instanceof \App\Models\PermisoAdministrativo
                                            ? $documento->fecha_inicio
                                            : $documento->fecha;

                                        // UX-F4-11: la columna Documento muestra asunto/título,
                                        // no la referencia (que ya tiene su propia columna).
                                        $titulo = match (true) {
                                            $documento instanceof \App\Models\Memo => $documento->asunto,
                                            $documento instanceof \App\Models\PermisoAdministrativo => $documento->motivo,
                                            $documento instanceof \App\Models\Citacion => "{$documento->estudiante_nombre} — {$documento->apoderado_nombre}",
                                            default => $documento->titulo,
                                        };
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $titulo ?: $documento->referencia() }}
                                            @if ($documento->archivo)
                                                <i class="bi bi-paperclip ms-1 text-muted" title="Con archivo adjunto"></i>
                                            @endif
                                        </td>
                                        <td class="fw-medium">{{ $documento->referencia() }}</td>
                                        <td>{{ $fecha?->format('d/m/Y') ?? '—' }}</td>
                                        <td>{{ $documento->deleted_at->format('d/m/Y H:i') }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('papelera.restaurar', [$modulo, $documento->id]) }}"
                                                  method="POST"
                                                  class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-success me-1"
                                                        title="Restaurar">
                                                    <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                                                </button>
                                            </form>
                                            <form action="{{ route('papelera.destroy', [$modulo, $documento->id]) }}"
                                                  method="POST"
                                                  class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Eliminar definitivamente"
                                                        data-confirm-modal
                                                        data-confirm-message="¿Eliminar definitivamente «{{ $documento->etiquetaAuditoria() }}»? Se borrará el archivo y no podrá recuperarse."
                                                        data-confirm-btn="Eliminar definitivamente"
                                                        data-confirm-icon="bi-trash"
                                                        data-confirm-btn-class="btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($documentos->hasPages())
                    <div class="card-footer">
                        {{ $documentos->links() }}
                    </div>
                @endif
            </div>
        @endforeach
    @endif
@endsection