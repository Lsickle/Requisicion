<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\InventarioBodega;
use App\Models\InventarioMovimiento;
use App\Models\UserxSubcentro;
use App\Models\Centro;
use App\Models\Producto;
use App\Models\Subcentro;
use App\Helpers\PermissionHelper;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InventarioExport;

class InventarioController extends Controller
{
    private function getMisBodegas(): array
    {
        // Obtener email del usuario de la sesión
        $email = session('user_email') ?? session('user.email') ?? session('email') ?? auth()->user()->email ?? null;

        if (!$email) {
            return [];
        }

        $subcentros = UserxSubcentro::where('email_user', $email)
            ->whereNull('deleted_at')
            ->with('subcentro.centro')
            ->get();

        $bodegas = [];
        foreach ($subcentros as $sc) {
            if ($sc->subcentro && $sc->subcentro->centro) {
                $bodegas[] = $sc->subcentro->centro->id;
            }
        }

        return array_unique($bodegas);
    }

    private function getMiEmail(): ?string
    {
        return session('user_email') ?? session('user.email') ?? session('email') ?? auth()->user()->email ?? null;
    }

    private function getMisSubcentros(): array
    {
        $email = $this->getMiEmail();

        if (!$email) {
            return [];
        }

        $subcentrosData = UserxSubcentro::where('email_user', $email)
            ->whereNull('deleted_at')
            ->with('subcentro.centro')
            ->get();

        $result = [];
        foreach ($subcentrosData as $sc) {
            if ($sc->subcentro) {
                $result[] = [
                    'id' => $sc->subcentro->id,
                    'nombre' => $sc->subcentro->name_subcentro,
                    'bodega_id' => $sc->subcentro->centro_id ?? null,
                    'bodega_nombre' => $sc->subcentro->centro->name_centro ?? null,
                    'subcentro_id' => $sc->subcentro->id,
                ];
            }
        }

        return $result;
    }

    private function getMisSubcentroIds(): array
    {
        return array_column($this->getMisSubcentros(), 'subcentro_id');
    }

    private function puedeVerTodas(): bool
    {
        return PermissionHelper::hasAnyRole(['compras', 'admin']);
    }

    private function puedeModificar(): bool
    {
        $email = $this->getMiEmail();
        $misSubcentros = $this->getMisSubcentros();
        
        Log::info('puedeModificar check', [
            'email' => $email,
            'misSubcentros' => $misSubcentros
        ]);
        
        return !empty($misSubcentros);
    }

    private function tieneBodegaAsignada(): bool
    {
        $misSubcentroIds = $this->getMisSubcentroIds();
        return !empty($misSubcentroIds);
    }

    public function index(Request $request): View
    {
        $verTodas = $this->puedeVerTodas();
        $misSubcentros = $this->getMisSubcentros();
        $misSubcentroIds = $this->getMisSubcentroIds();
        $email = $this->getMiEmail();

        $centros = Centro::orderBy('name_centro')->get();

        $bodegaSeleccionada = $request->get('bodega');
        $subcentroSeleccionado = $request->get('subcentro');

        $miSubcentro = null;
        $bodegaActual = null;
        $subcentroActual = null;

        if ($verTodas) {
            $todosLosSubcentros = Subcentro::with('centro')
                ->whereNull('deleted_at')
                ->whereHas('centro', fn($q) => $q->whereNull('deleted_at'))
                ->orderBy('name_subcentro')
                ->get()
                ->map(fn($sc) => [
                    'id' => $sc->id,
                    'nombre' => $sc->name_subcentro,
                    'bodega_id' => $sc->centro_id,
                    'bodega_nombre' => $sc->centro->name_centro ?? null,
                ]);
                
            if ($subcentroSeleccionado) {
                $subcentro = Subcentro::with('centro')->find($subcentroSeleccionado);
                if ($subcentro) {
                    $inventario = InventarioBodega::where('subcentro_id', $subcentroSeleccionado)
                        ->with('producto')
                        ->orderBy('cantidad', 'desc')
                        ->get();
                    $subcentroActual = $subcentro;
                    $bodegaActual = $subcentro->centro;
                }
            } elseif ($bodegaSeleccionada) {
                $inventario = InventarioBodega::where('bodega_id', $bodegaSeleccionada)
                    ->whereNull('subcentro_id')
                    ->with('producto')
                    ->orderBy('cantidad', 'desc')
                    ->get();
                $bodegaActual = Centro::find($bodegaSeleccionada);
            } else {
                $inventario = collect();
                if ($todosLosSubcentros->isNotEmpty()) {
                    $primerSubcentroId = $todosLosSubcentros->first()['id'];
                    $subcentro = Subcentro::with('centro')->find($primerSubcentroId);
                    if ($subcentro) {
                        $inventario = InventarioBodega::where('subcentro_id', $primerSubcentroId)
                            ->with('producto')
                            ->orderBy('cantidad', 'desc')
                            ->get();
                        $subcentroActual = $subcentro;
                        $bodegaActual = $subcentro->centro;
                    }
                }
            }
        } elseif (!empty($misSubcentroIds)) {
            if ($subcentroSeleccionado && in_array($subcentroSeleccionado, $misSubcentroIds)) {
                $subcentro = Subcentro::with('centro')->find($subcentroSeleccionado);
                if ($subcentro) {
                    $inventario = InventarioBodega::where('subcentro_id', $subcentroSeleccionado)
                        ->with('producto')
                        ->orderBy('cantidad', 'desc')
                        ->get();
                    $subcentroActual = $subcentro;
                    $bodegaActual = $subcentro->centro;
                }
            } else {
                $miSubcentroId = $misSubcentroIds[0];
                $subcentro = Subcentro::with('centro')->find($miSubcentroId);
                if ($subcentro) {
                    $inventario = InventarioBodega::where('subcentro_id', $miSubcentroId)
                        ->with('producto')
                        ->orderBy('cantidad', 'desc')
                        ->get();
                    $subcentroActual = $subcentro;
                    $bodegaActual = $subcentro->centro;
                } else {
                    $inventario = collect();
                }
            }
        } else {
            $inventario = collect();
        }

        $puedeModificar = !empty($misSubcentroIds);

        $productos = Producto::orderBy('name_produc')->get();
        
        // Mapear productos con cantidad en inventario actual
        $productosMap = [];
        $invMap = [];
        foreach ($inventario as $inv) {
            $invMap[$inv->producto_id] = $inv->cantidad;
        }
        
        foreach ($productos as $prod) {
            $productosMap[] = [
                'id' => $prod->id,
                'name_produc' => $prod->name_produc,
                'sku' => $prod->sku,
                'unit_produc' => $prod->unit_produc,
                'cantidad' => $invMap[$prod->id] ?? 0,
            ];
        }

        $todosLosSubcentros = collect($misSubcentros);

        return view('inventario.index', [
            'inventario' => $inventario,
            'centros' => $centros,
            'bodegaSeleccionada' => $bodegaSeleccionada,
            'bodegaActual' => $bodegaActual,
            'verTodas' => $verTodas,
            'puedeModificar' => $puedeModificar,
            'misSubcentros' => $misSubcentros,
            'productos' => $productos,
            'subcentroSeleccionado' => $subcentroSeleccionado,
            'subcentroActual' => $subcentroActual,
            'todosLosSubcentros' => $todosLosSubcentros,
            'productosMap' => $productosMap,
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $misBodegas = $this->getMisBodegas();
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Buscar en todos los productos (no solo en inventario)
        $productos = Producto::where('name_produc', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->limit(30)
            ->get();

        $resultados = $productos->map(fn($p) => [
            'id' => $p->id,
            'producto_id' => $p->id,
            'producto_nombre' => $p->name_produc ?? '',
            'producto_sku' => $p->sku ?? '',
            'producto_unidad' => $p->unit_produc ?? '',
            'bodega_id' => null,
            'bodega_nombre' => '',
            'cantidad' => 0,
        ]);

        return response()->json($resultados);
    }

    public function storeEntrada(Request $request): JsonResponse
    {
        // Renovar sesión primero
        if (!Session::has('api_token') || !Session::has('user')) {
            return response()->json(['message' => 'Sesión expirada'], 401);
        }
        
        // Mantener sesión activa primero
        Session::put('last_activity', time());
        
        $data = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'comentario' => 'nullable|string|max:500',
        ]);

        $misSubcentroIds = $this->getMisSubcentroIds();
        if (empty($misSubcentroIds)) {
            return response()->json(['message' => 'No tienes subcentros asignados'], 403);
        }

        $subcentroId = $misSubcentroIds[0];
        $subcentro = Subcentro::find($subcentroId);
        $bodegaId = $subcentro->centro_id;
        $userId = session('user.id') ?? (session('user')['id'] ?? null);

        if (!$userId) {
            return response()->json(['message' => 'Usuario no identificado'], 401);
        }
        
        // Verificar si el usuario existe en la tabla local, si no existe crear registro básico
        if (!\DB::table('users')->where('id', $userId)->exists()) {
            try {
                \DB::table('users')->insert([
                    'id' => $userId,
                    'name' => session('user.name') ?? session('user')['name'] ?? 'Usuario',
                    'email' => session('user.email') ?? session('user')['email'] ?? 'usuario@desconocido.com',
                    'password' => 'external_user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignorar error si usuario ya existe
            }
        }

        try {
            $inventario = InventarioBodega::firstOrCreate(
                ['bodega_id' => $bodegaId, 'producto_id' => $data['producto_id'], 'subcentro_id' => $subcentroId],
                ['cantidad' => 0]
            );

            $inventario->incrementar($data['cantidad'], $userId, $data['comentario'] ?? null);

            Session::save();

            return response()->json(['success' => true, 'message' => 'Entrada registrada']);
        } catch (\Throwable $e) {
            Log::error('Error storeEntrada inventario: ' . $e->getMessage());
            return response()->json(['message' => 'Error al registrar entrada'], 500);
        }
    }

    public function storeSalida(Request $request): JsonResponse
    {
        // Renovar sesión primero
        if (!Session::has('api_token') || !Session::has('user')) {
            return response()->json(['message' => 'Sesión expirada'], 401);
        }
        
        // Mantener sesión activa primero
        Session::put('last_activity', time());
        
        $data = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
            'comentario' => 'nullable|string|max:500',
        ]);

        $misSubcentroIds = $this->getMisSubcentroIds();
        if (empty($misSubcentroIds)) {
            return response()->json(['message' => 'No tienes subcentros asignados'], 403);
        }

        $subcentroId = $misSubcentroIds[0];
        $subcentro = Subcentro::find($subcentroId);
        $bodegaId = $subcentro->centro_id;
        $userId = session('user.id') ?? (session('user')['id'] ?? null);

        if (!$userId) {
            return response()->json(['message' => 'Usuario no identificado'], 401);
        }
        
        // Verificar si el usuario existe en la tabla local, si no existe crear registro básico
        if (!\DB::table('users')->where('id', $userId)->exists()) {
            try {
                \DB::table('users')->insert([
                    'id' => $userId,
                    'name' => session('user.name') ?? session('user')['name'] ?? 'Usuario',
                    'email' => session('user.email') ?? session('user')['email'] ?? 'usuario@desconocido.com',
                    'password' => 'external_user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignorar error si usuario ya existe
            }
        }

        try {
            $inventario = InventarioBodega::where('bodega_id', $bodegaId)
                ->where('producto_id', $data['producto_id'])
                ->where('subcentro_id', $subcentroId)
                ->first();

            if (!$inventario) {
                return response()->json(['message' => 'Producto no encontrado en inventario'], 404);
            }

            if ($inventario->cantidad < $data['cantidad']) {
                return response()->json([
                    'message' => 'Stock insuficiente',
                    'disponible' => $inventario->cantidad,
                ], 400);
            }

            $inventario->decrementar($data['cantidad'], $userId, $data['comentario'] ?? null);

            Session::save();

            return response()->json(['success' => true, 'message' => 'Salida registrada']);
        } catch (\Throwable $e) {
            Log::error('Error storeSalida inventario: ' . $e->getMessage());
            return response()->json(['message' => 'Error al registrar salida'], 500);
        }
    }

    public function editar(Request $request): JsonResponse
    {
        // Renovar sesión primero
        if (!Session::has('api_token') || !Session::has('user')) {
            return response()->json(['message' => 'Sesión expirada'], 401);
        }
        
        // Mantener sesión activa primero
        Session::put('last_activity', time());
        
        $data = $request->validate([
            'inventario_id' => 'required|integer|exists:inventario_bodega,id',
            'cantidad' => 'required|integer|min:0',
            'comentario' => 'nullable|string|max:500',
        ]);

        $misSubcentroIds = $this->getMisSubcentroIds();
        if (empty($misSubcentroIds)) {
            return response()->json(['message' => 'No tienes subcentros asignados'], 403);
        }

        $userId = session('user.id') ?? (session('user')['id'] ?? null);
        if (!$userId) {
            return response()->json(['message' => 'Usuario no identificado'], 401);
        }
        
        // Verificar si el usuario existe en la tabla local, si no existe crear registro básico
        if (!\DB::table('users')->where('id', $userId)->exists()) {
            try {
                \DB::table('users')->insert([
                    'id' => $userId,
                    'name' => session('user.name') ?? session('user')['name'] ?? 'Usuario',
                    'email' => session('user.email') ?? session('user')['email'] ?? 'usuario@desconocido.com',
                    'password' => 'external_user',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // Ignorar error si usuario ya existe
            }
        }

        try {
            $inventario = InventarioBodega::find($data['inventario_id']);
            if (!$inventario) {
                return response()->json(['message' => 'Producto no encontrado en inventario'], 404);
            }

            $diferencia = $data['cantidad'] - $inventario->cantidad;
            $comentario = $data['comentario'] ?? null;
            $tipoMovimiento = 'ajuste';
            
            if ($diferencia > 0) {
                $inventario->cantidad = $data['cantidad'];
                $inventario->save();
                InventarioMovimiento::create([
                    'inventario_bodega_id' => $inventario->id,
                    'user_id' => $userId,
                    'tipo' => 'entrada',
                    'cantidad' => $diferencia,
                    'comentario' => $comentario ?: 'Ajuste de inventario - Aumento',
                ]);
            } elseif ($diferencia < 0) {
                if ($inventario->cantidad < abs($diferencia)) {
                    return response()->json(['message' => 'Stock insuficiente para disminución'], 400);
                }
                $inventario->cantidad = $data['cantidad'];
                $inventario->save();
                InventarioMovimiento::create([
                    'inventario_bodega_id' => $inventario->id,
                    'user_id' => $userId,
                    'tipo' => 'salida',
                    'cantidad' => abs($diferencia),
                    'comentario' => $comentario ?: 'Ajuste de inventario - Disminución',
                ]);
            }

            Session::save();

            return response()->json(['success' => true, 'message' => 'Inventario actualizado']);
        } catch (\Throwable $e) {
            Log::error('Error editar inventario: ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar inventario'], 500);
        }
    }

    public function eliminar(Request $request): JsonResponse
    {
        // Renovar sesión primero
        if (!Session::has('api_token') || !Session::has('user')) {
            return response()->json(['message' => 'Sesión expirada'], 401);
        }
        
        // Mantener sesión activa primero
        Session::put('last_activity', time());
        
        $data = $request->validate([
            'inventario_id' => 'required|integer|exists:inventario_bodega,id',
        ]);

        $misSubcentroIds = $this->getMisSubcentroIds();
        if (empty($misSubcentroIds)) {
            return response()->json(['message' => 'No tienes subcentros asignados'], 403);
        }

        $userId = session('user.id') ?? (session('user')['id'] ?? null);
        
        if (!$userId) {
            return response()->json(['message' => 'Usuario no identificado'], 401);
        }

        try {
            $inventario = InventarioBodega::find($data['inventario_id']);
            if (!$inventario) {
                return response()->json(['message' => 'Producto no encontrado en inventario'], 404);
            }

            $cantidadEliminada = $inventario->cantidad;
            $comentario = 'Registro eliminado';
            
            // Verificar si el usuario existe en la tabla local
            $userExists = \DB::table('users')->where('id', $userId)->exists();
            
            if ($userExists) {
                InventarioMovimiento::create([
                    'inventario_bodega_id' => $inventario->id,
                    'user_id' => $userId,
                    'tipo' => 'eliminacion',
                    'cantidad' => $cantidadEliminada,
                    'comentario' => $comentario,
                ]);
            }

            $inventario->delete();

            Session::save();

            return response()->json(['success' => true, 'message' => 'Producto eliminado del inventario']);
        } catch (\Throwable $e) {
            Log::error('Error eliminar inventario: ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar: ' . $e->getMessage()], 500);
        }
    }

    public function historial(Request $request): View
    {
        $verTodas = $this->puedeVerTodas();
        $misSubcentroIds = $this->getMisSubcentroIds();
        $subcentroSeleccionado = $request->get('subcentro');

        $query = InventarioMovimiento::with('inventarioBodega.producto', 'inventarioBodega.bodega', 'inventarioBodega.subcentro', 'user')
            ->orderBy('inventario_movimiento.created_at', 'desc');

        if (!$verTodas && !empty($misSubcentroIds)) {
            $query->whereHas('inventarioBodega', function ($q) use ($misSubcentroIds) {
                $q->whereIn('subcentro_id', $misSubcentroIds);
            });
        } elseif ($verTodas && $subcentroSeleccionado) {
            $query->whereHas('inventarioBodega', function ($q) use ($subcentroSeleccionado) {
                $q->where('subcentro_id', $subcentroSeleccionado);
            });
        }

        $movimientos = $query->paginate(50);

        return view('inventario.historial', compact('movimientos', 'verTodas'));
    }

    public function exportar(Request $request)
    {
        $verTodas = $this->puedeVerTodas();
        $misSubcentroIds = $this->getMisSubcentroIds();
        $subcentroSeleccionado = $request->get('subcentro');
        $bodegaId = $request->get('bodega');

        if ($verTodas && $subcentroSeleccionado) {
            $subcentrosPermitidos = [(int)$subcentroSeleccionado];
        } elseif ($verTodas && $bodegaId) {
            $subcentrosPermitidos = [];
        } elseif (!empty($misSubcentroIds)) {
            $subcentrosPermitidos = $misSubcentroIds;
        } else {
            abort(403, 'No tienes permiso para exportar este inventario');
        }

        if (!empty($subcentrosPermitidos)) {
            $inventario = InventarioBodega::whereIn('subcentro_id', $subcentrosPermitidos)
                ->with('producto', 'bodega', 'subcentro')
                ->orderBy('cantidad', 'desc')
                ->get();

            $movimientos = InventarioMovimiento::whereHas('inventarioBodega', function ($q) use ($subcentrosPermitidos) {
                    $q->whereIn('subcentro_id', $subcentrosPermitidos);
                })
                ->with('inventarioBodega.producto', 'inventarioBodega.bodega', 'inventarioBodega.subcentro', 'user')
                ->orderBy('created_at', 'desc')
                ->limit(500)
                ->get();

            $nombre = $inventario->first()?->subcentro->name_subcentro ?? $inventario->first()?->bodega->name_centro ?? 'Inventario';
        } else {
            $inventario = InventarioBodega::where('bodega_id', $bodegaId)
                ->whereNull('subcentro_id')
                ->with('producto', 'bodega', 'subcentro')
                ->orderBy('cantidad', 'desc')
                ->get();

            $movimientos = InventarioMovimiento::whereHas('inventarioBodega', function ($q) use ($bodegaId) {
                    $q->where('bodega_id', $bodegaId)->whereNull('subcentro_id');
                })
                ->with('inventarioBodega.producto', 'inventarioBodega.bodega', 'inventarioBodega.subcentro', 'user')
                ->orderBy('created_at', 'desc')
                ->limit(500)
                ->get();

            $nombre = $inventario->first()?->bodega->name_centro ?? 'Inventario';
        }

        return Excel::download(new InventarioExport($inventario, $movimientos, $nombre), 'inventario_' . $nombre . '_' . date('Ymd_His') . '.xlsx');
    }
}