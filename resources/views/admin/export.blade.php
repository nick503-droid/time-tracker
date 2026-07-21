@extends('layouts.app')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
@endsection

@section('content')
<div style="width:100%;max-width:768px;">
    {{-- Header --}}
    <div style="margin-bottom:32px;padding-bottom:20px;border-bottom:1px solid var(--color-subtle);">
        <a href="{{ route('admin.dashboard') }}" class="back-link">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver al panel principal
        </a>
        <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
            Centro de <span style="color:var(--color-brand);">Exportación de Nómina</span>
        </h1>
        <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Genera reportes detallados en Excel para el pago de los empleados</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-error">
            <ul style="list-style:disc;padding-left:20px;margin:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.export.download') }}" style="display:flex;flex-direction:column;gap:24px;">
        @csrf

        <div class="card">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--color-subtle);">Parámetros del Reporte</h3>

            {{-- Filtro de Empleados --}}
            <div style="margin-bottom:24px;">
                <label class="label" style="font-size:12px;font-weight:500;">Seleccionar Empleado</label>
                <select name="employee_id" id="employee-select" required class="input input-lg">
                    <option value="all" style="font-weight:600;">Todos los empleados (Reporte Global)</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->email }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Botones Rápidos de Fechas --}}
            <div style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:8px;">
                <button type="button" onclick="setDates('today')" class="btn btn-ghost btn-sm">Hoy</button>
                <button type="button" onclick="setDates('week')" class="btn btn-ghost btn-sm">Esta Semana</button>
                <button type="button" onclick="setDates('biweek')" class="btn btn-ghost btn-sm">Últimos 15 días</button>
                <button type="button" onclick="setDates('month')" class="btn btn-ghost btn-sm">Este Mes</button>
            </div>

            {{-- Selectores de Fecha --}}
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;">
                <div>
                    <label class="label" style="font-size:12px;font-weight:500;">Fecha de Inicio</label>
                    <input type="date" name="start_date" id="start_date" value="{{ now()->toDateString() }}" required class="input">
                </div>
                <div>
                    <label class="label" style="font-size:12px;font-weight:500;">Fecha de Fin</label>
                    <input type="date" name="end_date" id="end_date" value="{{ now()->toDateString() }}" required class="input">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-full" style="padding:14px 16px;font-size:14px;border-radius:12px;">
            <svg style="width:20px;height:20px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Descargar Reporte en Excel
        </button>
    </form>
</div>

{{-- Librería del buscador y lógica --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    new TomSelect("#employee-select", {
        create: false,
        sortField: { field: "text", direction: "asc" }
    });

    function setDates(period) {
        const today = new Date();
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');

        let start = new Date();
        let end = new Date();

        if (period === 'today') {
            // Hoy
        } else if (period === 'week') {
            start.setDate(today.getDate() - today.getDay() + 1);
        } else if (period === 'biweek') {
            start.setDate(today.getDate() - 15);
        } else if (period === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        }

        startInput.value = start.toLocaleDateString('en-CA');
        endInput.value = end.toLocaleDateString('en-CA');
    }
</script>
@endsection
