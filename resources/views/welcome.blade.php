@extends('layouts.app')

@section('content')
<div class="w-full max-w-sm">

    {{-- Logo / Ícono --}}
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-brand/10 mb-4">
            <svg class="w-6 h-6 text-brand" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-800">Time Tracker</h1>
        <p class="text-sm text-muted mt-1">Ingresa tus credenciales para continuar</p>
    </div>

    {{-- Errores --}}
    @if ($errors->any())
        <div class="bg-dangerBg border border-dangerBrd text-danger rounded-lg px-4 py-3 text-sm mb-5">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- Card --}}
    <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-8">
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-600 mb-1.5">Correo electrónico</label>
                <input type="email" name="email" id="email" required value="{{ old('email') }}"
                    placeholder="nombre@empresa.com"
                    class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition-all placeholder-gray-400">
            </div>

            <div class="mb-7">
                <label for="password" class="block text-sm font-medium text-gray-600 mb-1.5">Contraseña</label>
                <input type="password" name="password" id="password" required
                    placeholder="••••••••"
                    class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:border-brand focus:ring-2 focus:ring-brand/20 transition-all placeholder-gray-400">
            </div>

            <button type="submit"
                class="w-full bg-brand hover:bg-brandHov text-white font-medium rounded-lg px-4 py-2.5 text-sm transition-colors">
                Ingresar
            </button>
        </form>
    </div>
</div>
@endsection
