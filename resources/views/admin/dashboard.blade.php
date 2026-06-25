@extends('layouts.app')

@section('content')
<div class="w-full max-w-6xl">
    {{-- Header --}}
    <div class="flex justify-between items-start mb-8 pb-5 border-b border-subtle">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Panel de <span class="text-brand">administración</span></h1>
            <p class="text-sm text-muted mt-0.5">Control total del sistema WFM</p>
        </div>
        <div class="flex gap-3 items-center">
            <a href="{{ route('employee.dashboard') }}" class="text-sm text-brand border border-brand/40 bg-brand/5 hover:bg-brand/10 px-4 py-2 rounded-lg transition-colors font-medium">Mi reloj</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-muted hover:text-gray-700 transition-colors">Cerrar sesión</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            {{ session('status') }}
        </div>
    @endif

    {{-- Panel de Reportes Único --}}
    <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6 w-full">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 pb-4 border-b border-subtle gap-4">
            <h3 class="text-sm font-semibold text-gray-800">Monitoreo y Reporte de tiempos</h3>

            <div class="flex gap-3">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex gap-2">
                    <input type="date" name="start_date" value="{{ $startDate }}" class="bg-bgPage border border-subtle text-sm text-gray-700 rounded-lg px-3 py-1.5 outline-none focus:border-brand">
                    <input type="date" name="end_date" value="{{ $endDate }}" class="bg-bgPage border border-subtle text-sm text-gray-700 rounded-lg px-3 py-1.5 outline-none focus:border-brand">
                    <button type="submit" class="bg-brand text-white px-4 py-1.5 text-sm font-medium rounded-lg hover:bg-brandHov transition-colors">Filtrar</button>
                    <a href="{{ route('admin.export.form') }}" class="bg-bgPage border border-subtle text-gray-700 px-4 py-1.5 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors inline-flex items-center">
                        Descargar Excel
                    </a>
                </form>
                <a href="{{ route('admin.createEmployee') }}" class="bg-gray-800 text-white px-4 py-1.5 text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2">
                    + Nuevo Empleado
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-muted uppercase tracking-wide border-b border-subtle">
                        <th class="pb-3 pr-4 font-medium">Empleado</th>
                        <th class="pb-3 pr-4 font-medium">Estado</th>
                        <th class="pb-3 pr-4 font-medium">Tiempo Trabajado</th>
                        <th class="pb-3 pr-4 font-medium">Tiempo en Breaks</th>
                        <th class="pb-3 text-right font-medium">Acción</th>
                    </tr>
                </thead>
                <tbody id="reportTableBody" class="text-gray-700">
                    @forelse($reportData as $row)
                    <tr class="border-b border-subtle/50 hover:bg-bgPage transition-colors">
                        <td class="py-4 pr-4 font-medium">
                            {{ $row['employee'] }}<br>
                            <span class="text-muted font-normal text-[11px]">{{ $row['email'] }}</span>
                        </td>
                        {{-- COLUMNA DE BADGES DE ESTADO PERSONALIZADOS --}}
                        <td class="py-4 pr-4">
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold tracking-wide
                                {{ $row['status'] == 'Trabajando' ? 'bg-green-50 text-green-700 border border-green-200' : '' }}
                                {{ $row['status'] == 'Descansando' || $row['status'] == 'En Lunch' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                {{ $row['status'] == 'Desconectado' || $row['status'] == 'Turno Terminado' ? 'bg-gray-50 text-gray-400 border border-gray-200' : '' }}
                                {{ $row['status'] == 'Iniciado' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                            ">
                                {{ $row['status'] }}
                            </span>
                        </td>
                        <td class="py-4 pr-4 font-mono text-gray-800 font-medium text-sm">{{ $row['total_worked'] }}</td>
                        <td class="py-4 pr-4 font-mono text-gray-500 text-sm">{{ $row['total_break'] }}</td>
                        <td class="py-4 text-right">
                            <a href="{{ route('admin.employeeDetails', $row['id']) }}" class="text-brand hover:underline font-medium">Ver detalle &rarr;</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-muted">No hay registros en estas fechas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- SCRIPT AJAX SEGURO Y OPTIMIZADO --}}
<script>
    // Sincronización silenciosa cada 30 segundos (30000ms) para no sobrecargar el servidor
    setInterval(function() {
        const currentUrl = window.location.href;

        fetch(currentUrl)
            .then(response => response.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTableBody = doc.getElementById('reportTableBody').innerHTML;
                document.getElementById('reportTableBody').innerHTML = newTableBody;
            }).catch(err => console.log("Error de sincronización silenciosa:", err));
    }, 30000);
</script>
@endsection
