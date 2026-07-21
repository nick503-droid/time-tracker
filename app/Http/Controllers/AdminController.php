<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminController extends Controller
{
    // =========================================================================
    // GESTIÓN DE EMPLEADOS
    // =========================================================================

    /**
     * Muestra el formulario de creación de un nuevo empleado.
     */
    public function createEmployee()
    {
        return view('admin.create-employee');
    }

    /**
     * Almacena un nuevo empleado en la base de datos.
     * - Valida todos los campos antes de guardar (atómico).
     * - Usa DB::transaction para garantizar integridad.
     * - Establece must_change_password = true (fuerza cambio en 1er login).
     */
    public function storeEmployee(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email',
            'password'               => 'required|min:8',
            'role'                   => 'required|in:admin,employee',
            'scheduled_in'           => 'required',
            'scheduled_out'          => 'required',
            'break_duration_minutes' => 'required|integer|min:0',
            'lunch_duration_minutes' => 'required|integer|min:0',
            'max_breaks_per_day'     => 'required|integer|min:0',
        ], [
            'password.min'   => 'La contraseña temporal debe tener al menos 8 caracteres.',
            'email.unique'   => 'Este correo electrónico ya está registrado en el sistema.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'name.required'  => 'El nombre completo es obligatorio.',
        ]);

        DB::transaction(function () use ($request) {
            User::create([
                'name'                   => $request->name,
                'email'                  => $request->email,
                'password'               => Hash::make($request->password),
                'role'                   => $request->role,
                'must_change_password'   => true, // Fuerza cambio en el primer inicio de sesión
                'scheduled_in'           => $request->scheduled_in,
                'scheduled_out'          => $request->scheduled_out,
                'break_duration_minutes' => $request->break_duration_minutes,
                'lunch_duration_minutes' => $request->lunch_duration_minutes,
                'max_breaks_per_day'     => $request->max_breaks_per_day,
            ]);
        });

        return redirect()->route('admin.employees')
            ->with('status', '¡Empleado registrado con éxito! Se le pedirá que cambie su contraseña en el primer inicio de sesión.');
    }

    /**
     * Lista todos los empleados con búsqueda y paginación.
     * Ruta dedicada a la administración de empleados (separada del monitoreo de tiempo).
     */
    public function listEmployees(Request $request)
    {
        $search = $request->get('search', '');

        $employees = User::when($search, function ($query, $search) {
                            $query->where(function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                            });
                        })
                        ->orderBy('name')
                        ->paginate(15)
                        ->withQueryString(); // Preserva el parámetro ?search= en la paginación

        return view('admin.employees', compact('employees', 'search'));
    }

    /**
     * Muestra el formulario de edición de un empleado.
     */
    public function editEmployee($id)
    {
        $employee = User::findOrFail($id);
        return view('admin.edit-employee', compact('employee'));
    }

    /**
     * Actualiza los datos de un empleado.
     * - El campo de nueva contraseña es OPCIONAL. Si se deja vacío, no se modifica.
     * - Si se asigna una nueva contraseña, se establece must_change_password = true
     *   para que el empleado deba cambiarla en su próximo inicio de sesión.
     */
    public function updateEmployee(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $request->validate([
            'name'                   => 'required|string|max:255',
            'email'                  => 'required|email|unique:users,email,' . $employee->id,
            'role'                   => 'required|in:admin,employee',
            'scheduled_in'           => 'required',
            'scheduled_out'          => 'required',
            'break_duration_minutes' => 'required|integer|min:0',
            'lunch_duration_minutes' => 'required|integer|min:0',
            'max_breaks_per_day'     => 'required|integer|min:0',
            // Nueva contraseña es opcional; si se proporciona debe tener >= 8 caracteres
            'new_password'           => 'nullable|string|min:8',
        ], [
            'new_password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'email.unique'     => 'Este correo electrónico ya está en uso por otro usuario.',
        ]);

        DB::transaction(function () use ($request, $employee) {
            $data = [
                'name'                   => $request->name,
                'email'                  => $request->email,
                'role'                   => $request->role,
                'scheduled_in'           => $request->scheduled_in,
                'scheduled_out'          => $request->scheduled_out,
                'break_duration_minutes' => $request->break_duration_minutes,
                'lunch_duration_minutes' => $request->lunch_duration_minutes,
                'max_breaks_per_day'     => $request->max_breaks_per_day,
            ];

            // Si el admin asigna una nueva contraseña, se actualiza y se fuerza el cambio
            if ($request->filled('new_password')) {
                $data['password']             = Hash::make($request->new_password);
                $data['must_change_password'] = true; // El empleado debe cambiarla en su próximo login
            }

            $employee->update($data);

            // Registrar la acción en el log de auditoría
            AuditLog::create([
                'admin_id'         => Auth::id(),
                'affected_user_id' => $employee->id,
                'action'           => 'Actualización de perfil de empleado' . ($request->filled('new_password') ? ' (con restablecimiento de contraseña)' : ''),
                'old_value'        => json_encode(['name' => $employee->getOriginal('name'), 'email' => $employee->getOriginal('email')]),
                'new_value'        => json_encode(['name' => $request->name, 'email' => $request->email]),
                'reason'           => 'Modificación desde panel de administración.',
            ]);
        });

        return redirect()->route('admin.employees')
            ->with('status', '¡Perfil y horarios de ' . $request->name . ' actualizados correctamente!');
    }

    /**
     * Elimina (soft delete) a un empleado del sistema.
     * - BLOQUEA la eliminación si el empleado tiene un turno activo (sin logoff).
     * - Si no tiene turnos activos, realiza un soft delete (deleted_at se marca).
     * - Conserva todo el historial de turnos y auditoría para pagos.
     */
    public function destroyEmployee($id)
    {
        $employee = User::findOrFail($id);

        // Bloquear si el empleado tiene un turno activo (login sin logoff)
        $hasActiveShift = $employee->shifts()
                                   ->whereNull('logoff_time')
                                   ->exists();

        if ($hasActiveShift) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar a ' . $employee->name . ' porque tiene un turno activo en curso. Espera a que finalice su jornada o cierra el turno manualmente.');
        }

        // Evitar que el admin se elimine a sí mismo
        if ($employee->id === Auth::id()) {
            return redirect()->back()
                ->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        DB::transaction(function () use ($employee) {
            // Registrar la acción ANTES del soft delete para mantener la referencia al usuario
            AuditLog::create([
                'admin_id'         => Auth::id(),
                'affected_user_id' => $employee->id,
                'action'           => 'Eliminación (soft delete) de empleado del sistema',
                'old_value'        => json_encode(['name' => $employee->name, 'email' => $employee->email, 'role' => $employee->role]),
                'new_value'        => json_encode(['deleted_at' => now()->toDateTimeString()]),
                'reason'           => 'Eliminación solicitada por administrador. Historial preservado.',
            ]);

            $employee->delete(); // Soft delete: marca deleted_at, no borra físicamente
        });

        return redirect()->route('admin.employees')
            ->with('status', 'El empleado ' . $employee->name . ' ha sido eliminado. Su historial de horas y pagos se conserva.');
    }

    // =========================================================================
    // PANEL DE MONITOREO DE TIEMPO
    // =========================================================================

    /**
     * Dashboard principal con el monitoreo de tiempo en vivo de los empleados.
     */
    public function dashboard(Request $request)
    {
        $startDate = $request->get('start_date', now()->toDateString());
        $endDate   = $request->get('end_date', now()->toDateString());

        $employees  = User::whereIn('role', ['employee', 'admin'])->get();
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

            $currentStatus     = 'Histórico';
            $totalWorkSeconds  = 0;
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
                if ($shift->date == now()->toDateString() || is_null($shift->logoff_time)) {
                    if ($shift->logoff_time) {
                        $currentStatus = 'Turno Terminado';
                    } else {
                        // Buscamos la actividad en curso directo en memoria
                        $activeActivity = $shift->activities->whereNull('ended_at')->first();

                        if ($activeActivity) {
                            $liveSeconds = Carbon::parse($activeActivity->started_at)->diffInSeconds(now());

                            if ($activeActivity->activity_type === 'ready') {
                                $totalWorkSeconds += $liveSeconds;
                                $currentStatus    = 'Trabajando';
                            } elseif ($activeActivity->activity_type === 'break') {
                                $totalBreakSeconds += $liveSeconds;
                                $currentStatus     = 'Descansando';
                            } elseif ($activeActivity->activity_type === 'lunch') {
                                $totalBreakSeconds += $liveSeconds;
                                $currentStatus     = 'En Lunch';
                            }
                        } else {
                            $currentStatus = 'Iniciado';
                        }
                    }
                }
            }

            $reportData[] = [
                'id'           => $employee->id,
                'employee'     => $employee->name,
                'email'        => $employee->email,
                'status'       => $currentStatus,
                'total_worked' => gmdate('H:i:s', $totalWorkSeconds),
                'total_break'  => gmdate('H:i:s', $totalBreakSeconds),
            ];
        }

        return view('admin.dashboard', compact('reportData', 'startDate', 'endDate'));
    }

    // =========================================================================
    // DETALLES Y EDICIÓN DE ACTIVIDADES
    // =========================================================================

    /**
     * Muestra el historial detallado de turnos de un empleado.
     */
    public function viewEmployeeDetails($id)
    {
        $employee = User::findOrFail($id);

        $shifts = Shift::with(['activities' => function ($query) {
                           $query->orderBy('started_at', 'asc');
                       }])
                       ->where('user_id', $employee->id)
                       ->orderBy('date', 'desc')
                       ->get();

        // Calculamos duraciones en vivo para actividades abiertas
        foreach ($shifts as $shift) {
            foreach ($shift->activities as $act) {
                if (!$act->ended_at) {
                    $act->duration_seconds = Carbon::parse($act->started_at)->diffInSeconds(now());
                }
            }
        }

        return view('admin.employee-details', compact('employee', 'shifts'));
    }

    /**
     * Actualiza manualmente una actividad de tiempo (con registro de auditoría).
     */
    public function updateActivity(Request $request)
    {
        $request->validate([
            'activity_id' => 'required|exists:shift_activities,id',
            'started_at'  => 'required|date_format:Y-m-d H:i:s',
            // VULNERABILITY FIX: Ensure ended_at is strictly after started_at
            'ended_at'    => 'required|date_format:Y-m-d H:i:s|after:started_at',
            'reason'      => 'required|string|min:10',
        ], [
            'ended_at.after' => 'La fecha y hora de fin debe ser posterior a la de inicio.',
        ]);

        $activity = ShiftActivity::findOrFail($request->activity_id);
        $shift    = Shift::findOrFail($activity->shift_id);

        $oldValue = [
            'started_at'       => $activity->started_at,
            'ended_at'         => $activity->ended_at,
            'duration_seconds' => $activity->duration_seconds,
        ];

        $start              = Carbon::parse($request->started_at);
        $end                = Carbon::parse($request->ended_at);
        $newDurationSeconds = $start->diffInSeconds($end);

        $activity->update([
            'started_at'       => $request->started_at,
            'ended_at'         => $request->ended_at,
            'duration_seconds' => $newDurationSeconds,
        ]);

        AuditLog::create([
            'admin_id'         => Auth::id(),
            'affected_user_id' => $shift->user_id,
            'action'           => 'Modificación manual de tiempo de ' . $activity->activity_type,
            'old_value'        => json_encode($oldValue),
            'new_value'        => json_encode([
                'started_at'       => $request->started_at,
                'ended_at'         => $request->ended_at,
                'duration_seconds' => $newDurationSeconds,
            ]),
            'reason' => $request->reason,
        ]);

        return back()->with('status', '¡Registro de tiempo modificado y auditado correctamente!');
    }

    // =========================================================================
    // REPORTES Y EXPORTACIÓN
    // =========================================================================

    /**
     * Muestra el formulario de exportación de reportes.
     */
    public function showExportForm()
    {
        $employees = User::whereIn('role', ['employee', 'admin'])->orderBy('name')->get();
        return view('admin.export', compact('employees'));
    }

    /**
     * Genera y descarga el reporte CSV de nómina agrupado por empleado.
     */
    public function downloadExcel(Request $request)
    {
        $request->validate([
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'employee_id' => 'required',
        ]);

        $startDate = $request->start_date;
        $endDate   = $request->end_date;

        $fileName = "reporte_nomina_agrupado_" . $startDate . "_al_" . $endDate . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0",
        ];

        // Consultamos Turnos y usamos chunk para eficiencia de RAM
        $query = Shift::with(['user', 'activities'])
                      ->whereBetween('date', [$startDate, $endDate]);

        if ($request->employee_id !== 'all') {
            $query->where('user_id', $request->employee_id);
        }

        $groupedData = [];

        // Procesamos en bloques de 200 para evitar agotar memoria
        $query->chunk(200, function ($shifts) use (&$groupedData) {
            foreach ($shifts as $shift) {
                // Seguridad: Si un turno quedó huérfano de usuario, lo saltamos
                if (!$shift->user) continue;

                $userId = $shift->user_id;

                if (!isset($groupedData[$userId])) {
                    $groupedData[$userId] = [
                        'name'                => $shift->user->name,
                        'email'               => $shift->user->email,
                        'role'                => $shift->user->role,
                        'days_worked'         => 0,
                        'total_work_seconds'  => 0,
                        'total_break_seconds' => 0,
                    ];
                }

                $groupedData[$userId]['days_worked'] += 1;

                foreach ($shift->activities as $act) {
                    if ($act->duration_seconds) {
                        if ($act->activity_type === 'ready') {
                            $groupedData[$userId]['total_work_seconds'] += $act->duration_seconds;
                        } elseif (in_array($act->activity_type, ['break', 'lunch'])) {
                            $groupedData[$userId]['total_break_seconds'] += $act->duration_seconds;
                        }
                    }
                }
            }
        });

        $callback = function () use ($groupedData) {
            $file = fopen('php://output', 'w');

            fputs($file, "\xEF\xBB\xBF");
            fputs($file, "sep=,\n");

            fputcsv($file, [
                'Empleado',
                'Email',
                'Rol',
                'Días Trabajados en el Rango',
                'Total Trabajo Efectivo (HH:MM:SS)',
                'Total Trabajo Efectivo (Decimal)',
                'Total Tiempo Breaks (HH:MM:SS)',
                'Total Tiempo Breaks (Decimal)',
            ]);

            foreach ($groupedData as $data) {
                $workSeconds = $data['total_work_seconds'];
                $workHours   = floor($workSeconds / 3600);
                $workMins    = floor(($workSeconds / 60) % 60);
                $workSecs    = $workSeconds % 60;
                $relojWork   = sprintf('%02d:%02d:%02d', $workHours, $workMins, $workSecs);

                $breakSeconds = $data['total_break_seconds'];
                $breakHours   = floor($breakSeconds / 3600);
                $breakMins    = floor(($breakSeconds / 60) % 60);
                $breakSecs    = $breakSeconds % 60;
                $relojBreak   = sprintf('%02d:%02d:%02d', $breakHours, $breakMins, $breakSecs);

                $decimalWork  = round($workSeconds / 3600, 4);
                $decimalBreak = round($breakSeconds / 3600, 4);

                fputcsv($file, [
                    $data['name'],
                    $data['email'],
                    strtoupper($data['role']),
                    $data['days_worked'],
                    $relojWork,
                    $decimalWork,
                    $relojBreak,
                    $decimalBreak,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
