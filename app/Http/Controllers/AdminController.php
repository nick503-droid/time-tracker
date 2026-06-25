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
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $employees = User::whereIn('role', ['employee', 'admin'])->get();
        $reportData = [];

        foreach ($employees as $employee) {

            // SOLUCIÓN N+1: Traemos los turnos con todas sus actividades precargadas en una sola consulta
            $shifts = Shift::with('activities')
                           ->where('user_id', $employee->id)
                           ->whereBetween('date', [$startDate, $endDate])
                           ->get();

            if ($shifts->isEmpty()) {
                continue;
            }

            $isTodayIncluded = ($startDate <= now()->toDateString() && $endDate >= now()->toDateString());
            $currentStatus = 'Histórico';

            $totalWorkSeconds = 0;
            $totalBreakSeconds = 0;

            foreach ($shifts as $shift) {
                // Sumamos los tiempos iterando sobre la colección ya cargada en PHP
                foreach ($shift->activities as $act) {
                    if ($act->duration_seconds) {
                        if ($act->activity_type === 'ready') {
                            $totalWorkSeconds += $act->duration_seconds;
                        } elseif (in_array($act->activity_type, ['break', 'lunch'])) {
                            $totalBreakSeconds += $act->duration_seconds;
                        }
                    }
                }

                // Determinamos el estado en vivo si corresponde a la fecha de hoy
                if ($shift->date == now()->toDateString()) {
                    if ($shift->logoff_time) {
                        $currentStatus = 'Turno Terminado';
                    } else {
                        // Buscamos la actividad en curso directo en memoria
                        $activeActivity = $shift->activities->whereNull('ended_at')->first();

                        if ($activeActivity) {
                            $liveSeconds = Carbon::parse($activeActivity->started_at)->diffInSeconds(now());

                            if ($activeActivity->activity_type === 'ready') {
                                $totalWorkSeconds += $liveSeconds;
                                $currentStatus = 'Trabajando';
                            } elseif ($activeActivity->activity_type === 'break') {
                                $totalBreakSeconds += $liveSeconds;
                                $currentStatus = 'Descansando';
                            } elseif ($activeActivity->activity_type === 'lunch') {
                                $totalBreakSeconds += $liveSeconds;
                                $currentStatus = 'En Lunch';
                            }
                        } else {
                            $currentStatus = 'Iniciado';
                        }
                    }
                }
            }

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

        $shifts = Shift::where('user_id', $employee->id)
                       ->orderBy('date', 'desc')
                       ->get();

        foreach ($shifts as $shift) {
            $shift->activities = ShiftActivity::where('shift_id', $shift->id)
                                              ->orderBy('started_at', 'asc')
                                              ->get();

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

        $oldValue = [
            'started_at' => $activity->started_at,
            'ended_at' => $activity->ended_at,
            'duration_seconds' => $activity->duration_seconds,
        ];

        $start = Carbon::parse($request->started_at);
        $end = Carbon::parse($request->ended_at);
        $newDurationSeconds = $start->diffInSeconds($end);

        $activity->update([
            'started_at' => $request->started_at,
            'ended_at' => $request->ended_at,
            'duration_seconds' => $newDurationSeconds,
        ]);

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

            // BOM para Excel y forzar el separador de comas
            fputs($file, "\xEF\xBB\xBF");
            fputs($file, "sep=,\n");

            // Encabezados con ambos formatos (Reloj y Decimal)
            fputcsv($file, [
                'Fecha',
                'Empleado',
                'Email',
                'Rol',
                'Hora Login',
                'Hora Logoff',
                'Trabajo Efectivo (HH:MM:SS)',
                'Trabajo Efectivo (Decimal)',
                'Tiempo Breaks (HH:MM:SS)',
                'Tiempo Breaks (Decimal)'
            ]);

            foreach ($shifts as $shift) {
                $totalWorkSeconds = 0;
                $totalBreakSeconds = 0;

                foreach ($shift->activities as $act) {
                    if ($act->duration_seconds) {
                        if ($act->activity_type == 'ready') {
                            $totalWorkSeconds += $act->duration_seconds;
                        } elseif (in_array($act->activity_type, ['break', 'lunch'])) {
                            $totalBreakSeconds += $act->duration_seconds;
                        }
                    }
                }

                // Cálculos duales: Formato Reloj y Formato Decimal (4 decimales de precisión)
                $relojWork = gmdate('H:i:s', $totalWorkSeconds);
                $decimalWork = round($totalWorkSeconds / 3600, 4);

                $relojBreak = gmdate('H:i:s', $totalBreakSeconds);
                $decimalBreak = round($totalBreakSeconds / 3600, 4);

                // Limpieza visual para las fechas de inicio y fin
                $loginClean = $shift->login_time ? \Carbon\Carbon::parse($shift->login_time)->format('H:i:s') : 'N/A';
                $logoffClean = $shift->logoff_time ? \Carbon\Carbon::parse($shift->logoff_time)->format('H:i:s') : 'Sin cerrar';

                fputcsv($file, [
                    $shift->date,
                    $shift->user->name,
                    $shift->user->email,
                    strtoupper($shift->user->role),
                    $loginClean,
                    $logoffClean,
                    $relojWork,
                    $decimalWork,
                    $relojBreak,
                    $decimalBreak
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
