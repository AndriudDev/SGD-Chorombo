<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SGD-Chorombo') }} - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        {{-- Sidebar --}}
        <nav class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 250px; background-color: #1e293b;">
            <a href="{{ route('dashboard') }}" class="d-flex align-items-center mb-3 text-white text-decoration-none">
                <span class="fs-5 fw-semibold">{{ config('app.name', 'SGD-Chorombo') }}</span>
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="{{ route('users.index') }}"
                       class="nav-link {{ request()->routeIs('users.*') ? 'active' : 'text-white' }}">
                        <i class="bi bi-people-fill me-2"></i> Usuarios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('memos.index') }}"
                       class="nav-link {{ request()->routeIs('memos.*') ? 'active' : 'text-white' }}">
                        <i class="bi bi-file-text me-2"></i> Memos y Oficios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('permisos.index') }}"
                       class="nav-link {{ request()->routeIs('permisos.*') ? 'active' : 'text-white' }}">
                        <i class="bi bi-calendar-check me-2"></i> Permisos Administrativos
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                   data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-2"></i>
                    <strong>{{ auth()->user()->name ?? 'Usuario' }}</strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Cerrar sesión</button>
                        </form>
                    </li>
                </ul>
            </div>
        </nav>

        {{-- Main content --}}
        <div class="d-flex flex-column flex-grow-1">
            {{-- Top navbar --}}
            <nav class="navbar navbar-expand navbar-light bg-white shadow-sm px-4">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h6">Administración</span>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            {{-- Page content --}}
            <main class="flex-grow-1 p-4 bg-light">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>