<?php

namespace App\Http\Controllers;

use App\Models\Entrega;
use App\Models\Producto;
use App\Models\Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalidaStockController extends Controller
{
    public function index()
    {
        $userId = session('user.id');
        $userSubcentroId = session('user.subcentro_id');
        
        $productos = collect();
        
        if ($userSubcentroId) {
            $productos = Producto::where('stock_produc', '>', 0)
                ->orderBy('name_produc')
                ->get();
        }
        
        return view('salida_stock.index', compact('productos'));
    }

    public function buscar(Request $request)
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $productos = Producto::where('stock_produc', '>', 0)
            ->where(function($q) use ($query) {
                $q->where('name_produc', 'like', "%{$query}%")
                  ->orWhere('categoria_produc', 'like', "%{$query}%");
            })
            ->limit(20)
            ->get(['id', 'name_produc', 'stock_produc', 'unit_produc', 'categoria_produc']);
        
        return response()->json($productos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'firma' => 'required|string',
            'nombre_firma' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
        ]);
        
        $userId = session('user.id');
        $userName = session('user.name') ?? session('user.email');
        
        DB::beginTransaction();
        try {
            $productosActualizados = [];
            
            foreach ($request->productos as $item) {
                $productoId = (int) $item['producto_id'];
                $cantidad = (int) $item['cantidad'];
                
                $producto = Producto::lockForUpdate()->findOrFail($productoId);
                
                if ($producto->stock_produc < $cantidad) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuficiente para {$producto->name_produc}. Stock actual: {$producto->stock_produc}"
                    ], 422);
                }
                
                $stockAnterior = $producto->stock_produc;
                $nuevoStock = $stockAnterior - $cantidad;
                $producto->stock_produc = $nuevoStock;
                $producto->save();
                
                // Obtener unidad del producto
                $unidad = DB::table('productos')->where('id', $productoId)->value('unit_produc') ?? 'UND';
                
                $productosActualizados[] = [
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->name_produc,
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $nuevoStock,
                    'unidad' => $unidad,
                ];
                
                // Registrar en tabla de entrega como salida de stock
                Entrega::create([
                    'requisicion_id' => null, // No relacionado a requisición
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'cantidad_recibido' => $cantidad,
                    'fecha' => now(),
                    'user_id' => $userId,
                    'user_name' => $userName,
                    'firma_base64' => $request->firma,
                    'firma_nombre' => $request->nombre_firma,
                    'observaciones' => $request->observaciones,
                ]);
            }
            
            $numero = 'SS-' . date('Ymd') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $fecha = now()->format('d/m/Y');
            $hora = now()->format('H:i:s');
            
            // Generar HTML del PDF
            $html = view('salida_stock.pdf', [
                'numero' => $numero,
                'fecha' => $fecha,
                'hora' => $hora,
                'userName' => $userName,
                'productos' => $productosActualizados,
                'firma' => $request->firma,
                'nombreFirma' => $request->nombre_firma,
                'observaciones' => $request->observaciones,
            ])->render();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Salida de stock registrada correctamente',
                'data' => [
                    'numero' => $numero,
                    'html' => $html,
                    'productos' => $productosActualizados,
                ]
            ]);
            
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en salida stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la salida: ' . $e->getMessage()
            ], 500);
        }
    }

    public function historial()
    {
        $userId = session('user.id');
        
        $salidas = collect();
        
        return view('salida_stock.historial', compact('salidas'));
    }
}
