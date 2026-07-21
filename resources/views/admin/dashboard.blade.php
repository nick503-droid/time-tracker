@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:1152px;padding-left:16px;padding-right:16px;margin-left:auto;margin-right:auto;">

    {{-- Header --}}
    <div class="page-header">
        <div>
            <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
                Panel de <span style="color:var(--color-brand);">administración</span>
            </h1>
            <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Control total del sistema WFM</p>
        </div>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('admin.employees') }}" class="btn btn-ghost" style="font-size:14px;">
                <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Empleados
            </a>
            <a href="{{ route('employee.dashboard') }}" class="btn btn-brand-outline" style="font-size:14px;">Mi reloj</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:none;border:none;font-size:14px;color:var(--color-muted);cursor:pointer;transition:color 0.15s;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='var(--color-muted)'">Cerrar sesión</button>
            </form>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-error">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Panel de Reportes --}}
    <div class="card">
        {{-- Toolbar del reporte --}}
        <div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:24px;padding-bottom:16px;border-bottom:1px solid var(--color-subtle);">
            <h3 style="font-size:14px;font-weight:600;color:#1f2937;">Monitoreo y Reporte de tiempos</h3>

            <div style="display:flex;flex-wrap:wrap;gap:12px;width:100%;">
                <form method="GET" action="{{ route('admin.dashboard') }}" style="display:flex;flex-wrap:wrap;gap:8px;flex:1;">
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <input type="date" name="start_date" value="{{ $startDate }}"
                               class="input" style="width:auto;font-size:14px;">
                        <input type="date" name="end_date" value="{{ $endDate }}"
                               class="input" style="width:auto;font-size:14px;">
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary" style="font-size:14px;">Filtrar</button>
                        <a href="{{ route('admin.export.form') }}" class="btn btn-ghost" style="font-size:14px;">Descargar Excel</a>
                    </div>
                </form>
                <a href="{{ route('admin.employees') }}" class="btn btn-dark" style="font-size:14px;">Gestionar Empleados</a>
            </div>
        </div>

        {{-- Encabezados de tabla (solo desktop) --}}
        <div class="report-table-head" style="display:grid;grid-template-columns:repeat(5,1fr);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.03em;color:var(--color-muted);padding-bottom:12px;margin-bottom:12px;border-bottom:1px solid var(--color-subtle);">
            <div>Empleado</div>
            <div>Estado</div>
            <div>Tiempo Trabajado</div>
            <div>Tiempo en Breaks</div>
            <div style="text-align:right;">Acción</div>
        </div>

        <div id="reportContainer" style="display:flex;flex-direction:column;gap:0;">
            @forelse($reportData as $row)
            <div style="display:grid;grid-template-columns:repeat(5,1fr);align-items:center;padding:16px 0;border-bottom:1px solid rgba(232,230,225,0.5);font-size:13px;color:#374151;transition:background 0.15s;" onmouseover="this.style.background='var(--color-bg-page)'" onmouseout="this.style.background='transparent'">

                {{-- Empleado --}}
                <div style="padding-right:16px;">
                    <div style="font-weight:500;font-size:13px;">{{ $row['employee'] }}</div>
                    <div style="font-size:11px;color:var(--color-muted);margin-top:2px;">{{ $row['email'] }}</div>
                </div>

                {{-- Estado --}}
                <div style="padding-right:16px;">
                    <span class="badge
                        {{ $row['status'] == 'Trabajando' ? 'badge-working' : '' }}
                        {{ $row['status'] == 'Descansando' || $row['status'] == 'En Lunch' ? 'badge-break' : '' }}
                        {{ $row['status'] == 'Desconectado' || $row['status'] == 'Turno Terminado' ? 'badge-off' : '' }}
                        {{ $row['status'] == 'Iniciado' ? 'badge-started' : '' }}
                    ">
                        {{ $row['status'] }}
                    </span>
                </div>

                {{-- Tiempo trabajado --}}
                <div style="padding-right:16px;font-family:monospace;font-weight:500;">
                    {{ $row['total_worked'] }}
                </div>

                {{-- Breaks --}}
                <div style="padding-right:16px;font-family:monospace;color:#6b7280;">
                    {{ $row['total_break'] }}
                </div>

                {{-- Acción --}}
                <div style="text-align:right;">
                    <a href="{{ route('admin.employeeDetails', $row['id']) }}"
                       style="color:var(--color-brand);font-size:13px;font-weight:500;text-decoration:none;"
                       onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                        Ver detalle &rarr;
                    </a>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:32px 16px;color:var(--color-muted);font-size:14px;">
                No hay registros en estas fechas.
            </div>
            @endforelse
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
                
                const newContainerContent = doc.getElementById('reportContainer').innerHTML;
                document.getElementById('reportContainer').innerHTML = newContainerContent;
            }).catch(err => console.log("Error de sincronización silenciosa:", err));
    }, 30000);
</script>

<style>
/* En móvil, la tabla del reporte cambia a tarjetas */
@media (max-width: 767px) {
    .report-table-head { display: none !important; }
    #reportContainer > div {
        display: flex !important;
        flex-direction: column;
        gap: 8px;
        background: #fff;
        border: 1px solid var(--color-subtle);
        border-radius: 12px;
        padding: 16px !important;
        margin-bottom: 12px;
    }
    #reportContainer > div > div { padding-right: 0 !important; }
}
</style>
@endsection