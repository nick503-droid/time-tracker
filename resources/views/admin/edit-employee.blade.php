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
            Editar perfil de <span style="color:var(--color-brand);">{{ $employee->name }}</span>
        </h1>
        <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Modifica sus datos de acceso, horarios asignados o límites de tiempo</p>
    </div>

    {{-- Mensajes de error --}}
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

    {{-- Mensaje de error general --}}
    @if (session('error'))
        <div class="alert alert-error" style="margin-bottom:24px;">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.updateEmployee', $employee->id) }}" style="display:flex;flex-direction:column;gap:24px;">
        @csrf
        @method('PUT')

        {{-- SECCIÓN 1: Perfil --}}
        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <svg style="width:16px;height:16px;color:var(--color-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Información Personal
            </h3>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
                <div>
                    <label class="label">Nombre completo</label>
                    <input type="text" name="name" value="{{ old('name', $employee->name) }}" required
                           class="input @error('name') input-error @enderror">
                    @error('name')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Correo electrónico</label>
                    <input type="email" name="email" value="{{ old('email', $employee->email) }}" required
                           class="input @error('email') input-error @enderror">
                    @error('email')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
                </div>
                <div style="grid-column: span 2;">
                    <label class="label">Rol en el sistema</label>
                    <select name="role" required class="input">
                        <option value="employee" {{ old('role', $employee->role) == 'employee' ? 'selected' : '' }}>Empleado Normal</option>
                        <option value="admin" {{ old('role', $employee->role) == 'admin' ? 'selected' : '' }}>Administrador</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- SECCIÓN 2: Restablecer contraseña --}}
        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:4px;display:flex;align-items:center;gap:8px;">
                <svg style="width:16px;height:16px;color:var(--color-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                Restablecer Contraseña
                <span style="font-size:11px;font-weight:400;color:var(--color-muted);margin-left:4px;">(Opcional)</span>
            </h3>
            <p style="font-size:12px;color:var(--color-muted);margin-bottom:16px;">Si asignas una nueva contraseña, el empleado deberá cambiarla obligatoriamente en su próximo inicio de sesión.</p>

            <div>
                <label class="label">Nueva contraseña</label>
                <input type="password" name="new_password" autocomplete="new-password"
                       placeholder="Dejar vacío para no modificar"
                       class="input @error('new_password') input-error @enderror">
                <p class="label-hint">Mínimo 8 caracteres.</p>
                @error('new_password')<p style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            @if ($employee->must_change_password)
                <div class="warn-notice">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    Este empleado tiene pendiente cambiar su contraseña en el próximo inicio de sesión.
                </div>
            @endif
        </div>

        {{-- SECCIÓN 3: Horarios y Reglas --}}
        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
                <svg style="width:16px;height:16px;color:var(--color-brand);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Configuración de Horario (Schedule)
            </h3>

            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-bottom:24px;">
                <div>
                    <label class="label">Hora de Entrada</label>
                    <input type="time" name="scheduled_in"
                           value="{{ old('scheduled_in', $employee->scheduled_in ? \Carbon\Carbon::parse($employee->scheduled_in)->format('H:i') : '') }}"
                           required class="input">
                </div>
                <div>
                    <label class="label">Hora de Salida</label>
                    <input type="time" name="scheduled_out"
                           value="{{ old('scheduled_out', $employee->scheduled_out ? \Carbon\Carbon::parse($employee->scheduled_out)->format('H:i') : '') }}"
                           required class="input">
                </div>
            </div>

            <div style="border-top:1px solid var(--color-subtle);padding-top:20px;display:grid;grid-template-columns:repeat(3,1fr);gap:20px;">
                <div>
                    <label class="label">Tiempo de Break (min)</label>
                    <input type="number" name="break_duration_minutes" value="{{ old('break_duration_minutes', $employee->break_duration_minutes) }}" required class="input">
                </div>
                <div>
                    <label class="label">Tiempo de Lunch (min)</label>
                    <input type="number" name="lunch_duration_minutes" value="{{ old('lunch_duration_minutes', $employee->lunch_duration_minutes) }}" required class="input">
                </div>
                <div>
                    <label class="label">Max. Breaks al día</label>
                    <input type="number" name="max_breaks_per_day" value="{{ old('max_breaks_per_day', $employee->max_breaks_per_day) }}" required class="input">
                </div>
            </div>
        </div>

        <div style="display:flex;gap:12px;">
            <a href="{{ route('admin.employees') }}" class="btn btn-ghost" style="flex:1;text-align:center;">
                Cancelar
            </a>
            <button type="submit" class="btn btn-primary" style="flex:1;">
                Guardar Cambios
            </button>
        </div>
    </form>

    {{-- Zona de peligro --}}
    <div class="danger-zone">
        <h3 style="font-size:14px;font-weight:600;color:var(--color-danger);margin-bottom:4px;display:flex;align-items:center;gap:8px;">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            Zona de peligro
        </h3>
        <p style="font-size:12px;color:var(--color-muted);margin-bottom:16px;">
            Eliminar a este empleado es reversible en la base de datos, pero lo desactivará del sistema.
            <strong style="color:#374151;">Todo su historial de horas y pagos se conservará.</strong>
            No podrás eliminar al empleado si tiene un turno activo en curso.
        </p>
        <button type="button" onclick="document.getElementById('deleteModal').style.display='flex'"
                class="btn btn-danger" style="font-size:14px;">
            Eliminar empleado
        </button>
    </div>
</div>

{{-- Modal de confirmación de eliminación --}}
<div id="deleteModal" style="display:none;position:fixed;inset:0;background:rgba(17,24,39,0.5);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;z-index:50;">
    <div class="card" style="width:100%;max-width:384px;padding:24px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
            <div style="width:40px;height:40px;background:var(--color-danger-bg);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg style="width:20px;height:20px;color:var(--color-danger);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            </div>
            <div>
                <h3 style="font-size:14px;font-weight:600;color:#1f2937;">¿Eliminar empleado?</h3>
                <p style="font-size:12px;color:var(--color-muted);">Esta acción desactivará la cuenta</p>
            </div>
        </div>
        <p style="font-size:14px;color:#4b5563;margin-bottom:20px;">
            Estás a punto de eliminar a <strong style="color:#1f2937;">{{ $employee->name }}</strong>.
            El empleado no podrá iniciar sesión, pero su historial de turnos y pagos se conservará en el sistema.
        </p>
        <form method="POST" action="{{ route('admin.destroyEmployee', $employee->id) }}">
            @csrf
            @method('DELETE')
            <div style="display:flex;gap:12px;">
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'"
                        class="btn btn-ghost" style="flex:1;">
                    Cancelar
                </button>
                <button type="submit" class="btn" style="flex:1;background:var(--color-danger);color:#fff;border-color:var(--color-danger);"
                    onmouseover="this.style.background='#7a2828'" onmouseout="this.style.background='var(--color-danger)'">
                    Sí, eliminar
                </button>
            </div>
        </form>
    </div>
</div>

<style>
    @media (max-width: 640px) {
        .form-grid-2 { grid-template-columns: 1fr !important; }
    }
</style>
@endsection
