@extends('layouts.admin')

@section('content')
    @php
        $puedeGestionar = auth()->user()->hasAnyRole(['utp', 'directora']);
        $coloresCategoria = ['red_utp' => 'bg-primary', 'reunion_directores' => 'bg-info text-dark', 'mesa_convivencia' => 'bg-warning text-dark', 'daem_slep' => 'bg-secondary'];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">{{ $reunion->titulo }}</h1>
        <a href="{{ route('comunales.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><i class="bi bi-people me-1"></i> Detalle de la reunión</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Categoría</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $coloresCategoria[$reunion->categoria] ?? 'bg-secondary' }} rounded-pill">
                                {{ \App\Models\ReunionComunal::CATEGORIAS[$reunion->categoria] ?? $reunion->categoria }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Fecha</dt>
                        <dd class="col-sm-8">{{ $reunion->fecha->format('d/m/Y') }}</dd>

                        <dt class="col-sm-4">Registró</dt>
                        <dd class="col-sm-8">{{ $reunion->user->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Descripción</dt>
                        <dd class="col-sm-8">{!! nl2br(e($reunion->descripcion ?? 'Sin descripción')) !!}</dd>

                        <dt class="col-sm-4">Documento</dt>
                        <dd class="col-sm-8">
                            @if ($reunion->archivo)
                                <a href="{{ route('comunales.download', $reunion->id) }}" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-text-fill me-1"></i>
                                    Descargar minuta / presentación
                                </a>
                            @else
                                <span class="text-muted">Sin documento</span>
                            @endif
                        </dd>
                    </dl>

                    @if ($puedeGestionar)
                        <hr>
                        <div class="d-flex gap-2">
                            <a href="{{ route('comunales.edit', $reunion->id) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-pencil me-1"></i> Editar
                            </a>
                            <form action="{{ route('comunales.destroy', $reunion->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        data-confirm-modal
                                        data-confirm-message="¿Está seguro de eliminar la reunión «{{ $reunion->titulo }}»? Sus compromisos también se eliminarán."
                                        data-confirm-btn="Eliminar"
                                        data-confirm-icon="bi-trash"
                                        data-confirm-btn-class="btn-danger">
                                    <i class="bi bi-trash me-1"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            {{-- Compromisos acordados y fechas límite (HU-06) --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-check me-1"></i> Compromisos y fechas límite</span>
                    <span class="badge bg-dark rounded-pill">{{ $reunion->compromisos->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Compromiso</th>
                                    <th>Fecha límite</th>
                                    @if ($puedeGestionar)
                                        <th class="text-end">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reunion->compromisos as $compromiso)
                                    <tr>
                                        <td>{{ $compromiso->descripcion }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark rounded-pill">
                                                {{ $compromiso->fecha_limite->format('d/m/Y') }}
                                            </span>
                                        </td>
                                        @if ($puedeGestionar)
                                            <td class="text-end">
                                                <a href="{{ route('comunales.compromisos.edit', [$reunion->id, $compromiso->id]) }}"
                                                   class="btn btn-sm btn-outline-secondary me-1"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('comunales.compromisos.destroy', [$reunion->id, $compromiso->id]) }}"
                                                      method="POST"
                                                      class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Eliminar"
                                                            data-confirm-modal
                                                            data-confirm-message="¿Está seguro de eliminar este compromiso?"
                                                            data-confirm-btn="Eliminar"
                                                            data-confirm-icon="bi-trash"
                                                            data-confirm-btn-class="btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $puedeGestionar ? 3 : 2 }}" class="text-center text-muted py-4">
                                            No hay compromisos registrados para esta reunión.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($puedeGestionar)
                <div class="card shadow-sm">
                    <div class="card-header"><i class="bi bi-plus-circle me-1"></i> Registrar compromiso</div>
                    <div class="card-body">
                        <form action="{{ route('comunales.compromisos.store', $reunion->id) }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-md-7">
                                <label for="descripcion" class="form-label">
                                    Descripción del compromiso
                                    <span class="required-asterisk">*</span>
                                </label>
                                <input type="text"
                                       class="form-control @error('descripcion') is-invalid @enderror"
                                       id="descripcion"
                                   name="descripcion"
                                   maxlength="500"
                                   value="{{ old('descripcion') }}"
                                   placeholder="Ej: Enviar plan de mejora a DAEM"
                                   required>
                                @error('descripcion')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_limite" class="form-label">
                                    Fecha límite
                                    <span class="required-asterisk">*</span>
                                </label>
                                <input type="date"
                                       class="form-control @error('fecha_limite') is-invalid @enderror"
                                       id="fecha_limite"
                                       name="fecha_limite"
                                       value="{{ old('fecha_limite') }}"
                                       required>
                                @error('fecha_limite')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-lg me-1"></i> Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection