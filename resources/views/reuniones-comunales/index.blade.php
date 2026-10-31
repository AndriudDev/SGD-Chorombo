@extends('layouts.admin')

@section('content')
    @php
        $puedeGestionar = auth()->user()->hasAnyRole(['utp', 'directora']);
        $coloresCategoria = ['red_utp' => 'bg-primary', 'reunion_directores' => 'bg-info text-dark', 'mesa_convivencia' => 'bg-warning text-dark', 'daem_slep' => 'bg-secondary'];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Reuniones Comunales e Interinstitucionales</h1>
        @if ($puedeGestionar)
            <a href="{{ route('comunales.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva Reunión
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('comunales.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select name="categoria" id="categoria" class="form-select">
                        <option value="">Todas</option>
                        @foreach (\App\Models\ReunionComunal::CATEGORIAS as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request()->input('categoria') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="{{ request()->input('search', '') }}"
                           placeholder="Buscar por título o descripción...">
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
                            <th>Título</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th>Compromisos</th>
                            <th>Registró</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reuniones as $reunion)
                            <tr>
                                <td class="fw-medium">{{ $reunion->titulo }}</td>
                                <td>
                                    <span class="badge {{ $coloresCategoria[$reunion->categoria] ?? 'bg-secondary' }} rounded-pill">
                                        {{ \App\Models\ReunionComunal::CATEGORIAS[$reunion->categoria] ?? $reunion->categoria }}
                                    </span>
                                </td>
                                <td>{{ $reunion->fecha->format('d/m/Y') }}</td>
                                <td>
                                    <span class="badge bg-dark rounded-pill">{{ $reunion->compromisos_count }}</span>
                                </td>
                                <td>{{ $reunion->user->name ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('comunales.show', $reunion->id) }}"
                                       class="btn btn-sm btn-outline-info me-1"
                                       title="Ver y gestionar compromisos">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('comunales.download', $reunion->id) }}"
                                       class="btn btn-sm btn-outline-primary me-1"
                                       title="Descargar documento">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    @if ($puedeGestionar)
                                        <a href="{{ route('comunales.edit', $reunion->id) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('comunales.destroy', $reunion->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Está seguro de eliminar la reunión «{{ $reunion->titulo }}»? Sus compromisos también se eliminarán."
                                                    data-confirm-btn="Eliminar"
                                                    data-confirm-icon="bi-trash"
                                                    data-confirm-btn-class="btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay reuniones comunales registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($reuniones->hasPages())
            <div class="card-footer">
                {{ $reuniones->links() }}
            </div>
        @endif
    </div>
@endsection