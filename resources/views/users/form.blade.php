@extends('layouts.admin')

@section('content')
    @php
        $isEdit = isset($user) && $user !== null;
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">{{ $isEdit ? 'Editar Usuario' : 'Nuevo Usuario' }}</h1>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ $isEdit ? route('users.update', $user->id) : route('users.store') }}"
                  method="POST">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                {{-- Nombre --}}
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="name"
                           name="name"
                           value="{{ old('name', $isEdit ? $user->name : '') }}"
                           required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Correo electrónico</label>
                    <input type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           id="email"
                           name="email"
                           value="{{ old('email', $isEdit ? $user->email : '') }}"
                           required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Contraseña
                        @if ($isEdit)
                            <small class="text-muted">(dejar en blanco para mantener la actual)</small>
                        @endif
                    </label>
                    <input type="password"
                           class="form-control @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           {{ $isEdit ? '' : 'required' }}>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Roles --}}
                <div class="mb-3">
                    <label class="form-label">Roles</label>
                    @error('roles')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    <div class="row mt-1">
                        @forelse ($roles as $role)
                            <div class="col-md-4 mb-2">
                                <div class="form-check">
                                    <input type="checkbox"
                                           class="form-check-input @error('roles') is-invalid @enderror"
                                           name="roles[]"
                                           value="{{ $role->id }}"
                                           id="role_{{ $role->id }}"
                                           {{ in_array($role->id, old('roles', $isEdit ? $user->roles->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->display_name ?? $role->name }}
                                    </label>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <p class="text-muted mb-0">No hay roles disponibles.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        {{ $isEdit ? 'Actualizar Usuario' : 'Crear Usuario' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection