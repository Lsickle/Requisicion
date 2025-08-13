<?php

namespace App\Http\Controllers;

use App\Models\Estatus;
use App\Models\Requisicion;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EstatusRequisicionController extends Controller
{
    /**
     * Avanza al siguiente estado disponible
     */
    public function avanzarEstatus(Request $request, $requisicionId)
    {
        $request->validate([
            'comentario' => 'nullable|string|max:255'
        ]);

        return DB::transaction(function () use ($request, $requisicionId) {
            // 1. Obtener el último estatus activo
            $ultimoEstatus = Estatus_Requisicion::where('requisicion_id', $requisicionId)
                ->where('estatus', 1)
                ->first();

            // 2. Obtener el siguiente estatus en la secuencia
            $siguienteEstatus = Estatus::where('id', '>', $ultimoEstatus->estatus_id)
                ->orderBy('id')
                ->first();

            if (!$siguienteEstatus) {
                return back()->with('error', 'No hay más estados disponibles');
            }

            // 3. Desactivar todos los estatus anteriores
            Estatus_Requisicion::where('requisicion_id', $requisicionId)
                ->update(['estatus' => 0]);

            // 4. Crear nuevo registro con estatus activo
            Estatus_Requisicion::create([
                'estatus_id' => $siguienteEstatus->id,
                'requisicion_id' => $requisicionId,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => $request->comentario
            ]);

            return back()->with('success', "Estado cambiado a: {$siguienteEstatus->status_name}");
        });
    }

    /**
     * Cancelar requisición (estado especial)
     */
    public function cancelar(Request $request, $requisicionId)
    {
        return DB::transaction(function () use ($request, $requisicionId) {
            // 1. Desactivar todos los estatus
            Estatus_Requisicion::where('requisicion_id', $requisicionId)
                ->update(['estatus' => 0]);

            // 2. Marcar como cancelado (ID 8 según tu seeder)
            Estatus_Requisicion::create([
                'estatus_id' => 8, // ID de "Cancelado"
                'requisicion_id' => $requisicionId,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => $request->motivo
            ]);

            return back()->with('success', 'Requisición cancelada');
        });
    }

    /**
     * Historial completo de estados
     */
    public function historial($requisicionId)
    {
        $historial = Estatus_Requisicion::with('estatus')
            ->where('requisicion_id', $requisicionId)
            ->orderBy('created_at', 'desc')
            ->get();

        $estadoActual = $historial->firstWhere('estatus', 1);

        return view('requisiciones.estatus.historial', [
            'historial' => $historial,
            'estadoActual' => $estadoActual,
            'requisicionId' => $requisicionId
        ]);
    }

    /**
     * Obtener el siguiente estado disponible
     */
    public function getSiguienteEstado($requisicionId)
    {
        $ultimoEstatus = Estatus_Requisicion::where('requisicion_id', $requisicionId)
            ->where('estatus', 1)
            ->first();

        $siguienteEstatus = Estatus::where('id', '>', $ultimoEstatus->estatus_id)
            ->orderBy('id')
            ->first();

        return response()->json($siguienteEstatus);
    }


    /**
     * Genera PDF con el estado actual de la requisición
     */
    public function descargarPDF($id)
    {
        // Cargamos requisición con sus estatus
        $requisicion = Requisicion::with('estatus')->findOrFail($id);

        // Estado actual (último)
        $estadoActual = $requisicion->estatus->sortByDesc('pivot.created_at')->first();

        // Historial ordenado por fecha descendente
        $historial = $requisicion->estatus->sortByDesc('pivot.created_at');

        // Generar PDF
        $pdf = Pdf::loadView('estatus.pdf', [
            'requisicion'  => $requisicion,  //  aquí se envia
            'estadoActual' => $estadoActual,
            'historial'    => $historial
        ]);


        return $pdf->download("Estatus requisición_{$id}.pdf");
    }
}
