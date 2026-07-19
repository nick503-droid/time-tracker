@extends('layouts.app')

@section('content')
<div class="w-full max-w-6xl px-4 sm:px-6 lg:px-8 mx-auto">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 pb-5 border-b border-subtle gap-4">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Panel de <span class="text-brand">administración</span></h1>
            <p class="text-sm text-muted mt-0.5">Control total del sistema WFM</p>
        </div>
        <div class="flex gap-3 items-center w-full sm:w-auto justify-between sm:justify-start">
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
    <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-4 sm:p-6 w-full">
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center mb-6 pb-4 border-b border-subtle gap-4">
            <h3 class="text-sm font-semibold text-gray-800">Monitoreo y Reporte de tiempos</h3>

            <div class="flex flex-col sm:flex-row gap-3 w-full xl:w-auto">
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex flex-col sm:flex-row gap-2 w-full">
                    <div class="flex gap-2 w-full sm:w-auto">
                        <input type="date" name="start_date" value="{{ $startDate }}" class="w-full sm:w-auto bg-bgPage border border-subtle text-sm text-gray-700 rounded-lg px-3 py-1.5 outline-none focus:border-brand">
                        <input type="date" name="end_date" value="{{ $endDate }}" class="w-full sm:w-auto bg-bgPage border border-subtle text-sm text-gray-700 rounded-lg px-3 py-1.5 outline-none focus:border-brand">
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                        <button type="submit" class="flex-1 sm:flex-none bg-brand text-white px-4 py-1.5 text-sm font-medium rounded-lg hover:bg-brandHov transition-colors text-center">Filtrar</button>
                        <a href="{{ route('admin.export.form') }}" class="flex-1 sm:flex-none bg-bgPage border border-subtle text-gray-700 px-4 py-1.5 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center">
                            Descargar Excel
                        </a>
                    </div>
                </form>
                <a href="{{ route('admin.createEmployee') }}" class="bg-gray-800 text-white px-4 py-2 sm:py-1.5 text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors flex items-center justify-center gap-2 w-full sm:w-auto mt-2 sm:mt-0">
                    + Nuevo Empleado
                </a>
            </div>
        </div>

        {{-- Vista Híbrida: Tarjetas en móvil, Tabla en Desktop --}}
        <div class="block w-full">
            
            {{-- Encabezados de tabla (Solo visibles en Desktop) --}}
            <div class="hidden md:grid grid-cols-5 text-muted uppercase tracking-wide border-b border-subtle pb-3 mb-3 text-xs font-medium">
                <div class="col-span-1 pr-4">Empleado</div>
                <div class="col-span-1 pr-4">Estado</div>
                <div class="col-span-1 pr-4">Tiempo Trabajado</div>
                <div class="col-span-1 pr-4">Tiempo en Breaks</div>
                <div class="col-span-1 text-right">Acción</div>
            </div>

            <div id="reportContainer" class="text-gray-700 flex flex-col gap-4 md:gap-0">
                @forelse($reportData as $row)
                {{-- Fila/Tarjeta de empleado --}}
                <div class="bg-white md:bg-transparent border border-subtle md:border-0 md:border-b md:border-subtle/50 rounded-lg md:rounded-none p-4 md:p-0 md:py-4 hover:bg-bgPage transition-colors flex flex-col md:grid md:grid-cols-5 md:items-center gap-3 md:gap-0">
                    
                    {{-- Información del empleado --}}
                    <div class="col-span-1 md:pr-4 flex justify-between md:block items-start">
                        <div class="font-medium text-sm md:text-xs">
                            {{ $row['employee'] }}
                            <div class="text-muted font-normal text-[11px] mt-0.5">{{ $row['email'] }}</div>
                        </div>
                        {{-- Estado (Visible aquí solo en móvil para optimizar espacio) --}}
                        <div class="md:hidden">
                            <span class="inline-block px-2.5 py-1 rounded-full text-[10px] font-semibold tracking-wide
                                {{ $row['status'] == 'Trabajando' ? 'bg-green-50 text-green-700 border border-green-200' : '' }}
                                {{ $row['status'] == 'Descansando' || $row['status'] == 'En Lunch' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                                {{ $row['status'] == 'Desconectado' || $row['status'] == 'Turno Terminado' ? 'bg-gray-50 text-gray-400 border border-gray-200' : '' }}
                                {{ $row['status'] == 'Iniciado' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                            ">
                                {{ $row['status'] }}
                            </span>
                        </div>
                    </div>

                    {{-- Estado (Visible solo en Desktop) --}}
                    <div class="col-span-1 md:pr-4 hidden md:block text-xs">
                        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold tracking-wide
                            {{ $row['status'] == 'Trabajando' ? 'bg-green-50 text-green-700 border border-green-200' : '' }}
                            {{ $row['status'] == 'Descansando' || $row['status'] == 'En Lunch' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}
                            {{ $row['status'] == 'Desconectado' || $row['status'] == 'Turno Terminado' ? 'bg-gray-50 text-gray-400 border border-gray-200' : '' }}
                            {{ $row['status'] == 'Iniciado' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                        ">
                            {{ $row['status'] }}
                        </span>
                    </div>

                    {{-- Tiempos (Móvil y Desktop) --}}
                    <div class="col-span-2 grid grid-cols-2 md:grid-cols-2 gap-2 text-xs">
                        <div class="md:pr-4 flex flex-col md:block">
                            <span class="md:hidden text-muted text-[10px] uppercase mb-1">Trabajado</span>
                            <span class="font-mono text-gray-800 font-medium">{{ $row['total_worked'] }}</span>
                        </div>
                        <div class="md:pr-4 flex flex-col md:block">
                            <span class="md:hidden text-muted text-[10px] uppercase mb-1">Breaks</span>
                            <span class="font-mono text-gray-500">{{ $row['total_break'] }}</span>
                        </div>
                    </div>

                    {{-- Acción (Móvil y Desktop) --}}
                    <div class="col-span-1 md:text-right mt-2 md:mt-0 pt-3 md:pt-0 border-t border-subtle md:border-0 text-xs">
                        <a href="{{ route('admin.employeeDetails', $row['id']) }}" class="text-brand hover:underline font-medium inline-block w-full md:w-auto text-center md:text-right py-1 md:py-0">
                            Ver detalle &rarr;
                        </a>
                    </div>
                </div>
                @empty
                <div class="text-center py-8 text-muted border border-subtle md:border-0 rounded-lg text-sm">
                    No hay registros en estas fechas.
                </div>
                @endforelse
            </div>
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
                
                // Actualizado para buscar el nuevo contenedor híbrido
                const newContainerContent = doc.getElementById('reportContainer').innerHTML;
                document.getElementById('reportContainer').innerHTML = newContainerContent;
            }).catch(err => console.log("Error de sincronización silenciosa:", err));
    }, 30000);
</script>
@endsection