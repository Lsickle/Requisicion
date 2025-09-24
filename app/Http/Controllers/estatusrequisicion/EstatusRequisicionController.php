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
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Mail;
use App\Mail\EstatusRequisicionActualizado as EstatusRequisicionMail;

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

        $requisiciones = Requisicion::with(['ultimoEstatus.estatusRelation', 'productos', 'estatusHistorial.estatusRelation'])
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

        $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
        ]);

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::with('ultimoEstatus')->findOrFail($requisicionId);

            // Si no hay cambio de estatus, no crear registro ni enviar correo
            $currentStatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if ($currentStatus == (int)$request->estatus_id) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cambios en el estatus. Operación cancelada.'
                ], 200);
            }

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

            // Desactivar todos los estatus anteriores
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

            $mensajeAccion = 'aprobada';
            $comentario = $request->comentario ? trim($request->comentario) : null;

            if ($request->estatus_id == 9) {
                // rechazo
                if ($role === 'Area de compras') {
                    // Envía a corrección (11). Comentario opcional.
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 11,
                        'estatus' => 1,
                        'comentario' => $comentario,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $mensajeAccion = 'enviada a corrección';
                } elseif ($role === 'Gerencia') {
                    // Rechazo por Gerencia -> registrar 13 (histórico) y completar (10)
                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 13,
                        'estatus' => 0,
                        'comentario' => $comentario,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 10,
                        'estatus' => 1,
                        'comentario' => null,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $mensajeAccion = 'rechazada por gerencia';
                } elseif ($role === 'Gerente financiero') {
                    // Rechazo por Financiera -> registrar 9 (histórico) y completar (10)
                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 9,
                        'estatus' => 0,
                        'comentario' => $comentario,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 10,
                        'estatus' => 1,
                        'comentario' => null,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $mensajeAccion = 'rechazada';
                } else {
                    // Fallback: marcar rechazado (9) como histórico y completar (10)
                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 9,
                        'estatus' => 0,
                        'comentario' => $comentario,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id' => 10,
                        'estatus' => 1,
                        'comentario' => null,
                        'date_update' => now(),
                        'user_id' => session('user.id') ?? null,
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
                    'user_id' => session('user.id') ?? null,
                ]);

                // Al aprobar por financiero (estatus 4) NO crear orden de compra automáticamente
                if ($request->estatus_id == 4 && $role === 'Gerente financiero') {
                    Log::info("Aprobación financiera para requisición {$requisicionId}; no se crea orden de compra automáticamente.");
                }
            }

            // Notificación: intentar envío directo si hay email, si falla despachar job
            try {
                $userEmail = $requisicion->email_user ?? null;
                if (empty($userEmail)) {
                    $userInfo = $this->obtenerInformacionUsuario($requisicion->user_id);
                    $userEmail = $userInfo['email'] ?? null;
                }

                if (!empty($userEmail)) {
                    // Enriquecer $nuevoEstatus con user_id si no viene
                    if (empty($nuevoEstatus->user_id) && session('user.id')) {
                        $nuevoEstatus->user_id = session('user.id');
                    }

                    // Envío síncrono
                    Mail::to($userEmail)->send(new EstatusRequisicionMail($requisicion, $nuevoEstatus));
                    Log::info("Correo enviado directamente a {$userEmail} para requisición {$requisicion->id}");
                } else {
                    // No hay email; despachar job que hará fallback
                    EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, null);
                }
            } catch (\Exception $e) {
                Log::error("Error enviando correo directamente: " . $e->getMessage());
                try {
                    EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, $requisicion->email_user ?? null);
                } catch (\Exception $jobEx) {
                    Log::error("Error despachando job de correo: " . $jobEx->getMessage());
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Requisición ' . $mensajeAccion . ' correctamente',
                'nuevo_estatus' => optional($nuevoEstatus->estatusRelation)->status_name ?? 'Desconocido'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERROR CRÍTICO al actualizar estatus: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor al actualizar el estatus: ' . $e->getMessage()
            ], 500);
        }
    }

    private function obtenerInformacionUsuario($userId)
    {
        try {
            $apiToken = session('api_token');
            if (!$apiToken) {
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
                    ->timeout(10)
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

            return ['email' => null];
        } catch (\Throwable $e) {
            return ['email' => null];
        }
    }
}
