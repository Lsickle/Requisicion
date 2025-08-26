<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Estatus;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Mail\EstatusRequisicionActualizado;
use Illuminate\Support\Facades\Mail;

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        $estatusRequisiciones = Estatus_Requisicion::with(['estatus', 'requisicion'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($estatusRequisiciones);
    }

    public function create()
    {
        $estados = Estatus::all();
        return response()->json($estados);
    }

    /**
     * Mostrar requisiciones pendientes de aprobación
     */
    public function aprobacion()
    {
        $requisicionesPendientes = collect();
        $userPermissions = Session::get('user')['permissions'] ?? [];

        $aprobacionGerenciaId = Estatus::where('status_name', 'Aprobación Gerencia')->value('id');
        $aprobacionFinancieraId = Estatus::where('status_name', 'Aprobación Financiera')->value('id');

        if (in_array('Gerencia', $userPermissions)) {
            $requisicionesPendientes = Requisicion::whereHas('estatusHistorial', function ($query) use ($aprobacionGerenciaId) {
                $query->where('estatus_id', $aprobacionGerenciaId)
                      ->where('estatus', 1);
            })->with(['user', 'productos.distribucion_centros', 'estatusHistorial.estatus'])
            ->orderByDesc('created_at')
            ->get();

        } elseif (in_array('Gerente financiero', $userPermissions)) {
            $requisicionesPendientes = Requisicion::whereHas('estatusHistorial', function ($query) use ($aprobacionFinancieraId) {
                $query->where('estatus_id', $aprobacionFinancieraId)
                      ->where('estatus', 1);
            })->with(['user', 'productos.distribucion_centros', 'estatusHistorial.estatus'])
            ->orderByDesc('created_at')
            ->get();
        }

        $requisicionesPendientes = $requisicionesPendientes->filter(function ($requisicion) use ($userPermissions, $aprobacionGerenciaId, $aprobacionFinancieraId) {
            $ultimoEstatus = $requisicion->estatusHistorial->sortByDesc('created_at')->first();
            if (!$ultimoEstatus || !$ultimoEstatus->estatus) {
                return false;
            }
            return (in_array('Gerencia', $userPermissions) && $ultimoEstatus->estatus_id === $aprobacionGerenciaId) ||
                   (in_array('Gerente financiero', $userPermissions) && $ultimoEstatus->estatus_id === $aprobacionFinancieraId);
        });

        return view('requisiciones.aprobacion', ['requisiciones' => $requisicionesPendientes]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'requisicion_id' => 'required|exists:requisicion,id',
            'estatus_id'     => 'required|exists:estatus,id',
            'comentario'     => 'nullable|string|max:255',
            'estatus'        => 'required|boolean',
        ]);

        $nuevo = Estatus_Requisicion::create(array_merge($validated, [
            'date_update' => now()
        ]));

        $requisicion = Requisicion::find($validated['requisicion_id']);
        $destinatarios = ['pardomoyasegio@empresa.com'];
        Mail::to($destinatarios)->send(new EstatusRequisicionActualizado($requisicion, $nuevo));

        return response()->json([
            'message' => 'Estado creado exitosamente',
            'estado'  => $nuevo
        ]);
    }

    /**
     * Mostrar historial de TODAS las requisiciones
     */
    public function historial()
    {
        $requisiciones = Requisicion::with('estatus')->get();

        return view('requisiciones.historial', [
            'requisiciones' => $requisiciones
        ]);
    }

    /**
     * Mostrar historial de UNA requisición
     */
    public function show($id)
    {
        $requisicion = Requisicion::with('estatus')->findOrFail($id);
        $estatusOrdenados = $requisicion->estatus->sortBy('pivot.created_at');
        $estatusActual = $estatusOrdenados->last();

        return view('requisiciones.estatus', compact('requisicion', 'estatusOrdenados', 'estatusActual'));
    }

    public function edit(Estatus_Requisicion $estatusRequisicion)
    {
        return response()->json([
            'estado' => $estatusRequisicion,
            'estadosDisponibles' => Estatus::all()
        ]);
    }

    public function update(Request $request, Estatus_Requisicion $estatusRequisicion)
    {
        $validated = $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
            'comentario' => 'nullable|string|max:255',
            'estatus'    => 'required|boolean',
        ]);

        $estatusRequisicion->update(array_merge($validated, ['date_update' => now()]));

        if ($validated['estatus']) {
            $requisicion = Requisicion::find($estatusRequisicion->requisicion_id);
            $destinatarios = ['pardomoyasegio@gmail.com'];
            Mail::to($destinatarios)->send(new EstatusRequisicionActualizado($requisicion, $estatusRequisicion));
        }

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado'  => $estatusRequisicion
        ]);
    }

    public function destroy(Estatus_Requisicion $estatusRequisicion)
    {
        $estatusRequisicion->delete();
        return response()->json(['message' => 'Estado eliminado correctamente']);
    }

    public function actualizarEstatusAprobacion(Request $request, Requisicion $requisicion)
    {
        $request->validate([
            'accion'     => 'required|in:aprobar,rechazar',
            'comentario' => 'nullable|string|max:255',
        ]);

        $userPermissions = Session::get('user')['permissions'] ?? [];
        $accion = $request->accion;
        $comentario = $request->comentario;

        return DB::transaction(function () use ($requisicion, $userPermissions, $accion, $comentario) {
            $ultimoEstatusRegistro = Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->where('estatus', 1)
                ->orderByDesc('created_at')
                ->first();

            if (!$ultimoEstatusRegistro) {
                return response()->json(['error' => 'La requisición no tiene un estado activo'], 400);
            }

            $ultimoEstatus = $ultimoEstatusRegistro->estatus;
            $nuevoEstatusId = null;
            $comentarioAccion = ($accion === 'aprobar' ? 'Aprobado' : 'Rechazado') . ($comentario ? ': ' . $comentario : '');

            $aprobacionGerenciaId = Estatus::where('status_name', 'Aprobación Gerencia')->value('id');
            $aprobacionFinancieraId = Estatus::where('status_name', 'Aprobación Financiera')->value('id');
            $contactoProveedorId = Estatus::where('status_name', 'Contacto con proveedor')->value('id');
            $rechazadoId = Estatus::where('status_name', 'Rechazado')->value('id');

            if ($accion === 'rechazar') {
                $nuevoEstatusId = $rechazadoId;
            } elseif ($accion === 'aprobar') {
                if (in_array('Gerencia', $userPermissions) && $ultimoEstatus->id === $aprobacionGerenciaId) {
                    $nuevoEstatusId = $aprobacionFinancieraId;
                } elseif (in_array('Gerente financiero', $userPermissions) && $ultimoEstatus->id === $aprobacionFinancieraId) {
                    $nuevoEstatusId = $contactoProveedorId;
                } else {
                    return response()->json(['error' => 'No tienes permiso para aprobar en este estado.'], 403);
                }
            }

            if ($nuevoEstatusId === null) {
                return response()->json(['error' => 'Transición de estado no válida.'], 400);
            }

            $ultimoEstatusRegistro->update(['estatus' => 0]);

            $nuevoEstadoRegistro = Estatus_Requisicion::create([
                'estatus_id'     => $nuevoEstatusId,
                'requisicion_id' => $requisicion->id,
                'estatus'        => 1,
                'date_update'    => now(),
                'comentario'     => $comentarioAccion,
            ]);

            return response()->json([
                'message'      => "Requisición " . ($accion === 'aprobar' ? 'aprobada' : 'rechazada') . " y estado actualizado.",
                'nuevo_estado' => $nuevoEstadoRegistro
            ]);
        });
    }

    public function avanzar(Request $request, Requisicion $requisicion)
    {
        $request->validate(['comentario' => 'nullable|string|max:255']);

        return DB::transaction(function () use ($request, $requisicion) {
            $ultimo = Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->where('estatus', 1)
                ->first();

            if (!$ultimo) return response()->json(['error' => 'No hay estado activo'], 404);

            $siguiente = Estatus::where('id', '>', $ultimo->estatus_id)->orderBy('id')->first();
            if (!$siguiente) return response()->json(['error' => 'No hay más estados disponibles'], 400);

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)->update(['estatus' => 0]);

            $nuevo = Estatus_Requisicion::create([
                'estatus_id'     => $siguiente->id,
                'requisicion_id' => $requisicion->id,
                'estatus'        => 1,
                'date_update'    => now(),
                'comentario'     => $request->comentario
            ]);

            $destinatarios = ['pardomoyasegio@empresa.com'];
            Mail::to($destinatarios)->send(new EstatusRequisicionActualizado($requisicion, $nuevo));

            return response()->json([
                'message'      => "Estado cambiado a {$siguiente->status_name}",
                'nuevo_estado' => $nuevo
            ]);
        });
    }

    public function cancelar(Request $request, Requisicion $requisicion)
    {
        $request->validate(['motivo' => 'nullable|string|max:255']);

        return DB::transaction(function () use ($request, $requisicion) {
            Estatus_Requisicion::where('requisicion_id', $requisicion->id)->update(['estatus' => 0]);

            $cancelado = Estatus_Requisicion::create([
                'estatus_id'     => 8,
                'requisicion_id' => $requisicion->id,
                'estatus'        => 1,
                'date_update'    => now(),
                'comentario'     => $request->motivo
            ]);

            $destinatarios = ['pardomoyasegio@empresa.com'];
            Mail::to($destinatarios)->send(new EstatusRequisicionActualizado($requisicion, $cancelado));

            return response()->json([
                'message'          => 'Requisición cancelada',
                'estado_cancelado' => $cancelado
            ]);
        });
    }

    public function siguiente(Requisicion $requisicion)
    {
        $ultimo = Estatus_Requisicion::where('requisicion_id', $requisicion->id)
            ->where('estatus', 1)
            ->first();

        if (!$ultimo) return response()->json(['error' => 'No hay estado activo'], 404);

        $siguiente = Estatus::where('id', '>', $ultimo->estatus_id)->orderBy('id')->first();

        return response()->json($siguiente);
    }
}
