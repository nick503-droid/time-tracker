<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftActivity;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TimeTrackingController extends Controller
{
    public function clockIn()
    {
        $user = Auth::user();
        $now = now();

        // REGLA NOCTURNA: Buscamos si el usuario tiene un turno ABIERTO, sin importar de qué día sea.
        $shift = Shift::where('user_id', $user->id)
                      ->whereNull('logoff_time')
                      ->latest()
                      ->first();

        // Si no tiene ningún turno abierto, le creamos uno nuevo con la fecha de hoy.
        if (!$shift) {
            $shift = Shift::create([
                'user_id' => $user->id,
                'date' => $now->toDateString(),
                'login_time' => $now,
            ]);
        }

        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        if (!$currentActivity) {
            ShiftActivity::create([
                'shift_id' => $shift->id,
                'activity_type' => 'ready',
                'started_at' => $now,
            ]);
        }

        return back()->with('status', '¡Turno iniciado! Estás Ready.');
    }

    public function changeStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|in:ready,break,lunch,other'
        ]);

        $user = Auth::user();
        $now = now();

        // REGLA NOCTURNA: Buscamos el turno ABIERTO, en lugar del turno "de hoy"
        $shift = Shift::where('user_id', $user->id)
                      ->whereNull('logoff_time')
                      ->latest()
                      ->first();

        if (!$shift) {
            return back()->withErrors('No tienes un turno activo en este momento.');
        }

        // --- VALIDACIONES DE BACKEND PARA LÍMITES DE TIEMPOS ---
        if ($request->status === 'break') {
            $breaksCount = ShiftActivity::where('shift_id', $shift->id)
                                        ->where('activity_type', 'break')
                                        ->count();
            if ($breaksCount >= $user->max_breaks_per_day) {
                return back()->withErrors('Acceso Denegado: Has alcanzado el límite máximo de breaks permitidos por hoy.');
            }
        }

        if ($request->status === 'lunch') {
            $hasLunch = ShiftActivity::where('shift_id', $shift->id)
                                       ->where('activity_type', 'lunch')
                                       ->exists();
            if ($hasLunch) {
                return back()->withErrors('Acceso Denegado: Ya registraste tu tiempo de almuerzo obligatorio para este turno.');
            }
        }

        // Cerramos la actividad previa calculando su duración
        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        if ($currentActivity) {
            $currentActivity->ended_at = $now;
            $currentActivity->duration_seconds = Carbon::parse($currentActivity->started_at)->diffInSeconds($now);
            $currentActivity->save();
        }

        // Creamos la nueva marca solicitada
        ShiftActivity::create([
            'shift_id' => $shift->id,
            'activity_type' => $request->status,
            'started_at' => $now,
        ]);

        return back()->with('status', 'Estado actualizado a: ' . $request->status);
    }

    public function clockOut()
    {
        $user = Auth::user();
        $now = now();

        // REGLA NOCTURNA: Buscamos el turno que sigue abierto
        $shift = Shift::where('user_id', $user->id)
                      ->whereNull('logoff_time')
                      ->latest()
                      ->first();

        if (!$shift) {
            return back()->withErrors('No tienes un turno activo para finalizar.');
        }

        $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                        ->whereNull('ended_at')
                                        ->first();

        if ($currentActivity) {
            $currentActivity->ended_at = $now;
            $currentActivity->duration_seconds = Carbon::parse($currentActivity->started_at)->diffInSeconds($now);
            $currentActivity->save();
        }

        $shift->logoff_time = $now;
        $shift->save();

        return back()->with('status', '¡Turno finalizado! Buen descanso.');
    }
}
