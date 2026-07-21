@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:384px;">

    <div style="text-align:center;margin-bottom:32px;">
        <div class="logo-icon" style="background:rgba(146,98,42,0.1);margin-left:auto;margin-right:auto;">
            <svg fill="none" stroke="var(--color-warn)" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M12 15v2m0-6v.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h1 style="font-size:20px;font-weight:600;color:#1f2937;">Actualización requerida</h1>
        <p style="font-size:14px;color:var(--color-muted);margin-top:4px;">Debes cambiar tu contraseña temporal antes de continuar.</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom:20px;">
            <ul style="list-style:disc;list-style-position:inside;margin:0;padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card" style="padding:32px;">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <div style="margin-bottom:20px;">
                <label for="password" class="label" style="color:#4b5563;">Nueva contraseña</label>
                <input type="password" name="password" id="password" required minlength="8"
                    placeholder="Mínimo 8 caracteres"
                    class="input input-lg">
                <p class="label-hint">Mínimo 8 caracteres.</p>
            </div>

            <div style="margin-bottom:28px;">
                <label for="password_confirmation" class="label" style="color:#4b5563;">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                    placeholder="Repite la contraseña"
                    class="input input-lg">
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg">
                Guardar y continuar
            </button>
        </form>
    </div>
</div>
@endsection
