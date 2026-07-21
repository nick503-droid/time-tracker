@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:1024px;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:1px solid var(--color-subtle);">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="back-link">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 19l-7-7 7-7"/></svg>
                Volver al panel
            </a>
            <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
                    Historial de <span style="color:var(--color-brand);">{{ $employee->name }}</span>
                </h1>
                <a href="{{ route('admin.editEmployee', $employee->id) }}"
                   class="btn btn-ghost btn-sm">
                    Modificar Horario / Perfil
                </a>
            </div>
            <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">{{ $employee->email }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    {{-- Jornadas --}}
    <div style="display:flex;flex-direction:column;gap:20px;">
        @forelse($shifts as $shift)
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--color-subtle);">
                    <h3 style="font-size:14px;font-weight:600;color:#374151;">
                        Jornada: {{ Carbon\Carbon::parse($shift->date)->format('d/m/Y') }}
                    </h3>
                    <span style="font-size:12px;color:var(--color-muted);font-family:monospace;">
                        Login: {{ $shift->login_time }} &nbsp;|&nbsp; Logoff: {{ $shift->logoff_time ?? 'Activo' }}
                    </span>
                </div>

                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;text-align:left;font-size:12px;">
                        <thead>
                            <tr style="color:var(--color-muted);text-transform:uppercase;letter-spacing:0.03em;border-bottom:1px solid var(--color-subtle);">
                                <th style="padding-bottom:8px;padding-right:16px;font-weight:600;">Actividad</th>
                                <th style="padding-bottom:8px;padding-right:16px;font-weight:600;">Inicio</th>
                                <th style="padding-bottom:8px;padding-right:16px;font-weight:600;">Fin</th>
                                <th style="padding-bottom:8px;padding-right:16px;font-weight:600;">Duración</th>
                                <th style="padding-bottom:8px;text-align:right;font-weight:600;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="activitiesTable" style="color:#374151;">
                            @foreach($shift->activities as $act)
                                <tr style="border-bottom:1px solid rgba(232,230,225,0.5);transition:background 0.15s;"
                                    onmouseover="this.style.background='var(--color-bg-page)'"
                                    onmouseout="this.style.background='transparent'">
                                    <td style="padding:12px 16px 12px 0;">
                                        <span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:12px;font-weight:500;
                                            background:{{ $act->activity_type == 'ready' ? 'rgba(74,124,89,0.1)' : 'var(--color-warn-bg)' }};
                                            color:{{ $act->activity_type == 'ready' ? 'var(--color-brand)' : 'var(--color-warn)' }};">
                                            {{ ucfirst(str_replace('_', ' ', $act->activity_type)) }}
                                        </span>
                                    </td>
                                    <td class="started-at-data" data-time="{{ $act->started_at }}"
                                        style="padding:12px 16px 12px 0;font-family:monospace;color:#4b5563;">
                                        {{ $act->started_at }}
                                    </td>
                                    <td style="padding:12px 16px 12px 0;font-family:monospace;color:#4b5563;">
                                        {{ $act->ended_at ?? 'Corriendo...' }}
                                    </td>
                                    <td class="duration-display" style="padding:12px 16px 12px 0;font-family:monospace;color:#374151;">
                                        {{ $act->duration_seconds ? gmdate('H:i:s', $act->duration_seconds) : '00:00:00' }}
                                    </td>
                                    <td style="padding:12px 0;text-align:right;">
                                        @if($act->ended_at)
                                            <button onclick="openEditModal('{{ $act->id }}', '{{ $act->started_at }}', '{{ $act->ended_at }}', '{{ $act->activity_type }}')"
                                                class="btn btn-ghost btn-sm">
                                                Editar
                                            </button>
                                        @else
                                            <span style="color:var(--color-muted);font-style:italic;font-size:12px;">En progreso</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="card" style="text-align:center;padding:40px;color:var(--color-muted);font-size:14px;">
                Este empleado no tiene registros de tiempo todavía.
            </div>
        @endforelse
    </div>
</div>

{{-- Modal de edición --}}
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(17,24,39,0.4);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:16px;z-index:50;">
    <div class="card" style="width:100%;max-width:448px;padding:24px;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
        <h3 style="font-size:14px;font-weight:600;color:#1f2937;margin-bottom:20px;">
            Modificar registro: <span id="modalActivityType" style="color:var(--color-brand);text-transform:capitalize;"></span>
        </h3>
        <form method="POST" action="{{ route('admin.updateActivity') }}" style="display:flex;flex-direction:column;gap:16px;">
            @csrf
            <input type="hidden" name="activity_id" id="modalActivityId">
            <div>
                <label class="label">Inicio <span style="color:var(--color-muted);font-weight:400;">(YYYY-MM-DD HH:MM:SS)</span></label>
                <input type="text" name="started_at" id="modalStartedAt" required class="input input-mono">
            </div>
            <div>
                <label class="label">Fin <span style="color:var(--color-muted);font-weight:400;">(YYYY-MM-DD HH:MM:SS)</span></label>
                <input type="text" name="ended_at" id="modalEndedAt" required class="input input-mono">
            </div>
            <div>
                <label class="label">Razón del cambio</label>
                <textarea name="reason" required minlength="10"
                    style="width:100%;background:var(--color-bg-page);border:1px solid var(--color-subtle);border-radius:8px;padding:8px 12px;font-size:14px;color:#1f2937;outline:none;height:80px;resize:none;font-family:inherit;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--color-brand)'"
                    onblur="this.style.borderColor='var(--color-subtle)'"></textarea>
            </div>
            <div style="display:flex;gap:12px;padding-top:4px;">
                <button type="button" onclick="closeEditModal()" class="btn btn-ghost" style="flex:1;">Cancelar</button>
                <button type="submit" class="btn btn-primary" style="flex:1;">Guardar cambios</button>
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
        document.getElementById('editModal').style.display = 'flex';
    }
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Cronómetro en vivo solo para filas sin fecha de fin
    setInterval(function() {
        if (document.getElementById('editModal').style.display === 'none') {
            const rows = document.querySelectorAll('#activitiesTable tr');
            rows.forEach(row => {
                const startCell = row.querySelector('.started-at-data');
                const endCell = row.querySelector('.duration-display');

                if (startCell && row.cells[2].innerText.trim() === 'Corriendo...') {
                    const startTime = new Date(startCell.dataset.time.replace(' ', 'T'));
                    const now = new Date(new Date().getTime() - clientTimeOffset);
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
