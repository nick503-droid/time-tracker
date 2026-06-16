@extends('layouts.app')

@section('content')
<div class="w-full max-w-sm">

    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warn/10 mb-4">
            <svg class="w-6 h-6 text-warn" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path d="M12 15v2m0-6v.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-800">Actualización requerida</h1>
        <p class="text-sm text-muted mt-1">Debes cambiar tu contraseña temporal antes de continuar.</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="bg-dangerBg border border-dangerBrd text-danger rounded-lg px-4 py-3 text-sm mb-5">
            <ul class="list-disc list-inside space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-8">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-600 mb-1.5">Nueva contraseña</label>
                <input type="password" name="password" id="password" required minlength="8"
                    placeholder="Mínimo 8 caracteres"
                    class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition-all placeholder-gray-400">
                <p class="text-xs text-muted mt-1.5">Mínimo 8 caracteres.</p>
            </div>

            <div class="mb-7">
                <label for="password_confirmation" class="block text-sm font-medium text-gray-600 mb-1.5">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                    placeholder="Repite la contraseña"
                    class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition-all placeholder-gray-400">
            </div>

            <button type="submit"
                class="w-full bg-brand hover:bg-brandHov text-white font-medium rounded-lg px-4 py-2.5 text-sm transition-colors">
                Guardar y continuar
            </button>
        </form>
    </div>
</div>
@endsection
