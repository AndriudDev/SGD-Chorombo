@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Permisos Administrativos</h1>
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
                    <span class="display-number ms-2">{{ $diasAcumulados }}</span>
                </div>
                @if ($diasAcumulados >= 10)
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
                                <td>{{ $permiso->diasSolicitados() }}</td>
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
                                    {{-- View/Edit/Delete si pendiente --}}
                                    @if ($permiso->estado === 'pendiente')
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

                                    {{-- Aprobar/Rechazar para admin/directora --}}
                                    @if ($showAprobar && $permiso->estado === 'pendiente')
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
                                <td colspan="7" class="text-center text-muted py-4">
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