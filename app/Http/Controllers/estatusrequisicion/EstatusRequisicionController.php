<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Estatus;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
     * Mostrar historial de una requisición específica
     */
    public function show($id)
    {
        // Cargar la requisición con su relación de estatus
        $requisicion = Requisicion::with('estatus')->findOrFail($id);

        // Ordenar los estatus por fecha de creación en el pivot
        $estatusOrdenados = $requisicion->estatus->sortBy('pivot.created_at');

        // Último estatus (el actual)
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
                'estatus_id'     => 8, // ID de "Cancelado"
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
