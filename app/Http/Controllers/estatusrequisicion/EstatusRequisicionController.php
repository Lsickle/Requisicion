<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\DB;

class EstatusRequisicionController extends Controller
{
    /**
     * Mostrar panel de aprobación de requisiciones
     */
    public function index()
    {
        if (!PermissionHelper::hasPermission('aprobar requisicion')) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        // 👇 Solo mostrar requisiciones cuyo último estatus sea 1 (Iniciada / Activa)
        $requisiciones = Requisicion::with(['ultimoEstatus.estatus', 'productos'])
            ->whereHas('ultimoEstatus', function($query) {
                $query->where('estatus_id', 1); // Solo activas
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // 👇 Opciones disponibles para actualizar (ya aprobaciones o rechazo)
        $estatusOptions = Estatus::whereIn('id', [2,3,8,9]) // Gerencia, Financiera, Rechazado, Completado
            ->pluck('status_name', 'id');

        return view('requisiciones.aprobacion', compact('requisiciones', 'estatusOptions'));
    }

    /**
     * Actualizar estatus de una requisición
     */
    public function updateStatus(Request $request, $requisicionId)
    {
        if (!PermissionHelper::hasPermission('aprobar requisicion')) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.'
            ], 403);
        }

        $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
            'comentarios' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::findOrFail($requisicionId);
            
            $estatusRequisicion = new Estatus_Requisicion();
            $estatusRequisicion->requisicion_id = $requisicionId;
            $estatusRequisicion->estatus_id = $request->estatus_id;
            $estatusRequisicion->estatus = $request->comentarios ?? 'Cambio de estatus';
            $estatusRequisicion->date_update = now();
            $estatusRequisicion->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Estatus actualizado correctamente.',
                'nuevo_estatus' => $estatusRequisicion->estatusRelation->status_name ?? 'Desconocido'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estatus: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener detalles de requisición
     */
    public function getRequisicionDetails($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'estatusHistorial.estatus',
            'centros'
        ])->findOrFail($id);

        $data = [
            'requisicion' => $requisicion,
            'productos' => $requisicion->productos->map(function($producto) {
                return [
                    'nombre' => $producto->name_produc,
                    'cantidad' => $producto->pivot->pr_amount,
                    'unidad' => $producto->unit_produc
                ];
            }),
            'historial' => $requisicion->estatusHistorial->map(function($historial) {
                return [
                    'estatus' => $historial->estatus->status_name,
                    'fecha' => $historial->created_at->format('d/m/Y H:i'),
                    'comentarios' => $historial->estatus
                ];
            })
        ];

        return response()->json($data);
    }

    /**
     * Estadísticas para dashboard
     */
    public function getStats()
    {
        $stats = DB::table('estatus_requisicion as er')
            ->join('estatus as e', 'er.estatus_id', '=', 'e.id')
            ->select(
                'e.status_name',
                DB::raw('COUNT(DISTINCT er.requisicion_id) as total')
            )
            ->groupBy('e.status_name')
            ->get()
            ->pluck('total', 'status_name');

        return response()->json($stats);
    }
}
