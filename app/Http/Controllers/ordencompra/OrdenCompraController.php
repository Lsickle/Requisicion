<?php

namespace App\Http\Controllers\ordencompra;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraProducto;
use App\Models\OrdenCompraCentroProducto;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Producto;
use App\Models\EstatusOrdenCompra;
use App\Models\Centro;
use App\Models\Estatus_Requisicion;
use App\Models\OrdenCompraEstatus;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use App\Jobs\RequisicionEntregaRegistradaJob; // NUEVO

class OrdenCompraController extends Controller
{
    public function index()
    {
        $ordenes = OrdenCompra::with(['requisicion', 'ordencompraProductos.proveedor'])
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('ordenes_compra.lista', compact('ordenes'));
    }

    public function create(Request $request, $requisicion_id = null)
    {
        $reqId = $requisicion_id ?? $request->query('requisicion_id');

        $requisiciones = Requisicion::select('requisicion.*')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('productos')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->whereColumn('requisicion.id', 'producto_requisicion.id_requisicion')
                    ->whereNull('productos.deleted_at');
            })
            ->get();

        $requisicion = null;
        $productosDisponibles = collect();
        $productoSeleccionado = null;
        $productoProveedores = collect();
        $proveedores = Proveedor::all();
        $centros = Centro::all();
        $lineasDistribuidas = collect();
        $ordenes = collect();
        $totalConfirmadoPorProducto = collect();

        if ($reqId) {
            $requisicion = Requisicion::find($reqId);

            if ($requisicion) {
                // Productos disponibles: excluir solo si ya existen en OCs principales (no OC-DIST)
                $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
                    ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
                    ->where('producto_requisicion.id_requisicion', $requisicion->id)
                    ->whereNull('productos.deleted_at')
                    ->whereNotExists(function ($subquery) use ($requisicion) {
                        $subquery->select(DB::raw(1))
                            ->from('ordencompra_producto')
                            ->join('orden_compras', 'ordencompra_producto.orden_compras_id', '=', 'orden_compras.id')
                            ->whereRaw('ordencompra_producto.producto_id = productos.id')
                            ->where('orden_compras.requisicion_id', $requisicion->id)
                            ->whereNull('ordencompra_producto.deleted_at');
                    })
                    ->whereNotExists(function ($subquery) use ($requisicion) {
                        // Excluir si el producto tiene líneas distribuidas pendientes para esta requisición
                        $subquery->select(DB::raw(1))
                            ->from('ordencompra_producto as ocp0')
                            ->whereColumn('ocp0.producto_id', 'productos.id')
                            ->where('ocp0.requisicion_id', $requisicion->id)
                            ->whereNull('ocp0.orden_compras_id')
                            ->whereNull('ocp0.deleted_at');
                    })
                    ->orderBy('productos.id', 'asc')
                    ->get();

                foreach ($productosDisponibles as $producto) {
                    $producto->setRelation('pivot', (object) [
                        'pr_amount' => $producto->pr_amount
                    ]);
                }

                // Líneas distribuidas (pendientes, sin OC): orden_compras_id IS NULL
                $lineasDistribuidas = DB::table('ordencompra_producto as ocp')
                    ->join('productos as p', 'ocp.producto_id', '=', 'p.id')
                    ->leftJoin('proveedores as prov', 'ocp.proveedor_id', '=', 'prov.id')
                    ->leftJoin('productoxproveedor as pxp', function($join){
                        $join->on('pxp.producto_id', '=', 'p.id')
                             ->on('pxp.proveedor_id', '=', 'ocp.proveedor_id');
                    })
                    ->whereNull('ocp.deleted_at')
                    ->whereNull('ocp.orden_compras_id')
                    ->where('ocp.requisicion_id', $requisicion->id)
                    ->select(
                        'ocp.id as ocp_id',
                        'ocp.producto_id',
                        'ocp.proveedor_id',
                        'ocp.total as cantidad',
                        'p.name_produc',
                        'p.unit_produc',
                        'p.stock_produc',
                        DB::raw('COALESCE(pxp.price_produc, 0) as price_produc'),
                        'prov.prov_name'
                    )
                    ->get();

                // Órdenes principales (no OC-DIST)
                $ordenes = OrdenCompra::with('ordencompraProductos.producto', 'ordencompraProductos.proveedor')
                    ->where('requisicion_id', $requisicion->id)
                    ->whereNull('deleted_at')
                    ->where(function ($q) {
                        $q->whereNull('order_oc')
                          ->orWhere('order_oc', 'not like', 'OC-DIST-%');
                    })
                    ->orderBy('id', 'desc')
                    ->get();

                // Totales entregados (confirmados) por producto para esta requisición (tabla entrega)
                try {
                    $totalConfirmadoPorProducto = DB::table('entrega')
                        ->where('requisicion_id', $requisicion->id)
                        ->whereNull('deleted_at')
                        ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as total'))
                        ->groupBy('producto_id')
                        ->pluck('total', 'producto_id');
                } catch (\Throwable $e) {
                    $totalConfirmadoPorProducto = collect();
                }

                // Si ya está completa, actualizar estatus a 10 si no lo está
                try {
                    if ($this->isRequisitionComplete((int)$requisicion->id)) {
                        $estatusActual = (int) DB::table('estatus_requisicion')
                            ->where('requisicion_id', $requisicion->id)
                            ->whereNull('deleted_at')
                            ->where('estatus', 1)
                            ->value('estatus_id');
                        if ($estatusActual !== 10) {
                            $this->setRequisicionStatus((int)$requisicion->id, 10, 'Requisición completada automáticamente');
                        }
                    }
                } catch (\Throwable $e) { /* noop */ }
            }
        }

        // Si se pasó producto_id en la query, cargar el producto y sus proveedores (sin eager load de relaciones inexistentes)
        $prefillProducto = null;
        if ($request->has('producto_id') && $request->producto_id != 0) {
            $productoSeleccionado = Producto::find($request->producto_id);
            try {
                $productoProveedores = DB::table('productoxproveedor as pxp')
                    ->join('proveedores as prov', 'prov.id', '=', 'pxp.proveedor_id')
                    ->where('pxp.producto_id', $request->producto_id)
                    ->select('pxp.*', 'prov.id as proveedor_id', 'prov.prov_name')
                    ->orderBy('pxp.id')
                    ->get();
            } catch (\Throwable $e) {
                $productoProveedores = collect();
            }

            // Preparar datos para prellenar la vista (si vienen cantidad/proveedor in the query)
            $prefillProducto = [
                'producto_id' => (int)$request->producto_id,
                'cantidad' => isset($request->cantidad) ? (int)$request->cantidad : (int)($request->query('cantidad') ?? 0),
                'proveedor_id' => $request->query('proveedor_id') ?? $request->proveedor_id ?? null,
            ];

            // Asegurar que el producto seleccionado aparezca en la lista de productosDisponibles
            try {
                if (!empty($productoSeleccionado) && !$productosDisponibles->contains(fn($p)=> $p->id == $productoSeleccionado->id)) {
                    $productoSeleccionado->setRelation('pivot', (object)['pr_amount' => $prefillProducto['cantidad'] ?? 0]);
                    // Añadir al inicio para que quede visible en el selector
                    $productosDisponibles->prepend($productoSeleccionado);
                }
            } catch (\Throwable $e) { /* noop */ }
        }

        // Obtener últimas tasas TRM por moneda desde la tabla `trm` y pasar a la vista
        try {
            $trmLatest = DB::table('trm')
                ->orderByDesc('update_date')
                ->orderByDesc('id')
                ->get()
                ->unique('moneda')
                ->values();
        } catch (\Throwable $e) {
            Log::warning('No se pudo obtener TRM desde BD: ' . $e->getMessage());
            $trmLatest = collect();
        }

        return view('ordenes_compra.create', compact(
            'requisiciones',
            'requisicion',
            'productosDisponibles',
            'productoSeleccionado',
            'productoProveedores',
            'proveedores',
            'centros',
            'lineasDistribuidas',
            'ordenes',
            'trmLatest',
            'prefillProducto',
            'totalConfirmadoPorProducto'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'proveedor_id'   => 'nullable|exists:proveedores,id',
            'observaciones'  => 'nullable|string',
            'requisicion_id' => 'required|exists:requisicion,id',
            'date_oc'        => 'nullable|date|after_or_equal:today',
            'productos'      => 'required|array|min:1',
            'productos.*.id' => 'required|exists:productos,id',
            'productos.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'productos.*.ocp_id' => 'nullable|integer|exists:ordencompra_producto,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
            'productos.*.iva' => 'nullable|numeric',
            'productos.*.apply_iva' => 'nullable|boolean',
            'productos.*.stock_e' => 'nullable|integer|min:0',
            'productos.*.trm_oc' => 'nullable',
            'productos.*.price' => 'nullable|numeric',
            'productos.*.currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Agrupar productos por proveedor (usar proveedor del item; si falta, usar proveedor global si viene)
        $globalProv = $request->input('proveedor_id');
        $grupos = [];
        foreach ($request->productos as $rowKey => $productoData) {
            $provId = (int)($productoData['proveedor_id'] ?? 0);
            if (!$provId && $globalProv) { $provId = (int)$globalProv; }
            if (!$provId) {
                return redirect()->back()->withErrors(['productos' => 'Debe seleccionar proveedor para cada producto.'])->withInput();
            }
            if (!isset($grupos[$provId])) $grupos[$provId] = [];
            $grupos[$provId][] = ['rowKey' => $rowKey, 'data' => $productoData];
        }

        DB::beginTransaction();
        try {
            $ordenesCreadas = [];

            foreach ($grupos as $provId => $items) {
                // Crear encabezado por proveedor
                $ultimaOrden = OrdenCompra::withTrashed()->orderBy('id', 'desc')->first();
                $numeroOrden = 'OC-' . (($ultimaOrden ? $ultimaOrden->id : 0) + 1) . '-' . now()->format('Ymd');

                $orden = OrdenCompra::create([
                    'requisicion_id' => $request->requisicion_id,
                    'observaciones'  => $request->observaciones,
                    'date_oc'        => $request->input('date_oc') ?: null,
                    'order_oc'       => $numeroOrden,
                ]);

                // Guardar info de usuario si existen columnas
                try {
                    $sessionUserId = session('user.id');
                    $sessionUserName = session('user.name') ?? $this->resolveCurrentUserName($request) ?? null;
                    $sessionUserEmail = session('user.email') ?? null;
                    $sessionUserOperacion = session('user.operaciones') ?? session('user.operacion') ?? null;

                    $dirty = false;
                    if (Schema::hasColumn('orden_compras', 'user_id') && $sessionUserId) { $orden->user_id = $sessionUserId; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'oc_user') && $sessionUserName) { $orden->oc_user = $sessionUserName; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'name_user') && $sessionUserName) { $orden->name_user = $sessionUserName; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'user_name') && $sessionUserName) { $orden->user_name = $sessionUserName; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'email_user') && $sessionUserEmail) { $orden->email_user = $sessionUserEmail; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'user_email') && $sessionUserEmail) { $orden->user_email = $sessionUserEmail; $dirty = true; }
                    if (Schema::hasColumn('orden_compras', 'operacion_user') && $sessionUserOperacion) { $orden->operacion_user = $sessionUserOperacion; $dirty = true; }
                    if ($dirty) { $orden->save(); }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo persistir info de usuario en orden ' . ($orden->id ?? 'n/a') . ': ' . $e->getMessage());
                }

                // Estatus inicial 'Creada' si no existe
                try {
                    $initial = EstatusOrdenCompra::find(1) ?? EstatusOrdenCompra::where('status_name', 'Creada')->first() ?? EstatusOrdenCompra::first();
                    if ($initial) {
                        $exists = OrdenCompraEstatus::where('orden_compra_id', $orden->id)->exists();
                        if (!$exists) {
                            OrdenCompraEstatus::create([
                                'estatus_id' => $initial->id,
                                'orden_compra_id' => $orden->id,
                                'recepcion_id' => null,
                                'activo' => 1,
                                'date_update' => now(),
                                'user_name' => session('user.name') ?? $this->resolveCurrentUserName($request) ?? null,
                                'user_id' => session('user.id') ?? null,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo crear estatus inicial para OC ' . ($orden->id ?? 'n/a') . ': ' . $e->getMessage());
                }

                // Crear líneas para este proveedor
                foreach ($items as $it) {
                    $productoData = $it['data'];
                    if (empty($productoData['id'])) continue;

                    $productoId = (int) $productoData['id'];
                    $cantidadIngresada = (int) ($productoData['cantidad'] ?? 0);
                    $ocpId = $productoData['ocp_id'] ?? null;
                    $stockE = isset($productoData['stock_e']) && $productoData['stock_e'] !== '' ? (int)$productoData['stock_e'] : null;

                    // Asegurar pxp_id en pivot producto_requisicion
                    try { $this->setProductRequisitionPxpId((int)$request->requisicion_id, (int)$productoId, (int)$provId); } catch (\Throwable $e) { Log::warning('setProductRequisitionPxpId fallo (store, pre-linea): '.$e->getMessage()); }

                    if ($ocpId) {
                        $ocp = OrdenCompraProducto::where('id', $ocpId)
                            ->whereNull('orden_compras_id')
                            ->where('requisicion_id', $request->requisicion_id)
                            ->firstOrFail();
                        // Forzar proveedor al del grupo
                        $ocp->proveedor_id = (int)$provId;

                        // Actualizar cantidad si el usuario la editó
                        if ($cantidadIngresada > 0 && $cantidadIngresada !== (int)$ocp->total) {
                            $ocp->total = $cantidadIngresada;
                        }
                        // Aplicar IVA si seleccionó la casilla
                        if (!empty($productoData['apply_iva'])) {
                            $rate = null;
                            if (isset($productoData['iva']) && is_numeric($productoData['iva'])) {
                                $rate = (float)$productoData['iva'];
                            } else {
                                $prodTmp = Producto::find($productoId);
                                $rate = ($prodTmp && isset($prodTmp->iva) && is_numeric($prodTmp->iva)) ? (float)$prodTmp->iva : 0;
                            }
                            $frac = ($rate > 1) ? ($rate / 100.0) : $rate;
                            $ocp->apply_iva = ($frac > 0) ? $frac : null;
                        } else {
                            $ocp->apply_iva = null;
                        }
                        $ocp->orden_compras_id = $orden->id;
                        // Reintentar fijar pxp_id por si proveedor cambió
                        try { $this->setProductRequisitionPxpId((int)$request->requisicion_id, (int)$productoId, (int)$provId); } catch (\Throwable $e) { /* noop */ }

                        // Obtener precio y moneda del proveedor seleccionado
                        $precioOriginalRaw = null; $cur = 'COP';
                        try {
                            $pxp = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                                ->where('producto_id', $productoId)
                                ->where('proveedor_id', (int)$provId)
                                ->orderBy('id')
                                ->first();
                            if (!$pxp) {
                                $pxp = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                                    ->where('producto_id', $productoId)
                                    ->orderBy('id')
                                    ->first();
                            }
                            if ($pxp && isset($pxp->price_produc)) {
                                $precioOriginalRaw = (float)$pxp->price_produc;
                                if (!empty($pxp->moneda)) { $cur = $this->normalizeCurrency($pxp->moneda); }
                            }
                        } catch (\Throwable $e) { /* noop */ }
                        if ($precioOriginalRaw === null && isset($productoData['price'])) {
                            $precioOriginalRaw = (float) $productoData['price'];
                        }
                        if ($precioOriginalRaw === null) { $precioOriginalRaw = 0.0; }
                        // Si no se obtuvo moneda del pxp, usar la del request si viene
                        if ($cur === 'COP') {
                            if (!empty($productoData['currency'])) $cur = $this->normalizeCurrency($productoData['currency']);
                            elseif (!empty($productoData['moneda'])) $cur = $this->normalizeCurrency($productoData['moneda']);
                        }
                        // Calcular TRM hacia COP
                        $rate = null;
                        if (strtoupper($cur) === 'COP') {
                            $rate = 1.0;
                        } else {
                            $rate = $this->fetchExchangeRateServer($cur, 'COP');
                            if ($rate !== null) { $rate = round((float)$rate, 6); }
                            // Fallback: trm enviada por cliente
                            if ($rate === null) {
                                $fromClient = $this->parseLocalizedNumber($productoData['trm_oc'] ?? null);
                                if (is_numeric($fromClient) && $fromClient > 0) { $rate = round((float)$fromClient, 6); }
                            }
                        }
                        // Calcular precio en COP (si falta rate en moneda extranjera, guardar crudo como último recurso)
                        $precioCOP = null;
                        if ($rate !== null) { $precioCOP = round(((float)$precioOriginalRaw) * ((float)$rate), 2); }
                        else { $precioCOP = round((float)$precioOriginalRaw, 2); }

                        // Guardar conversiones y TRM
                        $ocp->precio_original = $precioCOP;
                        if ($ocp->precio_factura === null) { $ocp->precio_factura = $precioCOP; }
                        if ($rate !== null) {
                            $ocp->trm_oc = $rate;
                            $ocp->trm_factura = $rate;
                        }
                        $ocp->save();

                        // ...existing code...
                        continue;
                    }

                    // Línea normal con proveedor del grupo
                    $applyFrac = null;
                    if (!empty($productoData['apply_iva'])) {
                        if (isset($productoData['iva']) && is_numeric($productoData['iva'])) { $rateIva = (float)$productoData['iva']; }
                        else { $prodTmp = Producto::find($productoId); $rateIva = ($prodTmp && isset($prodTmp->iva) && is_numeric($prodTmp->iva)) ? (float)$prodTmp->iva : 0; }
                        $applyFrac = ($rateIva > 0) ?  ( ($rateIva > 1) ? ($rateIva/100.0) : $rateIva ) : null;
                    }

                    $trmOcValue = null;
                    // Determinar moneda para calcular tasa
                    $cur = 'COP';
                    if (!empty($productoData['currency'])) {
                        $cur = $this->normalizeCurrency($productoData['currency']);
                    } elseif (!empty($productoData['moneda'])) {
                        $cur = $this->normalizeCurrency($productoData['moneda']);
                    } else {
                        try {
                            $pxpMon = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                                ->where('producto_id', $productoId)
                                ->where('proveedor_id', (int)$provId)
                                ->orderBy('id')
                                ->first();
                            if ($pxpMon && !empty($pxpMon->moneda)) { $cur = $this->normalizeCurrency($pxpMon->moneda); }
                        } catch (\Throwable $e) { /* ignore */ }
                    }
                    if ($cur === 'COP') {
                        $trmOcValue = 1.0;
                    } else {
                        $rateSrv = $this->fetchExchangeRateServer($cur, 'COP');
                        $trmOcValue = $rateSrv ? round($rateSrv, 6) : null;
                    }
                    // Fallback: usar trm enviada desde el cliente solo si no hay TRM de servidor
                    if ($trmOcValue === null && $cur !== 'COP') {
                        $trmFromClient = $this->parseLocalizedNumber($productoData['trm_oc'] ?? null);
                        if (is_numeric($trmFromClient) && $trmFromClient > 0) { $trmOcValue = round((float)$trmFromClient, 6); }
                    }

                    // Precio base del proveedor
                    $precioOriginalRaw = null; $curFromPxp = $cur;
                    try {
                        $pxp = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                            ->where('producto_id', $productoId)
                            ->where('proveedor_id', (int)$provId)
                            ->orderBy('id')
                            ->first();
                        if (!$pxp) {
                            $pxp = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                                ->where('producto_id', $productoId)
                                ->orderBy('id')
                                ->first();
                        }
                        if ($pxp && isset($pxp->price_produc)) {
                            $precioOriginalRaw = (float) $pxp->price_produc;
                            if (!empty($pxp->moneda)) { $curFromPxp = $this->normalizeCurrency($pxp->moneda); }
                        }
                    } catch (\Throwable $e) { /* noop */ }
                    if ($precioOriginalRaw === null && isset($productoData['price'])) {
                        $precioOriginalRaw = (float) $productoData['price'];
                    }
                    if ($precioOriginalRaw === null) { $precioOriginalRaw = 0.0; }

                    // Usar TRM (1 si COP) para convertir a COP
                    $precioOriginalCOP = null;
                    $monUpper = strtoupper($curFromPxp ?? $cur ?? 'COP');
                    if ($monUpper === 'COP') {
                        $precioOriginalCOP = round((float)$precioOriginalRaw, 2);
                        $trmOcValue = $trmOcValue ?: 1.0;
                    } else {
                        // Si aún no hay TRM, intentar servidor de nuevo por la moneda de pxp
                        if ($trmOcValue === null) {
                            $rateSrv2 = $this->fetchExchangeRateServer($monUpper, 'COP');
                            $trmOcValue = $rateSrv2 ? round($rateSrv2, 6) : null;
                        }
                        $precioOriginalCOP = ($trmOcValue !== null)
                            ? round(((float)$precioOriginalRaw) * ((float)$trmOcValue), 2)
                            : round((float)$precioOriginalRaw, 2);
                    }

                    OrdenCompraProducto::create([
                        'producto_id'      => $productoId,
                        'orden_compras_id' => $orden->id,
                        'requisicion_id'   => $request->requisicion_id,
                        'proveedor_id'     => (int)$provId,
                        'total'            => $cantidadIngresada,
                        'stock_e'          => $stockE,
                        'apply_iva'        => $applyFrac,
                        'trm_oc'           => $trmOcValue,
                        'trm_factura'      => $trmOcValue,
                        'precio_original'  => $precioOriginalCOP,
                        'precio_factura'   => $precioOriginalCOP,
                    ]);

                    // Asegurar trm_oc si quedó NULL
                    try {
                        $last = OrdenCompraProducto::where('orden_compras_id', $orden->id)
                            ->where('producto_id', $productoId)
                            ->where('proveedor_id', (int)$provId)
                            ->orderBy('id', 'desc')
                            ->first();
                        if ($last && ($last->trm_oc === null || $last->trm_oc === '')) {
                            $pxp = DB::table('productoxproveedor')
                                ->where('producto_id', $productoId)
                                ->where('proveedor_id', (int)$provId)
                                ->orderBy('id')
                                ->first();
                            $curFix = strtoupper($pxp->moneda ?? 'COP');
                            $computed = ($curFix === 'COP') ? 1.0 : ($this->fetchExchangeRateServer($curFix, 'COP') ?: null);
                            if ($computed !== null) {
                                $val = round($computed, 6);
                                $last->trm_oc = $val;
                                if ($last->trm_factura === null || $last->trm_factura === '') { $last->trm_factura = $val; }
                                $last->save();
                            }
                        } else if ($last && ($last->trm_factura === null || $last->trm_factura === '')) {
                            // Si ya hay trm_oc pero falta trm_factura, copiarlo
                            $last->trm_factura = $last->trm_oc;
                            $last->save();
                        }
                    } catch (\Throwable $e) { /* noop */ }

                    if ($stockE !== null && $stockE > 0) {
                        $producto = Producto::lockForUpdate()->findOrFail($productoId);
                        $producto->stock_produc = max(0, (int)$producto->stock_produc - $stockE);
                        $producto->save();
                    }

                    // Distribución por centros (recrear)
                    OrdenCompraCentroProducto::where('orden_compra_id', $orden->id)
                        ->where('producto_id', $productoId)
                        ->delete();

                    if (!empty($productoData['centros'])) {
                        foreach ($productoData['centros'] as $centroId => $cantidad) {
                            if ((int)$cantidad > 0) {
                                OrdenCompraCentroProducto::create([
                                    'orden_compra_id' => $orden->id,
                                    'producto_id'     => $productoId,
                                    'centro_id'       => $centroId,
                                    'amount'          => (int)$cantidad,
                                ]);
                            }
                        }
                    }
                }

                $ordenesCreadas[] = $orden;
            }

            // Sincronizar pxp en producto_requisicion con las líneas creadas
            try { $this->syncPxpIdsForRequisition((int)$request->requisicion_id); } catch (\Throwable $e) { Log::warning('syncPxpIdsForRequisition fallo: '.$e->getMessage()); }

            // Generar PDF y enviar correos por cada orden creada
            foreach ($ordenesCreadas as $orden) {
                try {
                    $orden->load('ordencompraProductos.producto', 'ordencompraProductos.proveedor');
                    $pdfData = $this->buildPdfData($orden);
                    $pdf = Pdf::loadView('ordenes_compra.pdf', $pdfData);
                    $content = $pdf->output();
                    $orden->storePdfBlob($content);
                    $fileHash = hash('sha256', $content);
                    if (empty($orden->validation_hash)) {
                        $orden->validation_hash = $fileHash;
                        $orden->save();
                    }
                } catch (\Throwable $e) { /* noop */ }

                try {
                    $requisicionObj = Requisicion::find($orden->requisicion_id);
                    $conf = (array) config('requisiciones.destinatarios_oc', []);
                    $toConfig = array_values(array_unique((array)($conf['to'] ?? [])));
                    $ccConfig = array_values(array_unique((array)($conf['cc'] ?? [])));

                    if (!empty($requisicionObj?->email_user)) {
                        try { Mail::to($requisicionObj->email_user)->send(new \App\Mail\OrdenCompraCreada($orden)); }
                        catch (\Throwable $e) { Log::warning('No se pudo enviar correo al solicitante ' . $requisicionObj->email_user . ': ' . $e->getMessage()); }
                    }

                    $toFiltered = array_values(array_filter($toConfig, function($addr) use ($requisicionObj) {
                        if (empty($addr)) return false;
                        if (!empty($requisicionObj?->email_user) && $addr === $requisicionObj->email_user) return false;
                        return true;
                    }));

                    if (!empty($toFiltered)) {
                        try {
                            $m = new \App\Mail\OrdenCompraCreada($orden);
                            if (!empty($ccConfig)) Mail::to($toFiltered)->cc($ccConfig)->send($m);
                            else Mail::to($toFiltered)->send($m);
                        } catch (\Throwable $e) {
                            Log::warning('No se pudo enviar correo OC creada a destinatarios configurados: ' . $e->getMessage());
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Error durante envío de correos OC creada: ' . $e->getMessage());
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $request->requisicion_id])
                ->with('success', 'Órdenes guardadas correctamente (' . count($ordenesCreadas) . ').');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando orden(es) de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function distribuirProveedores(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:productos,id',
            'requisicion_id' => 'required|exists:requisicion,id',
            'distribucion' => 'required|array|min:1',
            // permitir proveedor_id nulo: ahora solo se pide cantidad
            'distribucion.*.proveedor_id' => 'nullable|exists:proveedores,id',
            'distribucion.*.cantidad' => 'required|integer|min:1',
            'distribucion.*.observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $productoId = (int)$request->producto_id;
            $requisicionId = (int)$request->requisicion_id;
            $distribuciones = $request->distribucion;

            // Validar suma exacta contra cantidad original de la requisición
            $cantidadOriginal = (int) DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->value('pr_amount');

            $totalDistribucion = collect($distribuciones)->sum(function($d){ return (int)$d['cantidad']; });
            if ($totalDistribucion !== $cantidadOriginal) {
                $msg = 'La distribución total ('.$totalDistribucion.') debe ser igual a la cantidad original ('.$cantidadOriginal.').';
                if ($request->expectsJson()) {
                    return response()->json(['message' => $msg], 422);
                }
                return redirect()->back()->with('error', $msg);
            }

            $producto = Producto::findOrFail($productoId);

            $lineas = [];
            foreach ($distribuciones as $dist) {
                $provId = isset($dist['proveedor_id']) && $dist['proveedor_id'] !== '' ? (int)$dist['proveedor_id'] : null;
                $ocp = OrdenCompraProducto::create([
                    'producto_id'      => $productoId,
                    'requisicion_id'   => $requisicionId,
                    'proveedor_id'     => $provId, // puede ser null ahora
                    'total'            => (int)$dist['cantidad'],
                ]);

                // Fijar pxp_id en producto_requisicion si es posible (por proveedor, o único pxp del producto)
                try { $this->setProductRequisitionPxpId((int)$requisicionId, (int)$productoId, $provId ? (int)$provId : null); } catch (\Throwable $e) { Log::warning('setProductRequisitionPxpId fallo (distribucion): '.$e->getMessage()); }

                $prov = $provId ? Proveedor::find($provId) : null;

                $lineas[] = [
                    'ocp_id' => $ocp->id,
                    'producto_id' => $productoId,
                    'producto_nombre' => $producto->name_produc,
                    'unidad' => $producto->unit_produc,
                    'stock' => $producto->stock_produc,
                    'cantidad' => (int)$dist['cantidad'],
                    'proveedor_id' => $provId,
                    'proveedor_nombre' => $prov?->prov_name ?? 'Proveedor',
                ];
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'lineas' => $lineas], 200);
            }

            return redirect()->route('ordenes_compra.create', ['requisicion_id' => $requisicionId])
                ->with('success', 'Distribución guardada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error distribuyendo producto: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Descargar ZIP de órdenes
     */
    public function downloadZip($requisicionId)
    {
        $requisicion = Requisicion::findOrFail($requisicionId);
        $ordenes = OrdenCompra::where('requisicion_id', $requisicionId)
            ->with(['ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->get();

        if ($ordenes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay órdenes de compra para descargar.');
        }

        // Marcar estatus 5 (OC generada) o 10 si ya está completa
        try { 
            $estatus = $this->isRequisitionComplete((int)$requisicionId) ? 10 : 5;
            $this->setRequisicionStatus((int)$requisicionId, $estatus, $estatus===10?'Requisición completa':null);
        } catch (\Throwable $e) {}
        
        $zip = new \ZipArchive();
        $zipFileName = 'ordenes_compra_requisicion_' . $requisicionId . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($ordenes as $orden) {
                // Siempre obtener el binario correcto: si pdf_file está guardado en base64, decodificarlo
                if (!empty($orden->pdf_file)) {
                    $bin = null;
                    // Intentar decodificar base64 en modo estricto
                    $decoded = @base64_decode($orden->pdf_file, true);
                    if ($decoded !== false && strpos($decoded, '%PDF') === 0) {
                        $bin = $decoded;
                    } elseif (strpos($orden->pdf_file, '%PDF') === 0) {
                        // ya está en binario
                        $bin = $orden->pdf_file;
                    }

                    if ($bin !== null) {
                        // Calcular hash y guardar si es distinto
                        try {
                            $fileHash = hash('sha256', $bin);
                            $this->storeOrderPdfHash((int)$orden->id, $fileHash);
                        } catch (\Throwable $e) { /* noop */ }

                        $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';
                        $zip->addFromString($fileName, $bin);
                        continue;
                    }
                    // si no logramos obtener binario válido, caeremos a generar
                }

                // Generar PDF y almacenar en la orden (si no existe o si el stored no era válido)
                $pdfData = $this->buildPdfData($orden);
                $pdf = Pdf::loadView('ordenes_compra.pdf', $pdfData);
                $content = $pdf->output();

                try {
                    // Intentar guardar blob (storePdfBlob puede esperar base64 o binario según implementación)
                    $orden->storePdfBlob($content);
                } catch (\Throwable $e) {
                    // noop: no bloquear el proceso si falla el guardado
                }

                // Calcular y guardar hash sobre el contenido que realmente se agregará
                try {
                    $fileHash = hash('sha256', $content);
                    $this->storeOrderPdfHash((int)$orden->id, $fileHash);
                } catch (\Throwable $e) { /* noop */ }

                $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';
                $zip->addFromString($fileName, $content);
            }
            $zip->close();

            return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Error al crear el archivo ZIP.');
    }

    /** 
     * Anular orden (soft delete de orden y sus relaciones)
     */
    public function anular($id)
    {
        DB::beginTransaction();
        try {
            $orden = OrdenCompra::findOrFail($id);

            // Procesar líneas: si se originaron antes que la OC (fueron distribuidas), volver a pendientes; si no, eliminar lógicamente
            $lineas = OrdenCompraProducto::where('orden_compras_id', $id)->get();
            foreach ($lineas as $linea) {
                if ($linea->created_at && $orden->created_at && $linea->created_at->lt($orden->created_at)) {
                    // Línea proveniente de distribución previa: volver a pendiente
                    $linea->orden_compras_id = null;
                    $linea->save();
                } else {
                    // Línea normal creada con la OC: soft delete
                    $linea->delete();
                }
            }

            // Borrar distribución por centros (relaciones)
            OrdenCompraCentroProducto::where('orden_compra_id', $id)->delete();

            // Soft delete del encabezado
            $orden->delete();

            // Mantener histórico de estatus: desactivar estatus previos y crear un nuevo estatus 'Anulada' (no borrar registros)
            // Desactivar estatus anteriores (mantener histórico) y crear nuevo registro 'Anulada'
            OrdenCompraEstatus::where('orden_compra_id', $id)->update(['activo' => 0]);
            $anulada = EstatusOrdenCompra::find(4) ?: EstatusOrdenCompra::where('status_name', 'Anulada')->first() ?: EstatusOrdenCompra::first();
            if ($anulada) {
                OrdenCompraEstatus::create([
                    'estatus_id' => $anulada->id,
                    'orden_compra_id' => $id,
                    'recepcion_id' => null,
                    'activo' => 1,
                    'date_update' => now(),
                    'user_name' => session('user.name') ?? $this->resolveCurrentUserName(null) ?? null,
                    'user_id' => session('user.id') ?? null,
                ]);
            }

            // Si, tras anular, no quedan órdenes activas para la requisición -> revertir estatus si el activo es 5
            try {
                $reqId = $orden->requisicion_id;
                $remaining = OrdenCompra::where('requisicion_id', $reqId)->whereNull('deleted_at')->count();

                if ($remaining === 0) {
                    $active = Estatus_Requisicion::where('requisicion_id', $reqId)->where('estatus', 1)->first();
                    if ($active && (int)$active->estatus_id === 5) {
                        // soft-delete del estatus activo (5)
                        try {
                            $active->delete();
                        } catch (\Throwable $e) {
                            Log::warning('No se pudo soft-delete estatus_requisicion activo (5) para requisicion '.$reqId.': '.$e->getMessage());
                        }

                        // Reactivar el último estatus previo (estatus = 0)
                        try {
                            $prev = Estatus_Requisicion::where('requisicion_id', $reqId)
                                ->where('estatus', 0)
                                ->orderByDesc('id')
                                ->first();
                            if ($prev) {
                                $prev->estatus = 1;
                                $prev->updated_at = now();
                                $prev->save();
                            }
                        } catch (\Throwable $e) {
                            Log::warning('No se pudo reactivar estatus previo para requisicion ' . $reqId . ': ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error comprobando órdenes restantes tras anulación OC '.$id.': '.$e->getMessage());
            }

            DB::commit();
            return redirect()->back()->with('success', 'Orden de compra anulada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al anular la orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al anular la orden: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar detalle
     */
    public function show($id)
    {
        $orden = OrdenCompra::with(['requisicion', 'ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->findOrFail($id);

        return view('ordenes_compra.show', ['ordenCompra' => $orden]);
    }

    /**
     * Formulario de edición
     */
    public function edit($id)
    {
        $ordenCompra = OrdenCompra::with([
            'requisicion',
            'distribucionCentrosProductos.centro',
            'distribucionCentrosProductos.producto'
        ])->findOrFail($id);

        $requisicion = Requisicion::with('productos')->findOrFail($ordenCompra->requisicion_id);
        $centros = Centro::all();

        // Obtener distribución de la orden de compra
        $distribucionOrden = [];
        foreach ($ordenCompra->distribucionCentrosProductos as $dist) {
            if (!isset($distribucionOrden[$dist->producto_id])) {
                $distribucionOrden[$dist->producto_id] = [];
            }
            $distribucionOrden[$dist->producto_id][$dist->centro_id] = $dist->amount;
        }

        return view('ordenes_compra.edit', [
            'ordenCompra' => $ordenCompra,
            'requisicion' => $requisicion,
            'centros' => $centros,
            'distribucion' => $distribucionOrden,
        ]);
    }

    /**
     * Actualizar orden
     */
    public function update(Request $request, $id)
    {
        $ordenCompra = OrdenCompra::with('requisicion')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'observaciones'  => 'nullable|string',
            'productos'      => 'required|array|min:1',
            'productos.*.cantidad' => 'nullable|integer|min:0',
            'productos.*.centros' => 'nullable|array',
            'productos.*.centros.*' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $ordenCompra->update([
                'observaciones'  => $request->observaciones,
            ]);

            foreach ($request->productos as $productoId => $productoData) {
                $ordenProducto = OrdenCompraProducto::where('orden_compras_id', $id)
                    ->where('producto_id', $productoId)
                    ->first();

                if ($ordenProducto) {
                    // calcular fracción de IVA al actualizar
                    $applyFrac = null;
                    if (!empty($productoData['apply_iva'])) {
                        if (isset($productoData['iva']) && is_numeric($productoData['iva'])) {
                            $rate = (float)$productoData['iva'];
                        } else {
                            $prodTmp = Producto::find($productoId);
                            $rate = ($prodTmp && isset($prodTmp->iva) && is_numeric($prodTmp->iva)) ? (float)$prodTmp->iva : 0;
                        }
                        $applyFrac = ($rate > 0) ?  ( ($rate > 1) ? ($rate/100.0) : $rate ) : null;
                    }
                    $ordenProducto->update([
                        'total' => $productoData['cantidad'] ?? 0,
                        'apply_iva' => $applyFrac,
                    ]);
                }

                OrdenCompraCentroProducto::where('orden_compra_id', $id)
                    ->where('producto_id', $productoId)
                    ->delete();

                if (!empty($productoData['centros'])) {
                    foreach ($productoData['centros'] as $centroId => $cantidad) {
                        if ((int)$cantidad > 0) {
                            OrdenCompraCentroProducto::create([
                                'orden_compra_id' => $id,
                                'producto_id'     => $productoId,
                                'centro_id'       => $centroId,
                                'amount'          => (int)$cantidad,
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return redirect()->route('ordenes_compra.show', $id)
                ->with('success', 'Orden de compra actualizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando orden de compra: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function vistaDistribucionProveedores(Request $request, $requisicion_id = null)
    {
        $reqId = $request->query('requisicion_id', $requisicion_id);
        if (!$reqId) {
            abort(404, 'Requisición no especificada');
        }

        $requisicion = Requisicion::findOrFail($reqId);

        $productosDisponibles = Producto::select('productos.*', 'producto_requisicion.pr_amount')
            ->join('producto_requisicion', 'productos.id', '=', 'producto_requisicion.id_producto')
            ->where('producto_requisicion.id_requisicion', $requisicion->id)
            ->whereNull('productos.deleted_at')
            ->orderBy('productos.id', 'asc')
            ->get();

        foreach ($productosDisponibles as $producto) {
            $producto->setRelation('pivot', (object)[
                'pr_amount' => $producto->pr_amount ?? 0
            ]);
        }

        $proveedores = Proveedor::all();

        return view('ordenes_compra.distribucion_proveedores', compact('requisicion', 'productosDisponibles', 'proveedores'));
    }

    public function undoDistribucion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'requisicion_id' => 'required|exists:requisicion,id',
            'ocp_ids' => 'required|array|min:1',
            'ocp_ids.*' => 'integer|exists:ordencompra_producto,id',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $requisicionId = (int) $request->requisicion_id;
            $ids = array_map('intval', $request->ocp_ids);

            $lineas = OrdenCompraProducto::whereIn('id', $ids)
                ->where('requisicion_id', $requisicionId)
                ->whereNull('orden_compras_id')
                ->get();

            foreach ($lineas as $l) {
                $l->delete();
            }

            DB::commit();
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'count' => $lineas->count()], 200);
            }
            return redirect()->back()->with('success', 'Distribución deshecha correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al deshacer distribución: ' . $e->getMessage());
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Descargar un archivo PDF de la orden de compra
     */
    public function download($requisicionId)
    {
        // Marcar estatus 5 (OC generada) o 10 si ya está completa
        try { 
            $estatus = $this->isRequisitionComplete((int)$requisicionId) ? 10 : 5;
            $this->setRequisicionStatus((int)$requisicionId, $estatus, $estatus===10?'Requisición completa':null); 
        } catch (\Throwable $e) {}

        $ordenes = OrdenCompra::where('requisicion_id', $requisicionId)
            ->with(['ordencompraProductos.producto', 'ordencompraProductos.proveedor'])
            ->get();

        if ($ordenes->isEmpty()) {
            return redirect()->back()->with('error', 'No hay órdenes de compra para descargar.');
        }

        if ($ordenes->count() === 1) {
            $orden = $ordenes->first();
            $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';

            if (!empty($orden->pdf_file)) {
                // Intentar decodificar base64 estrictamente
                $bin = @base64_decode($orden->pdf_file, true);
                if ($bin === false || strpos($bin, '%PDF') !== 0) {
                    $bin = $orden->pdf_file;
                }

                // Calcular y registrar hash en tabla orden_hash
                try {
                    $fileHash = hash('sha256', $bin);
                    $this->storeOrderPdfHash((int)$orden->id, $fileHash);
                } catch (\Throwable $e) { /* noop */ }

                return response($bin, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
                ]);
            }

            // Generar PDF, guardar blob y registrar hash
            $data = $this->buildPdfData($orden);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('ordenes_compra.pdf', $data);
            $content = $pdf->output();

            try { $orden->storePdfBlob($content); } catch (\Throwable $e) { /* noop */ }

            try {
                $fileHash = hash('sha256', $content);
                $this->storeOrderPdfHash((int)$orden->id, $fileHash);
            } catch (\Throwable $e) { /* noop */ }

            $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';
            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
        }

        // Varias órdenes: generar ZIP
        $zip = new \ZipArchive();
        $zipFileName = 'ordenes_compra_requisicion_' . $requisicionId . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFileName);

        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'No se pudo crear el ZIP.');
        }

        foreach ($ordenes as $orden) {
            $fileName = 'orden_' . ($orden->order_oc ?? ('OC-' . $orden->id)) . '.pdf';

            if (!empty($orden->pdf_file)) {
                $bin = null;
                $decoded = @base64_decode($orden->pdf_file, true);
                if ($decoded !== false && strpos($decoded, '%PDF') === 0) { $bin = $decoded; }
                elseif (strpos($orden->pdf_file, '%PDF') === 0) { $bin = $orden->pdf_file; }

                if ($bin !== null) {
                    // Registrar hash en orden_hash
                    try {
                        $fileHash = hash('sha256', $bin);
                        $this->storeOrderPdfHash((int)$orden->id, $fileHash);
                    } catch (\Throwable $e) { /* noop */ }

                    $zip->addFromString($fileName, $bin);
                    continue;
                }
            }

            // Generar PDF y registrar hash
            $data = $this->buildPdfData($orden);
            $pdf = Pdf::loadView('ordenes_compra.pdf', $data);
            $content = $pdf->output();

            try { $orden->storePdfBlob($content); } catch (\Throwable $e) { /* noop */ }

            try {
                $fileHash = hash('sha256', $content);
                $this->storeOrderPdfHash((int)$orden->id, $fileHash);
            } catch (\Throwable $e) { /* noop */ }

            $zip->addFromString($fileName, $content);
        }
        $zip->close();

        return Response::download($zipPath, $zipFileName)->deleteFileAfterSend(true);
    }

    // Compatibilidad: algunas rutas llaman a exportPDF; delegar a download()
    public function exportPDF($requisicionId)
    {
        return $this->download($requisicionId);
    }

    // Añadir helper para construir los datos del PDF (usado por download/export)
    private function buildPdfData(\App\Models\OrdenCompra $orden): array
    {
        // Resolver proveedor de forma robusta: usar proveedor_id de líneas o, si falta, inferir desde productoxproveedor
        $proveedor = null;
        try {
            $lineas = $orden->ordencompraProductos ?? collect();
            if ($lineas instanceof \Illuminate\Support\Collection && $lineas->count()) {
                $withProv = $lineas->first(fn($l) => !empty($l->proveedor_id));
                if ($withProv) {
                    $proveedor = $withProv->proveedor ?: Proveedor::find($withProv->proveedor_id);
                }
                if (!$proveedor) {
                    $firstLinea = $lineas->first();
                    $pid = $firstLinea?->producto_id;
                    if ($pid) {
                        $provId = DB::table('productoxproveedor')
                            ->where('producto_id', $pid)
                            ->orderBy('id')
                            ->value('proveedor_id');
                        if ($provId) { $proveedor = Proveedor::find($provId); }
                    }
                }
            }
        } catch (\Throwable $e) { /* noop */ }
        if (!$proveedor) {
            $proveedor = optional($orden->ordencompraProductos->first())->proveedor;
        }

        $items = [];
        $porProducto = $orden->ordencompraProductos->groupBy('producto_id');
        $subtotalCop = 0.0; $ivaTotalCop = 0.0;
        $subtotalOrigin = 0.0; $ivaTotalOrigin = 0.0;
        $orderCurrency = null;

        foreach ($porProducto as $productoId => $lineas) {
            $producto = optional($lineas->first())->producto;
            if (!$producto) { continue; }
            $cantidad = (int) $lineas->sum('total');

            $ivaRate = 0.0;
            foreach ($lineas as $ln) {
                if ($ln->apply_iva !== null && is_numeric($ln->apply_iva)) {
                    $val = (float)$ln->apply_iva;
                    $ivaRate = ($val > 1) ? ($val / 100.0) : $val;
                    break;
                }
            }

            $unitPriceCop = null;
            // Precio y moneda base (de proveedor)
            $provId = optional($proveedor)->id;
            $pxp = DB::table('productoxproveedor')
                ->where('producto_id', $producto->id)
                ->when($provId, function($q) use ($provId){ $q->where('proveedor_id', $provId); })
                ->orderBy('id')
                ->first();
            if (!$pxp) {
                $pxp = DB::table('productoxproveedor')
                    ->where('producto_id', $producto->id)
                    ->orderBy('id')
                    ->first();
            }
            if (!$proveedor && $pxp && isset($pxp->proveedor_id)) { $proveedor = Proveedor::find($pxp->proveedor_id); }
            $priceRaw = (float)($pxp->price_produc ?? ($producto->price_produc ?? 0));
            $mon = strtoupper($pxp->moneda ?? ($producto->moneda ?? 'COP'));

            // Si hay trm_oc en la línea, interpretarlo como tasa y convertir priceRaw
            $lineWithRate = $lineas->first(function($ln){ return $ln->trm_oc !== null && $ln->trm_oc !== ''; });
            if ($mon === 'COP') {
                $unitPriceCop = round($priceRaw, 2);
            } else if ($lineWithRate) {
                $rate = (float) $lineWithRate->trm_oc; // tasa COP por 1 unidad
                $unitPriceCop = round($priceRaw * $rate, 2);
            } else {
                $rate = $this->fetchExchangeRateServer($mon, 'COP');
                $unitPriceCop = round(($rate ? ($priceRaw * $rate) : $priceRaw), 2);
            }

            // Cálculos COP (compatibilidad)
            $unitIvaCop = round($unitPriceCop * $ivaRate, 2);
            $unitWithIvaCop = round($unitPriceCop + $unitIvaCop, 2);
            $lineSubtotalCop = round($unitPriceCop * $cantidad, 2);
            $lineTotalWithIvaCop = round($unitWithIvaCop * $cantidad, 2);
            $lineIvaCop = round($lineTotalWithIvaCop - $lineSubtotalCop, 2);
            $subtotalCop += $lineSubtotalCop; $ivaTotalCop += $lineIvaCop;

            // Cálculos en moneda original
            $orderCurrency = $orderCurrency ?: $mon;
            $unitIvaOrig = round($priceRaw * $ivaRate, 2);
            $unitWithIvaOrig = round($priceRaw + $unitIvaOrig, 2);
            $lineSubtotalOrig = round($priceRaw * $cantidad, 2);
            $lineTotalWithIvaOrig = round($unitWithIvaOrig * $cantidad, 2);
            $lineIvaOrig = round($lineTotalWithIvaOrig - $lineSubtotalOrig, 2);
            $subtotalOrigin += $lineSubtotalOrig; $ivaTotalOrigin += $lineIvaOrig;

            $items[] = [
                'producto_id' => $producto->id,
                'name_produc' => $producto->name_produc,
                'description_produc' => $producto->description_produc ?? '',
                'unit_produc' => $producto->unit_produc ?? '',
                'po_amount' => $cantidad,
                // COP (no usado en PDF ahora, pero se mantiene)
                'precio_unitario' => $unitPriceCop,
                'iva' => $ivaRate * 100,
                'precio_unitario_con_iva' => $unitWithIvaCop,
                'total_con_iva' => $lineTotalWithIvaCop,
                // Moneda original (usado en PDF)
                'currency' => $mon,
                'unit_price' => $priceRaw,
                'unit_price_con_iva' => $unitWithIvaOrig,
                'line_total_with_iva' => $lineTotalWithIvaOrig,
            ];
        }

        // Distribución por centros
        $distRows = DB::table('ordencompra_centro_producto as ocp')
            ->join('centro as c', 'ocp.centro_id', '=', 'c.id')
            ->select('ocp.producto_id', 'c.name_centro', 'ocp.amount')
            ->where('ocp.orden_compra_id', $orden->id)
            ->get();
        $distribucion = [];
        foreach ($distRows as $r) {
            $distribucion[$r->producto_id][] = [ 'name_centro' => $r->name_centro, 'amount' => (int)$r->amount ];
        }

        $totalOrigin = round($subtotalOrigin + $ivaTotalOrigin, 2);

        return [
            'orden' => $orden,
            'proveedor' => $proveedor,
            'items' => $items,
            'distribucion' => $distribucion,
            // Totales COP (compat)
            'subtotal_cop' => $subtotalCop,
            'iva_total_cop' => $ivaTotalCop,
            // Totales originales (mostrar en PDF)
            'subtotal' => $subtotalOrigin,
            'iva_total' => $ivaTotalOrigin,
            'total' => $totalOrigin,
            'currency' => $orderCurrency ?: 'COP',
            'observaciones' => $orden->observaciones,
            'fecha_actual' => now()->format('d/m/Y H:i'),
            'logo' => $this->resolveLogoDataUri(),
            'date_oc' => ($orden->date_oc ? \Carbon\Carbon::parse($orden->date_oc)->format('d/m/Y') : ($orden->created_at ? $orden->created_at->format('d/m/Y') : now()->format('d/m/Y'))),
            // Traer método y plazo de pago desde el proveedor
            'methods_oc' => $proveedor->methods_oc ?? '',
            'plazo_oc' => $proveedor->plazo_oc ?? '',
        ];
    }

    // Helper para obtener tasa de cambio (servidor) consultando la tabla `trm` primero and no llamar a APIs externas
    private function fetchExchangeRateServer(string $from, string $to = 'COP'){
        try {
            $from = strtoupper(trim($from ?: 'COP'));
            $to = strtoupper(trim($to ?: 'COP'));
            if ($from === $to) return 1;

            // Tabla `trm`:
            // price = unidades de la MONEDA por 1 COP (ej.: USD => 0.000258, EUR => 0.000222, COP => 1)
            // Conversión FROM -> TO: rate = pTo / pFrom
            // Ej.: USD->COP => 1 / 0.000258 ≈ 3876 (porque pTo=COP=1)
            try {
                $rowFrom = DB::table('trm')->where('moneda', $from)->orderByDesc('update_date')->orderByDesc('id')->first();
                $rowTo = DB::table('trm')->where('moneda', $to)->orderByDesc('update_date')->orderByDesc('id')->first();
                if ($rowFrom && $rowTo && isset($rowFrom->price) && isset($rowTo->price)) {
                    $pFrom = (float)$rowFrom->price; // unidades FROM por 1 COP
                    $pTo = (float)$rowTo->price;     // unidades TO por 1 COP
                    if ($pFrom > 0) {
                        return $pTo / $pFrom; // unidades TO por 1 FROM
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('fetchExchangeRateServer: fallo lectura tabla trm: ' . $e->getMessage());
            }
        } catch (\Throwable $e) { /* noop */ }
        return null;
    }

    // Resolver logo como data URI buscando en public/images
    private function resolveLogoDataUri(): ?string
    {
        $candidates = [
            public_path('images/VigiaLogoC.png'),
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $contents = file_get_contents($path);
                $mime = null;
                if (function_exists('mime_content_type')) {
                    $mime = mime_content_type($path);
                }
                if (empty($mime)) {
                    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
                    if ($extension === 'svg') $mime = 'image/svg+xml';
                    elseif ($extension === 'png') $mime = 'image/png';
                    elseif ($extension === 'jpg' || $extension === 'jpeg') $mime = 'image/jpeg';
                    else $mime = 'application/octet-stream';
                }
                return 'data:' . $mime . ';base64,' . base64_encode($contents);
            }
        }
        return asset('images/VigiaLogoC.png');
    }
    // Normaliza cadenas de moneda a códigos ISO (COP, USD, EUR, etc.)
    private function normalizeCurrency(?string $s): string
    {
        if (!$s) return 'COP';
        $s = strtoupper(trim($s));
        // Limpieza básica de símbolos comunes y mapeos
        $map = [
            'US$' => 'USD', 'USD$' => 'USD', 'U$D' => 'USD', 'U$S' => 'USD', '$US' => 'USD', '$U' => 'USD', 'COL$' => 'COP', 'COP$' => 'COP', '€' => 'EUR', 'US DOLLAR' => 'USD', 'DOLLAR' => 'USD', 'DOLAR' => 'USD', 'EURO' => 'EUR', 'PESOS' => 'COP', 'PESO COLOMBIANO' => 'COP', 'PESO COLOMBIA' => 'COP'
        ];
        if (isset($map[$s])) return $map[$s];
        if ($s === '$') return 'USD';
        if (strpos($s, 'USD') !== false) return 'USD';
        if (strpos($s, 'COP') !== false || strpos($s, 'COLOMB') !== false) return 'COP';
        if (strpos($s, 'EUR') !== false) return 'EUR';
        if (preg_match('/^[A-Z]{3}$/', $s)) return $s;
        if (strpos($s, '$') !== false) return 'USD';
        return $s ?: 'COP';
    }

    // Historial público de órdenes de compra
    public function historial()
    {
        $ordenes = OrdenCompra::with([
            'requisicion',
            'ordencompraProductos.producto',
            'ordencompraProductos.proveedor'
        ])->orderBy('id', 'desc')->get();

        return view('ordenes_compra.historial', compact('ordenes'));
    }

    // Helper local para cambiar el estatus de una requisición de forma segura.
    // Desactiva cualquier estatus activo previo (estatus = 1) y crea un nuevo registro activo.
    private function setRequisicionStatus(int $requisicionId, int $estatusId, ?string $comentario = null)
    {
        try {
            // Obtener estatus activo currente (si existe)
            $currentActive = DB::table('estatus_requisicion')
                ->where('requisicion_id', $requisicionId)
                ->where('estatus', 1)
                ->value('estatus_id');

            // Si ya tiene el mismo estatus activo, solo actualizar comentario/fecha si se pidió
            if (!is_null($currentActive) && (int)$currentActive === (int)$estatusId) {
                if (!is_null($comentario)) {
                    DB::table('estatus_requisicion')
                        ->where('requisicion_id', $requisicionId)
                        ->where('estatus', 1)
                        ->update(['comentario' => $comentario, 'date_update' => now(), 'updated_at' => now()]);
                }
                return;
            }

            // Desactivar cualquier estatus activo previo
            DB::table('estatus_requisicion')
                ->where('requisicion_id', $requisicionId)
                ->where('estatus', 1)
                ->update(['estatus' => 0, 'updated_at' => now()]);

            // Intentar reutilizar un registro existente con el mismo estatus_id (incluso soft-deleted)
            try {
                $existing = Estatus_Requisicion::withTrashed()
                    ->where('requisicion_id', $requisicionId)
                    ->where('estatus_id', $estatusId)
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    // Si estaba soft-deleted, restaurarlo
                    if (method_exists($existing, 'trashed') && $existing->trashed()) {
                        try { $existing->restore(); } catch (\Throwable $e) { /* ignore restore errors */ }
                    }

                    $existing->estatus = 1;
                    $existing->comentario = $comentario;
                    $existing->date_update = now();
                    $existing->updated_at = now();
                    $existing->save();
                    return;
                }
            } catch (\Throwable $e) {
                // Si falla la búsqueda con el modelo, continuar intentando insertar (no bloquear)
                Log::warning('setRequisicionStatus: fallo buscando registro existente: ' . $e->getMessage());
            }

            // Insertar nuevo estatus activo si no existe uno reutilizable
            DB::table('estatus_requisicion')->insert([
                'requisicion_id' => $requisicionId,
                'estatus_id' => $estatusId,
                'estatus' => 1,
                'comentario' => $comentario,
                'date_update' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('setRequisicionStatus falló: ' . $e->getMessage());
        }
    }

    /**
     * Confirmar recepción de productos
     */
    public function confirmarRecepcion(Request $request)
    {
        $payload = $request->all();

        // aceptar múltiples items (array) o un solo item
        $items = [];
        if (!empty($payload['items']) && is_array($payload['items'])) {
            $items = $payload['items'];
        } else {
            $items = [$payload];
        }

        // validar al menos 1 item
        if (empty($items)) {
            return response()->json(['message' => 'No hay items para procesar'], 422);
        }

        try {
            DB::beginTransaction();

            $affectedRequisiciones = [];

            foreach ($items as $itIndex => $it) {
                // determinar usuario que realiza la acción (viene en payload o tomarse de session)
                $receptionUser = $it['reception_user'] ?? $this->resolveCurrentUserName($request) ?? (session('user.id') ?? null);

                // normalizar claves
                $recepcionId = isset($it['recepcion_id']) ? (int)$it['recepcion_id'] : null;
                $ordenCompraId = isset($it['orden_compra_id']) ? (int)$it['orden_compra_id'] : null;
                $productoId = isset($it['producto_id']) ? (int)$it['producto_id'] : null;
                $cantidad = isset($it['cantidad']) ? (int)$it['cantidad'] : null; // cantidad total/especificada (OC line)
                $cantidadRec = isset($it['cantidad_recibido']) ? (int)$it['cantidad_recibido'] : null; // cantidad acumulada deseada

                // Determinar ordenCompraId y base cantidad si se pasa recepcion_id
                $baseCantidad = $cantidad;
                $ocIdForCalc = $ordenCompraId;
                if ($recepcionId) {
                    $recBase = DB::table('recepcion')->where('id', $recepcionId)->first();
                    if ($recBase) {
                        $ocIdForCalc = $recBase->orden_compra_id;
                        $baseCantidad = $recBase->cantidad;
                    }
                }

                if (empty($ocIdForCalc) || empty($productoId)) {
                    throw new \Exception('Para crear recepción se requiere orden_compra_id y producto_id');
                }

                // total ya recibido acumulado según la BD
                $totalRecibidoBD = (int) DB::table('recepcion')
                    ->where('orden_compra_id', $ocIdForCalc)
                    ->where('producto_id', $productoId)
                    ->whereNull('deleted_at')
                    ->sum(DB::raw('COALESCE(cantidad_recibido,0)'));

                $desiredTotal = $cantidadRec ?? 0;
                // Si desiredTotal está vacío, asumimos que se quiere recibir el máximo pendiente
                if ($desiredTotal <= 0) {
                    // intentar tomar como total el total OC o la base
                    $desiredTotal = $cantidad ?? $baseCantidad ?? 0;
                }

                $delta = $desiredTotal - $totalRecibidoBD;
                if ($delta <= 0) {
                    // nada que hacer
                    $ocRow = DB::table('orden_compras')->where('id', $ocIdForCalc)->first();
                    if ($ocRow) $affectedRequisiciones[] = (int)$ocRow->requisicion_id;
                    continue;
                }

                // Insertar nuevo registro con la diferencia (histórico)
                $now = now();
                $newId = DB::table('recepcion')->insertGetId([
                    'orden_compra_id' => $ocIdForCalc,
                    'producto_id' => $productoId,
                    'cantidad' => $baseCantidad ?? ($cantidad ?? 0),
                    'cantidad_recibido' => $delta,
                    'reception_user' => $receptionUser,
                    'fecha' => $now->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // Crear/actualizar estatus en orden_compra_estatus: 'Recibido' (estatus id 2 preferido)
                try {
                    // desactivar previos
                    OrdenCompraEstatus::where('orden_compra_id', $ocIdForCalc)->update(['activo' => 0]);
                    $recEstatus = EstatusOrdenCompra::find(2) ?? EstatusOrdenCompra::where('status_name', 'Recibido')->first() ?? EstatusOrdenCompra::first();
                    if ($recEstatus) {
                        OrdenCompraEstatus::create([
                            'estatus_id' => $recEstatus->id,
                            'orden_compra_id' => $ocIdForCalc,
                            'recepcion_id' => $newId,
                            'activo' => 1,
                            'date_update' => now(),
                            'user_name' => session('user.name') ?? $this->resolveCurrentUserName($request),
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('No se pudo crear estatus Recibido para OC '.$ocIdForCalc.': '.$e->getMessage());
                }

                if ($delta > 0) {
                    $producto = Producto::lockForUpdate()->findOrFail($productoId);
                    $producto->stock_produc = (int)$producto->stock_produc + $delta;
                    $producto->save();
                }

                $ocRow = DB::table('orden_compras')->where('id', $ocIdForCalc)->first();
                if ($ocRow) $affectedRequisiciones[] = (int)$ocRow->requisicion_id;
            }

            // actualizar estatus por cada requisición afectada (únicos)
            // Si hay cualquier recepción, mover a estatus 7 (Material recibido)
            $affectedRequisiciones = array_values(array_unique($affectedRequisiciones));
            foreach ($affectedRequisiciones as $reqId) {
                    // Para cada requisición, comprobar si todas las órdenes de compra asociadas están completamente recibidas
                    $ocs = DB::table('orden_compras')->where('requisicion_id', $reqId)->pluck('id');
                    $allComplete = true;
                    foreach ($ocs as $ocId) {
                        $totOrdered = (int) DB::table('ordencompra_producto')
                            ->where('orden_compras_id', $ocId)
                            ->whereNull('deleted_at')
                            ->sum('total');

                        $totReceived = (int) DB::table('recepcion')
                            ->where('orden_compra_id', $ocId)
                            ->whereNull('deleted_at')
                            ->sum(DB::raw('COALESCE(cantidad_recibido,0)'));

                        if ($totOrdered > 0 && $totReceived < $totOrdered) {
                            $allComplete = false;
                            break;
                        }
                    }

                    // Forzar estatus 7 independientemente de si está completa o parcial
                    $desiredStatus = 7;
                    $desiredMessage = 'Recepción registrada';

                    // Comprobar estatus activo currente y solo cambiar si difiere
                    $currentActive = DB::table('estatus_requisicion')
                        ->where('requisicion_id', $reqId)
                        ->where('estatus', 1)
                        ->value('estatus_id');

                    if ((int)$currentActive !== (int)$desiredStatus) {
                        $this->setRequisicionStatus((int)$reqId, $desiredStatus, $desiredMessage);

                        // Enviar email cuando queda en estatus 7 (Material recibido en bodega)
                        try {
                            $requisicion = Requisicion::find($reqId);
                            if ($requisicion && !empty($requisicion->email_user)) {
                                $productNames = DB::table('producto_requisicion as pr')
                                    ->join('productos as p', 'pr.id_producto', '=', 'p.id')
                                    ->where('pr.id_requisicion', $reqId)
                                    ->pluck('p.name_produc')
                                    ->toArray();

                                $lista = !empty($productNames) ? implode(', ', $productNames) : 'Productos disponibles';
                                $subject = "Material recibido en bodega - Requisición #{$reqId}";
                                $viewData = [
                                    'requisicion' => $requisicion,
                                    'productos' => $productNames,
                                    'lista' => $lista,
                                ];

                                Mail::send('emails.requisicion_material_recibido', $viewData, function ($message) use ($requisicion, $subject) {
                                    $message->to($requisicion->email_user)->subject($subject);
                                });
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Error enviando notificación por estatus 7 para requisicion ' . $reqId . ': ' . $e->getMessage());
                        }
                    }
                 
             }
            DB::commit();
            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function completarSiListo(Request $request)
    {
        $data = $request->validate([
            'requisicion_id' => 'required|exists:requisicion,id',
        ]);
        try {
            $reqId = (int)$data['requisicion_id'];
            $complete = $this->isRequisitionComplete($reqId);
            if ($complete) {
                $this->setRequisicionStatus($reqId, 10, 'Requisición completada automáticamente');
                return response()->json(['ok' => true, 'estatus' => 10]);
            }
            return response()->json(['ok' => false, 'message' => 'Aún no está completa'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function isRequisitionComplete(int $requisicionId): bool
    {
        // Requerido por producto según centros distribuidos
        $reqPorProducto = DB::table('centro_producto')
            ->where('requisicion_id', $requisicionId)
            ->select('producto_id', DB::raw('SUM(amount) as req'))
            ->groupBy('producto_id')
            ->pluck('req', 'producto_id');

        // Fallback: si no hay distribución por centros, usar producto_requisicion
        if ($reqPorProducto->isEmpty()) {
           
            $reqPorProducto = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                ->groupBy('id_producto')
                ->pluck('req', 'producto_id');
        }
        if ($reqPorProducto->isEmpty()) return false;

        // Recibido por coordinador (entregas confirmadas)
        $recEnt = DB::table('entrega')
            ->where('requisicion_id', $requisicionId)
            ->whereNull('deleted_at')
            ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as rec'))
            ->groupBy('producto_id')
            ->pluck('rec', 'producto_id');

        // Recibido desde stock (confirmado)
        $recStock = DB::table('recepcion as r')
            ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
            ->where('oc.requisicion_id', $requisicionId)
            ->whereNull('r.deleted_at')
            ->select('r.producto_id', DB::raw('SUM(COALESCE(r.cantidad_recibido,0)) as rec'))
            ->groupBy('r.producto_id')
            ->pluck('rec', 'producto_id');

        foreach ($reqPorProducto as $pid => $req) {
            $recibido = (int)($recEnt[$pid] ?? 0) + (int)($recStock[$pid] ?? 0);
            if ($recibido < (int)$req) return false;
        }
        return true;
    }

    public function storeSalidaStockEnEntrega(Request $request)
    {
        $data = $request->validate([
            'requisicion_id' => 'required|exists:requisicion,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);
        try {
            DB::beginTransaction();

            // No permitir segunda salida del mismo producto para esta requisición
            $yaExiste = DB::table('entrega')
                ->where('requisicion_id', (int)$data['requisicion_id'])
                ->where('producto_id', (int)$data['producto_id'])
                ->whereNull('deleted_at')
                ->exists();
            if ($yaExiste) {
                throw new \Exception('Ya existe una salida registrada para este producto en esta requisición');
            }

            $cantidad = (int)$data['cantidad'];

            // Verificar disponibilidad actual de stock pero NO descontar ahora; la resta ocurrirá cuando el usuario confirme (cantidad_recibido)
            $stockActual = (int) Producto::where('id', (int)$data['producto_id'])->value('stock_produc') ?? 0;
            if ($cantidad > $stockActual) {
                throw new \Exception('Stock insuficiente para realizar la salida');
            }

            // Registrar en tabla entrega (pendiente de confirmación)
            DB::table('entrega')->insert([
                'requisicion_id' => (int)$data['requisicion_id'],
                'producto_id' => (int)$data['producto_id'],
                'cantidad' => $cantidad,
                'cantidad_recibido' => null,
                'fecha' => now()->toDateString(),
                'user_name' => session('user.name') ?? $this->resolveCurrentUserName($request),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Estatus   12: movimiento parcial registrado (pendiente de confirmación)
            $this->setRequisicionStatus((int)$data['requisicion_id'], 12, 'Salida de stock registrada');

            DB::commit();

            // Enviar correo de entrega registrada (sin requerir queue worker)
            try {
                $requisicion = Requisicion::find((int)$data['requisicion_id']);
                $producto = Producto::find((int)$data['producto_id']);
                if ($requisicion && $producto) {
                    $items = [[ 'id' => (int)$producto->id, 'nombre' => (string)$producto->name_produc, 'cantidad' => (int)$cantidad ]];
                    // Ejecutar de forma sincrónica para garantizar envío inmediato
                    RequisicionEntregaRegistradaJob::dispatchSync($requisicion, $items);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo enviar correo de entrega registrada: ' . $e->getMessage());
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    // Obtener nombre de usuario actual desde session, headers, request o Auth
    private function resolveCurrentUserName(?\Illuminate\Http\Request $request = null): ?string
    {
        try {
            $userSession = session('user');
            if (is_array($userSession) && !empty($userSession['name'])) return (string)$userSession['name'];
            if (is_object($userSession) && isset($userSession->name) && $userSession->name) return (string)$userSession->name;
            if (session()->has('user.name') && session('user.name')) return (string)session('user.name');
            if (session()->has('user.email') && session('user.email')) return (string)session('user.email');

            $req = $request ?? request();
            $fromHeader = $req->header('X-User-Name') ?: $req->header('X-User-Email') ?: $req->input('user.name') ?: $req->input('user.email');
            if (!empty($fromHeader)) return (string)$fromHeader;

            if (class_exists(\Illuminate\Support\Facades\Auth::class)) {
                $authUser = \Illuminate\Support\Facades\Auth::user();
                if ($authUser) {
                    if (!empty($authUser->name)) return (string)$authUser->name;
                    if (!empty($authUser->email)) return (string)$authUser->email;
                }
            }
            return null;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('resolveCurrentUserName fallo: ' . $e->getMessage());
            return null;
        }
    }

    // Parse localized numeric strings like "1.234.567,89" or "1,234,567.89" into float, return null if not parseable
    private function parseLocalizedNumber($value): ?float
    {
        if ($value === null || $value === '') return null;
        if (is_numeric($value)) return (float)$value;
        $s = (string)$value;
        // keep digits, dot, comma, minus
        $s = trim(preg_replace('/[^0-9,\.\-]/u', '', $s));
        if ($s === '' || $s === '-' || $s === '. ' ) return null;
        // If contains both dot and comma, assume dot thousands and comma decimal
        if (strpos($s, '.') !== false && strpos($s, ',') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') !== false && strpos($s, '.') === false) {
            // single comma as decimal separator
            $s = str_replace(',', '.', $s);
        } else {
            // multiple dots posiblemente separadores de miles -> eliminar si hay más de uno
            if (substr_count($s, '.') > 1) {
                $s = str_replace('.', '', $s);
            }
        }
        // limpieza final
        if ($s === '' || $s === '-' ) return null;
        $num = floatval($s);
        return is_nan($num) ? null : $num;
    }

    public function updateBasicos(Request $request, $id)
    {
        $data = $request->validate([
            'date_oc' => 'required|date|after_or_equal:today',
            'observaciones' => 'nullable|string',
        ]);
        try {
            $orden = \App\Models\OrdenCompra::findOrFail($id);
            $orden->date_oc = $data['date_oc'];
            $orden->observaciones = $data['observaciones'] ?? null;
            $orden->save();
            return redirect()->back()->with('success', 'Orden actualizada correctamente.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('updateBasicos error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    private function storeOrderPdfHash(int $ordenId, string $hash): void
    {
        try {
            DB::table('orden_hash')->insert([
                'orden_compra_id' => $ordenId,
                'validation_hash' => strtolower($hash),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo registrar hash para OC ' . $ordenId . ': ' . $e->getMessage());
        }
    }

    public function actualizarPreciosFactura(Request $request)
    {
        $data = $request->all();
        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'orden_compra_id' => 'required|integer|exists:orden_compras,id',
            'items' => 'required|array|min:1',
            'items.*.ocp_id' => 'required|integer|exists:ordencompra_producto,id',
            'items.*.precio_factura' => ['required','numeric','min:0','regex:/^\d+(?:\.\d{1,2})?$/'],
            'items.*.trm_factura' => ['nullable','numeric','min:0','regex:/^\d+(?:\.\d{1,2})?$/'],
        ], [
            'items.*.precio_factura.regex' => 'El precio de factura debe tener máximo 2 decimales.',
            'items.*.trm_factura.regex' => 'La TRM debe tener máximo 2 decimales.',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $ocId = (int)$data['orden_compra_id'];
        $items = $data['items'];

        DB::beginTransaction();
        try {
            $updated = 0; $locked = 0; $skipped = [];
            $userName = session('user.name') ?? $this->resolveCurrentUserName($request) ?? (session('user.email') ?? session('user.id') ?? null);
            foreach ($items as $it) {
                $line = OrdenCompraProducto::where('id', (int)$it['ocp_id'])
                    ->where('orden_compras_id', $ocId)
                    ->lockForUpdate()
                    ->first();
                if (!$line) { $skipped[] = (int)$it['ocp_id']; continue; }

                $pf = round((float)$it['precio_factura'], 2);
                $trm = (array_key_exists('trm_factura', $it) && $it['trm_factura'] !== null && $it['trm_factura'] !== '')
                    ? round((float)$it['trm_factura'], 2) : null;

                // Permitir actualizar siempre
                $line->precio_factura = $pf;
                if ($trm !== null) { $line->trm_factura = $trm; }
                else if ($line->trm_factura === null) { /* no-op */ }
                $line->save();

                // Log por cada campo en tabla genérica 'logs'
                try {
                    DB::table('logs')->insert([
                        'table_name' => 'ordencompra_producto',
                        'ordencompra_producto_id' => (int)$line->id,
                        'user_name' => $userName,
                        'field_name' => 'precio_factura',
                        'new_value' => number_format($pf, 2, '.', ''),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    if ($trm !== null) {
                        DB::table('logs')->insert([
                            'table_name' => 'ordencompra_producto',
                            'ordencompra_producto_id' => (int)$line->id,
                            'user_name' => $userName,
                            'field_name' => 'trm_factura',
                            'new_value' => number_format($trm, 2, '.', ''),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                } catch (\Throwable $e) { /* noop logging */ }

                $updated++;
            }
            DB::commit();

            if ($updated === 0) {
                return response()->json(['ok' => false, 'updated' => 0, 'locked' => 0, 'skipped' => $skipped, 'message' => 'No se actualizaron líneas.'], 200);
            }
            return response()->json(['ok' => true, 'updated' => $updated, 'locked' => 0, 'skipped' => $skipped]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function setProductRequisitionPxpId(int $requisicionId, int $productoId, ?int $proveedorId = null): ?int
    {
        try {
            $current = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->value('id_productoxproveedor');

            $targetPxpId = null;
            if ($proveedorId) {
                $targetPxpId = DB::table('productoxproveedor')
                    ->where('producto_id', $productoId)
                    ->where('proveedor_id', $proveedorId)
                    ->orderBy('id')
                    ->value('id');
            }
            if (!$targetPxpId) {
                $rows = DB::table('productoxproveedor')
                    ->where('producto_id', $productoId)
                    ->orderBy('id')
                    ->pluck('id');
                if ($rows->count() === 1) { $targetPxpId = (int)$rows->first(); }
            }
            if ($targetPxpId && ((int)$current !== (int)$targetPxpId)) {
                DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicionId)
                    ->where('id_producto', $productoId)
                    ->update(['id_productoxproveedor' => (int)$targetPxpId]);
                return (int)$targetPxpId;
            }
            return $current ? (int)$current : null;
        } catch (\Throwable $e) {
            Log::warning('No se pudo fijar id_productoxproveedor: '.$e->getMessage());
        }
        return null;
    }

    private function syncPxpIdsForRequisition(int $requisicionId): void
    {
        // Para cada producto en la requisición, si hay proveedor definido en alguna línea OC, fijar pxp
        $pairs = DB::table('ordencompra_producto')
            ->where('requisicion_id', $requisicionId)
            ->whereNull('deleted_at')
            ->select('producto_id', 'proveedor_id')
            ->whereNotNull('proveedor_id')
            ->groupBy('producto_id', 'proveedor_id')
            ->get();
        foreach ($pairs as $p) {
            try { $this->setProductRequisitionPxpId($requisicionId, (int)$p->producto_id, (int)$p->proveedor_id); } catch (\Throwable $e) { /* noop */ }
        }
        // Además, para productos sin líneas OC pero con un único pxp, fijarlo
        $prodIds = DB::table('producto_requisicion')
            ->where('id_requisicion', $requisicionId)
            ->pluck('id_producto');
        foreach ($prodIds as $pid) {
            try { $this->setProductRequisitionPxpId($requisicionId, (int)$pid, null); } catch (\Throwable $e) { /* noop */ }
        }
    }
}