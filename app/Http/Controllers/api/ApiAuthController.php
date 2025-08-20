<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;

class ApiAuthController extends Controller
{
    /**
     * Login contra el API externo
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Petición al API externo (ignora verificación SSL en local)
        $response = Http::withoutVerifying()->post('https://vpl-nexus-core-test-testing.up.railway.app/api/auth/login', [
            'email' => $request->email,
            'password' => $request->password,
        ]);

        if ($response->failed()) {
            return back()->withErrors([
                'email' => 'Credenciales inválidas o error de conexión con API externo'
            ])->withInput();
        }

        $data = $response->json();

        // Guardar token y usuario en la sesión
        session([
            'api_token' => $data['access_token'] ?? null,
            'user' => $data['user'] ?? null,
        ]);

        return redirect()->route('requisiciones.create');
    }


    /**
     * Logout (elimina la sesión)
     */
    public function logout(Request $request)
    {
        // Eliminar sesión
        $request->session()->forget(['api_token', 'user']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Opcional: logout en API externa
        if ($token = session('api_token')) {
            Http::withoutVerifying()->withToken($token)->post(
                'https://vpl-nexus-core-test-testing.up.railway.app/api/auth/logout'
            );
        }

        // Redirigir con mensaje flash
        return redirect()->route('index')->with('logout_success', 'Has cerrado sesión correctamente.');
    }
}
