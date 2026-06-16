@extends('layouts.app')

@section('content')
<div class="w-full max-w-5xl">

    {{-- Header --}}
    <div class="flex justify-between items-start mb-8 pb-5 border-b border-subtle">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="text-xs text-muted hover:text-brand transition-colors inline-flex items-center gap-1 mb-3">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                Volver al panel
            </a>
            <div class="flex items-center gap-4">
                <h1 class="text-xl font-semibold text-gray-800">Historial de <span class="text-brand">{{ $employee->name }}</span></h1>

                {{-- BOTÓN NUEVO PARA IR A EDITAR HORARIOS --}}
                <a href="{{ route('admin.editEmployee', $employee->id) }}" class="text-xs bg-bgPage border border-subtle text-gray-600 hover:text-brand px-2.5 py-1 rounded-lg transition-colors font-medium">
                    Modificar Horario / Perfil
                </a>
            </div>
            <p class="text-sm text-muted mt-0.5">{{ $employee->email }}</p>
        </div>
    </div>



    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            {{ session('status') }}
        </div>
    @endif

    {{-- Jornadas --}}
    <div class="space-y-5">
        @forelse($shifts as $shift)
            <div class="bg-bgCard border border-subtle rounded-2xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-subtle">
                    <h3 class="text-sm font-semibold text-gray-700">
                        Jornada: {{ Carbon\Carbon::parse($shift->date)->format('d/m/Y') }}
                    </h3>
                    <span class="text-xs text-muted font-mono">
                        Login: {{ $shift->login_time }} &nbsp;|&nbsp; Logoff: {{ $shift->logoff_time ?? 'Activo' }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="text-muted uppercase tracking-wide border-b border-subtle">
                                <th class="pb-2 pr-4 font-medium">Actividad</th>
                                <th class="pb-2 pr-4 font-medium">Inicio</th>
                                <th class="pb-2 pr-4 font-medium">Fin</th>
                                <th class="pb-2 pr-4 font-medium">Duración</th>
                                <th class="pb-2 text-right font-medium">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="activitiesTable" class="text-gray-700">
                            @foreach($shift->activities as $act)
                                <tr class="border-b border-subtle/50 hover:bg-bgPage transition-colors">
                                    <td class="py-3 pr-4">
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ $act->activity_type == 'ready' ? 'bg-brand/10 text-brand' : 'bg-warnBg text-warn' }}">
                                            {{ ucfirst(str_replace('_', ' ', $act->activity_type)) }}
                                        </span>
                                    </td>
                                    {{-- Aquí agregamos las clases para el JS --}}
                                    <td class="py-3 pr-4 font-mono text-gray-600 started-at-data" data-time="{{ $act->started_at }}">
                                        {{ $act->started_at }}
                                    </td>
                                    <td class="py-3 pr-4 font-mono text-gray-600">{{ $act->ended_at ?? 'Corriendo...' }}</td>
                                    <td class="py-3 pr-4 font-mono text-gray-700 duration-display">
                                        {{ $act->duration_seconds ? gmdate('H:i:s', $act->duration_seconds) : '00:00:00' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if($act->ended_at)
                                            <button onclick="openEditModal('{{ $act->id }}', '{{ $act->started_at }}', '{{ $act->ended_at }}', '{{ $act->activity_type }}')"
                                                class="text-xs text-gray-600 bg-bgPage border border-subtle hover:border-brand/40 hover:text-brand px-3 py-1 rounded-lg transition-colors">
                                                Editar
                                            </button>
                                        @else
                                            <span class="text-muted italic text-xs">En progreso</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="bg-bgCard rounded-2xl border border-subtle p-10 text-center text-muted text-sm">
                Este empleado no tiene registros de tiempo todavía.
            </div>
        @endforelse
    </div>
</div>

{{-- Modal de edición --}}
<div id="editModal" class="hidden fixed inset-0 bg-gray-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50">
    <div class="bg-bgCard border border-subtle rounded-2xl shadow-xl w-full max-w-md p-6">
        <h3 class="text-sm font-semibold text-gray-800 mb-5">Modificar registro: <span id="modalActivityType" class="text-brand capitalize"></span></h3>
        <form method="POST" action="{{ route('admin.updateActivity') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="activity_id" id="modalActivityId">
            <div>
                <label class="block text-xs text-muted mb-1.5">Inicio <span class="text-gray-400">(YYYY-MM-DD HH:MM:SS)</span></label>
                <input type="text" name="started_at" id="modalStartedAt" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm font-mono focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs text-muted mb-1.5">Fin <span class="text-gray-400">(YYYY-MM-DD HH:MM:SS)</span></label>
                <input type="text" name="ended_at" id="modalEndedAt" required class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm font-mono focus:border-brand outline-none transition-all">
            </div>
            <div>
                <label class="block text-xs text-muted mb-1.5">Razón del cambio</label>
                <textarea name="reason" required minlength="10" class="w-full bg-bgPage border border-subtle text-gray-800 rounded-lg px-3 py-2 text-sm focus:border-brand outline-none h-20 resize-none"></textarea>
            </div>
            <div class="flex gap-3 pt-1">
                <button type="button" onclick="closeEditModal()" class="flex-1 bg-bgPage border border-subtle text-gray-600 rounded-lg py-2 text-sm font-medium hover:text-gray-800">Cancelar</button>
                <button type="submit" class="flex-1 bg-brand hover:bg-brandHov text-white rounded-lg py-2 text-sm font-medium transition-colors">Guardar cambios</button>
            </div>
        </form>
    </div>
</div>

<script>

    // La hora real del servidor (formato ISO)
    const serverTime = new Date("{{ now()->toIso8601String() }}");
    const clientTimeOffset = new Date() - serverTime;

    function openEditModal(id, start, end, type) {
        document.getElementById('modalActivityId').value = id;
        document.getElementById('modalStartedAt').value = start;
        document.getElementById('modalEndedAt').value = end;
        document.getElementById('modalActivityType').innerText = type.replace('_', ' ');
        document.getElementById('editModal').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Cronómetro en vivo solo para filas sin fecha de fin
    setInterval(function() {
        if (document.getElementById('editModal').classList.contains('hidden')) {
            const rows = document.querySelectorAll('#activitiesTable tr');
            rows.forEach(row => {
                const startCell = row.querySelector('.started-at-data');
                const endCell = row.querySelector('.duration-display');

                // Solo actualizamos si la celda de inicio existe y no hay fecha de fin
                if (startCell && row.cells[2].innerText.trim() === 'Corriendo...') {

                    const startTime = new Date(startCell.dataset.time.replace(' ', 'T'));                    const now = new Date(new Date().getTime() - clientTimeOffset); // Ajustamos por la diferencia
                    const diff = Math.floor((now - startTime) / 1000);

                    const h = Math.floor(diff / 3600);
                    const m = Math.floor((diff % 3600) / 60);
                    const s = diff % 60;

                    endCell.innerText = String(h).padStart(2,'0') + ':' +
                                       String(m).padStart(2,'0') + ':' +
                                       String(s).padStart(2,'0');
                }
            });
        }
    }, 1000);
</script>
@endsection
