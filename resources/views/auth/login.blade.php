@extends('layouts.auth')

@section('title', config('app.name', 'SGD-Chorombo') . ' - Iniciar Sesión')

@section('auth-content')
    <div class="card sgd-auth-card">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock-fill sgd-auth-icon" style="font-size: 2.5rem;"></i>
                <h4 class="mt-2 fw-bold">{{ config('app.name', 'SGD-Chorombo') }}</h4>
                <p class="text-muted small">Ingrese sus credenciales para acceder al sistema</p>
            </div>

            {{-- Session errors --}}
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    @foreach ($errors->all() as $error)
                        <span>{{ $error }}</span>
                        @if (!$loop->last)<br>@endif
                    @endforeach
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Session status (e.g. from logout) --}}
            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Email --}}
                <div class="mb-3">
                    <label for="email" class="form-label">
                        Correo electrónico
                        <span class="required-asterisk">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email"
                               class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" required autofocus autocomplete="email"
                               placeholder="admin@ejemplo.cl">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">
                        Contraseña
                        <span class="required-asterisk">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required autocomplete="current-password"
                               placeholder="••••••••">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- Remember --}}
                <div class="mb-3 form-check">
                    <input type="checkbox" id="remember" name="remember"
                           class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Recordar sesión</label>
                </div>

                {{-- Submit --}}
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-loading-submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar
                    </button>
                </div>
            </form>
        </div>
    </div>

    @endsection