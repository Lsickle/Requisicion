<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\Transferencia;
use App\Models\Centro;
use App\Models\Producto;
use App\Models\InventarioBodega;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferenciaController extends Controller
{
    private function getMisBodegas(): array
    {
        $email = $this->getMiEmail();
        if (!$email) return [];

        $subcentros = DB::table('userxsubcentro')
            ->where('userxsubcentro.email_user', $email)
            ->whereNull('userxsubcentro.deleted_at')
            ->join('subcentros', 'userxsubcentro.subcentro_id', '=', 'subcentros.id')
            ->select('subcentros.centro_id')
            ->distinct()
            ->pluck('subcentros.centro_id');

        return $subcentros->toArray();
    }

    private function getMiEmail(): ?string
    {
        return session('user_email') ?? session('user.email') ?? session('email') ?? auth()->user()->email ?? null;
    }

    public function index(): View
    {
        $misBodegas = $this->getMisBodegas();
        $centros = Centro::orderBy('name_centro')->get();
        $productos = Producto::orderBy('name_produc')->get();
        $isAdmin = PermissionHelper::hasAnyRole(['admin', 'compras']);

        $transferencias = Transferencia::with(['bodegaOrigen', 'bodegaDestino', 'producto'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('inventario.transferencia', compact('centros', 'productos', 'transferencias', 'misBodegas', 'isAdmin'));
    }

    public function getInventario(Request $request): JsonResponse
    {
        $bodegaId = $request->get('bodega_id');
        
        $inventario = InventarioBodega::where('bodega_id', $bodegaId)
            ->where('cantidad', '>', 0)
            ->with('producto')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'producto_id' => $item->producto_id,
                'producto_nombre' => $item->producto->name_produc,
                'producto_sku' => $item->producto->sku,
                'cantidad' => $item->cantidad,
            ]);

        return response()->json($inventario);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bodega_origen_id' => 'required|integer|exists:centro,id',
            'bodega_destino_id' => 'required|integer|exists:centro,id',
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'nombre_origen' => 'required|string|max:255',
            'cedula_origen' => 'required|string|max:50',
            'firma_origen' => 'required|string',
            'nombre_destino' => 'required|string|max:255',
            'cedula_destino' => 'required|string|max:50',
            'firma_destino' => 'required|string',
        ]);

        // Verificar stock en bodega origen
        $inventarioOrigen = InventarioBodega::where('bodega_id', $data['bodega_origen_id'])
            ->where('producto_id', $data['producto_id'])
            ->first();

        if (!$inventarioOrigen || $inventarioOrigen->cantidad < $data['cantidad']) {
            return response()->json([
                'message' => 'Stock insuficiente en bodega origen'
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Descontar de bodega origen
            $inventarioOrigen->cantidad -= $data['cantidad'];
            $inventarioOrigen->save();

            // Agregar a bodega destino (crear si no existe)
            $inventarioDestino = InventarioBodega::firstOrNew([
                'bodega_id' => $data['bodega_destino_id'],
                'producto_id' => $data['producto_id'],
            ]);
            $inventarioDestino->cantidad = ($inventarioDestino->cantidad ?? 0) + $data['cantidad'];
            $inventarioDestino->save();

            // Crear registro de transferencia
            $transferencia = Transferencia::create([
                'bodega_origen_id' => $data['bodega_origen_id'],
                'bodega_destino_id' => $data['bodega_destino_id'],
                'producto_id' => $data['producto_id'],
                'cantidad' => $data['cantidad'],
                'observaciones' => $request->get('observaciones'),
                'nombre_origen' => $data['nombre_origen'],
                'cedula_origen' => $data['cedula_origen'],
                'firma_origen' => $data['firma_origen'],
                'nombre_destino' => $data['nombre_destino'],
                'cedula_destino' => $data['cedula_destino'],
                'firma_destino' => $data['firma_destino'],
                'estado' => 'aprobado',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transferencia realizada correctamente',
                'transferencia_id' => $transferencia->id,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferencia: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al realizar la transferencia'
            ], 500);
        }
    }

    public function pdf(int $id): JsonResponse
    {
        $transferencia = Transferencia::with(['bodegaOrigen', 'bodegaDestino', 'producto'])->find($id);

        if (!$transferencia) {
            return response()->json(['message' => 'Transferencia no encontrada'], 404);
        }

        return response()->json([
            'id' => $transferencia->id,
            'fecha' => $transferencia->created_at->format('d/m/Y H:i'),
            'bodega_origen' => $transferencia->bodegaOrigen->name_centro,
            'bodega_destino' => $transferencia->bodegaDestino->name_centro,
            'producto' => $transferencia->producto->name_produc,
            'producto_sku' => $transferencia->producto->sku,
            'cantidad' => $transferencia->cantidad,
            'observaciones' => $transferencia->observaciones,
            'nombre_origen' => $transferencia->nombre_origen,
            'cedula_origen' => $transferencia->cedula_origen,
            'firma_origen' => $transferencia->firma_origen,
            'nombre_destino' => $transferencia->nombre_destino,
            'cedula_destino' => $transferencia->cedula_destino,
            'firma_destino' => $transferencia->firma_destino,
        ]);
    }
}