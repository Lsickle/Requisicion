<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionHelper;
use App\Jobs\EstatusRequisicionActualizadoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Mail;
use App\Mail\EstatusRequisicionActualizado as EstatusRequisicionMail;
use App\Jobs\NotificarAprobacionEtapaJob; 
use App\Models\Productoxproveedor;
use \App\Http\Controllers\requisicion\RequisicionController;

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        // Requiere permiso aprobar requisicion
        if (!PermissionHelper::hasPermission('aprobar requisicion')) {
            return redirect()->route('index')->with('error', 'Debes iniciar sesión o no tienes permisos suficientes.');
        }

        // Roles de usuario desde sesión (helper los mantiene crudos)
        $userRoles = PermissionHelper::getUserRoles();
        $normalize = function($txt){
            $txt = mb_strtolower(trim($txt ?? ''), 'UTF-8');
            return strtr($txt,[
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u','ñ'=>'n'
            ]);
        };
        $userRolesNorm = array_map($normalize, $userRoles);

        // Mapa operación => rol requerido
        $operacionRoleMap = [
            // Originales
            'Cedi Frio' => 'Gerente operaciones',
            'Cedi Frio - Mantenimiento' => 'Gerente operaciones',
            'Mary Kay' => 'Gerente operaciones',
            'Oriflame' => 'Gerente operaciones',
            'Sony' => 'Gerente operaciones',
            'Macmillan' => 'Gerente operaciones',
            'Kw' => 'Gerente operaciones',
            'Tranpsrtes Vigia' => 'Gerente operaciones',
            'Cumbria' => 'Gerente operaciones',
            'Ortopedicos Futuro' => 'Gerente operaciones',
            'Naos' => 'Gerente operaciones',
            'Mattel' => 'Gerente operaciones',
            'Huawei' => 'Gerente operaciones',
            'Cedi Frio Agrofrut' => 'Gerente operaciones',
            'Cedi Frio Kikes' => 'Gerente operaciones',
            'Cedi Frio La Fazenda' => 'Gerente operaciones',
            'Cedi Frio Calypso' => 'Gerente operaciones',
            'Cedi Frio Ibazan' => 'Gerente operaciones',
            'Cedi Frio Todos Comemos' => 'Gerente operaciones',
            'Cedi Frio Food Box' => 'Gerente operaciones',
            'Inventarios' => 'Gerente operaciones',
            'Transportes' => 'Gerente operaciones',
            'Mejoramiento Contínuo' => 'Gerente operaciones',
            // Aliases / variantes sin acentos o con diferencias de mayúsculas/typos
            'Cedi frio' => 'Gerente operaciones',
            'Cei frtio - Mantenimiento' => 'Gerente operaciones', // typo reportado
            'Mejoramiento Continuo' => 'Gerente operaciones',

            // Otros roles
            'Seguridad' => 'Director de proyectos',
            'HSEQ' => 'Director de proyectos',
            'Calidad' => 'Director de proyectos',
            'Compras' => 'Director de proyectos',

            'Talento Humano' => 'Gerente talento humano',
            'Financiera' => 'Director contable',
        ];

        // Roles por etapas (estatus 2 y 3) y bandera para área de compras
        $stage2Roles = ['director de proyectos'];
        $stage3Roles = ['director contable'];
        $hasAdmin = in_array('admin requisicion', $userRolesNorm, true);
        $hasAreaCompras = in_array('area de compras', $userRolesNorm, true);
        $hasGerenteFinanciero = in_array('gerente financiero', $userRolesNorm, true);
        $rolesEstatus2 = ['gerente operaciones','gerente talento humano','director contable','director de proyectos','gerente financiero','admin requisicion'];
        $rolesEstatus3 = ['director contable','gerente financiero','admin requisicion'];
        $watchStatuses = [];
        if ($hasAdmin) { $watchStatuses = [1,2,3]; }
        else {
            if ($hasAreaCompras) { $watchStatuses[] = 1; }
            if (count(array_intersect($userRolesNorm, $rolesEstatus2))>0) { $watchStatuses[] = 2; }
            if (count(array_intersect($userRolesNorm, $rolesEstatus3))>0) { $watchStatuses[] = 3; }
        }
        $watchStatuses = array_values(array_unique($watchStatuses));
        if ($hasAreaCompras && !$hasAdmin && $watchStatuses === [1]) { $watchStatuses = [1]; }
        if (empty($watchStatuses)) {
            $requisiciones = collect(); $estatusOptions = collect();
            return view('requisiciones.aprobacion',[ 'requisiciones'=>$requisiciones,'requisicionesFiltradas'=>$requisiciones,'estatusOptions'=>$estatusOptions ]);
        }

        // Obtener requisiciones en las etapas relevantes
        $requisiciones = Requisicion::with(['ultimoEstatus.estatusRelation','productos','estatusHistorial.estatusRelation'])
            ->whereHas('ultimoEstatus', fn($q)=> $q->whereIn('estatus_id',$watchStatuses))
            ->orderBy('created_at','desc')->get();

        // Filtrar por operaciones que corresponden al rol del usuario
        $requisicionesFiltradas = $requisiciones->filter(function($req) use ($hasAreaCompras,$userRolesNorm,$operacionRoleMap,$hasGerenteFinanciero,$hasAdmin){
            if ($hasAdmin) return true; // Admin ve todas
            $estatusActual = optional($req->ultimoEstatus)->estatus_id;
            if ($estatusActual==1) return $hasAreaCompras; // sólo Área de compras
            if ($estatusActual==2) {
                $op = $req->operacion_user; if(!$op || !isset($operacionRoleMap[$op])) return false; $rolReq = mb_strtolower($operacionRoleMap[$op],'UTF-8');
                return in_array($rolReq,$userRolesNorm,true);
            }
            if ($estatusActual==3) {
                if ($hasGerenteFinanciero) return true;
                $op = $req->operacion_user; if(!$op || !isset($operacionRoleMap[$op])) return false; $rolReq = mb_strtolower($operacionRoleMap[$op],'UTF-8');
                return in_array('director contable',$userRolesNorm,true) && ($rolReq==='director contable');
            }
            return false;
        })->values();

        // Cargar proveedores (productoxproveedor) y datos derivados por cada producto en cada requisición
        foreach ($requisicionesFiltradas as $req) {
            foreach ($req->productos as $prod) {
                try {
                    $provList = DB::table('productoxproveedor as pxp')
                        ->join('proveedores as prov','pxp.proveedor_id','=','prov.id')
                        ->where('pxp.producto_id', $prod->id)
                        ->whereNull('pxp.deleted_at')
                        ->select('pxp.id as pxp_id','prov.id','prov.prov_name','pxp.price_produc','pxp.moneda')
                        ->orderBy('prov.prov_name')
                        ->get();
                } catch (\Throwable $e) { $provList = collect(); }

                // usar método local de conversión a COP
                $self = $this;
                $provJson = $provList->map(function($pv) use ($self) {
                    $priceCop = $self->convertToCop($pv->price_produc ?? 0, $pv->moneda ?? 'COP');
                    return [
                        'id' => $pv->id ?? null,
                        'prov_name' => $pv->prov_name ?? null,
                        'price_produc' => (float)($pv->price_produc ?? 0),
                        'moneda' => strtoupper(trim($pv->moneda ?? 'COP')),
                        'price_cop' => $priceCop,
                        'pxp_id' => $pv->pxp_id ?? null,
                    ];
                });

                $pivotPxpId = $prod->pivot->id_productoxproveedor ?? DB::table('producto_requisicion')
                    ->where('id_requisicion', $req->id)
                    ->where('id_producto', $prod->id)
                    ->value('id_productoxproveedor');
                $selProv = $pivotPxpId ? $provList->firstWhere('pxp_id', $pivotPxpId) : null;
                $selProvId = $selProv->id ?? ($prod->pivot->proveedor_id ?? $prod->pivot->prov_id ?? $prod->proveedor_id ?? null);
                $selPrice = isset($selProv) ? (float)($selProv->price_produc ?? 0) : ($prod->pivot->price_produc ?? $prod->pivot->price ?? $prod->price_produc ?? 0);
                $selProvName = $selProv->prov_name ?? null;
                // moneda del precio seleccionado (original)
                $selCurrency = null;
                if ($selProv && isset($selProv->moneda)) { $selCurrency = strtoupper(trim($selProv->moneda)); }
                elseif (isset($prod->pivot->moneda)) { $selCurrency = strtoupper(trim($prod->pivot->moneda)); }
                else { $selCurrency = 'COP'; }
                // precio convertido a COP (usado para cálculos totales)
                $selPriceCop = $this->convertToCop($selPrice, $selCurrency);

                $distribucion = DB::table('centro_producto')
                    ->where('requisicion_id', $req->id)
                    ->where('producto_id', $prod->id)
                    ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                    ->select('centro.name_centro', 'centro_producto.amount')
                    ->get();

                // Adjuntar propiedades que la vista usaba directamente
                $prod->provList = $provList;
                $prod->provJson = $provJson;
                $prod->pivotPxpId = $pivotPxpId;
                $prod->selProvId = $selProvId;
                $prod->selPrice = $selPrice;
                $prod->selCurrency = $selCurrency;
                $prod->selPriceCop = $selPriceCop;
                $prod->selProvName = $selProvName;
                $prod->distribucion = $distribucion;
            }
        }

        // Opciones (se mantienen para compatibilidad; la vista ahora calcula por requisición)
        $nextOptions = collect();
        if ($hasAreaCompras) { $nextOptions = Estatus::whereIn('id',[2,9])->pluck('status_name','id'); }
        elseif (in_array(2,$watchStatuses)) { $nextOptions = Estatus::whereIn('id',[3,9])->pluck('status_name','id'); }
        if (in_array(3,$watchStatuses)) { // sobrescribe para etapa 3 cuando aplica (gerente financiero o director contable)
            $nextOptions = Estatus::whereIn('id',[4,9])->pluck('status_name','id');
        }

        Log::info('Aprobacion index debug', [ 'watch_statuses'=>$watchStatuses,'filtered_reqs'=>$requisicionesFiltradas->count(), 'admin'=>$hasAdmin ]);
        return view('requisiciones.aprobacion',[ 'requisiciones'=>$requisiciones,'requisicionesFiltradas'=>$requisicionesFiltradas,'estatusOptions'=>$nextOptions ]);
    }

    public function show($id)
    {
        $requisicion = Requisicion::with('estatus')->findOrFail($id);
        $estatusOrdenados = $requisicion->estatus->sortBy('pivot.created_at');
        $estatusActual = $estatusOrdenados->last();

        return view('requisiciones.estatus', compact('requisicion', 'estatusOrdenados', 'estatusActual'));
    }

    public function updateStatus(Request $request, $requisicionId)
    {
        $allowedRoles = [ 'Area de compras','Gerente operaciones','Gerente talento humano','Director de proyectos','Director contable','Gerente financiero','Admin requisicion' ];
        if (!PermissionHelper::hasAnyRole($allowedRoles) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return response()->json(['success'=>false,'message'=>'Debes iniciar sesión o no tienes permisos.'],403);
        }

        $request->validate(['estatus_id'=>'required|exists:estatus,id']);

        try {
            DB::beginTransaction();
            $requisicion = Requisicion::with('ultimoEstatus')->findOrFail($requisicionId);
            $currentStatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            $targetStatus = (int)$request->estatus_id;
            $opNombre = mb_strtolower(trim($requisicion->operacion_user ?? ''), 'UTF-8');
            $opNombre = strtr($opNombre, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
            $operacionEspecial = in_array($opNombre, ['tecnologia','compras'], true);

            // Si no cambia
            if ($currentStatus === $targetStatus) {
                DB::rollBack();
                return response()->json(['success'=>true,'message'=>'El estatus ya está en el valor solicitado. Sin cambios.']);
            }

            // Obtener roles del usuario
            $userRoles = PermissionHelper::getUserRoles();
            $rolesLower = array_map(fn($r)=> mb_strtolower($r,'UTF-8'), $userRoles);
            $hasRole = fn($name)=> in_array(mb_strtolower($name,'UTF-8'), $rolesLower, true);
            $hasAdmin = $hasRole('Admin requisicion');

            // NUEVO: asegurar id_productoxproveedor desde servidor si falta (evita depender de la vista)
            try {
                foreach ($requisicion->productos as $prod) {
                    $productoId = $prod->id;
                    $existing = DB::table('producto_requisicion')
                        ->where('id_requisicion', $requisicionId)
                        ->where('id_producto', $productoId)
                        ->value('id_productoxproveedor');
                    if (!empty($existing)) {
                        continue;
                    }

                    // 1) intentar por proveedor en pivot
                    $pivotProvId = $prod->pivot->proveedor_id ?? null;
                    $foundPxp = null;
                    if ($pivotProvId) {
                        $foundPxp = DB::table('productoxproveedor')
                            ->where('producto_id', $productoId)
                            ->where('proveedor_id', $pivotProvId)
                            ->whereNull('deleted_at')
                            ->value('id');
                    }

                    // 2) si no, si sólo existe una fila productoxproveedor para el producto usarla
                    if (!$foundPxp) {
                        $rows = DB::table('productoxproveedor')
                            ->where('producto_id', $productoId)
                            ->whereNull('deleted_at')
                            ->pluck('id');
                        if ($rows->count() === 1) { $foundPxp = (int) $rows->first(); }
                    }

                    // 3) si se encontró, asegurar pivot
                    if ($foundPxp) {
                        try { $this->ensurePivotPxp($requisicionId, $productoId, (int)$foundPxp); } catch (\Throwable $e) { Log::warning('auto-assign pxp failed', ['req'=>$requisicionId,'prod'=>$productoId,'err'=>$e->getMessage()]); }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('auto-assign pxp loop failed', ['req'=>$requisicionId,'err'=>$e->getMessage()]);
            }

            // Si el frontend envío proveedores seleccionados (pxp_id), procesarlos y asegurar id_productoxproveedor en producto_requisicion
            $proveedores = $request->input('proveedores', []);
            if (is_array($proveedores) && count($proveedores) > 0) {
                foreach ($proveedores as $p) {
                    $productoId = isset($p['producto_id']) ? (int)$p['producto_id'] : null;
                    if (!$productoId) continue;
                    $pxpIdProvided = isset($p['pxp_id']) && is_numeric($p['pxp_id']) ? (int)$p['pxp_id'] : null;
                    $proveedorId = isset($p['proveedor_id']) && is_numeric($p['proveedor_id']) ? (int)$p['proveedor_id'] : null;
                    $price = isset($p['price']) && is_numeric($p['price']) ? (float)$p['price'] : null;
                    $currency = isset($p['currency']) ? strtoupper(trim($p['currency'])) : null;

                    try {
                        if ($pxpIdProvided) {
                            $pxp = Productoxproveedor::find($pxpIdProvided);
                            if ($pxp) {
                                $changed = false;
                                if ($price !== null && $pxp->price_produc != $price) { $pxp->price_produc = $price; $changed = true; }
                                if (!empty($currency) && ($pxp->moneda ?? '') !== $currency) { $pxp->moneda = $currency; $changed = true; }
                                if ($changed) $pxp->save();
                                // asegurar pivot
                                $this->ensurePivotPxp($requisicionId, $productoId, $pxp->id);
                                continue;
                            }
                        }

                        // Si no vino pxp_id, crear o actualizar productoxproveedor por producto+proveedor
                        if (!$proveedorId) continue;
                        $pxp = Productoxproveedor::where('producto_id', $productoId)->where('proveedor_id', $proveedorId)->first();
                        if (!$pxp) {
                            $pxp = Productoxproveedor::create([
                                'producto_id' => $productoId,
                                'proveedor_id' => $proveedorId,
                                'price_produc' => $price ?? 0,
                                'moneda' => $currency ?? 'COP',
                            ]);
                        } else {
                            $updated = false;
                            if ($price !== null && $pxp->price_produc != $price) { $pxp->price_produc = $price; $updated = true; }
                            if (!empty($currency) && ($pxp->moneda ?? '') !== $currency) { $pxp->moneda = $currency; $updated = true; }
                            if ($updated) $pxp->save();
                        }

                        // asegurar pivot en producto_requisicion
                        $this->ensurePivotPxp($requisicionId, $productoId, $pxp->id);
                    } catch (\Throwable $e) {
                        Log::warning('estatus.updateStatus ensureProveedor failed', ['req'=>$requisicionId,'prod'=>$productoId,'err'=>$e->getMessage()]);
                    }
                }
            }

            // Desactivar históricos
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus'=>0]);

            $comentario = $request->comentario ? trim($request->comentario) : null;
            $nuevoEstatus = null;
            $mensajeAccion = 'aprobada';

            if ($targetStatus == 9) { // Rechazo según etapa
                if ($currentStatus == 1) { // a corrección (11)
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>11,
                        'estatus'=>1,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'enviada a corrección';
                } elseif ($currentStatus == 2) { // registrar 13 histórico y 10 finalizada
                    Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>13,
                        'estatus'=>0,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>10,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'rechazada';
                } elseif ($currentStatus == 3) { // registrar 9 histórico y 10 finalizada
                    Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>9,
                        'estatus'=>0,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>10,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'rechazada';
                }
            } else {
                // Aprobación con paso intermedio para operaciones especiales cuando se aprueba a 3 desde 1
                if ($currentStatus == 1 && $operacionEspecial && $targetStatus == 3) {
                    // Insertar estatus 2 como histórico para trazabilidad
                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicionId,
                        'estatus_id'     => 2,
                        'estatus'        => 0,
                        'comentario'     => null,
                        'date_update'    => now(),
                        'user_id'        => session('user.id')
                    ]);
                    // Insertar estatus 3 como activo
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>3,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                } else {
                    // Aprobación directa al estatus solicitado (2->3, 3->4, 1->2)
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>$targetStatus,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                }
                if ($targetStatus == 4) {
                    Log::info("Aprobación final (estatus 4) requisición {$requisicionId}");
                }

                // Notificar por la etapa final aplicada (si hubo intermedio, es 3)
                $this->notificarPorEtapa($requisicion, $nuevoEstatus, ($currentStatus == 1 && $operacionEspecial && $targetStatus == 3) ? 3 : $targetStatus);
            }

            // Notificación correo (igual anterior)
            try {
                $userEmail = $requisicion->email_user ?? null;
                if (!$userEmail) {
                    $info = $this->obtenerInformacionUsuario($requisicion->user_id);
                    $userEmail = $info['email'] ?? null;
                }
                if ($userEmail) {
                    if (empty($nuevoEstatus->user_id) && session('user.id')) $nuevoEstatus->user_id = session('user.id');
                    Mail::to($userEmail)->send(new EstatusRequisicionMail($requisicion, $nuevoEstatus));
                } else {
                    EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, null);
                }
            } catch (\Exception $e) {
                Log::error('Error correo updateStatus: '.$e->getMessage());
                try { EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, $requisicion->email_user ?? null); } catch(\Exception $e2) {}
            }

            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Requisición '.$mensajeAccion.' correctamente',
                'nuevo_estatus'=> optional($nuevoEstatus->estatusRelation)->status_name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR CRÍTICO al actualizar estatus: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Error interno del servidor al actualizar el estatus: '.$e->getMessage()],500);
        }
    }

    /**
     * Asegura que la tabla producto_requisicion tenga id_productoxproveedor para una pareja requisicion-producto.
     * Intenta actualizar; si no existe la fila, inserta una nueva con pr_amount inferido.
     */
    private function ensurePivotPxp(int $requisicionId, int $productoId, int $pxpId): bool
    {
        try {
            $affected = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->where('id_producto', $productoId)
                ->update(['id_productoxproveedor' => $pxpId, 'deleted_at' => null, 'updated_at' => now()]);
            if ($affected > 0) return true;

            // Si no existía, intentar insertar la fila
            $prAmount = (int) DB::table('centro_producto')
                ->where('requisicion_id', $requisicionId)
                ->where('producto_id', $productoId)
                ->sum('amount');
            if ($prAmount <= 0) {
                $prev = DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicionId)
                    ->where('id_producto', $productoId)
                    ->value('pr_amount');
                $prAmount = (int) ($prev ?? 0);
            }
            DB::table('producto_requisicion')->insert([
                'id_producto' => $productoId,
                'id_requisicion' => $requisicionId,
                'id_productoxproveedor' => $pxpId,
                'pr_amount' => max(0, $prAmount),
                'created_at' => now(),
                'updated_at' => now(),
                'deleted_at' => null,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('ensurePivotPxp failed (estatus controller)', ['req'=>$requisicionId,'prod'=>$productoId,'pxp'=>$pxpId,'err'=>$e->getMessage()]);
            return false;
        }
    }

    private function obtenerInformacionUsuario($userId)
    {
        try {
            $apiToken = session('api_token');
            if (!$apiToken) {
                return ['email' => null];
            }

            $possibleEndpoints = [
                env('VPL_CORE') . "/api/user/{$userId}",
                env('VPL_CORE') . "/api/users/{$userId}",
                env('VPL_CORE') . "/api/auth/user/{$userId}",
            ];

            foreach ($possibleEndpoints as $apiUrl) {
                $response = Http::withoutVerifying()
                    ->withToken($apiToken)
                    ->timeout(10)
                    ->get($apiUrl);

                if ($response->successful()) {
                    $userData = $response->json();
                    $email = $userData['email'] ??
                        $userData['user']['email'] ??
                        ($userData['data']['email'] ?? null);

                    if ($email) {
                        return ['email' => $email];
                    }
                }
            }

            return ['email' => null];
        } catch (\Throwable $e) {
            return ['email' => null];
        }
    }

    // NUEVO: Notificar por etapa destino y operación (stage2, stage3, final)
    private function notificarPorEtapa(Requisicion $requisicion, Estatus_Requisicion $nuevoEstatus, int $targetStatus): void
    {
        try {
            $stageKey = null;
            if ($targetStatus === 2) { $stageKey = 'stage2'; }
            elseif ($targetStatus === 3) { $stageKey = 'stage3'; }
            elseif ($targetStatus === 4) { $stageKey = 'final'; }
            if (!$stageKey) { return; }

            // Determinar rol objetivo para stage2 según operación (normalizado)
            $roleTarget = null;
            if ($stageKey === 'stage2') {
                $roleTarget = $this->getRoleTargetForOperation($requisicion->operacion_user);
            }

            $destinatarios = $this->recipientsByOperation($requisicion->operacion_user, $stageKey, $roleTarget);
            if (empty($destinatarios)) { return; }

            $id = $requisicion->id;
            $op = $requisicion->operacion_user ?? 'N/A';
            $solicitante = $requisicion->name_user ?? 'N/A';
            $prioridad = ucfirst($requisicion->prioridad_requisicion ?? '');
            $detalleUrl = route('requisiciones.show', $id);
            $panelUrl = url('/requisiciones/aprobacion');

            if ($stageKey === 'stage2') {
                $subject = "Nueva requisición #{$id} pendiente de aprobación ({$op})";
                $mensajePrincipal = "Se ha creado la requisición #{$id}. Ingresa a VPL-Compras para su aprobación.";
            } else {
                $etiqueta = [ 'stage2' => 'Etapa 2', 'stage3' => 'Etapa 3', 'final' => 'Aprobación Final' ][$stageKey] ?? 'Aprobación';
                $subject = "Requisición #{$id} aprobada - {$etiqueta} ({$op})";
                $mensajePrincipal = "La requisición #{$id} ha avanzado a {$etiqueta}.";
            }

            NotificarAprobacionEtapaJob::dispatch(
                $requisicion,
                $nuevoEstatus,
                $stageKey,
                $destinatarios,
                $subject,
                $mensajePrincipal,
                $panelUrl,
                $detalleUrl
            );
        } catch (\Throwable $e) {
            Log::error('Error al preparar correo por etapa: '.$e->getMessage());
        }
    }

    // NUEVO: Devolver el rol objetivo (normalizado) para una operación (normalizada), usando el mapa existente
    private function getRoleTargetForOperation(?string $operacion): ?string
    {
        $map = $this->getOperacionRoleMap();
        // construir mapa con claves normalizadas y valores (roles) también normalizados
        $normalized = [];
        foreach ($map as $opName => $roleName) {
            $normalized[$this->normalizeOperacionKey($opName)] = $this->normalizeOperacionKey($roleName);
        }
        $opKey = $this->normalizeOperacionKey($operacion);
        return $normalized[$opKey] ?? null;
    }

    // NUEVO: mapa operación => rol objetivo para etapa 2
    private function getOperacionRoleMap(): array
    {
        return [
            // Originales
            'Cedi Frio' => 'Gerente operaciones',
            'Cedi Frio - Mantenimiento' => 'Gerente operaciones',
            'Mary Kay' => 'Gerente operaciones',
            'Oriflame' => 'Gerente operaciones',
            'Sony' => 'Gerente operaciones',
            'Macmillan' => 'Gerente operaciones',
            'Kw' => 'Gerente operaciones',
            'Tranpsrtes Vigia' => 'Gerente operaciones',
            'Cumbria' => 'Gerente operaciones',
            'Ortopedicos Futuro' => 'Gerente operaciones',
            'Naos' => 'Gerente operaciones',
            'Mattel' => 'Gerente operaciones',
            'Huawei' => 'Gerente operaciones',
            'Cedi Frio Agrofrut' => 'Gerente operaciones',
            'Cedi Frio Kikes' => 'Gerente operaciones',
            'Cedi Frio La Fazenda' => 'Gerente operaciones',
            'Cedi Frio Calypso' => 'Gerente operaciones',
            'Cedi Frio Ibazan' => 'Gerente operaciones',
            'Cedi Frio Todos Comemos' => 'Gerente operaciones',
            'Cedi Frio Food Box' => 'Gerente operaciones',
            'Inventarios' => 'Gerente operaciones',
            'Transportes' => 'Gerente operaciones',
            'Mejoramiento Contínuo' => 'Gerente operaciones',
            // Aliases / variantes
            'Cedi frio' => 'Gerente operaciones',
            'Cei frtio - Mantenimiento' => 'Gerente operaciones',
            'Mejoramiento Continuo' => 'Gerente operaciones',

            // Otros
            'Seguridad' => 'Director de proyectos',
            'HSEQ' => 'Director de proyectos',
            'Calidad' => 'Director de proyectos',
            'Compras' => 'Director de proyectos',

            'Talento Humano' => 'Gerente talento humano',
            'Financiera' => 'Director contable',
        ];
    }

    // NUEVO: Obtener destinatarios por operación, etapa y opcionalmente rol específico (stage2_by_role)
    private function recipientsByOperation(?string $operacion, string $stageKey, ?string $roleKey = null): array
    {
        $map = config('requisiciones.destinatarios_por_operacion', []);
        $opKey = $this->normalizeOperacionKey($operacion);
        $cfg = $map[$opKey] ?? $map['default'] ?? [];

        // Si es stage2 y existe configuración por rol, úsala
        if ($stageKey === 'stage2' && $roleKey) {
            $byRole = $cfg['stage2_by_role'] ?? ($map['default']['stage2_by_role'] ?? []);
            $recips = $byRole[$roleKey] ?? [];
            if (!empty($recips)) {
                return is_array($recips) ? array_values(array_filter($recips)) : [];
            }
        }

        // Fallback a listas por etapa (stage2/stage3/final)
        $recips = $cfg[$stageKey] ?? ($map['default'][$stageKey] ?? []);
        if (is_string($recips)) {
            $recips = array_filter(array_map('trim', explode(',', $recips)));
        }
        return is_array($recips) ? array_values(array_filter($recips)) : [];
    }

    private function normalizeOperacionKey(?string $txt): string
    {
        $txt = mb_strtolower(trim($txt ?? ''), 'UTF-8');
        $txt = strtr($txt,[ 'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u','ñ'=>'n' ]);
        return $txt;
    }

    // (Opcional) Métodos previos de notificación específicos se mantienen para compatibilidad
    // private function notificarAreaComprasAprobacion(...) { /* deprecated */ }
    // private function recipientsForOperation(...) { /* deprecated */ }

    /**
     * Convertir un monto a COP usando la tabla 'trm' si está disponible.
     */
    private function convertToCop($amount, $currency = 'COP')
    {
        $amt = floatval($amount ?: 0);
        $cur = strtoupper(trim($currency ?? 'COP'));
        if ($cur === 'COP') return round($amt, 2);
        try {
            // Buscar la tasa más reciente para la moneda solicitada
            $rate = DB::table('trm')
                ->where('moneda', $cur)
                ->orderByDesc('update_date')
                ->orderByDesc('id')
                ->value('price');

            // Si no se encuentra, intentar buscar por moneda en minúsculas/variantes
            if (is_null($rate)) {
                $rate = DB::table('trm')
                    ->whereRaw('upper(moneda) = ?', [$cur])
                    ->orderByDesc('update_date')
                    ->orderByDesc('id')
                    ->value('price');
            }

            $rate = floatval($rate ?: 0);
            if ($rate <= 0) {
                Log::warning('convertToCop missing or invalid rate', ['currency'=>$cur,'rate'=>$rate]);
                return round($amt, 2);
            }

            // En la tabla trm la columna 'price' representa la cantidad de moneda extranjera por 1 COP
            // Ej: USD => 0.000255 significa 1 COP = 0.000255 USD -> para convertir USD->COP: amount / rate
            $converted = $amt / $rate;
            return round($converted, 2);
        } catch (\Throwable $e) {
            Log::warning('convertToCop fallback', ['err'=>$e->getMessage(),'amount'=>$amt,'currency'=>$cur]);
            return round($amt, 2);
        }
    }
}
