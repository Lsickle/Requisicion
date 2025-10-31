<?php

namespace App\Http\Controllers\centros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcentro;
use App\Models\UserxSubcentro;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserSubcentroController extends Controller
{
    public function index()
    {
        $subcentros = Subcentro::orderBy('name_subcentro')->get();
        return view('centros.user_subcentros', compact('subcentros'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email_user' => 'required|email',
            'subcentro_ids' => 'array',
            'subcentro_ids.*' => 'integer|exists:subcentros,id',
        ]);

        $email = $data['email_user'];
        $ids = $data['subcentro_ids'] ?? [];

        // eliminar asignaciones previas
        UserxSubcentro::where('email_user', $email)->delete();

        // crear nuevas
        $rows = [];
        foreach ($ids as $sid) {
            $rows[] = ['email_user' => $email, 'subcentro_id' => $sid, 'created_at' => now(), 'updated_at' => now()];
        }
        if (!empty($rows)) UserxSubcentro::insert($rows);

        return redirect()->back()->with('success', 'Asignaciones guardadas');
    }

    // API endpoint que devuelve subcentros asignados a un email
    public function listForUser($email)
    {
        $assigned = UserxSubcentro::where('email_user', $email)->pluck('subcentro_id')->toArray();
        return response()->json(['assigned' => $assigned]);
    }

    // Proxy endpoint para obtener usuarios desde VPL_CORE evitando CORS en cliente
    public function fetchUsers(Request $request)
    {
        $base = rtrim(env('VPL_CORE', ''), "\/");
        if (empty($base)) {
            return response()->json(['error' => 'VPL_CORE no configurado'], 500);
        }

        $url = $base . '/api/usuarios';

        try {
            $token = session('api_token') ?? null;
            $params = [];
            if ($request->has('start')) $params['start'] = $request->input('start');
            if ($request->has('length')) $params['length'] = $request->input('length');
            // aceptar search[value] o search
            if ($request->has('search') && is_array($request->input('search')) && isset($request->input('search')['value'])) {
                $params['search'] = $request->input('search');
            } elseif ($request->has('search')) {
                $params['search'] = $request->input('search');
            }

            $client = Http::withOptions(['verify' => false, 'timeout' => 30]);
            if ($token) $client = $client->withToken($token);

            $resp = $client->get($url, $params);

            if (!$resp->ok()) {
                Log::warning('fetchUsers proxy failed', ['status' => $resp->status(), 'body' => $resp->body()]);
                return response()->json(['error' => 'Error al consultar servicio externo', 'status' => $resp->status()], 502);
            }

            return response($resp->body(), $resp->status())->header('Content-Type', 'application/json');
        } catch (\Throwable $e) {
            Log::error('fetchUsers error: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al obtener usuarios'], 500);
        }
    }
}
