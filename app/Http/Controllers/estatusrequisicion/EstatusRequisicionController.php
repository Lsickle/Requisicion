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

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        // Validar sesión y permisos
        if (!PermissionHelper::hasAnyRole(['Gerencia','Gerente financiero']) || !PermissionHelper::hasPermission('aprobar requisicion')) {
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
        $requisiciones = Requisicion::with(['ultimoEstatus.estatus','productos'])
            ->whereHas('ultimoEstatus', function($q) use ($estatusFiltrar){
                $q->where('estatus_id', $estatusFiltrar);
            })
            ->orderBy('created_at','desc')
            ->get();

        // Opciones de estatus según rol
        $estatusOptions = collect();
        if ($role === 'Gerencia') {
            $estatusOptions = Estatus::where('id',2)->pluck('status_name','id');
        } elseif ($role === 'Gerente financiero') {
            $estatusOptions = Estatus::where('id',3)->pluck('status_name','id');
        }

        return view('requisiciones.aprobacion', compact('requisiciones','estatusOptions'));
    }

    public function updateStatus(Request $request, $requisicionId)
    {
        if (!PermissionHelper::hasAnyRole(['Gerencia','Gerente financiero']) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return response()->json([
                'success' => false,
                'message' => 'Debes iniciar sesión o no tienes permisos.'
            ], 403);
        }

        $role = PermissionHelper::hasRole('Gerencia') ? 'Gerencia' : 'Gerente financiero';

        $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
            'comentarios' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $requisicion = Requisicion::findOrFail($requisicionId);

            if ($role === 'Gerencia' && $requisicion->ultimoEstatus->estatus_id != 1) {
                return response()->json(['success'=>false,'message'=>'Solo puedes aprobar requisiciones en estatus iniciada'],403);
            }

            if ($role === 'Gerente financiero' && $requisicion->ultimoEstatus->estatus_id != 2) {
                return response()->json(['success'=>false,'message'=>'Solo puedes aprobar requisiciones en estatus de aprobación financiera'],403);
            }

            $estatusRequisicion = new Estatus_Requisicion();
            $estatusRequisicion->requisicion_id = $requisicionId;
            $estatusRequisicion->estatus_id = $request->estatus_id;
            $estatusRequisicion->estatus = $request->comentarios ?? 'Cambio de estatus';
            $estatusRequisicion->date_update = now();
            $estatusRequisicion->save();

            DB::commit();

            return response()->json([
                'success'=>true,
                'message'=>'Estatus actualizado correctamente',
                'nuevo_estatus'=>$estatusRequisicion->estatusRelation->status_name ?? 'Desconocido'
            ]);

        } catch(\Exception $e){
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Error al actualizar el estatus: '.$e->getMessage()],500);
        }
    }

    public function getRequisicionDetails($id)
    {
        $requisicion = Requisicion::with(['productos','estatusHistorial.estatus','centros'])->findOrFail($id);

        $data = [
            'requisicion' => $requisicion,
            'productos' => $requisicion->productos->map(fn($p)=>[
                'nombre'=>$p->name_produc,
                'cantidad'=>$p->pivot->pr_amount,
                'unidad'=>$p->unit_produc
            ]),
            'historial' => $requisicion->estatusHistorial->map(fn($h)=>[
                'estatus'=>$h->estatus->status_name,
                'fecha'=>$h->created_at->format('d/m/Y H:i'),
                'comentarios'=>$h->estatus
            ])
        ];

        return response()->json($data);
    }

    public function getStats()
    {
        $stats = DB::table('estatus_requisicion as er')
            ->join('estatus as e','er.estatus_id','=','e.id')
            ->select('e.status_name', DB::raw('COUNT(DISTINCT er.requisicion_id) as total'))
            ->groupBy('e.status_name')
            ->get()
            ->pluck('total','status_name');

        return response()->json($stats);
    }
}
