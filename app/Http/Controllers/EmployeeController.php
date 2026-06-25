<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\Schedule;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $todayDate = now()->toDateString();
        $dayOfWeek = now()->format('l');

        $schedule = Schedule::where('user_id', $user->id)
                            ->where('day_of_week', $dayOfWeek)
                            ->first();

        // 1. REGLA NOCTURNA: Primero buscamos si tiene un turno ABIERTO de cualquier día.
        $openShift = Shift::where('user_id', $user->id)
                          ->whereNull('logoff_time')
                          ->latest()
                          ->first();

        if ($openShift) {
            // Si hay turno abierto, ese es su turno actual y no ha terminado.
            $currentShift = $openShift;
            $shiftFinished = false;
        } else {
            // 2. REGLA DE DÍA: Si no hay turno abierto, buscamos si ya cerró uno HOY.
            $currentShift = Shift::where('user_id', $user->id)
                                 ->where('date', $todayDate)
                                 ->latest()
                                 ->first();

            // Si encontró uno hoy y no está abierto, significa que ya lo terminó.
            $shiftFinished = $currentShift ? true : false;
        }

        $settings = Setting::firstOrCreate(['id' => 1]);

        $currentActivity = null;
        $breaksCount = 0;
        $hasTakenLunch = false;
        $totalWorkedSeconds = 0;

        if ($currentShift) {
            $currentActivity = ShiftActivity::where('shift_id', $currentShift->id)
                                            ->whereNull('ended_at')
                                            ->first();

            $breaksCount = ShiftActivity::where('shift_id', $currentShift->id)
                                        ->where('activity_type', 'break')
                                        ->count();

            $hasTakenLunch = ShiftActivity::where('shift_id', $currentShift->id)
                                          ->where('activity_type', 'lunch')
                                          ->exists();

            $totalWorkedSeconds = ShiftActivity::where('shift_id', $currentShift->id)
                                               ->where('activity_type', 'ready')
                                               ->sum('duration_seconds');
        }

        $totalWorkedFormatted = gmdate('H:i:s', $totalWorkedSeconds);

        return view('employee.dashboard', compact(
            'schedule',
            'currentShift',
            'currentActivity',
            'breaksCount',
            'hasTakenLunch',
            'totalWorkedFormatted',
            'shiftFinished'
        ));
    }
}
