<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Procesa el intento de login del usuario.
     */
    public function login(Request $request)
    {
        // 1. Validar que vengan los datos requeridos
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Intentar autenticar
        if (Auth::attempt($credentials)) {
            // Regenerar la sesión para evitar ataques de fijación de sesión (seguridad estándar)
            $request->session()->regenerate();

            // 3. Redirigir según su estado (el middleware decidirá si lo manda a cambiar pass o al dashboard)
            return redirect()->intended('/dashboard');
        }

        // 4. Si falla, regresarlo con un error
        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Muestra la vista para cambiar la contraseña.
     * (Retornamos la vista aunque aún no la hayamos creado).
     */
    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    /**
     * Procesa la actualización de la contraseña.
     */
    /**
     * Procesa la actualización de la contraseña.
     */
    public function updatePassword(Request $request)
    {
        // 1. Validar que la nueva contraseña sea segura y esté confirmada
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // 2. Obtener al usuario asegurando que sea la instancia del Modelo User
        $user = \App\Models\User::find(Auth::id());

        // 3. Actualizar la contraseña
        $user->password = Hash::make($request->password);

        // 4. Quitar la bandera de obligación
        $user->must_change_password = false;

        // 5. Ahora sí, guardar los cambios sin errores
        $user->save();

        // 6. Redirigir al dashboard con un mensaje de éxito
        return redirect()->route('employee.dashboard')->with('status', '¡Contraseña actualizada exitosamente!');
    }
}
