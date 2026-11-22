@extends('layouts.admin')

@section('content')
    @php
        // UX-F4-01: distinguir «sin documentos» de «sin coincidencias por filtros».
        $hayFiltros = collect(request()->query())->except(['page', 'volver'])->isNotEmpty();
    @endphp
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3 mb-0">
            Búsqueda Documental
            <span class="badge bg-secondary align-middle">{{ $resultados->total() }}</span>
        </h1>
    </div>

    {{-- Filtros combinables (HU-07): texto, rango de fechas, tipo, emisor y estado --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('buscador.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="q" class="form-label">Texto o referencia</label>
                    <input type="text"
                           class="form-control"
                           id="q"
                           name="q"
                           value="{{ request('q') }}"
                           placeholder="Asunto, título, folio, nombre o RUN...">
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
                <div class="col-md-2">
                    <label for="tipo" class="form-label">Tipo de documento</label>
                    <select name="tipo" id="tipo" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($tipos as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request('tipo') === $clave ? 'selected' : '' }}>
                                {{ $etiqueta }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    {{-- UX-F4-02: los estados se agrupan por módulo aplicable --}}
                    <label for="estado" class="form-label">Estado (según módulo)</label>
                    <select name="estado" id="estado" class="form-select">
                        <option value="">Todos</option>
                        <optgroup label="Permisos administrativos">
                            @foreach (['pendiente', 'aprobado', 'rechazado', 'revocada'] as $clave)
                                <option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>
                                    {{ $estados[$clave] }}
                                </option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Citaciones a apoderados">
                            @foreach (['programada', 'asistio', 'no_asistio', 'reprogramada'] as $clave)
                                <option value="{{ $clave }}" {{ request('estado') === $clave ? 'selected' : '' }}>
                                    {{ $estados[$clave] }}
                                </option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>
                <div class="col-md-3">
                    {{-- UX-F4-03: el filtro es por usuario registrador, no por emisor documental --}}
                    <label for="emisor" class="form-label">Registrado por</label>
                    <select name="emisor" id="emisor" class="form-select">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ (string) request('emisor') === (string) $usuario->id ? 'selected' : '' }}>
                                {{ $usuario->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="bi bi-filter me-1"></i> Filtrar
                    </button>
                    <a href="{{ route('buscador.index') }}" class="btn btn-link text-decoration-none">
                        <i class="bi bi-x-lg me-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Resultados (siempre limitados a los módulos que este usuario puede leer) --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Referencia</th>
                            <th>Fecha</th>
                            <th>Título / Asunto</th>
                            <th>Registrado por</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resultados as $fila)
                            <tr>
                                <td><span class="badge bg-secondary rounded-pill">{{ $fila['tipo'] }}</span></td>
                                <td class="fw-medium">{{ $fila['referencia'] }}</td>
                                <td>{{ \Illuminate\Support\Carbon::parse($fila['fecha'])->format('d/m/Y') }}</td>
                                <td>
                                    <span title="{{ $fila['titulo'] }}">
                                        {{ Str::limit($fila['titulo'], 70) }}
                                    </span>
                                </td>
                                <td>{{ $fila['emisor'] }}</td>
                                <td>
                                    @if ($fila['estado'])
                                        <span class="badge bg-info text-dark rounded-pill">{{ $fila['estado'] }}</span>
                                    @else
                                        {{-- UX-F4-14: módulos sin estado propio --}}
                                        <span class="text-muted">No aplica</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($fila['ruta_ver'])
                                        <a href="{{ $fila['ruta_ver'] }}"
                                           class="btn btn-sm btn-outline-info me-1"
                                           title="Ver">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    @if ($fila['ruta_descarga'])
                                        <a href="{{ $fila['ruta_descarga'] }}"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Descargar">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    @elseif (! $fila['ruta_ver'])
                                        <span class="text-muted">&mdash;</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    @if ($hayFiltros)
                                        Sin coincidencias con los filtros aplicados.
                                    @else
                                        Aún no hay documentos disponibles para tu perfil.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($resultados->hasPages())
            <div class="card-footer">
                {{ $resultados->links() }}
            </div>
        @endif
    </div>
@endsection