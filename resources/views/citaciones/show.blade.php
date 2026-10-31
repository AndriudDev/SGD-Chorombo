@extends('layouts.admin')

@section('content')
    @php
        $puedeEmitir = auth()->user()->hasAnyRole(['convivencia', 'secretaria', 'directora']);
        $coloresMotivo = ['conductual' => 'bg-danger', 'pedagogico' => 'bg-primary', 'administrativo' => 'bg-secondary'];
        $coloresEstado = ['programada' => 'bg-warning text-dark', 'asistio' => 'bg-success', 'no_asistio' => 'bg-danger', 'reprogramada' => 'bg-info text-dark'];
    @endphp

    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Citación a {{ $citacion->apoderado_nombre }}</h1>
        <a href="{{ route('citaciones.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-person-lines-fill me-1"></i> Detalle de la citación</span>
                    <span class="badge {{ $coloresEstado[$citacion->estado] ?? 'bg-secondary' }} rounded-pill">
                        {{ \App\Models\Citacion::ESTADOS[$citacion->estado] ?? $citacion->estado }}
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Estudiante</dt>
                        <dd class="col-sm-8">
                            {{ $citacion->estudiante_nombre }}
                            <span class="text-muted">({{ $citacion->estudiante_run }})</span>
                        </dd>

                        <dt class="col-sm-4">Apoderado citado</dt>
                        <dd class="col-sm-8">{{ $citacion->apoderado_nombre }}</dd>

                        <dt class="col-sm-4">Funcionario que cita</dt>
                        <dd class="col-sm-8">{{ $citacion->funcionario_cita }}</dd>

                        <dt class="col-sm-4">Fecha y hora</dt>
                        <dd class="col-sm-8">{{ $citacion->fecha->format('d/m/Y') }} — {{ $citacion->hora }} hrs</dd>

                        <dt class="col-sm-4">Motivo</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $coloresMotivo[$citacion->motivo] ?? 'bg-secondary' }} rounded-pill">
                                {{ \App\Models\Citacion::MOTIVOS[$citacion->motivo] ?? $citacion->motivo }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Registrada por</dt>
                        <dd class="col-sm-8">{{ $citacion->user->name ?? '—' }}</dd>

                        <dt class="col-sm-4">Adjunto</dt>
                        <dd class="col-sm-8">
                            @if ($citacion->archivo)
                                <a href="{{ route('citaciones.download', $citacion->id) }}" class="text-decoration-none">
                                    <i class="bi bi-file-earmark-text-fill me-1"></i>
                                    Descargar copia / colilla
                                </a>
                            @else
                                <span class="text-muted">Sin adjunto</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            @if ($puedeEmitir)
                {{-- Seguimiento de estado (HU-04) --}}
                <div class="card shadow-sm mb-3">
                    <div class="card-header"><i class="bi bi-signpost-split me-1"></i> Actualizar estado</div>
                    <div class="card-body">
                        <form action="{{ route('citaciones.estado', $citacion->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado de la citación</label>
                                <select class="form-select @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                                    @foreach (\App\Models\Citacion::ESTADOS as $clave => $etiqueta)
                                        <option value="{{ $clave }}" {{ old('estado', $citacion->estado) === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
                                    @endforeach
                                </select>
                                @error('estado')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-repeat me-1"></i> Actualizar estado
                            </button>
                        </form>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="{{ route('citaciones.edit', $citacion->id) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-pencil me-1"></i> Editar citación
                    </a>
                    <form action="{{ route('citaciones.destroy', $citacion->id) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button"
                                class="btn btn-outline-danger w-100"
                                data-confirm-modal
                                data-confirm-message="¿Está seguro de eliminar la citación a {{ $citacion->apoderado_nombre }}? Esta acción no se puede deshacer."
                                data-confirm-btn="Eliminar"
                                data-confirm-icon="bi-trash"
                                data-confirm-btn-class="btn-danger">
                            <i class="bi bi-trash me-1"></i> Eliminar citación
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection