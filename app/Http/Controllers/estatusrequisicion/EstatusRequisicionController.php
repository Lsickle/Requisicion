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
        if (
            !PermissionHelper::hasAnyRole(['Gerencia', 'Gerente financiero', 'Area de compras'])
            || !PermissionHelper::hasPermission('aprobar requisicion')
        ) {
            return redirect()->route('index')->with('error', 'Debes iniciar sesión o no tienes permisos suficientes.');
        }

        $role = null;
        if (PermissionHelper::hasRole('Gerencia')) {
            $role = 'Gerencia';
        } elseif (PermissionHelper::hasRole('Gerente financiero')) {
            $role = 'Gerente financiero';
        } elseif (PermissionHelper::hasRole('Area de compras')) {
            $role = 'Area de compras';
        }

        $estatusFiltrar = 0;
        if ($role === 'Area de compras') {
            $estatusFiltrar = 1;
        } elseif ($role === 'Gerencia') {
            $estatusFiltrar = 2;
        } elseif ($role === 'Gerente financiero') {
            $estatusFiltrar = 3;
        }

        $requisiciones = Requisicion::with(['ultimoEstatus.estatus', 'productos', 'estatusHistorial.estatus'])
            ->whereHas('ultimoEstatus', function ($q) use ($estatusFiltrar) {
                $q->where('estatus_id', $estatusFiltrar);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $estatusOptions = collect();
        if ($role === 'Area de compras') {
            $estatusOptions = Estatus::whereIn('id', [2, 9])->pluck('status_name', 'id');
        } elseif ($role === 'Gerencia') {
            $estatusOptions = Estatus::whereIn('id', [3, 9])->pluck('status_name', 'id');
        } elseif ($role === 'Gerente financiero') {
            $estatusOptions = Estatus::whereIn('id', [4, 9])->pluck('status_name', 'id');
        }

        return view('requisiciones.aprobacion', compact('requisiciones', 'estatusOptions', 'role'));
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
        if (
            !PermissionHelper::hasAnyRole(['Gerencia', 'Gerente financiero', 'Area de compras'])
            || !PermissionHelper::hasPermission('aprobar requisicion')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión o no tienes permisos.'
            ], 403);
        }

        if (PermissionHelper::hasRole('Gerencia')) {
            $role = 'Gerencia';
        } elseif (PermissionHelper::hasRole('Gerente financiero')) {
            $role = 'Gerente financiero';
        } elseif (PermissionHelper::hasRole('Area de compras')) {
            $role = 'Area de compras';
        }

        Log::info("Actualizando estatus para requisición $requisicionId por rol: $role");
        Log::info("Datos recibidos: " . json_encode($request->all()));

        $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
        ]);

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::with('ultimoEstatus')->findOrFail($requisicionId);

            // Validar estatus según rol
            if ($role === 'Area de compras' && $requisicion->ultimoEstatus->estatus_id != 1) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus Iniciada'], 403);
            }
            if ($role === 'Gerencia' && $requisicion->ultimoEstatus->estatus_id != 2) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus Revisión'], 403);
            }
            if ($role === 'Gerente financiero' && $requisicion->ultimoEstatus->estatus_id != 3) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus Aprobación Gerencia'], 403);
            }

            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

            $mensajeAccion = 'aprobada';
            $comentario = $request->comentario ? trim($request->comentario) : null;

            Log::info("Comentario procesado: " . ($comentario ?: 'NULL'));

            if ($request->estatus_id == 9) {
                if ($role === 'Area de compras') {
                    if (!$comentario || $comentario == '') {
                        Log::warning("Área de compras intentó rechazar sin comentario");
                        return response()->json(['success' => false, 'message' => 'Debes escribir un motivo de rechazo.'], 422);
                    }

                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 11,
                        'estatus' => 1,
                        'comentario' => $comentario,
                        'date_update' => now(),
                    ]);

                    $mensajeAccion = 'enviada a corrección';
                } else {
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 10,
                        'estatus' => 1,
                        'comentario' => $comentario,
                        'date_update' => now(),
                    ]);

                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 9,
                        'estatus' => 0,
                        'comentario' => $comentario,
                        'date_update' => now(),
                    ]);

                    $mensajeAccion = 'rechazada';
                }
            } else {
                $nuevoEstatus = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => $request->estatus_id,
                    'estatus' => 1,
                    'comentario' => null,
                    'date_update' => now(),
                ]);

                // Crear la orden de compra SOLO si se aprueba definitivamente
                if ($request->estatus_id == 4 && $role === 'Gerente financiero') {
                    Log::info("Creando orden de compra para requisición $requisicionId");

                    $ordenCompraId = DB::table('orden_compras')->insertGetId([
                        'requisicion_id' => $requisicionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info("Orden de compra creada: ID $ordenCompraId para requisición: $requisicionId");

                    // Traer productos de la requisición
                    $productosReq = DB::table('producto_requisicion')
                        ->where('id_requisicion', $requisicionId) // 👈 corregido
                        ->get();

                    foreach ($productosReq as $producto) {
                        DB::table('ordencompra_producto')->insert([
                            'producto_id' => $producto->id_producto,
                            'orden_compras_id' => $ordenCompraId,
                            'producto_requisicion_id' => $producto->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            $userInfo = $this->obtenerInformacionUsuario($requisicion->user_id);
            $userEmail = $userInfo['email'] ?? null;

            EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, $userEmail);

            DB::commit();

            Log::info("Estatus actualizado exitosamente: $mensajeAccion");

            return response()->json([
                'success' => true,
                'message' => 'Requisición ' . $mensajeAccion . ' correctamente',
                'nuevo_estatus' => optional($nuevoEstatus->estatusRelation)->status_name ?? 'Desconocido'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar el estatus: " . $e->getMessage());
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
