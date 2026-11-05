@extends('layouts.admin')

@section('content')
    @php
        $user = Auth::user();
        $esSupervisor = $user->hasAnyRole(['directora', 'administracion', 'inspectoria']);
        $formatearDias = fn (float $dias): string => rtrim(rtrim(number_format($dias, 1, ',', '.'), '0'), ',');
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <div class="d-flex align-items-center">
            <h1 class="h3 mb-0 me-3">Permisos Administrativos</h1>
            @if ($pendientesCount)
                <span class="badge bg-warning text-dark fs-6" id="solicitudes-pendientes">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    {{ $pendientesCount }} solicitud{{ $pendientesCount > 1 ? 'es' : '' }} pendiente{{ $pendientesCount > 1 ? 's' : '' }}
                </span>
            @endif
        </div>
        <a href="{{ route('permisos.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Solicitar Permiso
        </a>
    </div>

    {{-- Días acumulados --}}
    <div class="card shadow-sm mb-3 dias-acumulados">
        <div class="card-body">
            <div class="d-flex align-items-center">
                <i class="bi bi-calendar-week fs-3 me-3 text-primary"></i>
                <div>
                    <strong>Días acumulados en el año:</strong>
                    <span class="display-number ms-2">{{ $formatearDias($diasAcumulados) }}</span>
                    <span class="text-muted ms-3">
                        <strong>Disponibles:</strong>
                        {{ $formatearDias(max($cupo - $diasAcumulados, 0)) }}
                        <small>(cupo anual {{ $cupo }})</small>
                    </span>
                </div>
                @if ($diasAcumulados >= $cupo)
                    <span class="badge bg-warning ms-3 fs-6">
                        <i class="bi bi-exclamation-triangle me-1"></i> Cerca del límite
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Funcionario</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Jornada</th>
                            <th>Días</th>
                            @if ($esSupervisor)
                                <th>Acumulado año</th>
                                <th>Disponibles</th>
                            @endif
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($permisos as $permiso)
                            <tr>
                                <td class="fw-medium">{{ $permiso->user->name ?? '—' }}</td>
                                <td>{{ $permiso->fecha_inicio->format('d/m/Y') }}</td>
                                <td>{{ $permiso->fecha_fin->format('d/m/Y') }}</td>
                                <td>
                                    @if ($permiso->tipo_jornada === 'completa')
                                        <span class="badge bg-primary rounded-pill">Completa</span>
                                    @else
                                        <span class="badge bg-info rounded-pill">Media</span>
                                    @endif
                                </td>
                                <td>{{ $formatearDias($permiso->diasSolicitados()) }}</td>
                                @if ($esSupervisor)
                                    <td>{{ $formatearDias($acumuladosPorUsuario[$permiso->user_id] ?? 0) }}</td>
                                    <td>{{ $formatearDias(max($cupo - ($acumuladosPorUsuario[$permiso->user_id] ?? 0), 0)) }}</td>
                                @endif
                                <td>
                                    @if ($permiso->estado === 'pendiente')
                                        <span class="badge bg-warning rounded-pill">Pendiente</span>
                                    @elseif ($permiso->estado === 'aprobado')
                                        <span class="badge bg-success rounded-pill">Aprobado</span>
                                    @else
                                        <span class="badge bg-danger rounded-pill">Rechazado</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    {{-- Respaldo adjunto — dueño o supervisores (HU-03) --}}
                                    @if ($permiso->archivo)
                                        <a href="{{ route('permisos.download', $permiso->id) }}"
                                           class="btn btn-sm btn-outline-primary me-1"
                                           title="Descargar respaldo">
                                            <i class="bi bi-paperclip"></i>
                                        </a>
                                    @endif

                                    {{-- Editar/Eliminar — Dirección, Administración e Inspectoría General --}}
                                    @if ($permiso->estado === 'pendiente' && $esSupervisor)
                                        <a href="{{ route('permisos.edit', $permiso->id) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('permisos.destroy', $permiso->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger me-1"
                                                    title="Eliminar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Está seguro de eliminar esta solicitud de permiso?"
                                                    data-confirm-btn="Eliminar"
                                                    data-confirm-icon="bi-trash"
                                                    data-confirm-btn-class="btn-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Aprobar/Rechazar — Dirección, Administración e Inspectoría General --}}
                                    @if ($permiso->estado === 'pendiente' && $esSupervisor)
                                        <form action="{{ route('permisos.aprobar', $permiso->id) }}"
                                              method="POST"
                                              class="d-inline me-1">
                                            @csrf
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success"
                                                    title="Aprobar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Aprobar este permiso de {{ $permiso->user->name ?? '—' }}?"
                                                    data-confirm-btn="Aprobar permiso"
                                                    data-confirm-icon="bi-check-lg"
                                                    data-confirm-btn-class="btn-success">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('permisos.rechazar', $permiso->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Rechazar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Rechazar este permiso de {{ $permiso->user->name ?? '—' }}?"
                                                    data-confirm-btn="Rechazar permiso"
                                                    data-confirm-icon="bi-x-lg"
                                                    data-confirm-btn-class="btn-danger">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $esSupervisor ? 9 : 7 }}" class="text-center text-muted py-4">
                                    No hay solicitudes de permiso registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($permisos->hasPages())
            <div class="card-footer">
                {{ $permisos->links() }}
            </div>
        @endif
    </div>
@endsection