<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\PermissionHelper;
use App\Mail\EstatusRequisicionActualizado;
use Illuminate\Support\Facades\Mail;
use app\Jobs\EstatusRequisicionActualizadoJob;

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

        // Obtener requisiciones según estatus
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
                $rechazado = new Estatus_Requisicion();
                $rechazado->requisicion_id = $requisicionId;
                $rechazado->estatus_id = 8; // Rechazado
                $rechazado->estatus = 0; // Inactivo
                $rechazado->date_update = now();
                $rechazado->save();

                $completado = new Estatus_Requisicion();
                $completado->requisicion_id = $requisicionId;
                $completado->estatus_id = 9; // Completado
                $completado->estatus = 1; // Activo
                $completado->date_update = now();
                $completado->save();

                $nuevoEstatus = $completado;
                $mensajeAccion = 'rechazada';
            } else {
                $nuevo = new Estatus_Requisicion();
                $nuevo->requisicion_id = $requisicionId;
                $nuevo->estatus_id = $request->estatus_id;
                $nuevo->estatus = 1;
                $nuevo->date_update = now();
                $nuevo->save();

                $nuevoEstatus = $nuevo;
            }

            // Enviar correo con Job (asincrónico)
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


    public function getRequisicionDetails($id)
    {
        $requisicion = Requisicion::with(['productos', 'estatusHistorial.estatus', 'centros'])->findOrFail($id);

        $data = [
            'requisicion' => $requisicion,
            'productos' => $requisicion->productos->map(fn($p) => [
                'nombre' => $p->name_produc,
                'cantidad' => $p->pivot->pr_amount,
                'unidad' => $p->unit_produc
            ]),
            'historial' => $requisicion->estatusHistorial->map(fn($h) => [
                'estatus' => $h->estatus->status_name,
                'fecha' => $h->created_at->format('d/m/Y H:i'),
            ])
        ];

        return response()->json($data);
    }

    public function getStats()
    {
        $stats = DB::table('estatus_requisicion as er')
            ->join('estatus as e', 'er.estatus_id', '=', 'e.id')
            ->select('e.status_name', DB::raw('COUNT(DISTINCT er.requisicion_id) as total'))
            ->where('er.estatus', 1) // Solo contar estatus activos
            ->groupBy('e.status_name')
            ->get()
            ->pluck('total', 'status_name');

        return response()->json($stats);
    }
}
