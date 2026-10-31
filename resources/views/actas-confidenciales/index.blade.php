@extends('layouts.admin')

@section('content')
    @php
        $user = auth()->user();
        $esGestor = $user->hasAnyRole(['convivencia', 'directora']);
        $esUtp = $user->hasRole('utp');
        $coloresAmbito = ['conductual' => 'bg-danger', 'pedagogico' => 'bg-primary', 'administrativo' => 'bg-secondary'];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <div>
            <h1 class="h3 mb-1">Actas de Acuerdos con Apoderados</h1>
            <span class="badge bg-dark rounded-pill">
                <i class="bi bi-lock-fill me-1"></i> Información Confidencial
            </span>
        </div>
        @if ($esGestor)
            <a href="{{ route('actas.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva Acta
            </a>
        @endif
    </div>

    @if ($esUtp)
        <div class="alert alert-info py-2 small">
            <i class="bi bi-info-circle me-1"></i>
            Jefatura de UTP: consulta restringida a acuerdos de ámbito pedagógico.
        </div>
    @endif

    {{-- Filters --}}
    <div class="card filter-card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('actas.index') }}" class="row g-3">
                @unless ($esUtp)
                    <div class="col-md-3">
                        <label for="ambito" class="form-label">Ámbito</label>
                        <select name="ambito" id="ambito" class="form-select">
                            <option value="">Todos</option>
                            @foreach (\App\Models\ActaConfidencial::AMBITOS as $clave => $etiqueta)
                                <option value="{{ $clave }}" {{ request()->input('ambito') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select name="categoria" id="categoria" class="form-select">
                        <option value="">Todas</option>
                        @foreach (\App\Models\ActaConfidencial::CATEGORIAS as $clave => $etiqueta)
                            <option value="{{ $clave }}" {{ request()->input('categoria') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="{{ $esUtp ? 'col-md-7' : 'col-md-4' }}">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text"
                           class="form-control"
                           id="search"
                           name="search"
                           value="{{ request()->input('search', '') }}"
                           placeholder="Buscar por estudiante, RUN o título...">
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
                            <th>Estudiante</th>
                            <th>Apoderado</th>
                            <th>Ámbito</th>
                            <th>Categoría</th>
                            <th>Fecha</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($actas as $acta)
                            <tr>
                                <td class="fw-medium">{{ $acta->titulo }}</td>
                                <td>
                                    {{ $acta->estudiante_nombre }}
                                    <div class="small text-muted">{{ $acta->estudiante_run }}</div>
                                </td>
                                <td>{{ $acta->apoderado_nombre }}</td>
                                <td>
                                    <span class="badge {{ $coloresAmbito[$acta->ambito] ?? 'bg-secondary' }} rounded-pill">
                                        {{ \App\Models\ActaConfidencial::AMBITOS[$acta->ambito] ?? $acta->ambito }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $acta->categoria === 'sensible' ? 'bg-danger' : 'bg-dark' }} rounded-pill">
                                        {{ \App\Models\ActaConfidencial::CATEGORIAS[$acta->categoria] ?? $acta->categoria }}
                                    </span>
                                </td>
                                <td>{{ $acta->fecha->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('actas.download', $acta->id) }}"
                                       class="btn btn-sm btn-outline-primary me-1"
                                       title="Descargar acta">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    @if ($esGestor)
                                        <a href="{{ route('actas.edit', $acta->id) }}"
                                           class="btn btn-sm btn-outline-secondary me-1"
                                           title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('actas.destroy', $acta->id) }}"
                                              method="POST"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Eliminar"
                                                    data-confirm-modal
                                                    data-confirm-message="¿Está seguro de eliminar el acta «{{ $acta->titulo }}»? Esta acción no se puede deshacer."
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
                                <td colspan="7" class="text-center text-muted py-4">
                                    No hay actas de acuerdos registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($actas->hasPages())
            <div class="card-footer">
                {{ $actas->links() }}
            </div>
        @endif
    </div>
@endsection