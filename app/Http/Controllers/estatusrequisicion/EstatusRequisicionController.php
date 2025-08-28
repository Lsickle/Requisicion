<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionHelper;
use App\Jobs\EstatusRequisicionActualizadoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        if (!PermissionHelper::hasAnyRole(['Gerencia', 'Gerente financiero']) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return redirect()->route('index')->with('error', 'Debes iniciar sesión o no tienes permisos suficientes.');
        }

        $role = null;
        if (PermissionHelper::hasRole('Gerencia')) {
            $role = 'Gerencia';
        } elseif (PermissionHelper::hasRole('Gerente financiero')) {
            $role = 'Gerente financiero';
        }

        // Nuevo mapeo de estatus según rol
        $estatusFiltrar = 0;
        if ($role === 'Gerencia') $estatusFiltrar = 3; // ahora es "Aprobación Gerencia"
        if ($role === 'Gerente financiero') $estatusFiltrar = 4; // ahora es "Aprobación Financiera"

        $requisiciones = Requisicion::with(['ultimoEstatus.estatus', 'productos', 'estatusHistorial.estatus'])
            ->whereHas('ultimoEstatus', function ($q) use ($estatusFiltrar) {
                $q->where('estatus_id', $estatusFiltrar);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Opciones de estatus según rol (actualizadas)
        $estatusOptions = collect();
        if ($role === 'Gerencia') {
            // Gerencia puede aprobar (4) o rechazar (9)
            $estatusOptions = Estatus::whereIn('id', [4, 9])->pluck('status_name', 'id');
        } elseif ($role === 'Gerente financiero') {
            // Finanzas puede aprobar siguiente paso (5) o rechazar (9)
            $estatusOptions = Estatus::whereIn('id', [5, 9])->pluck('status_name', 'id');
        }

        return view('requisiciones.aprobacion', compact('requisiciones', 'estatusOptions'));
    }

    public function show($id)
    {
        $requisicion = Requisicion::with('estatus')->findOrFail($id);
        $estatusOrdenados = $requisicion->estatus->sortBy('pivot.created_at');
        $estatusActual = $estatusOrdenados->last();

        return view('requisiciones.estatus', compact('requisicion', 'estatusOrdenados', 'estatusActual'));
    }

    public function updateStatus(Request $request, $requisicionId)
    {
        if (!PermissionHelper::hasAnyRole(['Gerencia', 'Gerente financiero']) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión o no tienes permisos.'
            ], 403);
        }

        $role = PermissionHelper::hasRole('Gerencia') ? 'Gerencia' : 'Gerente financiero';

        $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
        ]);

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::with('ultimoEstatus')->findOrFail($requisicionId);

            // Validar estatus según rol con los nuevos IDs
            if ($role === 'Gerencia' && $requisicion->ultimoEstatus->estatus_id != 3) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus Aprobación Gerencia'], 403);
            }

            if ($role === 'Gerente financiero' && $requisicion->ultimoEstatus->estatus_id != 4) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus Aprobación Financiera'], 403);
            }

            // Desactivar todos los estatus anteriores
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

            $mensajeAccion = 'aprobada';

            if ($request->estatus_id == 9) { // Rechazado
                $rechazado = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => 9,
                    'estatus' => 0,
                    'date_update' => now(),
                ]);

                $completado = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => 10,
                    'estatus' => 1,
                    'date_update' => now(),
                ]);

                $nuevoEstatus = $completado;
                $mensajeAccion = 'rechazada';
            } else {
                $nuevoEstatus = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => $request->estatus_id,
                    'estatus' => 1,
                    'date_update' => now(),
                ]);
            }

            $userInfo = $this->obtenerInformacionUsuario($requisicion->user_id);
            $userEmail = $userInfo['email'] ?? null;

            EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, $userEmail);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Requisición ' . $mensajeAccion . ' correctamente',
                'nuevo_estatus' => optional($nuevoEstatus->estatusRelation)->status_name ?? 'Desconocido'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estatus: ' . $e->getMessage()
            ], 500);
        }
    }

    private function obtenerInformacionUsuario($userId)
    {
        try {
            $apiToken = session('api_token');
            
            if (!$apiToken) {
                Log::error("No hay token de API disponible en la sesión");
                return ['email' => null];
            }

            $possibleEndpoints = [
                env('VPL_CORE') . "/api/user/{$userId}",
                env('VPL_CORE') . "/api/users/{$userId}",
                env('VPL_CORE') . "/api/auth/user/{$userId}",
            ];

            foreach ($possibleEndpoints as $apiUrl) {
                $response = Http::withoutVerifying()
                    ->withToken($apiToken)
                    ->timeout(15)
                    ->get($apiUrl);

                if ($response->successful()) {
                    $userData = $response->json();
                    
                    $email = $userData['email'] ?? 
                             $userData['user']['email'] ?? 
                             ($userData['data']['email'] ?? null);
                    
                    if ($email) {
                        return ['email' => $email];
                    }
                }
            }

            Log::error("Todos los endpoints fallaron para usuario {$userId}");
            return ['email' => null];

        } catch (\Throwable $e) {
            Log::error("Error obteniendo información del usuario {$userId}: " . $e->getMessage());
            return ['email' => null];
        }
    }
}
