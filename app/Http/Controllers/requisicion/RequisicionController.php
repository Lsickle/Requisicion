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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

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

        $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
        if ($ultimoEstatus != 11) {
            return redirect()->route('requisiciones.historial')->with('error', 'Solo se pueden editar requisiciones en estatus "Corregir"');
        }

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

            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            $requisicion->productos()->detach();
            DB::table('centro_producto')->where('requisicion_id', $requisicion->id)->delete();

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
            'operacion_user' => 'required|string|max:255',
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
            'operacion_user.required' => 'Debe seleccionar una operación.',
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

            $userId = session('user.id');
            $nombreUsuario = session('user.name', 'Usuario Desconocido');
            $emailUsuario = session('user.email', 'email@desconocido.com');
            // Obtener operación desde el formulario (ya validada) en lugar de la sesión
            $operacionUsuario = trim($validated['operacion_user']);

            $requisicion = new Requisicion();
            $requisicion->user_id = $userId;
            $requisicion->name_user = $nombreUsuario;
            $requisicion->email_user = $emailUsuario;
            $requisicion->operacion_user = $operacionUsuario;
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->save();

            $estatusInicial = Estatus::where('status_name', 'Requisición creada')->first();
            if ($estatusInicial) {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id'     => $estatusInicial->id,
                    'estatus'        => 1,
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

        $nombreSolicitante = $requisicion->name_user;
        $emailSolicitante = $requisicion->email_user;
        $operacionSolicitante = $requisicion->operacion_user;

        $logoData = null;
        $candidates = [public_path('images/VigiaLogoC.svg')];
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
            'logo' => $logoData ?? asset('images/VigiaLogoC.svg'),
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
                $query->whereIn('estatus_id', [4, 5, 7, 8, 12]);
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

            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cancelar esta requisición'
                ], 403);
            }

            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if (in_array($ultimoEstatus, [6, 9, 10, 5])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una requisición en su estado actual'
                ], 400);
            }

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id'     => 6,
                'estatus'        => 1,
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

    public function reenviar($id)
    {
        DB::beginTransaction();

        try {
            $requisicion = Requisicion::findOrFail($id);

            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para reenviar esta requisición'
                ], 403);
            }

            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if ($ultimoEstatus != 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden reenviar requisiciones canceladas'
                ], 400);
            }

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id'     => 1,
                'estatus'        => 1,
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

    // Mostrar interfaz de transferencia (solo Admin requisicion)
    public function transferIndex()
    {
        // Permiso explícito o rol 'Admin requisicion' permiten acceder
        $permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
        $hasTransferPerm = in_array(mb_strtolower('transferir titularidad', 'UTF-8'), $permissions, true);

        // Fallback: detectar rol Admin requisicion en session.user.roles o user_roles
        $rolesRaw = Session::get('user_roles') ?: (session('user.roles') ?? []);
        $rolesNormalized = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) { $rolesNormalized[] = $r; continue; }
                if (is_array($r) && isset($r['roles'])) { $rolesNormalized[] = $r['roles']; continue; }
                if (is_object($r) && isset($r->roles)) { $rolesNormalized[] = $r->roles; continue; }
            }
        }
        $singleRole = session('user.role') ?? Session::get('user.role') ?? null;
        $isAdmin = in_array('Admin requisicion', $rolesNormalized, true) || ($singleRole === 'Admin requisicion');
        Log::info('transferIndex permission check', ['hasTransferPerm' => $hasTransferPerm, 'roles_normalized' => $rolesNormalized, 'singleRole' => $singleRole, 'isAdmin' => $isAdmin]);
        if (!$hasTransferPerm && !$isAdmin) {
            Log::warning('Acceso denegado a transferIndex', ['session_user' => session('user'), 'user_permissions' => Session::get('user_permissions'), 'user_roles' => Session::get('user_roles')]);
            return redirect()->route('requisiciones.menu')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        $requisiciones = Requisicion::with(['productos', 'estatusHistorial.estatusRelation'])->orderBy('created_at', 'desc')->get();
        return view('requisiciones.transferir', compact('requisiciones'));
    }

    // Ejecutar transferencia de titularidad
    public function transferir(Request $request, $id)
    {
        // Permiso explícito o rol 'Admin requisicion' para ejecución
        $permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
        $hasTransferPerm = in_array(mb_strtolower('transferir titularidad', 'UTF-8'), $permissions, true);

        $rolesRaw = Session::get('user_roles') ?: (session('user.roles') ?? []);
        $rolesNormalized = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) { $rolesNormalized[] = $r; continue; }
                if (is_array($r) && isset($r['roles'])) { $rolesNormalized[] = $r['roles']; continue; }
                if (is_object($r) && isset($r->roles)) { $rolesNormalized[] = $r->roles; continue; }
            }
        }
        $singleRole = session('user.role') ?? Session::get('user.role') ?? null;
        $isAdmin = in_array('Admin requisicion', $rolesNormalized, true) || ($singleRole === 'Admin requisicion');
        Log::info('transferir permission check', ['hasTransferPerm' => $hasTransferPerm, 'roles_normalized' => $rolesNormalized, 'singleRole' => $singleRole, 'isAdmin' => $isAdmin]);
        if (!$hasTransferPerm && !$isAdmin) {
            Log::warning('Acceso denegado a transferir', ['session_user' => session('user'), 'user_permissions' => Session::get('user_permissions'), 'user_roles' => Session::get('user_roles')]);
            return response()->json(['success' => false, 'message' => 'No tienes permisos'], 403);
        }

        // Obtener datos directamente de los campos hidden
        $providedUserId = $request->input('new_user_id');
        $providedName = $request->input('name_user');
        $providedEmail = $request->input('email_user');
        $providedOperacion = $request->input('operacion_user');

        // Validación más estricta
        if (empty($providedUserId) || empty($providedName)) {
            $msg = 'Datos de usuario destino incompletos. Se requiere user_id y name_user.';
            Log::warning('transferir: datos incompletos', [
                'user_id' => $providedUserId, 
                'name_user' => $providedName,
                'email' => $providedEmail,
                'operacion' => $providedOperacion,
                'request_all' => $request->all()
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return redirect()->back()->withInput()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            $requisicion = Requisicion::findOrFail($id);
            $oldOwner = $requisicion->user_id;
            $oldOwnerName = $requisicion->name_user;

            // Reescribir los campos de la requisición
            $requisicion->user_id = $providedUserId;
            $requisicion->name_user = $providedName;
            $requisicion->email_user = $providedEmail ?? $requisicion->email_user;
            $requisicion->operacion_user = $providedOperacion ?? $requisicion->operacion_user;
            $requisicion->save();

            // Registrar en historial
            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id' => $requisicion->ultimoEstatus->estatus_id ?? 1,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => "Titularidad transferida de usuario {$oldOwner} ({$oldOwnerName}) a {$providedUserId} ({$providedName}) por " . (session('user.name') ?? session('user.id') ?? 'Sistema'),
            ]);

            DB::commit();
            
            $successMsg = 'Titularidad transferida correctamente';
            Log::info('Transferencia exitosa', [
                'requisicion_id' => $id,
                'old_owner' => $oldOwner,
                'old_owner_name' => $oldOwnerName,
                'new_owner' => $providedUserId,
                'new_name' => $providedName
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }
            return redirect()->back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferir titularidad: ' . $e->getMessage());
            $errMsg = 'Error al transferir titularidad: ' . $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 500);
            }
            return redirect()->back()->withInput()->with('error', $errMsg);
        }
    }

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

            $userIdSession = session('user.id') ?? null;
            $userNameSession = session('user.name') ?? session('user.email') ?? null;

            $entregaIds = [];
            foreach ($items as $item) {
                $productoId = $item['producto_id'] ?? null;
                $cantidad = (int)($item['cantidad'] ?? 0);

                if (!$productoId || $cantidad <= 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Datos de producto inválidos'], 400);
                }

                $totalReq = (int) DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicionId)
                    ->where('id_producto', $productoId)
                    ->value('pr_amount');

                $sumaCentros = (int) DB::table('centro_producto')
                    ->where('requisicion_id', $requisicionId)
                    ->where('producto_id', $productoId)
                    ->sum('amount');

                if ($sumaCentros > 0) $totalReq = $sumaCentros;

                $registrado = (int) (DB::table('entrega')
                    ->where('requisicion_id', $requisicionId)
                    ->where('producto_id', $productoId)
                    ->whereNull('deleted_at')
                    ->select(DB::raw('COALESCE(SUM(COALESCE(cantidad_recibido, cantidad, 0)),0) as total'))
                    ->value('total') ?? 0);

                $pendiente = max(0, $totalReq - $registrado);

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

                $insertId = DB::table('entrega')->insertGetId([
                    'requisicion_id' => $requisicionId,
                    'producto_id' => $productoId,
                    'cantidad' => $cantidad,
                    'cantidad_recibido' => null,
                    'fecha' => now()->toDateString(),
                    'user_id' => $userIdSession,
                    'user_name' => $userNameSession,
                    'reception_user_id' => null,
                    'reception_user' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $entregaIds[] = $insertId;
            }

            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);
            $entregaRef = !empty($entregaIds) ? $entregaIds[0] : null;
            Estatus_Requisicion::create([
                'requisicion_id' => $requisicionId,
                'estatus_id' => 12,
                'estatus' => 1,
                'comentario' => 'Entrega registrada desde módulo de requisiciones',
                'entrega_id' => $entregaRef,
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

            $sessionUser = session('user') ?? [];
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? session('user_id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? $request->input('receptor_user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_email') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? $request->input('recepcion_user') ?? null;

            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int)$receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

            $updateData = [];
            if (Schema::hasColumn('entrega', 'cantidad_recibido')) {
                $updateData['cantidad_recibido'] = $cantidad;
            } elseif (Schema::hasColumn('entrega', 'cantidad_recibida')) {
                $updateData['cantidad_recibida'] = $cantidad;
            }

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

            $sessionUser = session('user') ?? [];
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? session('user_id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? $request->input('receptor_user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_name') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? $request->input('recepcion_user') ?? null;

            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int)$receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

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

    public function confirmarRecepcionesMasivo(Request $request)
    {
        $tipo = $request->input('tipo', 'recepcion');
        $items = $request->input('items', []);

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'No hay items para procesar'], 400);
        }

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

    private function setRequisicionStatus(int $requisicionId, int $estatusId, string $comentario = null)
    {
        $current = Estatus_Requisicion::where('requisicion_id', $requisicionId)
            ->where('estatus', 1)
            ->orderBy('date_update', 'desc')
            ->first();

        if ($current && (int)$current->estatus_id === (int)$estatusId) {
            return;
        }

        Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

        Estatus_Requisicion::create([
            'requisicion_id' => $requisicionId,
            'estatus_id'     => $estatusId,
            'estatus'        => 1,
            'date_update'    => now(),
            'comentario'     => $comentario,
        ]);
    }

    private function isRequisitionComplete(int $requisicionId): bool
    {
        $productos = DB::table('producto_requisicion')
            ->where('id_requisicion', $requisicionId)
            ->pluck('id_producto');

        foreach ($productos as $productoId) {
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

    /**
     * Obtener usuarios desde servicio VPL_CORE /api/usuarios y devolverlos al cliente (proxy)
     */
    public function fetchExternalUsers(Request $request)
    {
        $base = rtrim(env('VPL_CORE', ''), '\\/');
        if (empty($base)) {
            return response()->json(['ok' => false, 'message' => 'VPL_CORE no configurado'], 500);
        }

        $url = $base . '/api/usuarios';

        try {
            $token = session('api_token') ?? null;

            $client = Http::withOptions([
                'force_ip_resolve' => 'v4',
                'connect_timeout' => 10,
                'timeout' => 60,
            ])->withoutVerifying();

            if ($token) {
                $client = $client->withToken($token);
            }

            // Modo inspección para ver headers y body de la primera respuesta
            if ($request->query('inspect')) {
                $respInspect = $client->get($url, ['per_page' => 10, 'page' => 1]);
                return response()->json([
                    'status' => $respInspect->status(),
                    'headers' => $respInspect->headers(),
                    'body_raw' => $respInspect->body(),
                    'body_json' => $respInspect->json(),
                ]);
            }

            $perPage = 200;
            $usersRaw = [];
            $usedIds = [];

            $nextUrl = null;
            $page = 1;
            $iterations = 0;
            $maxIterations = 200;

            do {
                if ($nextUrl) {
                    $resp = $client->get($nextUrl);
                } else {
                    $resp = $client->get($url, ['per_page' => $perPage, 'page' => $page]);
                }

                if (!$resp->ok()) {
                    Log::warning('fetchExternalUsers page request failed', ['page' => $page, 'nextUrl' => $nextUrl, 'status' => $resp->status(), 'body' => $resp->body()]);
                    if ($iterations === 0) {
                        $msg = $resp->status() === 401 ? 'No autorizado al consultar servicio externo (token inválido).' : 'Error al consultar servicio externo';
                        return response()->json(['ok' => false, 'message' => $msg, 'status' => $resp->status()], 502);
                    }
                    break;
                }

                $payload = $resp->json();
                $pageUsers = [];

                // extraer usuarios (varios formatos)
                if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
                    $pageUsers = $payload['data'];
                } elseif (is_array($payload) && isset($payload['usuarios']) && is_array($payload['usuarios'])) {
                    $pageUsers = $payload['usuarios'];
                } elseif (is_array($payload) && isset($payload['users']) && is_array($payload['users'])) {
                    $pageUsers = $payload['users'];
                } elseif (is_array($payload) && array_values($payload) === $payload) {
                    $pageUsers = $payload;
                } elseif (isset($payload['user']) && (is_array($payload['user']) || is_object($payload['user']))) {
                    $u = $payload['user'];
                    $pageUsers = [is_object($u) ? (array)$u : $u];
                }

                if (!is_array($pageUsers)) $pageUsers = [];

                $newAdded = 0;
                foreach ($pageUsers as $u) {
                    $id = $u['id'] ?? $u['user_id'] ?? $u['usuario_id'] ?? null;
                    if (!$id) continue;
                    if (isset($usedIds[$id])) continue;
                    $usedIds[$id] = true;
                    $usersRaw[] = $u;
                    $newAdded++;
                }

                // Determinar siguiente página
                $nextUrl = null;
                $linkHeader = $resp->header('Link');
                if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/i', $linkHeader, $m)) {
                    $nextUrl = $m[1];
                } elseif (is_array($payload) && isset($payload['links']) && !empty($payload['links']['next'])) {
                    $nextUrl = $payload['links']['next'];
                } elseif (is_array($payload) && isset($payload['meta']) && isset($payload['meta']['current_page']) && isset($payload['meta']['last_page'])) {
                    if ((int)$payload['meta']['current_page'] < (int)$payload['meta']['last_page']) {
                        $page = (int)$payload['meta']['current_page'] + 1;
                        $nextUrl = null;
                    } else {
                        $nextUrl = null;
                    }
                } else {
                    if ($newAdded > 0 && count($pageUsers) > 0) {
                        $page = ((int)$page) + 1;
                        $nextUrl = null;
                    } else {
                        $nextUrl = null;
                    }
                }

                if ($nextUrl && !preg_match('#^https?://#i', $nextUrl)) {
                    $nextUrl = rtrim($base, '/') . '/' . ltrim($nextUrl, '/');
                }

                $iterations++;
                if ($newAdded === 0 && !$nextUrl) break;

            } while (($nextUrl || $iterations < $maxIterations) && $iterations < $maxIterations);

            // mapear
            $mapped = array_map(function ($u) {
                return [
                    'id' => $u['id'] ?? $u['user_id'] ?? $u['usuario_id'] ?? null,
                    'name' => $u['name'] ?? $u['nombre'] ?? ($u['email'] ?? 'Usuario'),
                    'email' => $u['email'] ?? null,
                    'operacion_user' => $u['operacion_user'] ?? $u['operacion'] ?? $u['operaciones'] ?? null,
                ];
            }, $usersRaw);

            $mapped = array_values(array_filter($mapped, fn($x) => isset($x['id'])));

            Log::info('fetchExternalUsers mapped count', ['count' => count($mapped)]);

            if (empty($mapped)) {
                Log::info('fetchExternalUsers returned empty or unexpected payload', ['url' => $url, 'body' => isset($resp) ? $resp->body() : null]);
                return response()->json(['ok' => false, 'message' => 'No se encontraron usuarios en la respuesta del servicio externo'], 200);
            }

            return response()->json(['ok' => true, 'users' => $mapped]);

        } catch (\Throwable $e) {
            Log::error('fetchExternalUsers error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['ok' => false, 'message' => 'Error al consultar servicio externo: ' . $e->getMessage()], 500);
        }
    }
}
