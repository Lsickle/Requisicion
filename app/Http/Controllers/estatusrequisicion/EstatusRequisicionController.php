<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Estatus;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EstatusRequisicionController extends Controller
{
    /**
     * Listar todos los estados de todas las requisiciones
     */
    public function index()
    {
        $estatusRequisiciones = Estatus_Requisicion::with(['estatus', 'requisicion'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($estatusRequisiciones);
    }

    /**
     * Mostrar formulario para crear un nuevo estado (opcional, puede ser JSON)
     */
    public function create()
    {
        $estados = Estatus::all();
        return response()->json($estados);
    }

    /**
     * Guardar un nuevo estado manualmente
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requisicion_id' => 'required|exists:requisicions,id',
            'estatus_id'     => 'required|exists:estatus,id',
            'comentario'     => 'nullable|string|max:255',
            'estatus'        => 'required|boolean',
        ]);

        $nuevo = Estatus_Requisicion::create(array_merge($validated, ['date_update' => now()]));

        return response()->json([
            'message' => 'Estado creado exitosamente',
            'estado' => $nuevo
        ]);
    }

    /**
     * Mostrar historial de una requisición específica
     */
    public function show(Requisicion $requisicion)
    {
        $historial = Estatus_Requisicion::with('estatus')
            ->where('requisicion_id', $requisicion->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $estadoActual = $historial->firstWhere('estatus', 1);

        return response()->json([
            'historial' => $historial,
            'estadoActual' => $estadoActual,
        ]);
    }

    /**
     * Mostrar formulario de edición de un estado (opcional)
     */
    public function edit(Estatus_Requisicion $estatusRequisicion)
    {
        return response()->json([
            'estado' => $estatusRequisicion,
            'estadosDisponibles' => Estatus::all()
        ]);
    }

    /**
     * Actualizar un estado existente
     */
    public function update(Request $request, Estatus_Requisicion $estatusRequisicion)
    {
        $validated = $request->validate([
            'estatus_id' => 'required|exists:estatus,id',
            'comentario' => 'nullable|string|max:255',
            'estatus'    => 'required|boolean',
        ]);

        $estatusRequisicion->update(array_merge($validated, ['date_update' => now()]));

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado' => $estatusRequisicion
        ]);
    }

    /**
     * Eliminar un estado específico
     */
    public function destroy(Estatus_Requisicion $estatusRequisicion)
    {
        $estatusRequisicion->delete();

        return response()->json(['message' => 'Estado eliminado correctamente']);
    }

    /**
     * Avanzar al siguiente estado de una requisición
     */
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
                'estatus_id' => $siguiente->id,
                'requisicion_id' => $requisicion->id,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => $request->comentario
            ]);

            return response()->json(['message' => "Estado cambiado a {$siguiente->status_name}", 'nuevo_estado' => $nuevo]);
        });
    }

    /**
     * Cancelar la requisición
     */
    public function cancelar(Request $request, Requisicion $requisicion)
    {
        $request->validate(['motivo' => 'nullable|string|max:255']);

        return DB::transaction(function () use ($request, $requisicion) {
            Estatus_Requisicion::where('requisicion_id', $requisicion->id)->update(['estatus' => 0]);

            $cancelado = Estatus_Requisicion::create([
                'estatus_id' => 8, // ID de "Cancelado"
                'requisicion_id' => $requisicion->id,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => $request->motivo
            ]);

            return response()->json(['message' => 'Requisición cancelada', 'estado_cancelado' => $cancelado]);
        });
    }

    /**
     * Obtener el siguiente estado disponible
     */
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
