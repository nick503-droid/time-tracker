<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\ShiftActivity;
use App\Models\AuditLog;
use Carbon\Carbon;

class AutoCloseShifts extends Command
{
    protected $signature = 'shifts:auto-close';
    protected $description = 'Cierra automáticamente los turnos que llevan más de 16 horas abiertos (Turnos Zombie)';

    public function handle()
    {
        $now = now();
        // Buscamos turnos abiertos cuya hora de entrada fue hace más de 16 horas
        $zombies = Shift::whereNull('logoff_time')
                        ->where('login_time', '<=', $now->copy()->subHours(16))
                        ->get();

        $count = 0;

        foreach ($zombies as $shift) {

            // EL FIX FINANCIERO: Calculamos exactamente cuándo se cumplieron las 16 horas.
            // Ya no usamos $now para cerrar los tiempos.
            $forcedEndTime = Carbon::parse($shift->login_time)->addHours(16);

            $currentActivity = ShiftActivity::where('shift_id', $shift->id)
                                            ->whereNull('ended_at')
                                            ->first();

            // 1. Cerramos la actividad pendiente forzando el tope de 16 horas
            if ($currentActivity) {
                // Si la actividad empezó DESPUÉS del límite de 16 horas (muy raro, pero por seguridad)
                if (Carbon::parse($currentActivity->started_at)->greaterThan($forcedEndTime)) {
                    $forcedEndTime = Carbon::parse($currentActivity->started_at);
                }

                $currentActivity->ended_at = $forcedEndTime;
                $currentActivity->duration_seconds = Carbon::parse($currentActivity->started_at)->diffInSeconds($forcedEndTime);
                $currentActivity->save();
            }

            // 2. Cerramos el turno general al límite exacto de 16 hrs
            $shift->logoff_time = $forcedEndTime;
            $shift->save();

            // 3. Dejamos evidencia en Auditoría
            AuditLog::create([
                'admin_id' => null,
                'affected_user_id' => $shift->user_id,
                'action' => 'Cierre forzoso de Turno Zombie',
                'old_value' => 'Abierto',
                'new_value' => 'Cerrado en límite máximo permitido',
                'reason' => 'El sistema detectó un turno abandonado y cortó el tiempo a exactamente 16 horas de duración máxima por seguridad de nómina.',
            ]);

            $count++;
        }

        $this->info("Se han cerrado {$count} turnos zombie exitosamente y se ajustaron a 16 horas exactas.");
    }
}
