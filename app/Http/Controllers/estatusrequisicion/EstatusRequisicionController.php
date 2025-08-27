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

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        // Validar sesión y permisos
        if (!PermissionHelper::hasAnyRole(['Gerencia', 'Gerente financiero']) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return redirect()->route('index')->with('error', 'Debes iniciar sesión o no tienes permisos suficientes.');
        }

        // Determinar rol
        $role = null;
        if (PermissionHelper::hasRole('Gerencia')) {
            $role = 'Gerencia';
        } elseif (PermissionHelper::hasRole('Gerente financiero')) {
            $role = 'Gerente financiero';
        }

        // Determinar estatus según rol
        $estatusFiltrar = 0;
        if ($role === 'Gerencia') $estatusFiltrar = 1; // iniciada
        if ($role === 'Gerente financiero') $estatusFiltrar = 2; // aprobación financiera

        // Obtener requisiciones
        $requisiciones = Requisicion::with(['ultimoEstatus.estatus', 'productos', 'estatusHistorial.estatus'])
            ->whereHas('ultimoEstatus', function ($q) use ($estatusFiltrar) {
                $q->where('estatus_id', $estatusFiltrar);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Opciones de estatus según rol
        $estatusOptions = collect();
        if ($role === 'Gerencia') {
            $estatusOptions = Estatus::whereIn('id', [2, 8])->pluck('status_name', 'id');
        } elseif ($role === 'Gerente financiero') {
            $estatusOptions = Estatus::whereIn('id', [3, 8])->pluck('status_name', 'id');
        }

        return view('requisiciones.aprobacion', compact('requisiciones', 'estatusOptions'));
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

            // Validar estatus según rol
            if ($role === 'Gerencia' && $requisicion->ultimoEstatus->estatus_id != 1) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus iniciada'], 403);
            }

            if ($role === 'Gerente financiero' && $requisicion->ultimoEstatus->estatus_id != 2) {
                return response()->json(['success' => false, 'message' => 'Solo puedes aprobar requisiciones en estatus de aprobación financiera'], 403);
            }

            // Desactivar todos los estatus anteriores
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

            $mensajeAccion = 'aprobada'; // por defecto

            // Si se rechaza (estatus_id 8), crear rechazado y completado
            if ($request->estatus_id == 8) {
                $rechazado = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => 8,
                    'estatus' => 0,
                    'date_update' => now(),
                ]);

                $completado = Estatus_Requisicion::create([
                    'requisicion_id' => $requisicionId,
                    'estatus_id' => 9,
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

            // Enviar correo con Job
            EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus);

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
}
