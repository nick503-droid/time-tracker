@extends('layouts.app')

@section('content')
<div style="width:100%;max-width:768px;">

    {{-- Header --}}
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px;">
        <div>
            <h1 style="font-size:20px;font-weight:600;color:#1f2937;">
                Hola, <span style="color:var(--color-brand);">{{ Auth::user()->name }}</span>
            </h1>
            <p style="font-size:14px;color:var(--color-muted);margin-top:2px;">Panel de control de tiempos</p>
        </div>
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}" class="btn btn-brand-outline" style="font-size:14px;">
                    Panel admin
                </a>
            @endif

            {{-- Solo mostramos "Terminar turno" si hay un turno activo y NO se ha terminado --}}
            @if($currentShift && !$shiftFinished)
                <form method="POST" action="{{ route('time.clockOut') }}" onsubmit="return confirm('¿Seguro que deseas terminar tu día?');">
                    @csrf
                    <button type="submit" class="btn btn-danger" style="font-size:14px;">
                        Terminar turno
                    </button>
                </form>
            @endif

            {{-- Botón de Cerrar Sesión --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" style="background:none;border:none;font-size:14px;color:var(--color-muted);cursor:pointer;text-decoration:underline;text-underline-offset:2px;transition:color 0.15s;"
                    onmouseover="this.style.color='#374151'" onmouseout="this.style.color='var(--color-muted)'">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('status'))
        <div class="alert alert-success">
            {{ session('status') }}
        </div>
    @endif

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;" class="emp-dashboard-grid">

        {{-- Panel principal --}}
        <div class="card" style="padding:28px;">

            {{-- Hora actual + tiempo trabajado --}}
            <div style="display:flex;gap:24px;justify-content:space-around;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--color-subtle);">
                <div style="text-align:center;">
                    <p style="font-size:11px;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Hora actual</p>
                    <div id="realTimeClock" style="font-size:24px;font-family:monospace;font-weight:500;color:#374151;letter-spacing:0.025em;">--:--:--</div>
                </div>
                @if($currentShift)
                <div style="text-align:center;border-left:1px solid var(--color-subtle);padding-left:24px;">
                    <p style="font-size:11px;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:6px;">Tiempo trabajado hoy</p>
                    <div style="font-size:24px;font-family:monospace;font-weight:500;color:var(--color-brand);letter-spacing:0.025em;">{{ $totalWorkedFormatted }}</div>
                </div>
                @endif
            </div>

            {{-- Lógica de Vistas --}}
            @if(!$currentShift)
                <div style="text-align:center;padding:32px 0;">
                    <p style="color:#6b7280;font-size:14px;margin-bottom:24px;">No has iniciado tu turno de hoy</p>
                    <form method="POST" action="{{ route('time.clockIn') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" style="padding:12px 40px;border-radius:12px;font-size:14px;">
                            Iniciar turno
                        </button>
                    </form>
                </div>
            @elseif($shiftFinished)
                <div style="text-align:center;padding:32px 0;">
                    <h2 style="font-size:30px;font-weight:700;color:#374151;margin-bottom:8px;">¡Jornada Terminada!</h2>
                    <p style="color:#6b7280;font-size:14px;margin-bottom:8px;">
                        Tu hora de salida fue registrada a las
                        <span style="font-weight:500;color:#1f2937;">{{ \Carbon\Carbon::parse($currentShift->logoff_time)->format('h:i A') }}</span>.
                    </p>
                    <p style="color:#9ca3af;font-size:14px;">¡Buen trabajo, nos vemos mañana!</p>
                </div>
            @else
                <div style="text-align:center;">
                    <p style="font-size:11px;color:var(--color-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">Estado actual</p>

                    <span style="display:inline-block;padding:6px 16px;border-radius:9999px;font-size:14px;font-weight:500;margin-bottom:20px;
                        {{ $currentActivity && $currentActivity->activity_type == 'ready'
                            ? 'background:rgba(74,124,89,0.1);color:var(--color-brand);'
                            : 'background:var(--color-warn-bg);color:var(--color-warn);border:1px solid var(--color-warn-brd);' }}">
                        {{ $currentActivity ? ucfirst(str_replace('_', ' ', $currentActivity->activity_type)) : 'Esperando...' }}
                    </span>

                    <div id="timerDisplay" class="timer-display">
                        00:00:00
                    </div>
                    <p style="font-size:12px;color:var(--color-muted);">Tiempo transcurrido en este estado</p>
                </div>
            @endif
        </div>

        {{-- Columna Derecha --}}
        <div style="display:flex;flex-direction:column;gap:20px;">
            {{-- Panel de acciones --}}
            <div class="card" style="display:flex;flex-direction:column;justify-content:center;">
                @if($currentShift && !$shiftFinished)
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--color-subtle);">
                        <p style="font-size:14px;font-weight:500;color:#374151;">Acciones</p>
                        <span style="font-size:12px;color:var(--color-muted);">Breaks: {{ $breaksCount }}/{{ Auth::user()->max_breaks_per_day }}</span>
                    </div>

                    <form method="POST" action="{{ route('time.changeStatus') }}" style="display:flex;flex-direction:column;gap:12px;">
                        @csrf

                        @php
                            $isReady = $currentActivity && $currentActivity->activity_type == 'ready';
                            $isBreak = $currentActivity && $currentActivity->activity_type == 'break';
                            $isLunch = $currentActivity && $currentActivity->activity_type == 'lunch';
                            $breaksMaxed = $breaksCount >= Auth::user()->max_breaks_per_day;
                        @endphp

                        <button type="submit" name="status" value="ready"
                            @disabled($isReady)
                            style="width:100%;padding:10px;border-radius:8px;font-size:14px;font-weight:500;border:1px solid;cursor:pointer;transition:all 0.15s;
                            {{ $isReady
                                ? 'background:rgba(74,124,89,0.1);border-color:rgba(74,124,89,0.3);color:var(--color-brand);opacity:0.6;cursor:not-allowed;'
                                : 'background:var(--color-bg-page);border-color:var(--color-subtle);color:#374151;' }}"
                            @if(!$isReady) onmouseover="this.style.borderColor='rgba(74,124,89,0.4)';this.style.background='rgba(74,124,89,0.05)'" onmouseout="this.style.borderColor='var(--color-subtle)';this.style.background='var(--color-bg-page)'" @endif>
                            Ponerse ready
                        </button>

                        <button type="submit" name="status" value="break"
                            @disabled($breaksMaxed || $isBreak)
                            style="width:100%;padding:10px;border-radius:8px;font-size:14px;font-weight:500;border:1px solid;cursor:pointer;transition:all 0.15s;
                            {{ $breaksMaxed
                                ? 'background:var(--color-bg-page);border-color:var(--color-subtle);color:#9ca3af;cursor:not-allowed;opacity:0.5;'
                                : ($isBreak
                                    ? 'background:var(--color-warn-bg);border-color:var(--color-warn-brd);color:var(--color-warn);opacity:0.6;cursor:not-allowed;'
                                    : 'background:var(--color-warn-bg);border-color:var(--color-warn-brd);color:var(--color-warn);') }}"
                            @if(!$breaksMaxed && !$isBreak) onmouseover="this.style.background='#fde8c8'" onmouseout="this.style.background='var(--color-warn-bg)'" @endif>
                            Break ({{ Auth::user()->break_duration_minutes }}m)
                        </button>

                        <button type="submit" name="status" value="lunch"
                            @disabled($hasTakenLunch || $isLunch)
                            style="width:100%;padding:10px;border-radius:8px;font-size:14px;font-weight:500;border:1px solid;cursor:pointer;transition:all 0.15s;
                            {{ $hasTakenLunch
                                ? 'background:var(--color-bg-page);border-color:var(--color-subtle);color:#9ca3af;cursor:not-allowed;opacity:0.5;'
                                : ($isLunch
                                    ? 'background:var(--color-bg-page);border-color:var(--color-subtle);color:#374151;opacity:0.6;cursor:not-allowed;'
                                    : 'background:var(--color-bg-page);border-color:var(--color-subtle);color:#374151;') }}"
                            @if(!$hasTakenLunch && !$isLunch) onmouseover="this.style.borderColor='rgba(74,124,89,0.4)';this.style.background='rgba(74,124,89,0.05)'" onmouseout="this.style.borderColor='var(--color-subtle)';this.style.background='var(--color-bg-page)'" @endif>
                            {{ $hasTakenLunch ? 'Lunch ya tomado' : 'Lunch ('.Auth::user()->lunch_duration_minutes.'m)' }}
                        </button>
                    </form>
                @elseif($shiftFinished)
                    <div style="text-align:center;color:#9ca3af;font-size:14px;">
                        Acciones deshabilitadas.
                    </div>
                @else
                    <div style="text-align:center;color:#9ca3af;font-size:14px;">
                        Inicia turno para ver acciones.
                    </div>
                @endif
            </div>

            {{-- Panel de Horario Asignado --}}
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--color-subtle);">
                    <p style="font-size:14px;font-weight:500;color:#374151;">Mi Horario Asignado</p>
                </div>

                <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;">
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--color-muted);">Entrada:</span>
                        <span style="font-weight:500;color:#1f2937;">
                            {{ Auth::user()->scheduled_in ? \Carbon\Carbon::parse(Auth::user()->scheduled_in)->format('h:i A') : 'No asignado' }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--color-muted);">Salida:</span>
                        <span style="font-weight:500;color:#1f2937;">
                            {{ Auth::user()->scheduled_out ? \Carbon\Carbon::parse(Auth::user()->scheduled_out)->format('h:i A') : 'No asignado' }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--color-muted);">Break:</span>
                        <span style="font-weight:500;color:#1f2937;">{{ Auth::user()->max_breaks_per_day }} al día ({{ Auth::user()->break_duration_minutes }}m)</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;">
                        <span style="color:var(--color-muted);">Lunch:</span>
                        <span style="font-weight:500;color:#1f2937;">{{ Auth::user()->lunch_duration_minutes }}m</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function updateRealTimeClock() {
        const now = new Date();
        let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
        const ampm = h >= 12 ? 'PM' : 'AM';
        h = h % 12 || 12;
        document.getElementById('realTimeClock').innerText =
            h + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') + ' ' + ampm;
    }
    updateRealTimeClock();
    setInterval(updateRealTimeClock, 1000);

    @if($currentActivity && !$shiftFinished)
        let elapsedSeconds = {{ (int) \Carbon\Carbon::parse($currentActivity->started_at)->diffInSeconds(now()) }};
        const timerDisplay = document.getElementById('timerDisplay');
        const currentStatus = "{{ $currentActivity->activity_type }}";
        const limits = {
            'break': {{ Auth::user()->break_duration_minutes }} * 60,
            'lunch': {{ Auth::user()->lunch_duration_minutes }} * 60
        };

        setInterval(function() {
            elapsedSeconds++;
            const h = Math.floor(elapsedSeconds / 3600);
            const m = Math.floor((elapsedSeconds % 3600) / 60);
            const s = elapsedSeconds % 60;
            timerDisplay.innerHTML =
                String(h).padStart(2,'0') + ':' +
                String(m).padStart(2,'0') + ':' +
                String(s).padStart(2,'0');

            if (limits[currentStatus] && elapsedSeconds > limits[currentStatus]) {
                timerDisplay.style.color = 'var(--color-danger)';
                timerDisplay.style.opacity = elapsedSeconds % 2 === 0 ? '0.4' : '1';
            }
        }, 1000);
    @endif
</script>

<style>
    @media (max-width: 767px) {
        .emp-dashboard-grid {
            grid-template-columns: 1fr !important;
        }
    }
</style>
@endsection
