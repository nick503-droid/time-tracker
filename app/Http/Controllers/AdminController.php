<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function createEmployee()
    {
        return view('admin.create-employee');
    }

    public function dashboard(Request $request)
    {
        // Rango de fechas seleccionado (por defecto hoy)
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        // Traemos a todos los usuarios
        $employees = User::whereIn('role', ['employee', 'admin'])->get();
        $reportData = [];

        foreach ($employees as $employee) {

            // 1. BUSCAMOS LOS TURNOS EN EL RANGO DE FECHAS
            $shifts = Shift::where('user_id', $employee->id)
                           ->whereBetween('date', [$startDate, $endDate])
                           ->get();

            // Si el empleado no tiene turnos en estas fechas, lo saltamos por completo
            if ($shifts->isEmpty()) {
                continue;
            }

            // 2. DETERMINAR ESTADO EN VIVO (Solo tiene sentido si el filtro incluye HOY)
            $isTodayIncluded = ($startDate <= now()->toDateString() && $endDate >= now()->toDateString());
            $currentStatus = 'Histórico'; // Por defecto para reportes del pasado

            if ($isTodayIncluded) {
                $todayShift = Shift::where('user_id', $employee->id)
                                    ->where('date', now()->toDateString())
                                    ->first();
                if ($todayShift) {
                    if ($todayShift->logoff_time) {
                        $currentStatus = 'Turno Terminado';
                    } else {
                        $activeActivity = ShiftActivity::where('shift_id', $todayShift->id)
                                                      ->whereNull('ended_at')
                                                      ->first();
                        if ($activeActivity) {
                            if ($activeActivity->activity_type == 'ready') {
                                $currentStatus = 'Trabajando';
                            } elseif ($activeActivity->activity_type == 'break') {
                                $currentStatus = 'Descansando';
                            } elseif ($activeActivity->activity_type == 'lunch') {
                                $currentStatus = 'En Lunch';
                            }
                        } else {
                            $currentStatus = 'Iniciado';
                        }
                    }
                } else {
                    $currentStatus = 'Desconectado';
                }
            }

            // 3. CALCULAR TIEMPOS ACUMULADOS
            $totalWorkSeconds = 0;
            $totalBreakSeconds = 0;

            foreach ($shifts as $shift) {
                $totalWorkSeconds += ShiftActivity::where('shift_id', $shift->id)
                                                  ->where('activity_type', 'ready')
                                                  ->sum('duration_seconds');

                $totalBreakSeconds += ShiftActivity::where('shift_id', $shift->id)
                                                   ->whereIn('activity_type', ['break', 'lunch'])
                                                   ->sum('duration_seconds');

                // Sumar el tiempo "en vivo" si el turno de hoy sigue corriendo
                if ($shift->date == now()->toDateString() && !$shift->logoff_time) {
                    $activeActivity = ShiftActivity::where('shift_id', $shift->id)
                                                  ->whereNull('ended_at')
                                                  ->first();
                    if ($activeActivity) {
                        $liveSeconds = \Carbon\Carbon::parse($activeActivity->started_at)->diffInSeconds(now());

                        if ($activeActivity->activity_type == 'ready') {
                            $totalWorkSeconds += $liveSeconds;
                        } else {
                            $totalBreakSeconds += $liveSeconds;
                        }
                    }
                }
            }

            // Agregamos a la lista solo a los que pasaron el filtro
            $reportData[] = [
                'id' => $employee->id,
                'employee' => $employee->name,
                'email' => $employee->email,
                'status' => $currentStatus,
                'total_worked' => gmdate('H:i:s', $totalWorkSeconds),
                'total_break' => gmdate('H:i:s', $totalBreakSeconds),
            ];
        }

        return view('admin.dashboard', compact('reportData', 'startDate', 'endDate'));
    }

    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:admin,employee',
            'scheduled_in' => 'required',
            'scheduled_out' => 'required',
            'break_duration_minutes' => 'required|integer|min:0',
            'lunch_duration_minutes' => 'required|integer|min:0',
            'max_breaks_per_day' => 'required|integer|min:0',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'scheduled_in' => $request->scheduled_in,
            'scheduled_out' => $request->scheduled_out,
            'break_duration_minutes' => $request->break_duration_minutes,
            'lunch_duration_minutes' => $request->lunch_duration_minutes,
            'max_breaks_per_day' => $request->max_breaks_per_day,
            'password_changed_at' => null,
        ]);

        return redirect()->route('admin.dashboard')->with('status', '¡Usuario y horario creados con éxito!');
    }

    public function viewEmployeeDetails($id)
    {
        $employee = User::findOrFail($id);

        // Traemos los turnos del empleado con sus actividades ordenadas
        $shifts = Shift::where('user_id', $employee->id)
                       ->orderBy('date', 'desc')
                       ->get();

        foreach ($shifts as $shift) {
            $shift->activities = ShiftActivity::where('shift_id', $shift->id)
                                              ->orderBy('started_at', 'asc')
                                              ->get();

            // Calcular duración al vuelo si está en progreso
            foreach ($shift->activities as $act) {
                if (!$act->ended_at) {
                    $act->duration_seconds = Carbon::parse($act->started_at)->diffInSeconds(now());
                }
            }
        }

        return view('admin.employee-details', compact('employee', 'shifts'));
    }

    public function updateActivity(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|exists:shift_activities,id',
            'started_at' => 'required|date_format:Y-m-d H:i:s',
            'ended_at' => 'required|date_format:Y-m-d H:i:s',
            'reason' => 'required|string|min:10',
        ]);

        $activity = ShiftActivity::findOrFail($request->activity_id);
        $shift = Shift::findOrFail($activity->shift_id);

        // Guardamos los valores viejos para la auditoría antes de cambiarlos
        $oldValue = [
            'started_at' => $activity->started_at,
            'ended_at' => $activity->ended_at,
            'duration_seconds' => $activity->duration_seconds,
        ];

        // Calculamos la nueva duración en segundos basándonos en las nuevas horas
        $start = Carbon::parse($request->started_at);
        $end = Carbon::parse($request->ended_at);
        $newDurationSeconds = $start->diffInSeconds($end);

        // Actualizamos la actividad
        $activity->update([
            'started_at' => $request->started_at,
            'ended_at' => $request->ended_at,
            'duration_seconds' => $newDurationSeconds,
        ]);

        // Registramos el movimiento en la tabla de auditoría
        AuditLog::create([
            'admin_id' => Auth::id(),
            'affected_user_id' => $shift->user_id,
            'action' => 'Modificación manual de tiempo de ' . $activity->activity_type,
            'old_value' => json_encode($oldValue),
            'new_value' => json_encode([
                'started_at' => $request->started_at,
                'ended_at' => $request->ended_at,
                'duration_seconds' => $newDurationSeconds,
            ]),
            'reason' => $request->reason,
        ]);

        return back()->with('status', '¡Registro de tiempo modificado y auditado correctamente!');
    }

    public function editEmployee($id)
    {
        $employee = User::findOrFail($id);
        return view('admin.edit-employee', compact('employee'));
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $employee->id,
            'role' => 'required|in:admin,employee',
            'scheduled_in' => 'required',
            'scheduled_out' => 'required',
            'break_duration_minutes' => 'required|integer|min:0',
            'lunch_duration_minutes' => 'required|integer|min:0',
            'max_breaks_per_day' => 'required|integer|min:0',
        ]);

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'scheduled_in' => $request->scheduled_in,
            'scheduled_out' => $request->scheduled_out,
            'break_duration_minutes' => $request->break_duration_minutes,
            'lunch_duration_minutes' => $request->lunch_duration_minutes,
            'max_breaks_per_day' => $request->max_breaks_per_day,
        ]);

        return redirect()->route('admin.dashboard')->with('status', '¡Información y horarios actualizados correctamente!');
    }

    public function showExportForm()
    {
        $employees = User::whereIn('role', ['employee', 'admin'])->orderBy('name')->get();
        return view('admin.export', compact('employees'));
    }

    public function downloadExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'required'
        ]);

        $query = Shift::with(['user', 'activities'])
                      ->whereBetween('date', [$request->start_date, $request->end_date]);

        // Si seleccionó un empleado en específico
        if ($request->employee_id !== 'all') {
            $query->where('user_id', $request->employee_id);
        }

        $shifts = $query->orderBy('date', 'asc')->get();

        $fileName = "reporte_nomina_" . $request->start_date . "_al_" . $request->end_date . ".csv";

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $callback = function() use($shifts) {
            $file = fopen('php://output', 'w');

            // 1. BOM para que Excel lea las tildes (á, é, í, ñ)
            fputs($file, "\xEF\xBB\xBF");

            // 2. TRUCO MAESTRO: Le decimos a Excel explícitamente que el separador es la coma
            fputs($file, "sep=,\n");

            // 3. Encabezados (Ya sin el ';' al final, usando la coma por defecto)
            fputcsv($file, ['Fecha', 'Empleado', 'Email', 'Rol', 'Hora Login', 'Hora Logoff', 'Trabajo Efectivo', 'Tiempo Breaks']);

            foreach ($shifts as $shift) {
                $totalWork = 0;
                $totalBreak = 0;

                foreach ($shift->activities as $act) {
                    if ($act->duration_seconds) {
                        if ($act->activity_type == 'ready') {
                            $totalWork += $act->duration_seconds;
                        } elseif (in_array($act->activity_type, ['break', 'lunch'])) {
                            $totalBreak += $act->duration_seconds;
                        }
                    }
                }

                // 4. Filas de datos (También sin el ';')
                fputcsv($file, [
                    $shift->date,
                    $shift->user->name,
                    $shift->user->email,
                    strtoupper($shift->user->role),
                    $shift->login_time,
                    $shift->logoff_time ?? 'Sin cerrar',
                    gmdate('H:i:s', $totalWork),
                    gmdate('H:i:s', $totalBreak)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
