@extends('layouts.admin')

@section('content')
    @php
        $coloresAccion = [
            'crear' => 'bg-success',
            'editar' => 'bg-primary',
            'descargar' => 'bg-info text-dark',
            'eliminar' => 'bg-warning text-dark',
            'restaurar' => 'bg-secondary',
            'eliminar_definitivo' => 'bg-danger',
        ];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3 mb-0">
            Bitácora de Auditoría
            <span class="badge bg-secondary align-middle">{{ $registros->total() }}</span>
        </h1>
        <a href="{{ route('papelera.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-trash me-1"></i> Papelera
        </a>
    </div>

    {{-- Filtros (HU-08) --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('auditoria.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <select name="usuario" id="usuario" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ (string) request('usuario') === (string) $usuario->id ? 'selected' : '' }}>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="accion" class="form-label">Acción</label>
                    <select name="accion" id="accion" class="form-select">
                        <option value="">Todas</option>
                        @foreach ($acciones as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request('accion') === $clave ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="modulo" class="form-label">Módulo</label>
                    <select name="modulo" id="modulo" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($modulos as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request('modulo') === $clave ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fecha_desde" class="form-label">Fecha desde</label>
                    <input type="date" class="form-control" id="fecha_desde" name="fecha_desde"
                           value="{{ request('fecha_desde') }}">
                </div>
                <div class="col-md-2">
                    <label for="fecha_hasta" class="form-label">Fecha hasta</label>
                    <input type="date" class="form-control" id="fecha_hasta" name="fecha_hasta"
                           value="{{ request('fecha_hasta') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-filter"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Registros (filtrados por los módulos legibles del usuario) --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Fecha y hora</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Documento</th>
                            <th>Módulo</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($registros as $registro)
                            <tr>
                                <td class="text-nowrap">{{ $registro->created_at->format('d/m/Y H:i:s') }}</td>
                                <td>{{ $registro->user->name ?? 'Usuario eliminado' }}</td>
                                <td>
                                    <span class="badge {{ $coloresAccion[$registro->accion] ?? 'bg-secondary' }} rounded-pill">
                                        {{ $acciones[$registro->accion] ?? $registro->accion }}
                                    </span>
                                </td>
                                <td>
                                    <span title="{{ $registro->modelo_nombre }}">
                                        {{ Str::limit($registro->modelo_nombre ?? '—', 60) }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $modulo = \App\Services\DocumentosPermitidos::moduloDe($registro->modelo_type);
                                    @endphp
                                    {{ $modulo ? ($modulos[$modulo] ?? $modulo) : '—' }}
                                </td>
                                <td>
                                    @if ($registro->detalles)
                                        <div class="small text-muted">
                                            @foreach (array_slice($registro->detalles, 0, 3, true) as $campo => [$anterior, $nuevo])
                                                <div>
                                                    <strong>{{ $campo }}:</strong>
                                                    {{ $anterior ?? '—' }} &rarr; {{ $nuevo ?? '—' }}
                                                </div>
                                            @endforeach
                                            @if (count($registro->detalles) > 3)
                                                <div>+{{ count($registro->detalles) - 3 }} cambios más</div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No hay registros de auditoría para los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($registros->hasPages())
            <div class="card-footer">
                {{ $registros->links() }}
            </div>
        @endif
    </div>
@endsection