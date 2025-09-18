<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class StockController extends Controller
{
    public function restaurarStock(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requisicion_id' => 'required|integer|exists:requisicion,id',
            'comentario' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            // Limpiar stock_e de líneas de la requisición
            DB::table('ordencompra_producto')
                ->where('requisicion_id', $data['requisicion_id'])
                ->update(['stock_e' => null, 'updated_at' => now()]);

            // Cerrar estatus activo y abrir estatus 5
            $activo = DB::table('estatus_requisicion')
                ->where('requisicion_id', $data['requisicion_id'])
                ->whereNull('deleted_at')
                ->where('estatus', 1)
                ->first();
            if ($activo) {
                DB::table('estatus_requisicion')
                    ->where('id', $activo->id)
                    ->update(['estatus' => 0, 'updated_at' => now()]);
            }
            DB::table('estatus_requisicion')->insert([
                'requisicion_id' => $data['requisicion_id'],
                'estatus_id' => 5,
                'comentario' => null,
                'estatus' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error restaurarStock: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'No se pudo restaurar el stock'], 500);
        }
    }
}
