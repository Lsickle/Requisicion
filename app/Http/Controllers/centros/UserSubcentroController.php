<?php

namespace App\Http\Controllers\centros;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcentro;
use App\Models\UserxSubcentro;
use App\Models\Centro;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\PermissionHelper;

class UserSubcentroController extends Controller
{
    public function index()
    {
        $subcentros = Subcentro::with('centro')
            ->whereNull('deleted_at')
            ->whereHas('centro', function($q){ $q->whereNull('deleted_at'); })
            ->orderBy('name_subcentro')
            ->get();

        $centros = Centro::orderBy('name_centro')->get();

        return view('centros.user_subcentros', compact('subcentros', 'centros'));
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

        // Eliminar solo las asignaciones que se van a reemplazar (las que ya no vengan en el request)
        $asignacionesActuales = UserxSubcentro::where('email_user', $email)
            ->whereNull('deleted_at')
            ->pluck('subcentro_id')
            ->toArray();

        $idsAEliminar = array_diff($asignacionesActuales, $ids);
        if (!empty($idsAEliminar)) {
            UserxSubcentro::where('email_user', $email)
                ->whereIn('subcentro_id', $idsAEliminar)
                ->delete();
        }

        // Agregar las nuevas asignaciones que no existan
        $idsAAgregar = array_diff($ids, $asignacionesActuales);
        $rows = [];
        foreach ($idsAAgregar as $sid) {
            $rows[] = ['email_user' => $email, 'subcentro_id' => $sid, 'created_at' => now(), 'updated_at' => now()];
        }
        if (!empty($rows)) UserxSubcentro::insert($rows);

        return redirect()->back()->with('success', 'Asignaciones guardadas');
    }

    public function listForUser($email)
    {
        $assigned = UserxSubcentro::where('email_user', $email)
            ->whereNull('deleted_at')
            ->pluck('subcentro_id')
            ->toArray();

        $bodegas = UserxSubcentro::where('email_user', $email)
            ->whereNull('deleted_at')
            ->with('subcentro.centro')
            ->get()
            ->pluck('subcentro.centro')
            ->unique('id')
            ->values();

        $bodegaNombres = $bodegas->pluck('name_centro')->toArray();
        $bodegaIds = $bodegas->pluck('id')->toArray();

        return response()->json([
            'assigned' => $assigned,
            'bodega_actual' => $bodegaIds,
            'bodega_nombre' => $bodegaNombres,
        ]);
    }

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
