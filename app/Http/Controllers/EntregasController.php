<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EntregasController extends Controller
{
    public function storeMasiva(Request $request): JsonResponse
    {
        $data = $request->validate([
            'requisicion_id' => 'required|integer|exists:requisicion,id',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|integer|exists:productos,id',
            'items.*.ocp_id' => 'nullable|integer|exists:ordencompra_producto,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'comentario' => 'nullable'
        ]);

        DB::beginTransaction();
        try {
            $fecha = Carbon::now()->toDateString();
            foreach ($data['items'] as $it) {
                DB::table('entrega')->insert([
                    'requisicion_id' => $data['requisicion_id'],
                    'producto_id' => $it['producto_id'],
                    'cantidad' => $it['cantidad'],
                    'cantidad_recibido' => 0,
                    'fecha' => $fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]);
            }

            // Cerrar estatus activo previo y abrir estatus 8
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
                'estatus_id' => 8,
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
            Log::error('Error storeMasiva entrega: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'No se pudo registrar la entrega'], 500);
        }
    }

    public function confirmar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entrega_id' => 'required|integer|exists:entrega,id',
            'cantidad' => 'required|integer|min:0',
        ]);

        try {
            $row = DB::table('entrega')->where('id', $data['entrega_id'])->first();
            if (!$row) {
                return response()->json(['message' => 'Entrega no encontrada'], 404);
            }
            $cantidadMax = (int)($row->cantidad ?? 0);
            $cantidadRec = min(max((int)$data['cantidad'], 0), $cantidadMax);

            DB::table('entrega')->where('id', $data['entrega_id'])->update([
                'cantidad_recibido' => $cantidadRec,
                'updated_at' => now(),
            ]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Error confirmar entrega: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'No se pudo confirmar la recepción'], 500);
        }
    }
}
