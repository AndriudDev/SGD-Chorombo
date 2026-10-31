@extends('layouts.admin')

@section('content')
    @php
        $puedeEmitir = auth()->user()->hasAnyRole(['convivencia', 'secretaria', 'directora']);
        $coloresMotivo = ['conductual' => 'bg-danger', 'pedagogico' => 'bg-primary', 'administrativo' => 'bg-secondary'];
        $coloresEstado = ['programada' => 'bg-warning text-dark', 'asistio' => 'bg-success', 'no_asistio' => 'bg-danger', 'reprogramada' => 'bg-info text-dark'];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Citaciones a Apoderados</h1>
        @if ($puedeEmitir)
            <a href="{{ route('citaciones.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva Citación
            </a>
        @endif
    </div>

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('citaciones.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="estado" class="form-label">Estado</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Citacion::ESTADOS as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request()->input('estado') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="motivo" class="form-label">Motivo</label>
                    <select name="motivo" id="motivo" class="form-select">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Citacion::MOTIVOS as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request()->input('motivo') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="{{ request()->input('search', '') }}"
                           placeholder="Buscar por estudiante, RUN o apoderado...">
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
                            <th>Estudiante</th>
                            <th>Apoderado</th>
                            <th>Fecha / Hora</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th>Registró</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($citaciones as $citacion)
                            <tr>
                                <td class="fw-medium">
                                    {{ $citacion->estudiante_nombre }}
                                    <div class="small text-muted">{{ $citacion->estudiante_run }}</div>
                                </td>
                                <td>{{ $citacion->apoderado_nombre }}</td>
                                <td>
                                    {{ $citacion->fecha->format('d/m/Y') }}
                                    <div class="small text-muted">{{ $citacion->hora }} hrs</div>
                                </td>
                                <td>
                                    <span class="badge {{ $coloresMotivo[$citacion->motivo] ?? 'bg-secondary' }} rounded-pill">
                                        {{ \App\Models\Citacion::MOTIVOS[$citacion->motivo] ?? $citacion->motivo }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $coloresEstado[$citacion->estado] ?? 'bg-secondary' }} rounded-pill">
                                        {{ \App\Models\Citacion::ESTADOS[$citacion->estado] ?? $citacion->estado }}
                                    </span>
                                </td>
                                <td>{{ $citacion->funcionario_cita }}</td>
                                <td class="text-end">
                                    <a href="{{ route('citaciones.show', $citacion->id) }}"
                                       class="btn btn-sm btn-outline-info me-1"
                                       title="Ver">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if ($puedeEmitir)
                                        <a href="{{ route('citaciones.edit', $citacion->id) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('citaciones.destroy', $citacion->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger me-1"
                                                    title="Eliminar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Está seguro de eliminar la citación a {{ $citacion->apoderado_nombre }} del {{ $citacion->fecha->format('d/m/Y') }}? Esta acción no se puede deshacer."
                                                    data-confirm-btn="Eliminar"
                                                    data-confirm-icon="bi-trash"
                                                    data-confirm-btn-class="btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if ($citacion->archivo)
                                        <a href="{{ route('citaciones.download', $citacion->id) }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Descargar adjunto">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay citaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($citaciones->hasPages())
            <div class="card-footer">
                {{ $citaciones->links() }}
            </div>
        @endif
    </div>
@endsection