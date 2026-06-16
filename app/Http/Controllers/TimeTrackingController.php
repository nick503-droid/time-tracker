<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftActivity;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon; // Librería de Laravel para manejar fechas y horas súper fácil

class TimeTrackingController extends Controller
{
    /**
     * Inicia el turno del día (El primer "Ponerse Ready")
     */
    public function clockIn()
    {
        $user = Auth::user();
        $now = now(); // Obtiene fecha y hora actual

        // 1. Buscamos si el empleado ya tiene un turno creado hoy (para no duplicar)
        $shift = Shift::where('user_id', $user->id)
                      ->whereDate('date', $now->toDateString())
                      ->first();

        // 2. Si no tiene turno hoy, se lo creamos
        if (!$shift) {
            $shift = Shift::create([
                'user_id' => $user->id,
                'date' => $now->toDateString(),
                'login_time' => $now,
            ]);
        }

        // 3. Verificamos que no tenga una actividad activa (un botón ya presionado)
        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        // 4. Si no hay nada activo, lo ponemos en "ready"
        if (!$currentActivity) {
            ShiftActivity::create([
                'shift_id' => $shift->id,
                'activity_type' => 'ready',
                'started_at' => $now,
            ]);
        }

        return back()->with('status', '¡Turno iniciado! Estás Ready.');
    }

    /**
     * Cambia el estado del empleado (Ej: Pasa de Ready a Break_1)
     */
    public function changeStatus(Request $request)
    {
        // Validamos que los botones que presionen sean solo los permitidos
        $request->validate([
            'status' => 'required|in:ready,break,lunch,other'
        ]);

        $user = Auth::user();
        $now = now();

        // 1. Buscar el turno de hoy
        $shift = Shift::where('user_id', $user->id)
                      ->whereDate('date', $now->toDateString())
                      ->first();

        if (!$shift) {
            return back()->withErrors('No has iniciado tu turno hoy.');
        }

        // 2. Buscar la actividad actual (la que aún no tiene hora de fin)
        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        // 3. ¡LA MAGIA DE LOS TIEMPOS! Si hay una actividad abierta, la cerramos y calculamos cuánto duró
        if ($currentActivity) {
            $currentActivity->ended_at = $now;

            // diffInSeconds calcula la diferencia en segundos desde que inició hasta ahorita
            $currentActivity->duration_seconds = Carbon::parse($currentActivity->started_at)->diffInSeconds($now);
            $currentActivity->save();
        }

        // 4. Abrimos la nueva actividad que el usuario seleccionó
        ShiftActivity::create([
            'shift_id' => $shift->id,
            'activity_type' => $request->status, // Aquí entra el 'break_1', 'lunch', etc.
            'started_at' => $now,
        ]);

        return back()->with('status', 'Estado actualizado a: ' . $request->status);
    }

    /**
     * Termina el turno del día (Log Off)
     */
    public function clockOut()
    {
        $user = Auth::user();
        $now = now();

        // 1. Buscar el turno de hoy que siga abierto
        $shift = Shift::where('user_id', $user->id)
                      ->whereDate('date', $now->toDateString())
                      ->whereNull('logoff_time')
                      ->first();

        if (!$shift) {
            return back()->withErrors('No tienes un turno activo para finalizar.');
        }

        // 2. Buscar si quedó alguna actividad a medias (ej. le dio Log Off estando en Ready)
        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        // Cerramos esa actividad y calculamos sus últimos segundos
        if ($currentActivity) {
            $currentActivity->ended_at = $now;
            $currentActivity->duration_seconds = Carbon::parse($currentActivity->started_at)->diffInSeconds($now);
            $currentActivity->save();
        }

        // 3. Cerramos el turno general
        $shift->logoff_time = $now;
        $shift->save();

        // Opcional: Podrías desloguear al usuario aquí si lo deseas,
        // pero por ahora solo lo mandamos al dashboard con su mensaje de éxito.
        return back()->with('status', '¡Turno finalizado! Buen descanso.');
    }
}
