<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TimeTrackingController;

// ---------------------------------------------------------
// RUTAS PÚBLICAS (Invitados)
// ---------------------------------------------------------

// Si entran a la raíz sin loguearse, van directo al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Mostramos la vista 'welcome' directamente sin pasar por el controlador
Route::get('/login', function () {
    return view('welcome');
})->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ---------------------------------------------------------
// RUTAS PROTEGIDAS (Requieren estar logueado)
// ---------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Rutas para el cambio de contraseña obligatorio
    Route::get('/change-password', [AuthController::class, 'showChangePasswordForm'])->name('password.change');
    Route::post('/change-password', [AuthController::class, 'updatePassword'])->name('password.update');

    // Rutas principales (Requieren estar logueado Y haber cambiado la contraseña)
    Route::middleware(['check.password'])->group(function () {

        // ---------------------------------------------------------
        // ZONA GENERAL (Todos los empleados usan su propio reloj)
        // ---------------------------------------------------------
        Route::get('/dashboard', [EmployeeController::class, 'index'])->name('employee.dashboard');

        // Control de Tiempos interactivos del reloj
        Route::post('/clock-in', [TimeTrackingController::class, 'clockIn'])->name('time.clockIn');
        Route::post('/change-status', [TimeTrackingController::class, 'changeStatus'])->name('time.changeStatus');
        Route::post('/clock-out', [TimeTrackingController::class, 'clockOut'])->name('time.clockOut');

        // ---------------------------------------------------------
        // ZONA DE ADMINS (Área exclusiva para rol: admin)
        // ---------------------------------------------------------
        Route::middleware(['role:admin'])->group(function () {

            // Vista principal del panel de administración (monitoreo de tiempo)
            Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');

            // Gestión de Empleados (CRUD completo)
            Route::get('/admin/employees', [AdminController::class, 'listEmployees'])->name('admin.employees');
            Route::get('/admin/employee/create', [AdminController::class, 'createEmployee'])->name('admin.createEmployee');
            Route::post('/admin/employee/store', [AdminController::class, 'storeEmployee'])->name('admin.storeEmployee');
            Route::delete('/admin/employee/{id}', [AdminController::class, 'destroyEmployee'])->name('admin.destroyEmployee');

            // Detalles del Empleado, Cronómetro en Vivo y Edición Manual
            Route::get('/admin/employee/{id}', [AdminController::class, 'viewEmployeeDetails'])->name('admin.employeeDetails');
            Route::post('/admin/activity/update', [AdminController::class, 'updateActivity'])->name('admin.updateActivity');

            // Edición de Perfil y Horarios de Empleados
            Route::get('/admin/employee/{id}/edit', [AdminController::class, 'editEmployee'])->name('admin.editEmployee');
            Route::put('/admin/employee/{id}/update', [AdminController::class, 'updateEmployee'])->name('admin.updateEmployee');

            // Centro de Reportes y Exportación Dedicado
            Route::get('/admin/export', [AdminController::class, 'showExportForm'])->name('admin.export.form');
            Route::post('/admin/export/download', [AdminController::class, 'downloadExcel'])->name('admin.export.download');
        });

    });
});
