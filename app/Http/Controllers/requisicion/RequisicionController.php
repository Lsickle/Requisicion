<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\Centro;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use App\Models\Entrega;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Jobs\RequisicionCreadaJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema; // agregado

class RequisicionController extends Controller
{
    public function index()
    {
        $requisiciones = Requisicion::with('productos', 'estatusHistorial.estatusRelation')->get();
        return view('index', compact('requisiciones'));
    }

    public function create()
    {
        $centros = Centro::all();
        $productos = Producto::all();

        return view('requisiciones.create', compact('centros', 'productos'));
    }

    public function menu()
    {
        return view('requisiciones.menu');
    }

    public function edit($id)
    {
        $requisicion = Requisicion::with([
            'productos' => function ($query) use ($id) {
                $query->with(['centrosRequisicion' => function ($q) use ($id) {
                    $q->wherePivot('requisicion_id', $id);
                }]);
            },
            'estatusHistorial.estatusRelation'
        ])->findOrFail($id);

        // Verificar que la requisición está en estatus "Corregir"
        $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
        if ($ultimoEstatus != 11) { // 11 = Corregir
            return redirect()->route('requisiciones.historial')->with('error', 'Solo se pueden editar requisiciones en estatus "Corregir"');
        }

        // Obtener el comentario de rechazo
        $comentarioRechazo = Estatus_Requisicion::where('requisicion_id', $id)
            ->where('estatus_id', 11)
            ->where('estatus', 1)
            ->first()->comentario ?? '';

        $centros = Centro::all();
        $productos = Producto::all();

        return view('requisiciones.edit', compact('requisicion', 'centros', 'productos', 'comentarioRechazo'));
    }

    public function update(Request $request, $id)
    {
        $requisicion = Requisicion::findOrFail($id);

        // Verificar que la requisición está en estatus "Corregir"
        $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
        if ($ultimoEstatus != 11) {
            return redirect()->route('requisiciones.historial')->with('error', 'Solo se pueden editar requisiciones en estatus "Corregir"');
        }

        $validated = $request->validate([
            'Recobrable' => 'required|in:Recobrable,No recobrable',
            'prioridad_requisicion' => 'required|in:baja,media,alta',
            'justify_requisicion' => 'required|string|min:3|max:500',
            'detail_requisicion' => 'required|string|min:3|max:1000',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'productos.*.requisicion_amount' => 'required|integer|min:1',
            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();

        try {
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int)$prod['requisicion_amount'];
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int)$prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']})."
                    ]);
                }
            }

            // Actualizar la requisición
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            // Eliminar productos y centros anteriores
            $requisicion->productos()->detach();
            DB::table('centro_producto')->where('requisicion_id', $requisicion->id)->delete();

            // Agregar nuevos productos y centros
            foreach ($validated['productos'] as $prod) {
                $cantidadTotalCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros
                ]);

                foreach ($prod['centros'] as $centro) {
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id'   => $centro['id'],
                        'requisicion_id' => $requisicion->id,
                        'amount'      => $centro['cantidad'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            // Cambiar estatus a "Iniciada" para reenviar a aprobación
            Estatus_Requisicion::where('requisicion_id', $requisicion->id)->update(['estatus' => 0]);

            $estatusIniciada = Estatus::where('status_name', 'Requisición creada')->first();
            if ($estatusIniciada) {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id'     => $estatusIniciada->id,
                    'estatus'        => 1,
                    'date_update'    => now(),
                ]);
            }

            DB::commit();

            return redirect()->route('requisiciones.historial')->with('success', 'Requisición corregida y enviada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al actualizar la requisición: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'Recobrable' => 'required|in:Recobrable,No recobrable',
            'prioridad_requisicion' => 'required|in:baja,media,alta',
            'justify_requisicion' => 'required|string|min:3|max:500',
            'detail_requisicion' => 'required|string|min:3|max:1000',

            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'productos.*.requisicion_amount' => 'required|integer|min:1',

            'productos.*.centros' => 'required|array|min:1',
            'productos.*.centros.*.id' => 'required|exists:centro,id',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();

        try {
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int)$prod['requisicion_amount'];
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int)$prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']})."
                    ]);
                }
            }

            // Obtener información del usuario desde la SESIÓN
            $userId = session('user.id');
            $nombreUsuario = session('user.name', 'Usuario Desconocido');
            $emailUsuario = session('user.email', 'email@desconocido.com');
            $operacionUsuario = session('user.operaciones', 'Operación no definida');

            $requisicion = new Requisicion();
            $requisicion->user_id = $userId;
            $requisicion->name_user = $nombreUsuario; // Guardar el nombre
            $requisicion->email_user = $emailUsuario; // Guardar el email
            $requisicion->operacion_user = $operacionUsuario; // Guardar la operación
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            // 🔹 Asignar estatus inicial "Iniciada"
            $estatusInicial = Estatus::where('status_name', 'Requisición creada')->first();
            if ($estatusInicial) {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id'     => $estatusInicial->id,
                    'estatus'        => 1, // activo
                    'date_update'    => now(),
                ]);
            }

            foreach ($validated['productos'] as $prod) {
                $cantidadTotalCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros
                ]);

                foreach ($prod['centros'] as $centro) {
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id'   => $centro['id'],
                        'requisicion_id' => $requisicion->id,
                        'amount'      => $centro['cantidad'],
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }

            DB::commit();

            RequisicionCreadaJob::dispatch($requisicion, $nombreUsuario);

            return redirect()->route('requisiciones.create')->with('success', 'Requisición creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear la requisición: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centros',
            'estatusHistorial.estatusRelation'
        ])->findOrFail($id);

        return view('requisiciones.show', compact('requisicion'));
    }

    public function pdf($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centros',
            'estatusHistorial.estatusRelation'
        ])->findOrFail($id);

        // Usar los datos guardados en la requisición en lugar de hacer una nueva consulta
        $nombreSolicitante = $requisicion->name_user;
        $emailSolicitante = $requisicion->email_user;
        $operacionSolicitante = $requisicion->operacion_user;

        // Preparar logo como data URI para que DomPDF lo muestre sin depender de URLs remotas
        $logoData = null;
        $candidates = [
            public_path('images/logo.png'),
            public_path('images/logo.jpg'),
            public_path('images/logo.jpeg'),
            public_path('logo_empresa.png'),
            public_path('images/logo_empresa.png'),
        ];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $contents = file_get_contents($path);
                $mime = function_exists('mime_content_type') ? mime_content_type($path) : null;
                if (empty($mime)) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                }
                $logoData = 'data:' . $mime . ';base64,' . base64_encode($contents);
                break;
            }
        }

        $pdf = Pdf::loadView('requisiciones.pdf', [
            'requisicion' => $requisicion,
            'nombreSolicitante' => $nombreSolicitante,
            'emailSolicitante' => $emailSolicitante,
            'operacionSolicitante' => $operacionSolicitante,
            'logo' => $logoData ?? asset('images/logo.png'),
        ])->setPaper('A4', 'portrait');

        return $pdf->download("requisicion_{$requisicion->id}.pdf");
    }

    public function historial()
    {
        $userId = session('user.id');

        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation'
        ])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Debug: Verificar qué datos se están obteniendo
        Log::info('Requisiciones con estatus:', [
            'count' => $requisiciones->count(),
            'data' => $requisiciones->map(function ($req) {
                return [
                    'id' => $req->id,
                    'ultimoEstatus' => $req->ultimoEstatus ? [
                        'id' => $req->ultimoEstatus->id,
                        'estatus_id' => $req->ultimoEstatus->estatus_id,
                        'estatus_name' => $req->ultimoEstatus->estatusRelation->status_name ?? null
                    ] : null
                ];
            })
        ]);

        return view('requisiciones.historial', compact('requisiciones'));
    }

    public function listaAprobadas()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation'
        ])
            ->whereHas('ultimoEstatus', function ($query) {
                $query->whereIn('estatus_id', [4, 5, 7, 8, 12]); // incluir 4,5,7,8,12
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.lista', compact('requisiciones'));
    }

    public function cancelar($id)
    {
        DB::beginTransaction();

        try {
            $requisicion = Requisicion::findOrFail($id);

            // Verificar que el usuario es el propietario de la requisición
            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cancelar esta requisición'
                ], 403);
            }

            // Verificar que no esté ya cancelada o en un estatus final
            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if (in_array($ultimoEstatus, [6, 9, 10, 5])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una requisición en su estado actual'
                ], 400);
            }

            // Desactivar el estatus actual
            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            // Crear nuevo registro de estatus "Cancelada" (6)
            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id'     => 6, // Cancelada
                'estatus'        => 1, // activo
                'date_update'    => now(),
                'comentario'     => 'Cancelada por el solicitante'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Requisición cancelada correctamente'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error cancelando requisición {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la requisición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reenviar una requisición cancelada (cambiar estatus a 1)
     */
    public function reenviar($id)
    {
        DB::beginTransaction();

        try {
            $requisicion = Requisicion::findOrFail($id);

            // Verificar que el usuario es el propietario de la requisición
            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para reenviar esta requisición'
                ], 403);
            }

            // Verificar que esté cancelada (estatus 6)
            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if ($ultimoEstatus != 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden reenviar requisiciones canceladas'
                ], 400);
            }

            // Desactivar el estatus actual (Cancelada)
            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            // Crear nuevo registro de estatus "Iniciada" (1)
            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id'     => 1, // Iniciada
                'estatus'        => 1, // activo
                'date_update'    => now(),
                'comentario'     => 'Reenviada por el solicitante'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Requisición reenviada correctamente'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error reenviando requisición {$id}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar la requisición: ' . $e->getMessage()
            ], 500);
        }
    }

    public function todas()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation'
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('requisiciones.todas', compact('requisiciones'));
    }

    // Agregar este método al controlador
    public function entregarRequisicion(Request $request, $requisicionId)
    {
        try {
            DB::beginTransaction();

            $requisicion = Requisicion::findOrFail($requisicionId);
            $items = $request->input('items', []);

            if (empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos seleccionados para entregar'
                ], 400);
            }

            // obtener usuario que registra la entrega desde session
            $userIdSession = session('user.id') ?? null;
            $userNameSession = session('user.name') ?? session('user.email') ?? null;

            foreach ($items as $item) {
                $productoId = $item['producto_id'] ?? null;
                $cantidad = (int)($item['cantidad'] ?? 0);

                if (!$productoId || $cantidad <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Datos de producto inválidos'], 400);
                }

                // Obtener cantidad total solicitada para este producto en la requisición
                $totalReq = (int) DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicionId)
                    ->where('id_producto', $productoId)
                    ->value('pr_amount');

                // Si existe distribución por centros, usar la suma de centro_producto.amount
                $sumaCentros = (int) DB::table('centro_producto')
                    ->where('requisicion_id', $requisicionId)
                    ->where('producto_id', $productoId)
                    ->sum('amount');

                if ($sumaCentros > 0) $totalReq = $sumaCentros;

                // Cantidad ya registrada en entregas: contar entregas confirmadas (cantidad_recibido)
                // y entregas pendientes (cantidad) usando la misma lógica que la UI.
                $registrado = (int) (DB::table('entrega')
                    ->where('requisicion_id', $requisicionId)
                    ->where('producto_id', $productoId)
                    ->whereNull('deleted_at')
                    ->select(DB::raw('COALESCE(SUM(COALESCE(cantidad_recibido, cantidad, 0)),0) as total'))
                    ->value('total') ?? 0);

                $pendiente = max(0, $totalReq - $registrado);

                // Log para depuración
                Log::info('Entrega validación', [
                    'requisicion_id' => $requisicionId,
                    'producto_id' => $productoId,
                    'totalReq' => $totalReq,
                    'registrado' => $registrado,
                    'pendiente' => $pendiente,
                    'cantidad_enviada' => $cantidad,
                ]);

                if ($cantidad > $pendiente) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cantidad a entregar ({$cantidad}) excede el pendiente ({$pendiente}) para el producto {$productoId}.",
                        'debug' => [
                            'totalReq' => $totalReq,
                            'registrado' => $registrado,
                            'pendiente' => $pendiente,
                        ]
                    ], 400);
                }

                // Registrar entrega (pendiente de confirmación en cantidad_recibido)
                // Guardar únicamente quien entrega (user_id, user_name).
                // No aceptar ni almacenar reception_user desde la petición: debe guardarse al confirmar la recepción.
                DB::table('entrega')->insert([
                    'requisicion_id' => $requisicionId,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'cantidad_recibido' => null,
                    'fecha' => now()->toDateString(),
                    // usuario que entrega (desde sesión)
                    'user_id' => $userIdSession,
                    'user_name' => $userNameSession,
                    // reception_* dejar en NULL hasta que se confirme la recepción
                    'reception_user_id' => null,
                    'reception_user' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Desactivar estatus previo y crear estatus 8
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);
            Estatus_Requisicion::create([
                'requisicion_id' => $requisicionId,
                'estatus_id' => 8,
                'estatus' => 1,
                'comentario' => 'Entrega registrada desde módulo de requisiciones',
                'user_id' => session('user.id'),
                'date_update' => now(),
            ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Entrega registrada correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al registrar la entrega: ' . $e->getMessage()], 500);
        }
    }

    public function confirmarEntrega(Request $request)
    {
        $entregaId = $request->input('entrega_id') ?? $request->input('id');
        $cantidad = (int) $request->input('cantidad', 0);

        if (!$entregaId || $cantidad < 0) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            $entrega = DB::table('entrega')->where('id', $entregaId)->whereNull('deleted_at')->first();
            if (!$entrega) return response()->json(['success' => false, 'message' => 'Registro de entrega no encontrado'], 404);

            $max = (int) $entrega->cantidad;
            $cantidad = min($cantidad, $max);

            // Obtener receptor desde sesión (varias keys posibles)
            $sessionUser = session('user') ?? [];
            // Preferir sesión; si no está disponible usar payload (por ejemplo AJAX sin cookies)
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? session('user_id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? $request->input('receptor_user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_name') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? $request->input('recepcion_user') ?? null;

            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int)$receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

            // Preparar datos a actualizar: cantidad recibida y solo columnas de receptor (no tocar quien entrega)
            $updateData = [];
            if (Schema::hasColumn('entrega', 'cantidad_recibido')) {
                $updateData['cantidad_recibido'] = $cantidad;
            } elseif (Schema::hasColumn('entrega', 'cantidad_recibida')) {
                $updateData['cantidad_recibida'] = $cantidad;
            }

            // columnas receptoras posibles
            $nameCols = ['reception_user', 'recepcion_user', 'receptor_user'];
            $idCols = ['reception_user_id', 'recepcion_user_id', 'receptor_user_id'];

            foreach ($nameCols as $col) {
                if (Schema::hasColumn('entrega', $col) && $receiverName !== null) {
                    $updateData[$col] = $receiverName;
                }
            }
            foreach ($idCols as $col) {
                if (Schema::hasColumn('entrega', $col) && $receiverId !== null) {
                    $updateData[$col] = $receiverId;
                }
            }

            if (empty($updateData)) {
                Log::warning('confirmarEntrega: nothing to update for entrega_id ' . $entregaId);
                return response()->json(['success' => false, 'message' => 'No hay columnas para actualizar'], 500);
            }

            $updateData['updated_at'] = now();

            Log::info('confirmarEntrega payload/session', [
                'entrega_id' => $entregaId,
                'cantidad' => $cantidad,
                'receiver_id' => $receiverId,
                'receiver_name' => $receiverName,
                'updateData_keys' => array_keys($updateData),
                'request' => $request->all()
            ]);

            $affected = DB::table('entrega')->where('id', $entregaId)->update($updateData);

            Log::info('confirmarEntrega updated rows', ['entrega_id' => $entregaId, 'affected' => $affected, 'updateData' => $updateData]);

            return response()->json(['success' => true, 'message' => 'Entrega confirmada', 'affected' => $affected]);
        } catch (\Exception $e) {
            Log::error('Error confirmarEntrega', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error al confirmar entrega'], 500);
        }
    }

    public function confirmarRecepcion(Request $request)
    {
        $recepcionId = $request->input('recepcion_id') ?? $request->input('id');
        $cantidad = (int) $request->input('cantidad', 0);

        if (!$recepcionId || $cantidad < 0) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            $rec = DB::table('recepcion')->where('id', $recepcionId)->whereNull('deleted_at')->first();
            if (!$rec) return response()->json(['success' => false, 'message' => 'Registro de recepción no encontrado'], 404);

            $max = (int) $rec->cantidad;
            $cantidad = min($cantidad, $max);

            // Obtener receptor desde sesión (varias keys posibles)
            $sessionUser = session('user') ?? [];
            // Preferir sesión; si no está disponible usar payload (por ejemplo AJAX sin cookies)
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? session('user_id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? $request->input('receptor_user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_name') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? $request->input('recepcion_user') ?? null;

            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int)$receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

            // Preparar datos a actualizar: cantidad recibida y solo columnas de receptor
            $updateData = [];
            if (Schema::hasColumn('recepcion', 'cantidad_recibida')) {
                $updateData['cantidad_recibida'] = $cantidad;
            } elseif (Schema::hasColumn('recepcion', 'cantidad_recibido')) {
                $updateData['cantidad_recibido'] = $cantidad;
            }

            $nameCols = ['reception_user', 'recepcion_user', 'receptor_user'];
            $idCols = ['reception_user_id', 'recepcion_user_id', 'receptor_user_id'];

            foreach ($nameCols as $col) {
                if (Schema::hasColumn('recepcion', $col) && $receiverName !== null) {
                    $updateData[$col] = $receiverName;
                }
            }
            foreach ($idCols as $col) {
                if (Schema::hasColumn('recepcion', $col) && $receiverId !== null) {
                    $updateData[$col] = $receiverId;
                }
            }

            if (empty($updateData)) {
                Log::warning('confirmarRecepcion: nothing to update for recepcion_id ' . $recepcionId);
                return response()->json(['success' => false, 'message' => 'No hay columnas para actualizar'], 500);
            }

            $updateData['updated_at'] = now();

            Log::info('confirmarRecepcion payload/session', [
                'recepcion_id' => $recepcionId,
                'cantidad' => $cantidad,
                'receiver_id' => $receiverId,
                'receiver_name' => $receiverName,
                'updateData_keys' => array_keys($updateData),
                'request' => $request->all()
            ]);

            $affected = DB::table('recepcion')->where('id', $recepcionId)->update($updateData);

            Log::info('confirmarRecepcion updated rows', ['recepcion_id' => $recepcionId, 'affected' => $affected, 'updateData' => $updateData]);

            return response()->json(['success' => true, 'message' => 'Recepción confirmada', 'affected' => $affected]);
        } catch (\Exception $e) {
            Log::error('Error confirmarRecepcion', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error al confirmar recepción'], 500);
        }
    }

    /**
     * Confirmar varias recepciones/entregas en una sola petición.
     * Espera: { tipo: 'entrega'|'recepcion', items: [{id,cantidad}, ...] }
     */
    public function confirmarRecepcionesMasivo(Request $request)
    {
        $tipo = $request->input('tipo', 'recepcion');
        $items = $request->input('items', []);

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'No hay items para procesar'], 400);
        }

        // Obtener receptor preferentemente desde sesión
        $sessionUser = session('user') ?? [];
        $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? null;
        $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? data_get($sessionUser, 'email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? null;

        $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int)$receiverId : $receiverId) : null;
        $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

        $table = $tipo === 'entrega' ? 'entrega' : 'recepcion';
        $qtyCols = ($table === 'entrega') ? ['cantidad_recibido','cantidad_recibida'] : ['cantidad_recibida','cantidad_recibido'];
        $nameCols = ['reception_user', 'recepcion_user', 'receptor_user'];
        $idCols = ['reception_user_id', 'recepcion_user_id', 'receptor_user_id'];

        $results = [];

        DB::beginTransaction();
        try {
            foreach ($items as $it) {
                $id = isset($it['id']) ? (int)$it['id'] : null;
                $cantidad = isset($it['cantidad']) ? (int)$it['cantidad'] : 0;
                if (!$id || $cantidad < 0) {
                    $results[] = ['id' => $id, 'success' => false, 'message' => 'id o cantidad inválidos'];
                    continue;
                }

                // Construir update
                $update = [];
                foreach ($qtyCols as $c) {
                    if (Schema::hasColumn($table, $c)) { $update[$c] = $cantidad; break; }
                }

                foreach ($nameCols as $c) {
                    if (Schema::hasColumn($table, $c) && $receiverName !== null) $update[$c] = $receiverName;
                }
                foreach ($idCols as $c) {
                    if (Schema::hasColumn($table, $c) && $receiverId !== null) $update[$c] = $receiverId;
                }

                if (empty($update)) {
                    $results[] = ['id' => $id, 'success' => false, 'message' => 'No hay columnas para actualizar'];
                    continue;
                }

                $update['updated_at'] = now();
                $affected = DB::table($table)->where('id', $id)->update($update);

                $results[] = ['id' => $id, 'success' => true, 'affected' => $affected, 'update' => $update];
            }

            DB::commit();

            Log::info('confirmarRecepcionesMasivo executed', ['tipo' => $tipo, 'receiver_id' => $receiverId, 'receiver_name' => $receiverName, 'results' => $results, 'request' => $request->all()]);

            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirmarRecepcionesMasivo', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'request' => $request->all()]);
            return response()->json(['success' => false, 'message' => 'Error procesando recepciones masivas'], 500);
        }
    }

    // Método para actualizar el estatus de una requisición
    private function setRequisicionStatus(int $requisicionId, int $estatusId, string $comentario = null)
    {
        // Si el estatus activo ya es el mismo, no hacer nada
        $current = Estatus_Requisicion::where('requisicion_id', $requisicionId)
            ->where('estatus', 1)
            ->orderBy('date_update', 'desc')
            ->first();

        if ($current && (int)$current->estatus_id === (int)$estatusId) {
            return;
        }

        // Desactivar estatus previos y crear nuevo registro para histórico
        Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

        Estatus_Requisicion::create([
            'requisicion_id' => $requisicionId,
            'estatus_id'     => $estatusId,
            'estatus'        => 1,
            'date_update'    => now(),
            'comentario'     => $comentario,
            'user_id'        => session('user.id'),
        ]);
    }

    // Verificar si una requisición está completa (todas las entregas registradas y confirmadas)
    private function isRequisitionComplete(int $requisicionId): bool
    {
        // Obtener todos los productos de la requisición
        $productos = DB::table('producto_requisicion')
            ->where('id_requisicion', $requisicionId)
            ->pluck('id_producto');

        foreach ($productos as $productoId) {
            // Verificar si hay entregas pendientes de confirmación
            $pendientes = DB::table('entrega')
                ->where('requisicion_id', $requisicionId)
                ->where('producto_id', $productoId)
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('cantidad_recibido')
                        ->orWhere('cantidad_recibido', '<', DB::raw('cantidad'));
                })
                ->exists();

            if ($pendientes) {
                return false;
            }
        }

        return true;
    }
}
