@extends('layouts.app')

@section('content')
<div class="w-full max-w-3xl">

    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-xl font-semibold text-gray-800">Hola, <span class="text-brand">{{ Auth::user()->name }}</span></h1>
            <p class="text-sm text-muted mt-0.5">Panel de control de tiempos</p>
        </div>
        <div class="flex gap-4 items-center">
            @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.dashboard') }}"
                   class="text-sm text-brand border border-brand/40 bg-brand/5 hover:bg-brand/10 px-4 py-2 rounded-lg transition-colors font-medium">
                    Panel admin
                </a>
            @endif

            {{-- Solo mostramos "Terminar turno" si hay un turno activo y NO se ha terminado --}}
            @if($currentShift && !$shiftFinished)
                <form method="POST" action="{{ route('time.clockOut') }}" onsubmit="return confirm('¿Seguro que deseas terminar tu día?');">
                    @csrf
                    <button type="submit"
                        class="text-sm text-danger border border-dangerBrd bg-dangerBg hover:bg-red-100 px-4 py-2 rounded-lg transition-colors font-medium">
                        Terminar turno
                    </button>
                </form>
            @endif

            {{-- Botón de Cerrar Sesión --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-muted hover:text-gray-700 transition-colors underline decoration-transparent hover:decoration-gray-300">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-6">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

        {{-- Panel principal --}}
        <div class="col-span-1 md:col-span-2 bg-bgCard rounded-2xl border border-subtle shadow-sm p-7">

            {{-- Hora actual + tiempo trabajado --}}
            <div class="flex gap-6 justify-around mb-6 pb-6 border-b border-subtle">
                <div class="text-center">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1.5">Hora actual</p>
                    <div id="realTimeClock" class="text-2xl font-mono font-medium text-gray-700 tracking-wide">--:--:--</div>
                </div>
                @if($currentShift)
                <div class="text-center border-l border-subtle pl-6">
                    <p class="text-xs text-muted uppercase tracking-wide mb-1.5">Tiempo trabajado hoy</p>
                    <div class="text-2xl font-mono font-medium text-brand tracking-wide">{{ $totalWorkedFormatted }}</div>
                </div>
                @endif
            </div>

            {{-- Lógica de Vistas (Start, Despedida, o Reloj) --}}
            @if(!$currentShift)
                <div class="text-center py-8">
                    <p class="text-gray-500 text-sm mb-6">No has iniciado tu turno de hoy</p>
                    <form method="POST" action="{{ route('time.clockIn') }}">
                        @csrf
                        <button type="submit"
                            class="bg-brand hover:bg-brandHov text-white font-medium px-10 py-3 rounded-xl text-sm transition-colors shadow-sm">
                            Iniciar turno
                        </button>
                    </form>
                </div>
            @elseif($shiftFinished)
                <div class="text-center py-8">
                    <h2 class="text-3xl font-bold text-gray-700 mb-2">¡Jornada Terminada!</h2>
                    <p class="text-gray-500 text-sm mb-2">Tu hora de salida fue registrada a las <span class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($currentShift->logoff_time)->format('h:i A') }}</span>.</p>
                    <p class="text-gray-400 text-sm">¡Buen trabajo, nos vemos mañana!</p>
                </div>
            @else
                <div class="text-center">
                    <p class="text-xs text-muted uppercase tracking-wide mb-2">Estado actual</p>

                    <span class="inline-block px-4 py-1.5 rounded-full text-sm font-medium mb-5
                        {{ $currentActivity && $currentActivity->activity_type == 'ready'
                            ? 'bg-brand/10 text-brand'
                            : 'bg-warnBg text-warn border border-warnBrd' }}">
                        {{ $currentActivity ? ucfirst(str_replace('_', ' ', $currentActivity->activity_type)) : 'Esperando...' }}
                    </span>

                    <div id="timerDisplay" class="text-6xl font-mono font-medium text-gray-800 mb-2 tracking-wider transition-colors duration-300">
                        00:00:00
                    </div>
                    <p class="text-xs text-muted">Tiempo transcurrido en este estado</p>
                </div>
            @endif
        </div>

        {{-- Columna Derecha --}}
        <div>
            {{-- Panel de acciones --}}
            <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6 flex flex-col justify-center">
                @if($currentShift && !$shiftFinished)
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-subtle">
                        <p class="text-sm font-medium text-gray-700">Acciones</p>
                        <span class="text-xs text-muted">Breaks: {{ $breaksCount }}/{{ Auth::user()->max_breaks_per_day }}</span>
                    </div>

                    <form method="POST" action="{{ route('time.changeStatus') }}" class="flex flex-col gap-3">
                        @csrf

                        <button type="submit" name="status" value="ready"
                            @disabled($currentActivity && $currentActivity->activity_type == 'ready')
                            class="w-full py-2.5 rounded-lg text-sm font-medium transition-all border
                            {{ $currentActivity && $currentActivity->activity_type == 'ready'
                                ? 'bg-brand/10 border-brand/30 text-brand opacity-60 cursor-not-allowed'
                                : 'bg-bgPage border-subtle text-gray-700 hover:border-brand/40 hover:bg-brand/5' }}">
                            Ponerse ready
                        </button>

                        <button type="submit" name="status" value="break"
                            @disabled($breaksCount >= Auth::user()->max_breaks_per_day || ($currentActivity && $currentActivity->activity_type == 'break'))
                            class="w-full py-2.5 rounded-lg text-sm font-medium transition-all border
                            {{ $breaksCount >= Auth::user()->max_breaks_per_day
                                ? 'bg-bgPage border-subtle text-gray-400 cursor-not-allowed opacity-50'
                                : ($currentActivity && $currentActivity->activity_type == 'break'
                                    ? 'bg-warnBg border-warnBrd text-warn opacity-60 cursor-not-allowed'
                                    : 'bg-warnBg border-warnBrd text-warn hover:bg-amber-100') }}">
                            Break ({{ Auth::user()->break_duration_minutes }}m)
                        </button>

                        <button type="submit" name="status" value="lunch"
                            @disabled($hasTakenLunch || ($currentActivity && $currentActivity->activity_type == 'lunch'))
                            class="w-full py-2.5 rounded-lg text-sm font-medium transition-all border
                            {{ $hasTakenLunch
                                ? 'bg-bgPage border-subtle text-gray-400 cursor-not-allowed opacity-50'
                                : ($currentActivity && $currentActivity->activity_type == 'lunch'
                                    ? 'bg-bgPage border-subtle text-gray-700 opacity-60 cursor-not-allowed'
                                    : 'bg-bgPage border-subtle text-gray-700 hover:border-brand/40 hover:bg-brand/5') }}">
                            {{ $hasTakenLunch ? 'Lunch ya tomado' : 'Lunch ('.Auth::user()->lunch_duration_minutes.'m)' }}
                        </button>
                    </form>
                @elseif($shiftFinished)
                    <div class="text-center text-gray-400 text-sm">
                        Acciones deshabilitadas.
                    </div>
                @else
                    <div class="text-center text-gray-400 text-sm">
                        Inicia turno para ver acciones.
                    </div>
                @endif
            </div>

            {{-- Panel de Horario Asignado --}}
            <div class="bg-bgCard rounded-2xl border border-subtle shadow-sm p-6 flex flex-col justify-center mt-5">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-subtle">
                    <p class="text-sm font-medium text-gray-700">Mi Horario Asignado</p>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted">Entrada:</span>
                        <span class="font-medium text-gray-800">
                            {{ Auth::user()->scheduled_in ? \Carbon\Carbon::parse(Auth::user()->scheduled_in)->format('h:i A') : 'No asignado' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Salida:</span>
                        <span class="font-medium text-gray-800">
                            {{ Auth::user()->scheduled_out ? \Carbon\Carbon::parse(Auth::user()->scheduled_out)->format('h:i A') : 'No asignado' }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Break:</span>
                        <span class="font-medium text-gray-800">{{ Auth::user()->max_breaks_per_day }} al día ({{ Auth::user()->break_duration_minutes }}m)</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted">Lunch:</span>
                        <span class="font-medium text-gray-800">{{ Auth::user()->lunch_duration_minutes }}m</span>
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
                timerDisplay.classList.add('text-danger');
                timerDisplay.style.opacity = elapsedSeconds % 2 === 0 ? '0.4' : '1';
            }
        }, 1000);
    @endif
</script>
@endsection
