@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:1152px;padding-left:16px;padding-right:16px;margin-left:auto;margin-right:auto;">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="back-link">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                Volver al panel
            </a>
            <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
                Gestión de <span style="color:var(--color-brand);">empleados</span>
            </h1>
            <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Administra perfiles, horarios y accesos del equipo</p>
        </div>
        <a href="{{ route('admin.createEmployee') }}"
           class="btn btn-dark" style="gap:8px;">
            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Nuevo Empleado
        </a>
    </div>

    {{-- Mensajes de éxito --}}
    @if (session('status'))
        <div class="alert alert-success">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('status') }}
        </div>
    @endif

    {{-- Mensajes de error --}}
    @if (session('error'))
        <div class="alert alert-error">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Panel principal --}}
    <div class="emp-panel">

        {{-- Barra de búsqueda --}}
        <div class="emp-toolbar">
            <div class="emp-toolbar-title">
                <span class="emp-icon-badge">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </span>
                <span>
                    <h3 class="emp-toolbar-heading">Directorio de empleados</h3>
                    <p class="emp-toolbar-sub">{{ $employees->total() }} {{ $employees->total() == 1 ? 'empleado registrado' : 'empleados registrados' }}</p>
                </span>
            </div>
            <form method="GET" action="{{ route('admin.employees') }}" class="emp-search-form">
                <div class="emp-search-wrap">
                    <svg class="emp-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o correo..." class="emp-search-input">
                </div>
                <button type="submit" class="emp-btn emp-btn-primary">Buscar</button>
                @if ($search)
                    <a href="{{ route('admin.employees') }}" class="emp-btn emp-btn-ghost">Limpiar</a>
                @endif
            </form>
        </div>

        {{-- Tabla de empleados --}}
        @if ($employees->isEmpty())
            <div class="emp-empty">
                @if ($search)
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                    <p>No se encontraron empleados con "<strong>{{ $search }}</strong>"</p>
                @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                    <p>No hay empleados registrados todavía.</p>
                    <a href="{{ route('admin.createEmployee') }}">Crear el primero</a>
                @endif
            </div>
        @else
            <div class="emp-table-wrap">
                <table class="emp-table">
                    <colgroup>
                        <col style="width: 24%">
                        <col style="width: 24%">
                        <col style="width: 13%">
                        <col style="width: 13%">
                        <col style="width: 12%">
                        <col style="width: 14%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>Empleado</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Creado</th>
                            <th class="emp-col-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employees as $employee)
                            <tr>
                                <td class="emp-cell-name">{{ $employee->name }}</td>
                                <td class="emp-cell-muted">{{ $employee->email }}</td>
                                <td>
                                    <span class="emp-badge {{ $employee->role == 'admin' ? 'emp-badge-admin' : 'emp-badge-role' }}">
                                        {{ $employee->role == 'admin' ? 'Admin' : 'Empleado' }}
                                    </span>
                                </td>
                                <td>
                                    @if ($employee->must_change_password)
                                        <span class="emp-badge emp-badge-pending"><span class="emp-dot"></span>Pendiente</span>
                                    @else
                                        <span class="emp-badge emp-badge-active"><span class="emp-dot"></span>Activo</span>
                                    @endif
                                </td>
                                <td class="emp-cell-muted">{{ $employee->created_at->format('d/m/Y') }}</td>
                                <td class="emp-col-actions">
                                    <div class="emp-actions">
                                        <a href="{{ route('admin.employeeDetails', $employee->id) }}" title="Ver historial" class="emp-action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        </a>
                                        <a href="{{ route('admin.editEmployee', $employee->id) }}" title="Editar" class="emp-action-btn">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/></svg>
                                        </a>
                                        <button type="button" title="Eliminar" onclick="openDeleteModal({{ $employee->id }}, '{{ addslashes($employee->name) }}')" class="emp-action-btn emp-action-btn-danger">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if ($employees->hasPages())
                <div class="emp-pagination">
                    {{ $employees->links() }}
                </div>
            @endif
        @endif
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
                <p style="font-size:12px;color:var(--color-muted);">Esta acción desactivará la cuenta del sistema</p>
            </div>
        </div>
        <p style="font-size:14px;color:#4b5563;margin-bottom:20px;">
            Estás a punto de eliminar a <strong id="deleteEmployeeName" style="color:#1f2937;"></strong>.
            Todo su historial de turnos y pagos se conservará. No podrás eliminarlo si tiene un turno activo.
        </p>
        <form id="deleteForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div style="display:flex;gap:12px;">
                <button type="button" onclick="closeDeleteModal()" class="btn btn-ghost" style="flex:1;">
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
    /* ===== Panel del directorio de empleados ===== */
    .emp-panel {
        background: #fff;
        border: 1px solid var(--color-subtle);
        border-radius: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        padding: 24px;
        width: 100%;
    }
    .emp-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 16px;
        padding-bottom: 18px;
        margin-bottom: 18px;
        border-bottom: 1px solid var(--color-subtle);
    }
    .emp-toolbar-title { display: flex; align-items: center; gap: 10px; }
    .emp-icon-badge {
        width: 32px; height: 32px; min-width: 32px;
        border-radius: 8px; background: #eff6ff; color: #2563eb;
        display: flex; align-items: center; justify-content: center;
    }
    .emp-icon-badge svg { width: 16px; height: 16px; }
    .emp-toolbar-heading { font-size: 14px; font-weight: 600; color: #1f2937; margin: 0; }
    .emp-toolbar-sub { font-size: 12px; color: #9ca3af; margin: 2px 0 0; }
    .emp-search-form { display: flex; gap: 8px; align-items: center; }
    .emp-search-wrap { position: relative; display: flex; align-items: center; }
    .emp-search-icon { position: absolute; left: 10px; width: 14px; height: 14px; color: #9ca3af; pointer-events: none; }
    .emp-search-input {
        width: 220px; max-width: 100%;
        background: var(--gray-50); border: 1px solid var(--color-subtle);
        border-radius: 8px; font-size: 13px; color: #374151;
        padding: 8px 12px 8px 32px; outline: none; box-sizing: border-box;
    }
    .emp-search-input:focus { border-color: var(--color-brand); box-shadow: 0 0 0 2px rgba(74,124,89,0.15); }
    .emp-btn {
        font-size: 13px; font-weight: 500; padding: 8px 16px;
        border-radius: 8px; border: 1px solid transparent;
        cursor: pointer; white-space: nowrap; text-decoration: none;
        display: inline-flex; align-items: center;
    }
    .emp-btn-primary { background: var(--color-brand); color: #fff; }
    .emp-btn-primary:hover { background: var(--color-brand-hov); }
    .emp-btn-ghost { background: var(--gray-50); color: #4b5563; border-color: var(--color-subtle); }
    .emp-btn-ghost:hover { background: var(--gray-100); }
    .emp-table-wrap { overflow-x: auto; }
    .emp-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    .emp-table th {
        text-align: left; font-size: 11px; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.03em; color: #9ca3af;
        padding: 0 12px 10px 0; border-bottom: 1px solid var(--color-subtle);
    }
    .emp-table td {
        padding: 14px 12px 14px 0; border-bottom: 1px solid #f1f2f4;
        font-size: 13px; color: #374151; vertical-align: middle;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .emp-table tbody tr:hover { background: #fafafa; }
    .emp-table tbody tr:last-child td { border-bottom: none; }
    .emp-cell-name { font-weight: 500; color: #1f2937; }
    .emp-cell-muted { color: #6b7280; }
    .emp-col-actions { text-align: right; }
    .emp-badge {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 500; padding: 3px 10px;
        border-radius: 999px; border: 1px solid transparent;
    }
    .emp-badge-admin   { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .emp-badge-role    { background: #f9fafb; color: #4b5563; border-color: var(--color-subtle); }
    .emp-badge-active  { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
    .emp-badge-pending { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .emp-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
    .emp-actions { display: flex; justify-content: flex-end; gap: 4px; }
    .emp-action-btn {
        width: 30px; height: 30px; border: none; background: transparent;
        color: #9ca3af; border-radius: 8px; display: inline-flex;
        align-items: center; justify-content: center; cursor: pointer; text-decoration: none;
    }
    .emp-action-btn svg { width: 16px; height: 16px; }
    .emp-action-btn:hover { background: #eff6ff; color: #2563eb; }
    .emp-action-btn-danger:hover { background: #fef2f2; color: #b91c1c; }
    .emp-empty { text-align: center; padding: 56px 16px; color: #9ca3af; font-size: 13px; }
    .emp-empty svg { width: 40px; height: 40px; margin: 0 auto 12px; color: #d1d5db; display: block; }
    .emp-empty a { color: var(--color-brand); text-decoration: none; font-weight: 500; }
    .emp-empty a:hover { text-decoration: underline; }
    .emp-empty strong { color: #4b5563; }
    .emp-pagination { margin-top: 20px; padding-top: 16px; border-top: 1px solid var(--color-subtle); }

    @media (max-width: 640px) {
        .emp-toolbar { flex-direction: column; align-items: flex-start; }
        .emp-search-form { width: 100%; }
        .emp-search-wrap { flex: 1; }
        .emp-search-input { width: 100%; }
    }
</style>

<script>
    function openDeleteModal(id, name) {
        document.getElementById('deleteEmployeeName').textContent = name;
        document.getElementById('deleteForm').action = '/admin/employee/' + id;
        document.getElementById('deleteModal').style.display = 'flex';
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }
    document.getElementById('deleteModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });
</script>
@endsection
