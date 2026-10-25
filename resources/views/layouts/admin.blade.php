<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SGD-Chorombo') }} - Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="/css/app.css" rel="stylesheet">
    @stack('styles')
</head>
<body>
    {{-- Toast container --}}
    <div class="sgd-toast-container" id="sgd-toast-container" aria-live="polite"></div>

    <div class="d-flex" style="min-height: 100vh;">
        {{-- Sidebar institucional --}}
        <nav class="d-flex flex-column flex-shrink-0 p-3 sgd-sidebar">
            <a href="{{ route('dashboard') }}"
               class="d-flex align-items-center mb-3 text-decoration-none sidebar-brand">
                <i class="bi bi-shield-fill-check me-2"></i>
                {{ config('app.name', 'SGD-Chorombo') }}
            </a>
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                @if (auth()->user()->hasRole('administracion'))
                    <li class="nav-item">
                        <a href="{{ route('users.index') }}"
                           class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="bi bi-people-fill"></i>
                            Usuarios
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a href="{{ route('memos.index') }}"
                       class="nav-link {{ request()->routeIs('memos.*') ? 'active' : '' }}">
                        <i class="bi bi-file-text"></i>
                        Memos y Oficios
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('permisos.index') }}"
                       class="nav-link {{ request()->routeIs('permisos.*') ? 'active' : '' }}">
                        <i class="bi bi-calendar-check"></i>
                        Permisos Administrativos
                    </a>
                </li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#"
                   class="d-flex align-items-center text-white text-decoration-none dropdown-toggle sidebar-user"
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
            <nav class="navbar navbar-expand navbar-light sgd-topbar px-4">
                <div class="container-fluid">
                    <span class="navbar-brand mb-0 h6">
                        {{ auth()->user()->roles->first()?->display_name ?? 'Panel' }}
                    </span>
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
            <main class="flex-grow-1 p-4 sgd-main">
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

    {{-- Modal de confirmación genérico (se activa vía data-attributes) --}}
    <div class="modal fade sgd-confirm-modal" id="sgd-confirm-modal" tabindex="-1"
         aria-labelledby="sgd-confirm-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sgd-confirm-title">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Confirmar acción
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="sgd-confirm-body">
                    ¿Está seguro de realizar esta acción?
                </div>
                <div class="modal-footer d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="sgd-confirm-btn">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ── Confirmación modal ──
        (function() {
            const modalEl = document.getElementById('sgd-confirm-modal');
            if (!modalEl) return;
            const modal = new bootstrap.Modal(modalEl);
            const confirmBtn = document.getElementById('sgd-confirm-btn');
            let pendingForm = null;

            document.querySelectorAll('[data-confirm-modal]').forEach(function(trigger) {
                trigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    pendingForm = trigger.closest('form') || trigger.dataset.formAction;
                    const msg = trigger.dataset.confirmMessage || '¿Está seguro de realizar esta acción?';
                    const btnText = trigger.dataset.confirmBtn || 'Eliminar';
                    const icon = trigger.dataset.confirmIcon || 'bi-trash';

                    document.getElementById('sgd-confirm-body').textContent = msg;
                    confirmBtn.innerHTML = '<i class="bi ' + icon + ' me-1"></i> ' + btnText;

                    // Color del botón según data
                    const btnClass = trigger.dataset.confirmBtnClass || 'btn-danger';
                    confirmBtn.className = 'btn ' + btnClass;

                    modal.show();
                });
            });

            confirmBtn.addEventListener('click', function() {
                if (pendingForm && pendingForm.submit) {
                    pendingForm.submit();
                } else if (typeof pendingForm === 'string') {
                    // Si es una URL, redirigir (para enlaces)
                    window.location.href = pendingForm;
                }
                modal.hide();
            });

            // Limpiar al cerrar
            modalEl.addEventListener('hidden.bs.modal', function() {
                pendingForm = null;
            });
        })();

        // ── Loading state en formularios ──
        (function() {
            document.querySelectorAll('form').forEach(function(form) {
                form.addEventListener('submit', function() {
                    // Pequeño retraso para que el navegador envíe el formulario
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.classList.add('btn-loading');
                        submitBtn.disabled = true;
                    }
                });
            });
        })();

        // ── Toast helper ──
        function sgdToast(message, type) {
            type = type || 'success';
            var container = document.getElementById('sgd-toast-container');
            if (!container) return;
            var icons = { success: 'bi-check-circle-fill', danger: 'bi-x-circle-fill', warning: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
            var icon = icons[type] || icons.info;
            var id = 't-' + Date.now();
            var html = '<div id="' + id + '" class="toast align-items-center text-bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">'
                + '<div class="d-flex">'
                + '<div class="toast-body"><i class="bi ' + icon + ' me-2"></i>' + message + '</div>'
                + '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>'
                + '</div></div>';
            container.insertAdjacentHTML('beforeend', html);
            var el = document.getElementById(id);
            if (el && bootstrap.Toast) {
                var t = new bootstrap.Toast(el);
                t.show();
            }
        }
    </script>
    @stack('scripts')
</body>
</html>