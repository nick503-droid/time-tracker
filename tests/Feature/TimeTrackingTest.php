<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\ShiftActivity;
use Carbon\Carbon;

class TimeTrackingTest extends TestCase
{
    // Esto asegura que la base de datos se limpie y reinicie automáticamente después de cada prueba
    use RefreshDatabase;

    /**
     * Prueba 7: Simulación de múltiples días con diferentes cargas horarias.
     * Verifica que el Excel exporte líneas separadas con los cálculos exactos por día.
     */
    public function test_exportacion_excel_multiples_dias_horas_diferentes()
    {
        $employee = User::create([
            'name' => 'Trabajador Variable',
            'email' => 'variable@test.com',
            'password' => bcrypt('password123'),
            'role' => 'employee',
        ]);

        $admin = User::create([
            'name' => 'Admin Auditor',
            'email' => 'auditor@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        // ==========================================
        // DÍA 1: Lunes (Trabaja 5 horas, descansa 1 hora)
        // ==========================================
        $dia1 = '2026-06-08';
        $shift1 = Shift::create([
            'user_id' => $employee->id,
            'date' => $dia1,
            'login_time' => Carbon::parse("$dia1 08:00:00"),
            'logoff_time' => Carbon::parse("$dia1 14:00:00"),
        ]);

        ShiftActivity::create([
            'shift_id' => $shift1->id, 'activity_type' => 'ready',
            'started_at' => Carbon::parse("$dia1 08:00:00"), 'ended_at' => Carbon::parse("$dia1 13:00:00"),
            'duration_seconds' => 18000,
        ]);

        ShiftActivity::create([
            'shift_id' => $shift1->id, 'activity_type' => 'break',
            'started_at' => Carbon::parse("$dia1 13:00:00"), 'ended_at' => Carbon::parse("$dia1 14:00:00"),
            'duration_seconds' => 3600,
        ]);

        // ==========================================
        // DÍA 2: Martes (Trabaja 8 horas, descansa 30 minutos)
        // ==========================================
        $dia2 = '2026-06-09';
        $shift2 = Shift::create([
            'user_id' => $employee->id,
            'date' => $dia2,
            'login_time' => Carbon::parse("$dia2 08:00:00"),
            'logoff_time' => Carbon::parse("$dia2 16:30:00"),
        ]);

        ShiftActivity::create([
            'shift_id' => $shift2->id, 'activity_type' => 'ready',
            'started_at' => Carbon::parse("$dia2 08:00:00"), 'ended_at' => Carbon::parse("$dia2 16:00:00"),
            'duration_seconds' => 28800,
        ]);

        ShiftActivity::create([
            'shift_id' => $shift2->id, 'activity_type' => 'break',
            'started_at' => Carbon::parse("$dia2 16:00:00"), 'ended_at' => Carbon::parse("$dia2 16:30:00"),
            'duration_seconds' => 1800,
        ]);

        // ==========================================
        // ACCIÓN: El admin descarga el Excel de esa semana
        // ==========================================
        $response = $this->actingAs($admin)->post('/admin/export/download', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-15',
            'employee_id' => $employee->id
        ]);

        $response->assertStatus(200);
        $csvContent = $response->streamedContent();

        // ==========================================
        // VERIFICACIONES
        // ==========================================
        // Si agrupaste el Excel como un resumen mensual/semanal por empleado,
        // verificamos que la suma total sea 13 horas de trabajo y 1.5 horas de break.
        $this->assertStringContainsString('13:00:00', $csvContent); // 5 hrs + 8 hrs
        $this->assertStringContainsString('01:30:00', $csvContent); // 1 hr + 30 min
    }

    /**
     * Prueba Extrema 1: Turno nocturno que cruza la medianoche.
     */
    public function test_turno_nocturno_calcula_horas_correctamente()
    {
        $employee = User::factory()->create([
    'role' => 'employee',
    'max_breaks_per_day' => 5 // Le damos margen suficiente para que el error sea por doble clic, no por límite
]);

        // Simula inicio del turno a las 10:00 PM
        $horaInicio = now()->setHour(22)->setMinute(0)->setSecond(0);
        $this->travelTo($horaInicio);

        $this->actingAs($employee)->post(route('time.clockIn'));

        // Simula que pasan 4 horas (Son las 2:00 AM del día siguiente)
        $this->travel(4)->hours();
        $this->post(route('time.changeStatus'), ['status' => 'lunch']);

        // Simula que pasa 1 hora de lunch (Son las 3:00 AM)
        $this->travel(1)->hours();
        $this->post(route('time.changeStatus'), ['status' => 'ready']);

        // Simula que pasan 3 horas más y termina el turno (Son las 6:00 AM)
        $this->travel(3)->hours();
        $this->post(route('time.clockOut'));

        // Volvemos al tiempo normal
        $this->travelBack();

        $shift = Shift::where('user_id', $employee->id)->first();
        $this->assertNotNull($shift->logoff_time, 'El turno no se cerró.');

        // El trabajo efectivo debe ser exactamente 7 horas (25200 segundos)
        $trabajoEfectivo = ShiftActivity::where('shift_id', $shift->id)
                                        ->where('activity_type', 'ready')
                                        ->sum('duration_seconds');

        // El "5" es el Delta. Significa que aceptamos cualquier valor entre 25195 y 25205 segundos.
        $this->assertEqualsWithDelta(25200, $trabajoEfectivo, 5, 'El cálculo del turno nocturno falló al cruzar la medianoche.');
    }

    /**
     * Prueba Extrema 2: Spam de clics al mismo estado.
     */
    public function test_previene_multiples_clics_al_mismo_estado()
    {
        $employee = User::factory()->create([
    'role' => 'employee',
    'max_breaks_per_day' => 5 // Le damos margen suficiente para que el error sea por doble clic, no por límite
]);

        $this->actingAs($employee)->post(route('time.clockIn'));

        $this->post(route('time.changeStatus'), ['status' => 'break']);
        $this->post(route('time.changeStatus'), ['status' => 'break']);
        $response = $this->post(route('time.changeStatus'), ['status' => 'break']);

        $response->assertSessionHas('status', 'Ya te encuentras en el estado: break');

        $shift = Shift::where('user_id', $employee->id)->first();
        $breaks = ShiftActivity::where('shift_id', $shift->id)
                               ->where('activity_type', 'break')
                               ->count();

        $this->assertEquals(1, $breaks, 'El sistema permitió registrar estados duplicados simultáneos.');
    }

    /**
     * Prueba Extrema 3: Límite estricto de breaks por día.
     */
    public function test_rechaza_exceder_limite_maximo_de_breaks()
    {
        $employee = User::factory()->create([
            'role' => 'employee',
            'max_breaks_per_day' => 1
        ]);

        $this->actingAs($employee)->post(route('time.clockIn'));

        $this->post(route('time.changeStatus'), ['status' => 'break'])->assertSessionHasNoErrors();
        $this->post(route('time.changeStatus'), ['status' => 'ready']);

        $response = $this->post(route('time.changeStatus'), ['status' => 'break']);
        $response->assertSessionHasErrors();

        $errores = session('errors')->getBag('default')->all();
        $this->assertStringContainsString(
            'has alcanzado el límite máximo',
            strtolower($errores[0])
        );
    }

    /**
     * Prueba Extrema 4: Auditor intenta guardar un tiempo negativo.
     */
    public function test_auditor_no_puede_guardar_tiempos_negativos()
    {
        $admin = User::factory()->create(['role' => 'admin']);
       $employee = User::factory()->create([
    'role' => 'employee',
    'max_breaks_per_day' => 5 // Le damos margen suficiente para que el error sea por doble clic, no por límite
]);

        $shift = Shift::create([
    'user_id' => $employee->id,
    'date' => now()->toDateString(),
    'login_time' => now() // Agregamos el campo obligatorio
]);
        $activity = ShiftActivity::create([
            'shift_id' => $shift->id,
            'activity_type' => 'ready',
            'started_at' => now()->subHours(2),
            'ended_at' => now(),
            'duration_seconds' => 7200
        ]);

        $response = $this->actingAs($admin)->post(route('admin.updateActivity'), [
            'activity_id' => $activity->id,
            'started_at' => '2026-06-15 10:00:00',
            'ended_at' => '2026-06-15 08:00:00', // Error intencional: antes del inicio
            'reason' => 'Corrección de prueba'
        ]);

        $response->assertSessionHasErrors('ended_at');

        $activityFresca = $activity->fresh();
        $this->assertEquals(7200, $activityFresca->duration_seconds, 'El admin logró guardar una duración negativa.');
    }

    /**
     * Prueba Extrema 5: Ataque de fuerza bruta al Clock In.
     * Verifica que es imposible abrir dos turnos al mismo tiempo.
     */
    public function test_rechaza_multiples_turnos_abiertos_simultaneos()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee);

        // El empleado hace su Clock In normal
        $this->post(route('time.clockIn'));

        // Intenta hackear mandando 3 peticiones POST adicionales directas a la ruta
        $this->post(route('time.clockIn'));
        $this->post(route('time.clockIn'));
        $this->post(route('time.clockIn'));

        // Verificamos en la base de datos que la seguridad funcionó
        $turnosAbiertos = \App\Models\Shift::where('user_id', $employee->id)->count();

        $this->assertEquals(1, $turnosAbiertos, 'ALERTA: El sistema permitió crear múltiples turnos el mismo día para el mismo usuario.');
    }

    /**
     * Prueba Extrema 6: Turno abandonado por más de 48 horas.
     * Verifica que el sistema calcule correctamente lapsos masivos sin desbordarse.
     */
    public function test_turno_fantasma_abandonado_todo_el_fin_de_semana()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        // Simulamos que es VIERNES a las 5:00 PM y hace clock in
        $viernes = now()->startOfWeek()->addDays(4)->setHour(17)->setMinute(0)->setSecond(0);
        $this->travelTo($viernes);

        $this->actingAs($employee)->post(route('time.clockIn'));

        // El empleado se va a casa. Pasan exactamente 60 HORAS (Es lunes a las 5:00 AM)
        $this->travel(60)->hours();

        // El lunes se da cuenta y le da Clock Out asustado
        $this->post(route('time.clockOut'));

        // Volvemos a la realidad
        $this->travelBack();

        $shift = \App\Models\Shift::where('user_id', $employee->id)->first();
        $actividadPrincipal = \App\Models\ShiftActivity::where('shift_id', $shift->id)->first();

        // 60 horas * 60 minutos * 60 segundos = 216,000 segundos exactos.
        // Tolerancia de 5 segundos (Delta) por el tiempo de procesamiento de la prueba
        $this->assertEqualsWithDelta(216000, $actividadPrincipal->duration_seconds, 5, 'El sistema falló al calcular un turno de más de 48 horas.');
    }

    /**
     * Prueba Extrema 7: Manipulación del DOM en el navegador.
     * Verifica que el backend rechace un segundo almuerzo aunque fuercen el botón HTML.
     */
    public function test_rechaza_almuerzos_duplicados_por_manipulacion_html()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $this->actingAs($employee)->post(route('time.clockIn'));

        // El empleado toma su almuerzo legal
        $this->post(route('time.changeStatus'), ['status' => 'lunch']);

        // Regresa a trabajar
        $this->post(route('time.changeStatus'), ['status' => 'ready']);

        // El usuario altera el HTML con "Inspeccionar Elemento" y manda una petición POST forzada
        $response = $this->post(route('time.changeStatus'), ['status' => 'lunch']);

        // El servidor debe rechazar la petición con un error de sesión
        $response->assertSessionHasErrors();
        $errores = session('errors')->getBag('default')->all();

        $this->assertStringContainsString('ya registraste tu tiempo de almuerzo obligatorio', strtolower($errores[0]));

        // Verificamos que la base de datos se defendió y solo existe 1 almuerzo
        $almuerzosRegistrados = \App\Models\ShiftActivity::where('activity_type', 'lunch')->count();
        $this->assertEquals(1, $almuerzosRegistrados, 'El backend confió en el frontend y permitió un segundo almuerzo ilegal.');
    }

    /**
     * Prueba Extrema 8: Acciones huérfanas.
     * Verifica que el sistema bloquee intentos de descanso o cierre si no hay turno abierto.
     */
    public function test_rechaza_acciones_sin_turno_abierto()
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $this->actingAs($employee);

        // El empleado NUNCA hizo Clock In. Intenta irse directo a break.
        $responseBreak = $this->post(route('time.changeStatus'), ['status' => 'break']);

        $responseBreak->assertSessionHasErrors();
        $errores = session('errors')->getBag('default')->all();
        $this->assertStringContainsString('no tienes un turno activo', strtolower($errores[0]));

        // Intenta hacer Clock Out para irse a casa sin haber trabajado
        $responseOut = $this->post(route('time.clockOut'));

        $responseOut->assertSessionHasErrors();
        $erroresOut = session('errors')->getBag('default')->all();
        $this->assertStringContainsString('no tienes un turno activo para finalizar', strtolower($erroresOut[0]));
    }

    /**
     * Prueba Extrema 9: Turno de 0 segundos (Cero matemático).
     * Verifica que el Excel no colapse por errores de división por cero.
     */
    public function test_soporta_micro_turnos_de_cero_segundos_sin_colapsar()
    {
        $employee = User::factory()->create(['role' => 'employee']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Inyectamos un turno de exactamente 0 segundos directo en la BD
        // para evitar que la latencia de las peticiones HTTP sume milisegundos.
        $shift = \App\Models\Shift::create([
            'user_id' => $employee->id,
            'date' => now()->toDateString(),
            'login_time' => now(),
            'logoff_time' => now(),
        ]);

        \App\Models\ShiftActivity::create([
            'shift_id' => $shift->id,
            'activity_type' => 'ready',
            'started_at' => now(),
            'ended_at' => now(),
            'duration_seconds' => 0, // Cero absoluto
        ]);

        // El admin descarga el excel
        $response = $this->actingAs($admin)->post('/admin/export/download', [
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'employee_id' => $employee->id
        ]);

        $response->assertStatus(200);
        $csvContent = $response->streamedContent();

        // Debe exportarse correctamente con todo en ceros, sin crashear el servidor
        $this->assertStringContainsString('00:00:00', $csvContent); // Horas Reloj

        // Verificamos que el decimal sea un 0 (algunos sistemas operativos lo exportan como 0 y otros como 0.0000)
        $this->assertTrue(
            str_contains($csvContent, ',0,') || str_contains($csvContent, ',0.0000,'),
            'El Excel no exportó el cero correctamente.'
        );
    }

    /**
     * Prueba Extrema 10: Idempotencia del Clock In.
     * Verifica que dar Clock In múltiple veces con horas de diferencia no afecte ni reinicie el turno original.
     */
    public function test_clock_in_repetido_no_reinicia_turno()
    {
        $employee = User::factory()->create(['role' => 'employee']);

        $horaInicio = now()->setHour(8)->setMinute(0)->setSecond(0);
        $this->travelTo($horaInicio);

        // 8:00 AM - El usuario inicia su turno normal
        $this->actingAs($employee)->post(route('time.clockIn'));

        // Viajamos 30 minutos al futuro (Son las 8:30 AM)
        $this->travel(30)->minutes();

        // 8:30 AM - El usuario le da clic a Clock In de nuevo por error
        $this->post(route('time.clockIn'));

        // Verificamos que la base de datos se defendió
        $turnos = \App\Models\Shift::where('user_id', $employee->id)->count();
        $this->assertEquals(1, $turnos, 'El sistema creó un segundo turno en lugar de ignorar la acción.');

        $shift = \App\Models\Shift::where('user_id', $employee->id)->first();
        $actividad = \App\Models\ShiftActivity::where('shift_id', $shift->id)->first();

        // Validamos que su hora de inicio sigue intacta a las 8:00 AM, no se reseteó a las 8:30 AM
        $this->assertEquals(
            $horaInicio->format('H:i:s'),
            \Carbon\Carbon::parse($actividad->started_at)->format('H:i:s'),
            '¡El sistema reinició el tiempo del usuario y le robó 30 minutos de trabajo!'
        );
    }
}
