@extends('layouts.app')

@section('content')
<div class="w-full max-w-3xl">
    {{-- Header --}}
    <div class="mb-8 pb-5 border-b border-subtle">
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-muted hover:text-brand transition-colors inline-flex items-center gap-1 mb-3">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver al panel
        </a>
        <h1 class="text-xl font-semibold text-gray-800">Registrar nuevo <span class="text-brand">empleado</span></h1>
        <p class="text-sm text-muted mt-0.5">Configura su perfil, horario y límites de tiempo</p>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.storeEmployee') }}" class="space-y-6">
        @csrf

        {{-- SECCIÓN 1: Perfil --}}
        <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Información Personal
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs text-muted mb-1.5">Nombre completo</label>
                    <input type="text" name="name" required placeholder="Ej: Juan García" class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Correo electrónico</label>
                    <input type="email" name="email" required placeholder="juan@empresa.com" class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Contraseña temporal</label>
                    <input type="password" name="password" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Rol en el sistema</label>
                    <select name="role" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                        <option value="employee">Empleado Normal</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: Horarios y Reglas --}}
        <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Configuración de Horario (Schedule)
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                <div>
                    <label class="block text-xs text-muted mb-1.5">Hora de Entrada</label>
                    <input type="time" name="scheduled_in" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Hora de Salida</label>
                    <input type="time" name="scheduled_out" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
            </div>

            <div class="border-t border-subtle pt-5 grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs text-muted mb-1.5">Tiempo de Break (min)</label>
                    <input type="number" name="break_duration_minutes" value="15" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Tiempo de Lunch (min)</label>
                    <input type="number" name="lunch_duration_minutes" value="60" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs text-muted mb-1.5">Max. Breaks al día</label>
                    <input type="number" name="max_breaks_per_day" value="2" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-brand hover:bg-brandHov text-white font-medium px-4 py-3 rounded-xl text-sm transition-colors shadow-sm">
            Guardar y Registrar Empleado
        </button>
    </form>
</div>
@endsection
