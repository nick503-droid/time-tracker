@extends('layouts.app')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
@endsection

@section('content')
<div class="w-full max-w-3xl">
    {{-- Header --}}
    <div class="mb-8 pb-5 border-b border-subtle">
        <a href="{{ route('admin.dashboard') }}" class="text-xs text-muted hover:text-brand transition-colors inline-flex items-center gap-1 mb-3">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
            Volver al panel principal
        </a>
        <h1 class="text-xl font-semibold text-gray-800">Centro de <span class="text-brand">Exportación de Nómina</span></h1>
        <p class="text-sm text-muted mt-0.5">Genera reportes detallados en Excel para el pago de los empleados</p>
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

    <form method="POST" action="{{ route('admin.export.download') }}" class="space-y-6">
        @csrf

        <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6">
            <h3 class="text-sm font-semibold text-gray-800 mb-5 border-b border-subtle pb-3">Parámetros del Reporte</h3>

            {{-- Filtro de Empleados con el ID necesario --}}
            <div class="mb-6">
                <label class="block text-xs font-medium text-gray-600 mb-2">Seleccionar Empleado</label>
                <select name="employee_id" id="employee-select" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2.5 text-sm focus:border-brand outline-none transition-all">
                    <option value="all" class="font-semibold">Todos los empleados (Reporte Global)</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->email }})</option>
                    @endforeach
                </select>
            </div>

            {{-- Botones Rápidos de Fechas --}}
            <div class="mb-4 flex flex-wrap gap-2">
                <button type="button" onclick="setDates('today')" class="text-xs bg-bgPage border border-subtle hover:border-brand/50 hover:text-brand px-3 py-1.5 rounded-md transition-colors">Hoy</button>
                <button type="button" onclick="setDates('week')" class="text-xs bg-bgPage border border-subtle hover:border-brand/50 hover:text-brand px-3 py-1.5 rounded-md transition-colors">Esta Semana</button>
                <button type="button" onclick="setDates('biweek')" class="text-xs bg-bgPage border border-subtle hover:border-brand/50 hover:text-brand px-3 py-1.5 rounded-md transition-colors">Últimos 15 días</button>
                <button type="button" onclick="setDates('month')" class="text-xs bg-bgPage border border-subtle hover:border-brand/50 hover:text-brand px-3 py-1.5 rounded-md transition-colors">Este Mes</button>
            </div>

            {{-- Selectores de Fecha --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Fecha de Inicio</label>
                    <input type="date" name="start_date" id="start_date" value="{{ now()->toDateString() }}" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-2">Fecha de Fin</label>
                    <input type="date" name="end_date" id="end_date" value="{{ now()->toDateString() }}" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none transition-all">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full bg-brand hover:bg-brandHov text-white font-medium py-3.5 rounded-xl text-sm transition-colors shadow-sm flex justify-center items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Descargar Reporte en Excel
        </button>
    </form>
</div>

{{-- Librería del buscador y lógica --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
    // Inicializar el buscador en el desplegable
    new TomSelect("#employee-select", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    // Lógica para los botones de fechas rápidas
    function setDates(period) {
        const today = new Date();
        const startInput = document.getElementById('start_date');
        const endInput = document.getElementById('end_date');

        let start = new Date();
        let end = new Date();

        if (period === 'today') {
            // Hoy
        } else if (period === 'week') {
            start.setDate(today.getDate() - today.getDay() + 1); // Lunes de esta semana
        } else if (period === 'biweek') {
            start.setDate(today.getDate() - 15); // Últimos 15 días
        } else if (period === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1); // Primer día del mes
        }

        startInput.value = start.toLocaleDateString('en-CA');
        endInput.value = end.toLocaleDateString('en-CA');
    }
</script>
@endsection
