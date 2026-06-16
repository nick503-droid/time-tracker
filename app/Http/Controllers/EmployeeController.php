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

        $currentShift = Shift::where('user_id', $user->id)
                             ->where('date', $todayDate)
                             ->first();

        // Nueva validación: ¿El turno ya se terminó?
        $shiftFinished = $currentShift && $currentShift->logoff_time !== null;

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
