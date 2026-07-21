@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:768px;">
    {{-- Header --}}
    <div style="margin-bottom:32px;padding-bottom:20px;border-bottom:1px solid var(--color-subtle);">
        <a href="{{ route('admin.employees') }}" class="back-link">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver a empleados
        </a>
        <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
            Registrar nuevo <span style="color:var(--color-brand);">empleado</span>
        </h1>
        <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Configura su perfil, horario y límites de tiempo</p>
    </div>

    {{-- Bloque de errores de validación --}}
    @if ($errors->any())
        <div class="alert alert-error" style="flex-direction:column;align-items:flex-start;gap:8px;margin-bottom:24px;">
            <div style="display:flex;align-items:flex-start;gap:8px;">
                <svg style="width:20px;height:20px;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <div>
                    <p style="font-weight:600;margin-bottom:4px;">Por favor corrige los siguientes errores:</p>
                    <ul style="list-style:disc;padding-left:16px;margin:0;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.storeEmployee') }}" style="display:flex;flex-direction:column;gap:24px;">
        @csrf

        {{-- SECCIÓN 1: Perfil --}}
        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <svg style="width:16px;height:16px;color:var(--color-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Información Personal
            </h3>

            <div class="form-grid-2" style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
                <div>
                    <label class="label">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           placeholder="Ej: Juan García"
                           class="input @error('name') input-error @enderror">
                    @error('name')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           placeholder="juan@empresa.com"
                           class="input @error('email') input-error @enderror">
                    @error('email')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Contraseña temporal</label>
                    <input type="password" name="password" required
                           class="input @error('password') input-error @enderror">
                    <p class="label-hint">Mínimo 8 caracteres. El empleado deberá cambiarla en su primer inicio de sesión.</p>
                    @error('password')
                        <p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Rol en el sistema</label>
                    <select name="role" required class="input">
                        <option value="employee" {{ old('role', 'employee') == 'employee' ? 'selected' : '' }}>Empleado Normal</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: Horarios y Reglas --}}
        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <svg style="width:16px;height:16px;color:var(--color-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Configuración de Horario (Schedule)
            </h3>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:24px;">
                <div>
                    <label class="label">Hora de Entrada</label>
                    <input type="time" name="scheduled_in" value="{{ old('scheduled_in') }}" required class="input">
                </div>
                <div>
                    <label class="label">Hora de Salida</label>
                    <input type="time" name="scheduled_out" value="{{ old('scheduled_out') }}" required class="input">
                </div>
            </div>

            <div style="border-top:1px solid var(--color-subtle);padding-top:20px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
                <div>
                    <label class="label">Tiempo de Break (min)</label>
                    <input type="number" name="break_duration_minutes" value="{{ old('break_duration_minutes', 15) }}" required class="input">
                </div>
                <div>
                    <label class="label">Tiempo de Lunch (min)</label>
                    <input type="number" name="lunch_duration_minutes" value="{{ old('lunch_duration_minutes', 60) }}" required class="input">
                </div>
                <div>
                    <label class="label">Max. Breaks al día</label>
                    <input type="number" name="max_breaks_per_day" value="{{ old('max_breaks_per_day', 2) }}" required class="input">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <a href="{{ route('admin.employees') }}" class="btn btn-ghost" style="flex:1;text-align:center;">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary" style="flex:1;">
                Guardar y Registrar Empleado
            </button>
        </div>
    </form>
</div>

<style>
    @media (max-width: 640px) {
        .form-grid-2 { grid-template-columns: 1fr !important; }
    }
</style>
@endsection
