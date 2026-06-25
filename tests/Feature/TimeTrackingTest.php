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
            'must_change_password' => false,
        ]);

        $admin = User::create([
            'name' => 'Admin Auditor',
            'email' => 'auditor@test.com',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'must_change_password' => false,
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

        // Trabajo: 5 horas exactas (18000 segundos)
        ShiftActivity::create([
            'shift_id' => $shift1->id, 'activity_type' => 'ready',
            'started_at' => Carbon::parse("$dia1 08:00:00"), 'ended_at' => Carbon::parse("$dia1 13:00:00"),
            'duration_seconds' => 18000,
        ]);

        // Break: 1 hora exacta (3600 segundos)
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

        // Trabajo: 8 horas exactas (28800 segundos)
        ShiftActivity::create([
            'shift_id' => $shift2->id, 'activity_type' => 'ready',
            'started_at' => Carbon::parse("$dia2 08:00:00"), 'ended_at' => Carbon::parse("$dia2 16:00:00"),
            'duration_seconds' => 28800,
        ]);

        // Break: 30 minutos exactos (1800 segundos)
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
        // VERIFICACIONES: Leemos el Excel por dentro
        // ==========================================

        // Verificamos que existan las filas de ambos días
        $this->assertStringContainsString($dia1, $csvContent);
        $this->assertStringContainsString($dia2, $csvContent);

        // Verificamos los cálculos del DÍA 1 (5 hrs trabajo, 1 hr break)
        $this->assertStringContainsString('05:00:00', $csvContent);
        $this->assertStringContainsString('01:00:00', $csvContent);

        // Verificamos los cálculos del DÍA 2 (8 hrs trabajo, 30 min break)
        $this->assertStringContainsString('08:00:00', $csvContent);
        $this->assertStringContainsString('00:30:00', $csvContent);
    }
