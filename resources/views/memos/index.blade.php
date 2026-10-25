@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Memos y Oficios</h1>
        <a href="{{ route('memos.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Nuevo Memo/Oficio
        </a>
    </div>

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('memos.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        <option value="memo" {{ request()->input('tipo') === 'memo' ? 'selected' : '' }}>Memo</option>
                        <option value="oficio" {{ request()->input('tipo') === 'oficio' ? 'selected' : '' }}>Oficio</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="{{ request()->input('search', '') }}"
                           placeholder="Buscar por folio, destinatario o asunto...">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-filter me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Folio</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Destinatario</th>
                            <th>Asunto</th>
                            <th>Archivo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($memos as $memo)
                            <tr>
                                <td class="fw-medium">{{ $memo->folio }}</td>
                                <td>
                                    @if ($memo->tipo === 'memo')
                                        <span class="badge bg-info rounded-pill">Memo</span>
                                    @else
                                        <span class="badge bg-secondary rounded-pill">Oficio</span>
                                    @endif
                                </td>
                                <td>{{ $memo->fecha->format('d/m/Y') }}</td>
                                <td>{{ $memo->destinatario }}</td>
                                <td>
                                    <span title="{{ $memo->asunto }}">
                                        {{ Str::limit($memo->asunto, 60) }}
                                    </span>
                                </td>
                                <td>
                                    @if ($memo->archivo)
                                        <a href="{{ route('memos.download', $memo->id) }}"
                                           class="text-decoration-none"
                                           title="Descargar archivo">
                                            <i class="bi bi-file-earmark-text-fill fs-5"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('memos.show', $memo->id) }}"
                                       class="btn btn-sm btn-outline-info me-1"
                                       title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('memos.edit', $memo->id) }}"
                                       class="btn btn-sm btn-outline-secondary me-1"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('memos.destroy', $memo->id) }}"
                                          method="POST"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger me-1"
                                                title="Eliminar"
                                                data-confirm-modal
                                                data-confirm-message="¿Está seguro de eliminar el memo/oficio «{{ $memo->folio }}»? Esta acción no se puede deshacer."
                                                data-confirm-btn="Eliminar"
                                                data-confirm-icon="bi-trash"
                                                data-confirm-btn-class="btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @if ($memo->archivo)
                                        <a href="{{ route('memos.download', $memo->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Descargar">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay memos u oficios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($memos->hasPages())
            <div class="card-footer">
                {{ $memos->links() }}
            </div>
        @endif
    </div>
@endsection