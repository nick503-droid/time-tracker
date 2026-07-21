@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:384px;">

    {{-- Logo / Ícono --}}
    <div style="text-align:center;margin-bottom:32px;">
        <div class="logo-icon" style="margin-left:auto;margin-right:auto;">
            <svg fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
        </div>
        <h1 style="font-size:20px;font-weight:600;color:#1f2937;">Time Tracker</h1>
        <p style="font-size:14px;color:var(--color-muted);margin-top:4px;">Ingresa tus credenciales para continuar</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:20px;">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Card --}}
    <div class="card" style="padding:32px;">
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div style="margin-bottom:20px;">
                <label for="email" class="label" style="color:#4b5563;">Correo electrónico</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}"
                    placeholder="nombre@empresa.com"
                    class="input input-lg">
            </div>

            <div style="margin-bottom:28px;">
                <label for="password" class="label" style="color:#4b5563;">Contraseña</label>
                <input type="password" name="password" id="password" required
                    placeholder="••••••••"
                    class="input input-lg">
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">
                Ingresar
            </button>
        </form>
    </div>
</div>
@endsection
