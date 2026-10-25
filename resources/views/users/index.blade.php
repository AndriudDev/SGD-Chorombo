@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center sgd-page-header">
        <h1 class="h3">Usuarios</h1>
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Crear Usuario
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Creado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="fw-medium">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        <span class="badge bg-primary rounded-pill me-1">
                                            {{ $role->display_name ?? $role->name }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('users.edit', $user->id) }}"
                                       class="btn btn-sm btn-outline-secondary me-1"
                                       title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('users.destroy', $user->id) }}"
                                          method="POST"
                                          class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar"
                                                data-confirm-modal
                                                data-confirm-message="¿Está seguro de eliminar el usuario «{{ $user->name }}»? Esta acción no se puede deshacer."
                                                data-confirm-btn="Eliminar usuario"
                                                data-confirm-icon="bi-trash"
                                                data-confirm-btn-class="btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay usuarios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection