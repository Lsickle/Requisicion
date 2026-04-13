<?php

namespace App\Http\Controllers\requisicion;

use App\Http\Controllers\Controller;
use App\Jobs\EstatusRequisicionActualizadoJob;
use App\Jobs\RequisicionCanceladaJob;
use App\Jobs\RequisicionCorregidaJob;
use App\Jobs\RequisicionCreadaJob;
use App\Jobs\RequisicionEntregaRegistradaJob;
use App\Jobs\RequisicionEspecialCreadaJob;
use App\Jobs\RequisicionRevisionComprasJob;
use App\Models\Centro;
use App\Models\Entrega;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use App\Models\Producto;
use App\Models\Requisicion; // asegurar uso
use Barryvdh\DomPDF\Facade\Pdf; // correo para requisición especial
use Illuminate\Http\Request; // nuevo correo revisión compras
use Illuminate\Support\Facades\DB; // correo requisición corregida
use Illuminate\Support\Facades\Http; // notificación de cancelación
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class RequisicionController extends Controller
{
    /**
     * Mostrar todas las requisiciones con sus relaciones.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $requisiciones = Requisicion::with('productos', 'estatusHistorial.estatusRelation')->get();

        return view('index', compact('requisiciones'));
    }

    /**
     * Mostrar formulario de creación de requisición.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // Cargar solo los subcentros asignados al usuario en sesión (por email)
        $sessionEmail = session('user.email');
        $asignados = collect();
        if (! empty($sessionEmail)) {
            try {
                $ids = DB::table('userxsubcentro')
                    ->where('email_user', $sessionEmail)
                    ->whereNull('deleted_at')
                    ->pluck('subcentro_id');

                if ($ids && $ids->count() > 0) {
                    $asignados = DB::table('subcentros as s')
                        ->leftJoin('centro as c', 'c.id', '=', 's.centro_id')
                        ->whereIn('s.id', $ids)
                        ->whereNull('s.deleted_at')
                        ->whereNull('c.deleted_at')
                        ->select('s.id', 's.name_subcentro', 'c.id as centro_id', 'c.name_centro as centro_nombre')
                        ->orderBy('s.name_subcentro')
                        ->get()
                        ->map(function ($r) {
                            return (object) [
                                'id' => $r->id,
                                'name_centro' => trim(($r->name_subcentro ?? '').(($r->centro_nombre ?? '') !== '' ? (' ('.$r->centro_nombre.')') : '')),
                                'centro_nombre' => (string) ($r->centro_nombre ?? ''),
                                'centro_id' => (int) ($r->centro_id ?? 0),
                            ];
                        });
                }
            } catch (\Throwable $e) {
                // Si falla, dejar lista vacía para no exponer otros centros
                Log::warning('create(): no se pudieron cargar subcentros asignados', ['error' => $e->getMessage()]);
            }
        }

        // La vista espera variable $centros; enviamos solo los asignados (o vacío si no hay asignación)
        $centros = $asignados;

        // Catálogo de productos
        $productos = Producto::all();

        // Prefill desde ?from=ID (clonado de otra requisición)
        $prefillData = null;
        $fromId = request()->query('from');
        if (! empty($fromId)) {
            try {
                $reqSrc = Requisicion::with('productos')->find($fromId);
                if ($reqSrc) {
                    $distRows = DB::table('centro_producto as cp')
                        ->join('centro as c', 'c.id', '=', 'cp.centro_id')
                        ->where('cp.requisicion_id', $reqSrc->id)
                        ->select('cp.producto_id', 'cp.centro_id', 'cp.amount', 'c.name_centro')
                        ->get();
                    $distByProd = [];
                    foreach ($distRows as $r) {
                        $distByProd[$r->producto_id][] = [
                            'id' => (string) $r->centro_id,
                            'nombre' => $r->name_centro,
                            'cantidad' => (int) $r->amount,
                        ];
                    }
                    $prods = [];
                    foreach ($reqSrc->productos as $p) {
                        $prods[] = [
                            'id' => (int) $p->id,
                            'nombre' => $p->name_produc,
                            'unidad' => $p->unit_produc,
                            'proveedorId' => $p->proveedor_id ?? null,
                            'cantidadTotal' => (int) ($p->pivot->pr_amount ?? 0),
                            'centros' => $distByProd[$p->id] ?? [],
                        ];
                    }
                    $prefillData = [
                        'operacion_user' => $reqSrc->operacion_user ?? '',
                        'Recobrable' => $reqSrc->Recobrable ?? '',
                        'prioridad_requisicion' => $reqSrc->prioridad_requisicion ?? '',
                        'justify_requisicion' => $reqSrc->justify_requisicion ?? '',
                        'detail_requisicion' => $reqSrc->detail_requisicion ?? '',
                        'productos' => $prods,
                    ];
                }
            } catch (\Throwable $e) {
                $prefillData = null;
            }
        }

        // Normalización y filtros de categorías (excluir Servicios/Alquiler)
        $normalizeCat = function ($txt) {
            $t = mb_strtolower(trim((string) $txt), 'UTF-8');
            $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u', 'ñ' => 'n']);

            return $t;
        };
        $isServicioCat = function ($txt) use ($normalizeCat) {
            $n = $normalizeCat($txt);
            $compact = preg_replace('/[\s_\-]+/u', '', $n);

            return in_array($compact, ['servicio', 'servicios', 'servicioservicio', 'servicioservicios'], true);
        };
        $isAlquilerCat = function ($txt) use ($normalizeCat) {
            $n = $normalizeCat($txt);
            $compact = preg_replace('/[\s_\-]+/u', '', $n);

            return in_array($compact, ['alquiler', 'alquilers', 'alquileres'], true);
        };

        $productosFiltrados = isset($productos) ? $productos->filter(function ($p) use ($isServicioCat, $isAlquilerCat) {
            $cat = $p->categoria_produc ?? '';

            return ! $isServicioCat($cat) && ! $isAlquilerCat($cat);
        }) : collect();

        $categoriasListaFiltrada = $productosFiltrados
            ->pluck('categoria_produc')
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->mapWithKeys(function ($c) use ($normalizeCat) {
                return [$normalizeCat($c) => $c];
            })
            ->values()
            ->sort()
            ->values();

        // Preparar HTML de operaciones para dropdown (sin lógica en la vista)
        $operacionesLista = collect($centros ?? [])
            ->pluck('centro_nombre')
            ->filter(fn ($v) => ! empty($v))
            ->unique()
            ->sort()
            ->values();
        $operacionesOptionsHtml = $operacionesLista->map(function ($op) {
            $val = e($op);

            return '<div class="p-2 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer rounded" data-value="'.$val.'" onclick="seleccionarOperacion(event,this)">'.$val.'</div>';
        })->implode("\n");

        // Preparar HTML de categorías para el modal 1
        $categoriasOptionsHtml = $categoriasListaFiltrada->map(function ($cat) {
            $txt = e($cat);

            return '<div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" onclick="seleccionarOpcion(event, this, \'categoriaFilter\')">'.$txt.'</div>';
        })->implode("\n");

        // Preparar HTML de productos para el modal 1
        $productosOptionsHtml = $productosFiltrados->map(function ($p) {
            $id = e($p->id);
            $sku = e($p->sku ?? '');
            $nombre = e($p->name_produc);
            $proveedor = e($p->proveedor_id ?? '');
            $categoria = e($p->categoria_produc);
            $unidad = e($p->unit_produc);
            $skuDisplay = e($p->sku ?? $p->id);

            return '<div class="p-2 hover:bg-indigo-100 cursor-pointer rounded whitespace-normal break-words" onclick="seleccionarOpcion(event, this, \'productoSelect\')" data-id="'.$id.'" data-sku="'.$sku.'" data-nombre="'.$nombre.'" data-proveedor="'.$proveedor.'" data-categoria="'.$categoria.'" data-unidad="'.$unidad.'">('.$skuDisplay.') '.$nombre.' ('.$unidad.')</div>';
        })->implode("\n");

        // Subcentros asignados al usuario para el modal 2
        $userEmail = session('user.email') ?? session('email') ?? session('user_email') ?? null;
        $subcentrosUsuario = collect();
        try {
            if ($userEmail) {
                $subcentrosUsuario = DB::table('userxsubcentro as ux')
                    ->join('subcentros as s', 's.id', '=', 'ux.subcentro_id')
                    ->leftJoin('centro as c', 'c.id', '=', 's.centro_id')
                    ->whereNull('ux.deleted_at')
                    ->where('ux.email_user', $userEmail)
                    ->select('s.id as subcentro_id', 's.name_subcentro', 'c.name_centro')
                    ->orderBy('c.name_centro')
                    ->orderBy('s.name_subcentro')
                    ->get();
            }
        } catch (\Throwable $e) {
            $subcentrosUsuario = collect();
        }
        if ($subcentrosUsuario->isEmpty()) {
            $subcentrosOptionsHtml = '<div class="p-2 text-gray-500">No tienes subcentros asignados.</div>';
        } else {
            $subcentrosOptionsHtml = $subcentrosUsuario->map(function ($sc) {
                $id = e($sc->subcentro_id);
                $nom = e($sc->name_subcentro);
                $centro = trim((string) ($sc->name_centro ?? ''));
                $centroTxt = $centro !== '' ? ' ('.e($centro).')' : '';

                return '<div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" data-id="'.$id.'" data-nombre="'.$nom.'" onclick="seleccionarCentro(event, this)">'.$nom.$centroTxt.'</div>';
            })->implode("\n");
        }

        return view('requisiciones.create', compact(
            'centros', 'productosFiltrados', 'categoriasListaFiltrada', 'prefillData',
            'operacionesOptionsHtml', 'categoriasOptionsHtml', 'productosOptionsHtml', 'subcentrosOptionsHtml'
        ));
    }

    /**
     * Mostrar formulario de creación de requisición (vista especial).
     *
     * @return \Illuminate\View\View
     */
    public function createEspecial()
    {
        // Cargar solo los subcentros asignados al usuario en sesión (por email)
        $sessionEmail = session('user.email');
        $asignados = collect();
        if (! empty($sessionEmail)) {
            try {
                $ids = DB::table('userxsubcentro')
                    ->where('email_user', $sessionEmail)
                    ->whereNull('deleted_at')
                    ->pluck('subcentro_id');

                if ($ids && $ids->count() > 0) {
                    $asignados = DB::table('subcentros as s')
                        ->leftJoin('centro as c', 'c.id', '=', 's.centro_id')
                        ->whereIn('s.id', $ids)
                        ->whereNull('s.deleted_at')
                        ->whereNull('c.deleted_at')
                        ->select('s.id', 's.name_subcentro', 'c.id as centro_id', 'c.name_centro as centro_nombre')
                        ->orderBy('s.name_subcentro')
                        ->get()
                        ->map(function ($r) {
                            return (object) [
                                'id' => $r->id,
                                'name_centro' => trim(($r->name_subcentro ?? '').(($r->centro_nombre ?? '') !== '' ? (' ('.$r->centro_nombre.')') : '')),
                                'centro_nombre' => (string) ($r->centro_nombre ?? ''),
                                'centro_id' => (int) ($r->centro_id ?? 0),
                            ];
                        });
                }
            } catch (\Throwable $e) { /* noop */
            }
        }
        $centros = $asignados;

        // Catálogo de productos
        $productos = Producto::all();

        // Prefill desde ?from=ID (clonado de otra requisición)
        $prefillData = null;
        $fromId = request()->query('from');
        if (! empty($fromId)) {
            try {
                $reqSrc = Requisicion::with('productos')->find($fromId);
                if ($reqSrc) {
                    $distRows = DB::table('centro_producto as cp')
                        ->join('centro as c', 'c.id', '=', 'cp.centro_id')
                        ->where('cp.requisicion_id', $reqSrc->id)
                        ->select('cp.producto_id', 'cp.centro_id', 'cp.amount', 'c.name_centro')
                        ->get();
                    $distByProd = [];
                    foreach ($distRows as $r) {
                        $distByProd[$r->producto_id][] = [
                            'id' => (string) $r->centro_id,
                            'nombre' => $r->name_centro,
                            'cantidad' => (int) $r->amount,
                        ];
                    }
                    $prods = [];
                    foreach ($reqSrc->productos as $p) {
                        $prods[] = [
                            'id' => (int) $p->id,
                            'nombre' => $p->name_produc,
                            'unidad' => $p->unit_produc,
                            'proveedorId' => $p->proveedor_id ?? null,
                            'cantidadTotal' => (int) ($p->pivot->pr_amount ?? 0),
                            'centros' => $distByProd[$p->id] ?? [],
                        ];
                    }
                    $prefillData = [
                        'operacion_user' => $reqSrc->operacion_user ?? '',
                        'Recobrable' => $reqSrc->Recobrable ?? '',
                        'prioridad_requisicion' => $reqSrc->prioridad_requisicion ?? '',
                        'justify_requisicion' => $reqSrc->justify_requisicion ?? '',
                        'detail_requisicion' => $reqSrc->detail_requisicion ?? '',
                        'productos' => $prods,
                    ];
                }
            } catch (\Throwable $e) {
                $prefillData = null;
            }
        }

        // Filtros de categorías: solo Servicio/Alquiler para Especial
        $normalizeCat = function ($txt) {
            $t = mb_strtolower(trim((string) $txt), 'UTF-8');
            $t = strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u', 'ñ' => 'n']);

            return $t;
        };
        $isServicioCat = function ($txt) use ($normalizeCat) {
            $n = $normalizeCat($txt);
            $compact = preg_replace('/[\s_\-]+/u', '', $n);

            return in_array($compact, ['servicio', 'servicios', 'servicioservicio', 'servicioservicios'], true);
        };
        $isAlquilerCat = function ($txt) use ($normalizeCat) {
            $n = $normalizeCat($txt);
            $compact = preg_replace('/[\s_\-]+/u', '', $n);

            return in_array($compact, ['alquiler', 'alquilers', 'alquileres'], true);
        };

        $productosFiltrados = isset($productos) ? $productos->filter(function ($p) use ($isServicioCat, $isAlquilerCat) {
            $cat = $p->categoria_produc ?? '';

            return $isServicioCat($cat) || $isAlquilerCat($cat);
        }) : collect();

        $categoriasListaFiltrada = $productosFiltrados
            ->pluck('categoria_produc')
            ->map(fn ($c) => trim((string) $c))
            ->filter()
            ->mapWithKeys(function ($c) use ($normalizeCat) {
                return [$normalizeCat($c) => $c];
            })
            ->values()
            ->sort()
            ->values();

        // HTML de operaciones (estilos ámbar para especial)
        $operacionesLista = collect($centros ?? [])
            ->pluck('centro_nombre')
            ->filter(fn ($v) => ! empty($v))
            ->unique()
            ->sort()
            ->values();
        $operacionesOptionsHtml = $operacionesLista->map(function ($op) {
            $val = e($op);

            return '<div class="p-2 hover:bg-amber-50 hover:text-amber-700 cursor-pointer rounded" data-value="'.$val.'" onclick="seleccionarOperacion(event,this)">'.$val.'</div>';
        })->implode("\n");

        // HTML de categorías (ámbar)
        $categoriasOptionsHtml = $categoriasListaFiltrada->map(function ($cat) {
            $txt = e($cat);

            return '<div class="p-2 hover:bg-amber-100 cursor-pointer rounded" onclick="seleccionarOpcion(event, this, \'categoriaFilter\')">'.$txt.'</div>';
        })->implode("\n");

        // HTML de productos (ámbar hover)
        $productosOptionsHtml = $productosFiltrados->map(function ($p) {
            $id = e($p->id);
            $sku = e($p->sku ?? '');
            $nombre = e($p->name_produc);
            $proveedor = e($p->proveedor_id ?? '');
            $categoria = e($p->categoria_produc);
            $unidad = e($p->unit_produc);
            $skuDisplay = e($p->sku ?? $p->id);

            return '<div class="p-2 hover:bg-amber-100 cursor-pointer rounded whitespace-normal break-words" onclick="seleccionarOpcion(event, this, \'productoSelect\')" data-id="'.$id.'" data-sku="'.$sku.'" data-nombre="'.$nombre.'" data-proveedor="'.$proveedor.'" data-categoria="'.$categoria.'" data-unidad="'.$unidad.'">('.$skuDisplay.') '.$nombre.' ('.$unidad.')</div>';
        })->implode("\n");

        // Subcentros del usuario
        $userEmail = session('user.email') ?? session('email') ?? session('user_email') ?? null;
        $subcentrosUsuario = collect();
        try {
            if ($userEmail) {
                $subcentrosUsuario = DB::table('userxsubcentro as ux')
                    ->join('subcentros as s', 's.id', '=', 'ux.subcentro_id')
                    ->leftJoin('centro as c', 'c.id', '=', 's.centro_id')
                    ->whereNull('ux.deleted_at')
                    ->where('ux.email_user', $userEmail)
                    ->select('s.id as subcentro_id', 's.name_subcentro', 'c.name_centro')
                    ->orderBy('c.name_centro')
                    ->orderBy('s.name_subcentro')
                    ->get();
            }
        } catch (\Throwable $e) {
            $subcentrosUsuario = collect();
        }
        $subcentrosOptionsHtml = $subcentrosUsuario->isEmpty()
            ? '<div class="p-2 text-gray-500">No tienes subcentros asignados.</div>'
            : $subcentrosUsuario->map(function ($sc) {
                $id = e($sc->subcentro_id);
                $nom = e($sc->name_subcentro);
                $centro = trim((string) ($sc->name_centro ?? ''));
                $centroTxt = $centro !== '' ? ' ('.e($centro).')' : '';

                return '<div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" data-id="'.$id.'" data-nombre="'.$nom.'" onclick="seleccionarCentro(event, this)">'.$nom.$centroTxt.'</div>';
            })->implode("\n");

        return view('requisiciones.especial', compact(
            'centros', 'productosFiltrados', 'categoriasListaFiltrada', 'prefillData',
            'operacionesOptionsHtml', 'categoriasOptionsHtml', 'productosOptionsHtml', 'subcentrosOptionsHtml'
        ));
    }

    /**
     * Mostrar menú principal de requisiciones.
     *
     * @return \Illuminate\View\View
     */
    public function menu()
    {
        return view('requisiciones.menu');
    }

    /**
     * Mostrar formulario para editar una requisición en estado 'Corregir'.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function edit($id)
    {
        $requisicion = Requisicion::with([
            'productos' => function ($query) use ($id) {
                $query->with(['centrosRequisicion' => function ($q) use ($id) {
                    $q->wherePivot('requisicion_id', $id);
                }]);
            },
            'estatusHistorial.estatusRelation',
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

    /**
     * Actualizar la requisición corregida.
     * Valida la consistencia entre la suma por centros y la cantidad total por producto.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
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
            // permitir que el id sea de centro o subcentro; se resolverá al centro padre
            'productos.*.centros.*.id' => 'required|integer|min:1',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
            'productos.*.es_servicio' => 'nullable|boolean',
            'productos.*.observacion_servicio' => 'nullable|string|max:2000',
            'productos.*.plan_ejecucion' => 'nullable|string|max:5000',
        ], [
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();

        try {
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int) $prod['requisicion_amount'];
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int) $prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']}).",
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
                $esServicio = ! empty($prod['es_servicio']);
                $observacion = $prod['observacion_servicio'] ?? null;

                // Validar que si es servicio, tenga observación
                if ($esServicio && empty($observacion)) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto ID {$prod['id']} es un servicio y requiere una descripción.",
                    ]);
                }

                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros,
                    'es_servicio' => $esServicio,
                    'observacion_servicio' => $esServicio ? $observacion : null,
                ]);

                foreach ($prod['centros'] as $centro) {
                    $cidInput = (int) ($centro['id'] ?? 0);
                    $cidResolved = $this->resolveCentroIdLegacy($cidInput);
                    if (! $cidResolved) {
                        throw ValidationException::withMessages([
                            'productos' => "Centro/Subcentro inválido (ID {$cidInput}) en distribución.",
                        ]);
                    }
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id' => $cidResolved,
                        'requisicion_id' => $requisicion->id,
                        'amount' => $centro['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)->update(['estatus' => 0]);

            $estatusIniciada = Estatus::where('status_name', 'Requisición creada')->first();
            if ($estatusIniciada) {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id' => $estatusIniciada->id,
                    'estatus' => 1,
                    'date_update' => now(),
                ]);
            }

            DB::commit();

            $reqModel = $requisicion ?? ($req ?? null) ?? ($requisicionActualizada ?? null) ?? null;
            try {
                if ($reqModel instanceof \App\Models\Requisicion) {
                    $nombre = session('user.name') ?? session('user.nombre') ?? session('user_name') ?? $reqModel->operacion_user ?? 'Usuario';
                    RequisicionCorregidaJob::dispatch($reqModel, (string) $nombre);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar correo de requisición corregida: '.$e->getMessage());
            }

            return redirect()->route('requisiciones.historial')->with('success', 'Requisición corregida y enviada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['error' => 'Error al actualizar la requisición: '.$e->getMessage()]);
        }
    }

    /**
     * Almacenar una nueva requisición.
     * Realiza validación, guarda relaciones producto-centro y registra estatus inicial.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
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
            'productos.*.centros.*.id' => 'required|integer|min:1',
            'productos.*.centros.*.cantidad' => 'required|integer|min:1',
            'productos.*.es_servicio' => 'nullable|boolean',
            'productos.*.observacion_servicio' => 'nullable|string|max:2000',
        ], [
            'operacion_user.required' => 'Debe seleccionar una operación.',
            'productos.required' => 'Agrega al menos un producto.',
            'productos.*.requisicion_amount.required' => 'La cantidad total por producto es obligatoria.',
        ]);

        DB::beginTransaction();
        try {
            $totalRequisicion = 0;
            foreach ($validated['productos'] as $prod) {
                $totalRequisicion += (int) $prod['requisicion_amount'];
                $sumaCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                if ($sumaCentros !== (int) $prod['requisicion_amount']) {
                    throw ValidationException::withMessages([
                        'productos' => "Para el producto {$prod['id']}, la suma de cantidades por centros ({$sumaCentros}) no coincide con la cantidad total indicada ({$prod['requisicion_amount']}).",
                    ]);
                }
            }

            $userId = session('user.id');
            $nombreUsuario = session('user.name', 'Usuario Desconocido');
            $emailUsuario = session('user.email', 'email@desconocido.com');
            $operacionUsuario = trim($validated['operacion_user']);

            $requisicion = new Requisicion;
            $requisicion->user_id = $userId;
            $requisicion->name_user = $nombreUsuario;
            $requisicion->email_user = $emailUsuario;
            $requisicion->operacion_user = $operacionUsuario;
            $requisicion->Recobrable = $validated['Recobrable'];
            $requisicion->prioridad_requisicion = $validated['prioridad_requisicion'];
            $requisicion->justify_requisicion = $validated['justify_requisicion'];
            $requisicion->detail_requisicion = $validated['detail_requisicion'];
            $requisicion->amount_requisicion = $totalRequisicion;
            $requisicion->type = $request->input('type', 'Normal');
            $requisicion->save();

            // Estatus inicial: 4 si es Especial, de lo contrario 'Requisición creada'
            if (strtolower((string) $requisicion->type) === 'especial') {
                Estatus_Requisicion::create([
                    'requisicion_id' => $requisicion->id,
                    'estatus_id' => 4,
                    'estatus' => 1,
                    'date_update' => now(),
                ]);
            } else {
                $estatusInicial = Estatus::where('status_name', 'Requisición creada')->first();
                if ($estatusInicial) {
                    Estatus_Requisicion::create([
                        'requisicion_id' => $requisicion->id,
                        'estatus_id' => $estatusInicial->id,
                        'estatus' => 1,
                        'date_update' => now(),
                    ]);
                }
            }

            foreach ($validated['productos'] as $prod) {
                $cantidadTotalCentros = array_sum(array_column($prod['centros'], 'cantidad'));
                $esServicio = ! empty($prod['es_servicio']);
                $observacion = $prod['observacion_servicio'] ?? null;

                // Validar que si es servicio, tenga observación
                if ($esServicio && empty($observacion)) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto ID {$prod['id']} es un servicio y requiere una descripción.",
                    ]);
                }

                $requisicion->productos()->attach($prod['id'], [
                    'pr_amount' => $cantidadTotalCentros,
                    'es_servicio' => $esServicio,
                    'observacion_servicio' => $esServicio ? $observacion : null,
                ]);

                foreach ($prod['centros'] as $centro) {
                    $cidInput = (int) ($centro['id'] ?? 0);
                    $cidResolved = $this->resolveCentroIdLegacy($cidInput);
                    if (! $cidResolved) {
                        throw ValidationException::withMessages([
                            'productos' => "Centro/Subcentro inválido (ID {$cidInput}) en distribución.",
                        ]);
                    }
                    DB::table('centro_producto')->insert([
                        'producto_id' => $prod['id'],
                        'centro_id' => $cidResolved,
                        'requisicion_id' => $requisicion->id,
                        'amount' => $centro['cantidad'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            RequisicionCreadaJob::dispatch($requisicion, $nombreUsuario);

            // Encolar correo si es una requisición especial
            try {
                $tipo = strtolower((string) ($request->input('type') ?? ($requisicion->type ?? '')));
                if ($requisicion instanceof \App\Models\Requisicion && $tipo === 'especial') {
                    $nombre = session('user.name') ?? session('user.nombre') ?? session('user_name') ?? $request->input('operacion_user') ?? 'Usuario';
                    RequisicionEspecialCreadaJob::dispatch($requisicion, (string) $nombre);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar correo de requisición especial: '.$e->getMessage());
            }

            $reqModel = $requisicion ?? ($req ?? null) ?? ($requisicionGuardada ?? null) ?? null;
            try {
                $tipo = strtolower((string) ($request->input('type') ?? ($reqModel->type ?? '')));
                if ($reqModel instanceof \App\Models\Requisicion) {
                    if ($tipo === 'especial') {
                        // ...existing dispatch especial (ya agregado anteriormente)...
                    } else {
                        // Requisición normal: correo a área de compras para revisión
                        RequisicionRevisionComprasJob::dispatch($reqModel);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar correo de revisión compras: '.$e->getMessage());
            }

            if (strtolower((string) $requisicion->type) === 'especial') {
                return redirect()->route('requisiciones.especial')->with('success', 'Requisición especial creada correctamente.');
            }

            return redirect()->route('requisiciones.create')->with('success', 'Requisición creada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            $route = strtolower((string) $request->input('type')) === 'especial' ? 'requisiciones.especial' : 'requisiciones.create';

            return redirect()->route($route)->withInput()->withErrors(['error' => 'Error al crear la requisición: '.$e->getMessage()]);
        }
    }

    /**
     * Resolver un id de entrada a un centro válido.
     * Si el id corresponde a un centro, lo retorna tal cual; si corresponde a un subcentro, devuelve su centro padre.
     * Devuelve null si no lo puede resolver.
     */
    private function resolveCentroIdLegacy(int $inputId): ?int
    {
        try {
            // ¿Existe como centro?
            $existsCentro = DB::table('centro')->where('id', $inputId)->exists();
            if ($existsCentro) {
                return $inputId;
            }
            // ¿Existe como subcentro? devolver centro_id
            $centroId = DB::table('subcentros')->where('id', $inputId)->value('centro_id');
            if ($centroId) {
                return (int) $centroId;
            }
        } catch (\Throwable $e) {
            // noop
        }

        return null;
    }

    /**
     * Mostrar una requisición.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $requisicion = Requisicion::with([
            'productos',
            'productos.centros',
            'estatusHistorial.estatusRelation',
        ])->findOrFail($id);

        return view('requisiciones.show', compact('requisicion'));
    }

    /**
     * Generar y descargar el PDF de la requisición.
     *
     * @param  int  $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function pdf($id)
    {
        $requisicion = Requisicion::with([
            'productos' => function ($q) {
                $q->withTrashed();
            },
            'productos.centros',
            'estatusHistorial.estatusRelation',
        ])->findOrFail($id);

        $nombreSolicitante = $requisicion->name_user;
        $emailSolicitante = $requisicion->email_user;
        $operacionSolicitante = $requisicion->operacion_user;

        $logoData = null;
        $candidates = [public_path('images/VigiaLogoC.png')];
        foreach ($candidates as $path) {
            if (file_exists($path)) {
                $contents = file_get_contents($path);
                $mime = function_exists('mime_content_type') ? mime_content_type($path) : null;
                if (empty($mime)) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                    $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
                }
                $logoData = 'data:'.$mime.';base64,'.base64_encode($contents);
                break;
            }
        }

        $pdf = Pdf::loadView('requisiciones.pdf', [
            'requisicion' => $requisicion,
            'nombreSolicitante' => $nombreSolicitante,
            'emailSolicitante' => $emailSolicitante,
            'operacionSolicitante' => $operacionSolicitante,
            'logo' => $logoData ?? asset('images/VigiaLogoC.png'),
        ])->setPaper('A4', 'portrait');

        return $pdf->download("requisicion_{$requisicion->id}.pdf");
    }

    /**
     * Historial de requisiciones del usuario logueado.
     *
     * @return \Illuminate\View\View
     */
    public function historial()
    {
        $userId = session('user.id');

        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation',
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
                        'estatus_name' => $req->ultimoEstatus->estatusRelation->status_name ?? null,
                    ] : null,
                ];
            }),
        ]);

        return view('requisiciones.historial', compact('requisiciones'));
    }

    /**
     * Listar requisiciones aptas para generar órdenes de compra (aprobadas)
     *
     * @return \Illuminate\View\View
     */
    public function listaAprobadas()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation',
        ])
            ->whereHas('ultimoEstatus', function ($query) {
                $query->whereIn('estatus_id', [4, 5, 7, 8, 12]);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('ordenes_compra.lista', compact('requisiciones'));
    }

    /**
     * Cancelar una requisición (iniciada por solicitante).
     * Valida permisos y estado antes de cambiar estatus.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelar($id)
    {
        DB::beginTransaction();

        try {
            $requisicion = Requisicion::findOrFail($id);

            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para cancelar esta requisición',
                ], 403);
            }

            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if (in_array($ultimoEstatus, [6, 9, 10, 5])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una requisición en su estado actual',
                ], 400);
            }

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id' => 6,
                'estatus' => 1,
                'date_update' => now(),
            ]);

            DB::commit();

            try {
                $reqModel = $requisicion ?? (\App\Models\Requisicion::find($id) ?: null);
                if ($reqModel instanceof \App\Models\Requisicion) {
                    $nombre = session('user.name') ?? session('user.nombre') ?? session('user_name') ?? $reqModel->operacion_user ?? 'Usuario';
                    RequisicionCanceladaJob::dispatch($reqModel, (string) $nombre);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar correo de cancelación: '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Requisición cancelada correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error cancelando requisición {$id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la requisición: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reenviar una requisición cancelada.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reenviar($id)
    {
        DB::beginTransaction();

        try {
            $requisicion = Requisicion::findOrFail($id);

            $userId = session('user.id');
            if ($requisicion->user_id != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para reenviar esta requisición',
                ], 403);
            }

            $ultimoEstatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            if ($ultimoEstatus != 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden reenviar requisiciones canceladas',
                ], 400);
            }

            Estatus_Requisicion::where('requisicion_id', $requisicion->id)
                ->update(['estatus' => 0]);

            Estatus_Requisicion::create([
                'requisicion_id' => $requisicion->id,
                'estatus_id' => 1,
                'estatus' => 1,
                'date_update' => now(),
                'comentario' => 'Reenviada por el solicitante',
            ]);

            DB::commit();

            // Enviar correo de revisión al reenviar
            try {
                $reqModel = $requisicion ?? (\App\Models\Requisicion::find($id) ?: null);
                if ($reqModel instanceof \App\Models\Requisicion) {
                    RequisicionRevisionComprasJob::dispatch($reqModel);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo encolar correo de revisión (reenviar): '.$e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Requisición reenviada correctamente',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error reenviando requisición {$id}: ".$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al reenviar la requisición: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Finalizar una requisición: marca estatus 10 (Proceso completado).
     * Solo roles con permisos de gestión pueden ejecutar esta acción.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function todas()
    {
        $requisiciones = Requisicion::with([
            'productos',
            'ultimoEstatus.estatusRelation',
            'estatusHistorial.estatusRelation',
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('requisiciones.todas', compact('requisiciones'));
    }

    public function finalizar(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $requisicion = Requisicion::findOrFail($id);

            // Verificar permisos desde sesión: roles o permisos
            $rolesRaw = session('user_roles') ?? (session('user.roles') ?? session('user.role') ?? []);
            $rolesArr = [];
            if (is_string($rolesRaw)) {
                $rolesArr = preg_split('/\s*,\s*/', $rolesRaw);
            } elseif (is_array($rolesRaw)) {
                foreach ($rolesRaw as $r) {
                    if (is_string($r)) {
                        $rolesArr[] = $r;
                    } elseif (is_array($r) && isset($r['name'])) {
                        $rolesArr[] = $r['name'];
                    } elseif (is_object($r) && isset($r->name)) {
                        $rolesArr[] = $r->name;
                    }
                }
            }
            $rolesLower = array_map(fn ($v) => strtolower(trim((string) $v)), $rolesArr);
            $canManage = in_array('area de compras', $rolesLower) || in_array('admin requisicion', $rolesLower);
            if (! $canManage) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'No tienes permisos para finalizar esta requisición'], 403);
            }

            // Usar nullsafe por si no hay estatus activo
            $ultimoEstatus = $requisicion->ultimoEstatus?->estatus_id;
            if ($ultimoEstatus == 10) {
                DB::commit();

                return response()->json(['success' => true, 'message' => 'La requisición ya está finalizada.']);
            }

            // Registrar estatus 10
            $this->setRequisicionStatus((int) $requisicion->id, 10);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Requisición finalizada correctamente']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error finalizando requisición '.$id.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al finalizar la requisición: '.$e->getMessage()], 500);
        }
    }

    /**
     * Interfaz para transferir titularidad de una requisición (solo Admin requisicion)
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function transferIndex()
    {
        // Permiso explícito o rol 'Admin requisicion' permiten acceder
        $permissions = array_map(fn ($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
        $hasTransferPerm = in_array(mb_strtolower('transferir titularidad', 'UTF-8'), $permissions, true);

        // Fallback: detectar rol Admin requisicion en session.user.roles o user_roles
        $rolesRaw = Session::get('user_roles') ?: (session('user.roles') ?? []);
        $rolesNormalized = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) {
                    $rolesNormalized[] = $r;

                    continue;
                }
                if (is_array($r) && isset($r['roles'])) {
                    $rolesNormalized[] = $r['roles'];

                    continue;
                }
                if (is_object($r) && isset($r->roles)) {
                    $rolesNormalized[] = $r->roles;

                    continue;
                }
            }
        }
        $singleRole = session('user.role') ?? Session::get('user.role') ?? null;
        $isAdmin = in_array('Admin requisicion', $rolesNormalized, true) || ($singleRole === 'Admin requisicion');
        Log::info('transferIndex permission check', ['hasTransferPerm' => $hasTransferPerm, 'roles_normalized' => $rolesNormalized, 'singleRole' => $singleRole, 'isAdmin' => $isAdmin]);
        if (! $hasTransferPerm && ! $isAdmin) {
            Log::warning('Acceso denegado a transferIndex', ['session_user' => session('user'), 'user_permissions' => Session::get('user_permissions'), 'user_roles' => Session::get('user_roles')]);

            return redirect()->route('requisiciones.menu')->with('error', 'No tienes permisos para acceder a esta sección');
        }

        $requisiciones = Requisicion::with(['productos', 'estatusHistorial.estatusRelation'])->orderBy('created_at', 'desc')->get();

        return view('requisiciones.transferir', compact('requisiciones'));
    }

    /**
     * Ejecuta la transferencia de titularidad a otro usuario.
     * Valida permisos, reescribe campos relevantes y registra historial.
     *
     * @param  int  $id
     * @return mixed
     */
    public function transferir(Request $request, $id)
    {
        // Permiso explícito o rol 'Admin requisicion' para ejecución
        $permissions = array_map(fn ($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
        $hasTransferPerm = in_array(mb_strtolower('transferir titularidad', 'UTF-8'), $permissions, true);

        $rolesRaw = Session::get('user_roles') ?: (session('user.roles') ?? []);
        $rolesNormalized = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) {
                    $rolesNormalized[] = $r;

                    continue;
                }
                if (is_array($r) && isset($r['roles'])) {
                    $rolesNormalized[] = $r['roles'];

                    continue;
                }
                if (is_object($r) && isset($r->roles)) {
                    $rolesNormalized[] = $r->roles;

                    continue;
                }
            }
        }
        $singleRole = session('user.role') ?? Session::get('user.role') ?? null;
        $isAdmin = in_array('Admin requisicion', $rolesNormalized, true) || ($singleRole === 'Admin requisicion');
        Log::info('transferir permission check', ['hasTransferPerm' => $hasTransferPerm, 'roles_normalized' => $rolesNormalized, 'singleRole' => $singleRole, 'isAdmin' => $isAdmin]);
        if (! $hasTransferPerm && ! $isAdmin) {
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
                'request_all' => $request->all(),
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
                'comentario' => "Titularidad transferida de usuario {$oldOwner} ({$oldOwnerName}) a {$providedUserId} ({$providedName}) por ".(session('user.name') ?? session('user.id') ?? 'Sistema'),
            ]);

            DB::commit();

            $successMsg = 'Titularidad transferida correctamente';
            Log::info('Transferencia exitosa', [
                'requisicion_id' => $id,
                'old_owner' => $oldOwner,
                'old_owner_name' => $oldOwnerName,
                'new_owner' => $providedUserId,
                'new_name' => $providedName,
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'message' => $successMsg]);
            }

            return redirect()->back()->with('success', $successMsg);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error transferir titularidad: '.$e->getMessage());
            $errMsg = 'Error al transferir titularidad: '.$e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMsg], 500);
            }

            return redirect()->back()->withInput()->with('error', $errMsg);
        }
    }

    /**
     * Intentar completar automáticamente una requisición si todas las cantidades han sido recibidas.
     * - Suma cantidades requeridas por producto (centro_producto / producto_requisicion)
     * - Suma confirmadas por entrega y recepción
     * - Si todo coincide, crea estatus 10 (completado)
     *
     * @return bool true si se marcó como completada o ya estaba completa
     */
    private function attemptAutoComplete(int $requisicionId): bool
    {
        // Determinar cantidades requeridas
        $requeridos = DB::table('centro_producto')
            ->where('requisicion_id', $requisicionId)
            ->select('producto_id', DB::raw('SUM(amount) as req'))
            ->groupBy('producto_id')
            ->pluck('req', 'producto_id');
        if ($requeridos->isEmpty()) {
            $requeridos = DB::table('producto_requisicion')
                ->where('id_requisicion', $requisicionId)
                ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                ->groupBy('id_producto')
                ->pluck('req', 'producto_id');
        }
        if ($requeridos->isEmpty()) {
            return false;
        }
        // Elegir columna válida en entrega
        $entregaQtyCol = null;
        if (Schema::hasColumn('entrega', 'cantidad_recibido')) {
            $entregaQtyCol = 'cantidad_recibido';
        } elseif (Schema::hasColumn('entrega', 'cantidad_recibida')) {
            $entregaQtyCol = 'cantidad_recibida';
        } elseif (Schema::hasColumn('entrega', 'cantidad')) {
            $entregaQtyCol = 'cantidad';
        }
        // Elegir columna válida en recepcion
        $recepQtyCol = null;
        if (Schema::hasColumn('recepcion', 'cantidad_recibida')) {
            $recepQtyCol = 'cantidad_recibida';
        } elseif (Schema::hasColumn('recepcion', 'cantidad_recibido')) {
            $recepQtyCol = 'cantidad_recibido';
        } elseif (Schema::hasColumn('recepcion', 'cantidad')) {
            $recepQtyCol = 'cantidad';
        }
        // Sumar confirmadas en entrega
        $confirmadasEntrega = collect();
        if ($entregaQtyCol) {
            $confirmadasEntrega = DB::table('entrega')
                ->where('requisicion_id', $requisicionId)
                ->whereNull('deleted_at')
                ->select('producto_id', DB::raw('SUM(COALESCE('.$entregaQtyCol.',0)) as total'))
                ->groupBy('producto_id')
                ->pluck('total', 'producto_id');
        }
        // Sumar confirmadas en recepcion asociadas a la requisicion
        $confirmadasRecepcion = collect();
        if ($recepQtyCol) {
            $confirmadasRecepcion = DB::table('recepcion as r')
                ->join('orden_compras as oc', 'oc.id', '=', 'r.orden_compra_id')
                ->where('oc.requisicion_id', $requisicionId)
                ->whereNull('r.deleted_at')
                ->select('r.producto_id', DB::raw('SUM(COALESCE(r.'.$recepQtyCol.',0)) as total'))
                ->groupBy('r.producto_id')
                ->pluck('total', 'producto_id');
        }
        // Combinar
        $confirmadas = [];
        foreach ($confirmadasEntrega as $pid => $tot) {
            $confirmadas[$pid] = (int) $tot;
        }
        foreach ($confirmadasRecepcion as $pid => $tot) {
            $confirmadas[$pid] = ($confirmadas[$pid] ?? 0) + (int) $tot;
        }
        foreach ($requeridos as $prodId => $need) {
            if (((int) ($confirmadas[$prodId] ?? 0)) < (int) $need) {
                return false;
            }
        }
        // Marcar completado (estatus 10) sólo si aún no lo está
        $yaCompleto = Estatus_Requisicion::where('requisicion_id', $requisicionId)->where('estatus', 1)->where('estatus_id', 10)->exists();
        if ($yaCompleto) {
            return true;
        }
        Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);
        $nuevo = Estatus_Requisicion::create([
            'requisicion_id' => $requisicionId,
            'estatus_id' => 10,
            'estatus' => 1,
            'date_update' => now(),
            'comentario' => 'Completado automáticamente al recibir todos los productos',
        ]);
        // Despachar correo de completada
        try {
            $req = Requisicion::find($requisicionId);
            $email = $req->email_user ?? null;
            if ($req && $email) {
                EstatusRequisicionActualizadoJob::dispatch($req, $nuevo, $email);
            }
        } catch (\Throwable $e) { /* noop */
        }

        return true;
    }

    /**
     * Confirmar una entrega (registro único).
     * Actualiza cantidad recibida en tabla entrega, ajusta stock por delta y prueba auto-complete.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmarEntrega(Request $request)
    {
        $entregaId = $request->input('entrega_id') ?? $request->input('id');
        $cantidad = (int) $request->input('cantidad', 0);

        if (! $entregaId || $cantidad < 0) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            $entrega = DB::table('entrega')->where('id', $entregaId)->whereNull('deleted_at')->first();
            if (! $entrega) {
                return response()->json(['success' => false, 'message' => 'Registro de entrega no encontrado'], 404);
            }

            $max = (int) $entrega->cantidad;
            $cantidad = min($cantidad, $max);

            $prevRecibido = (int) ($entrega->cantidad_recibido ?? $entrega->cantidad_recibida ?? 0);
            $delta = $cantidad - $prevRecibido; // sólo restar delta positivo
            if ($delta < 0) {
                $delta = 0;
            }

            $sessionUser = session('user') ?? [];
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_email') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? null;

            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int) $receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

            $updateData = [];
            if (Schema::hasColumn('entrega', 'cantidad_recibido')) {
                $updateData['cantidad_recibido'] = $cantidad;
            } elseif (Schema::hasColumn('entrega', 'cantidad_recibida')) {
                $updateData['cantidad_recibida'] = $cantidad;
            }

            foreach (['reception_user', 'recepcion_user', 'receptor_user'] as $col) {
                if (Schema::hasColumn('entrega', $col) && $receiverName !== null) {
                    $updateData[$col] = $receiverName;
                }
            }
            foreach (['reception_user_id', 'recepcion_user_id', 'receptor_user_id'] as $col) {
                if (Schema::hasColumn('entrega', $col) && $receiverId !== null) {
                    $updateData[$col] = $receiverId;
                }
            }

            if (empty($updateData)) {
                return response()->json(['success' => false, 'message' => 'No hay columnas para actualizar'], 500);
            }

            DB::beginTransaction();

            // Actualizar entrega
            $updateData['updated_at'] = now();
            DB::table('entrega')->where('id', $entregaId)->update($updateData);

            // Restar stock sólo por delta y sólo si delta > 0
            if ($delta > 0) {
                $productoId = (int) $entrega->producto_id;
                $producto = DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
                if ($producto) {
                    $nuevoStock = max(0, ((int) $producto->stock_produc) - $delta);
                    DB::table('productos')->where('id', $productoId)->update(['stock_produc' => $nuevoStock, 'updated_at' => now()]);
                }
            }

            // Intentar auto-completar
            $auto = $this->attemptAutoComplete((int) $entrega->requisicion_id);
            DB::commit();

            Log::info('confirmarEntrega updated & stock adjusted', ['entrega_id' => $entregaId, 'delta' => $delta, 'auto_completed' => $auto]);

            return response()->json(['success' => true, 'message' => $auto ? 'Entrega confirmada y requisición completada' : 'Entrega confirmada', 'delta_stock' => $delta, 'auto_completed' => $auto]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirmarEntrega', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al confirmar entrega'], 500);
        }
    }

    /**
     * Confirmar una recepción (registro único).
     * Actualiza tabla recepcion y prueba auto-complete para la requisición asociada.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmarRecepcion(Request $request)
    {
        $recepcionId = $request->input('recepcion_id') ?? $request->input('id');
        $cantidad = (int) $request->input('cantidad', 0);

        if (! $recepcionId || $cantidad < 0) {
            return response()->json(['success' => false, 'message' => 'Datos inválidos'], 400);
        }

        try {
            DB::beginTransaction();
            $rec = DB::table('recepcion')->where('id', $recepcionId)->whereNull('deleted_at')->lockForUpdate()->first();
            if (! $rec) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'Registro de recepción no encontrado'], 404);
            }
            $max = (int) $rec->cantidad;
            $cantidad = min($cantidad, $max);
            $sessionUser = session('user') ?? [];
            $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? null;
            $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? session('user_name') ?? data_get($sessionUser, 'email') ?? session('user.email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? $request->input('recepcion_user') ?? null;
            $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int) $receiverId : $receiverId) : null;
            $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;
            $updateData = [];
            if (Schema::hasColumn('recepcion', 'cantidad_recibida')) {
                $updateData['cantidad_recibida'] = $cantidad;
            } elseif (Schema::hasColumn('recepcion', 'cantidad_recibido')) {
                $updateData['cantidad_recibido'] = $cantidad;
            }
            $nameCols = ['reception_user', 'recepcion_user', 'receptor_user'];
            $idCols = ['reception_user_id', 'recepcion_user_id', 'receptor_user_id'];
            foreach ($nameCols as $c) {
                if (Schema::hasColumn('recepcion', $c) && $receiverName !== null) {
                    $updateData[$c] = $receiverName;
                }
            }
            foreach ($idCols as $c) {
                if (Schema::hasColumn('recepcion', $c) && $receiverId !== null) {
                    $updateData[$c] = $receiverId;
                }
            }
            if (empty($updateData)) {
                DB::rollBack();

                return response()->json(['success' => false, 'message' => 'No hay columnas para actualizar'], 500);
            }
            $updateData['updated_at'] = now();
            DB::table('recepcion')->where('id', $recepcionId)->update($updateData);
            // Obtener requisicion id vía orden de compra
            $requisicionId = DB::table('orden_compras')->where('id', $rec->orden_compra_id)->value('requisicion_id');
            $auto = false;
            if ($requisicionId) {
                $auto = $this->attemptAutoComplete((int) $requisicionId);
            }
            DB::commit();

            return response()->json(['success' => true, 'message' => $auto ? 'Recepción confirmada y requisición completada' : 'Recepción confirmada', 'auto_completed' => $auto]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirmarRecepcion', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error al confirmar recepción'], 500);
        }
    }

    /**
     * Confirmar recepciones/entregas en masa.
     * Recibe un array de items {id,cantidad} y procesa cada uno de forma transaccional.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmarRecepcionesMasivo(Request $request)
    {
        $tipo = $request->input('tipo', 'recepcion');
        $items = $request->input('items', []);

        if (! is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'No hay items para procesar'], 400);
        }

        $sessionUser = session('user') ?? [];
        $receiverId = data_get($sessionUser, 'id') ?? session('user.id') ?? $request->input('reception_user_id') ?? $request->input('user_id') ?? null;
        $receiverName = data_get($sessionUser, 'name') ?? session('user.name') ?? data_get($sessionUser, 'email') ?? $request->input('reception_user') ?? $request->input('user_name') ?? null;

        $receiverId = $receiverId !== null ? (is_numeric($receiverId) ? (int) $receiverId : $receiverId) : null;
        $receiverName = is_string($receiverName) ? trim($receiverName) : $receiverName;

        $table = $tipo === 'entrega' ? 'entrega' : 'recepcion';
        $qtyCols = ($table === 'entrega') ? ['cantidad_recibido', 'cantidad_recibida'] : ['cantidad_recibida', 'cantidad_recibido'];
        $nameCols = ['reception_user', 'recepcion_user', 'receptor_user'];
        $idCols = ['reception_user_id', 'recepcion_user_id', 'receptor_user_id'];

        $results = [];

        DB::beginTransaction();
        try {
            $requisicionesTouched = [];
            foreach ($items as $it) {
                $id = isset($it['id']) ? (int) $it['id'] : null;
                $cantidad = isset($it['cantidad']) ? (int) $it['cantidad'] : 0;
                if (! $id || $cantidad < 0) {
                    $results[] = ['id' => $id, 'success' => false, 'message' => 'id o cantidad inválidos'];

                    continue;
                }
                $row = DB::table($table)->where('id', $id)->whereNull('deleted_at')->lockForUpdate()->first();
                if (! $row) {
                    $results[] = ['id' => $id, 'success' => false, 'message' => 'Registro no encontrado'];

                    continue;
                }
                $max = (int) ($row->cantidad ?? $cantidad);
                if ($cantidad > $max) {
                    $cantidad = $max;
                }
                $prevRecibido = 0;
                $delta = 0;
                $productoId = null;
                $reqIdForTouch = null;
                if ($table === 'entrega') {
                    $prevRecibido = (int) ($row->cantidad_recibido ?? $row->cantidad_recibida ?? 0);
                    $delta = $cantidad - $prevRecibido;
                    if ($delta < 0) {
                        $delta = 0;
                    } $productoId = (int) $row->producto_id;
                    $reqIdForTouch = (int) $row->requisicion_id;
                } else { // recepcion
                    // Mapear requisicion via orden de compra
                    $reqIdForTouch = DB::table('orden_compras')->where('id', $row->orden_compra_id)->value('requisicion_id');
                }
                $update = [];
                foreach ($qtyCols as $c) {
                    if (Schema::hasColumn($table, $c)) {
                        $update[$c] = $cantidad;
                        break;
                    }
                }
                foreach ($nameCols as $c) {
                    if (Schema::hasColumn($table, $c) && $receiverName !== null) {
                        $update[$c] = $receiverName;
                    }
                }
                foreach ($idCols as $c) {
                    if (Schema::hasColumn($table, $c) && $receiverId !== null) {
                        $update[$c] = $receiverId;
                    }
                }
                if (empty($update)) {
                    $results[] = ['id' => $id, 'success' => false, 'message' => 'No hay columnas para actualizar'];

                    continue;
                }
                $update['updated_at'] = now();
                DB::table($table)->where('id', $id)->update($update);
                if ($table === 'entrega' && $delta > 0 && $productoId) {
                    $prodRow = DB::table('productos')->where('id', $productoId)->lockForUpdate()->first();
                    if ($prodRow) {
                        $nuevoStock = max(0, ((int) $prodRow->stock_produc) - $delta);
                        DB::table('productos')->where('id', $productoId)->update(['stock_produc' => $nuevoStock, 'updated_at' => now()]);
                    }
                }
                if ($reqIdForTouch) {
                    $requisicionesTouched[(int) $reqIdForTouch] = true;
                }
                $results[] = ['id' => $id, 'success' => true, 'affected' => 1, 'delta_stock' => ($table === 'entrega' ? $delta : 0)];
            }
            $autoCompleted = [];
            foreach (array_keys($requisicionesTouched) as $reqId) {
                if ($this->attemptAutoComplete($reqId)) {
                    $autoCompleted[] = $reqId;
                }
            }
            DB::commit();
            Log::info('confirmarRecepcionesMasivo con ajuste de stock', ['tipo' => $tipo, 'results' => $results, 'auto_completed' => $autoCompleted]);

            return response()->json(['success' => true, 'results' => $results, 'auto_completed' => $autoCompleted]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error confirmarRecepcionesMasivo', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error procesando recepciones masivas'], 500);
        }
    }

    /**
     * Cambia el estatus actual de la requisición (ayuda centralizada).
     * Evita crear duplicados si el estatus ya está asignado.
     *
     * @return void
     */
    private function setRequisicionStatus(int $requisicionId, int $estatusId, ?string $comentario = null)
    {
        $current = Estatus_Requisicion::where('requisicion_id', $requisicionId)
            ->where('estatus', 1)
            ->orderBy('date_update', 'desc')
            ->first();

        if ($current && (int) $current->estatus_id === (int) $estatusId) {
            return;
        }

        Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus' => 0]);

        Estatus_Requisicion::create([
            'requisicion_id' => $requisicionId,
            'estatus_id' => $estatusId,
            'estatus' => 1,
            'date_update' => now(),
            'comentario' => $comentario,
        ]);
    }

    /**
     * Verifica si la requisición tiene todas sus entregas/recepciones completadas.
     */
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
     * Proxy para obtener usuarios desde servicio externo VPL_CORE y devolverlos al cliente.
     * Maneja paginación flexible y múltiples formatos de respuesta.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fetchExternalUsers(Request $request)
    {
        $base = rtrim(env('VPL_CORE', ''), '\\/');
        if (empty($base)) {
            return response()->json(['ok' => false, 'message' => 'VPL_CORE no configurado'], 500);
        }

        $url = $base.'/api/usuarios';

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

                if (! $resp->ok()) {
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
                    $pageUsers = [is_object($u) ? (array) $u : $u];
                }

                if (! is_array($pageUsers)) {
                    $pageUsers = [];
                }

                $newAdded = 0;
                foreach ($pageUsers as $u) {
                    $id = $u['id'] ?? $u['user_id'] ?? $u['usuario_id'] ?? null;
                    if (! $id) {
                        continue;
                    }
                    if (isset($usedIds[$id])) {
                        continue;
                    }
                    $usedIds[$id] = true;
                    $usersRaw[] = $u;
                    $newAdded++;
                }

                // Determinar siguiente página
                $nextUrl = null;
                $linkHeader = $resp->header('Link');
                if ($linkHeader && preg_match('/<([^>]+)>;\s*rel="next"/i', $linkHeader, $m)) {
                    $nextUrl = $m[1];
                } elseif (is_array($payload) && isset($payload['links']) && ! empty($payload['links']['next'])) {
                    $nextUrl = $payload['links']['next'];
                } elseif (is_array($payload) && isset($payload['meta']) && isset($payload['meta']['current_page']) && isset($payload['meta']['last_page'])) {
                    if ((int) $payload['meta']['current_page'] < (int) $payload['meta']['last_page']) {
                        $page = (int) $payload['meta']['current_page'] + 1;
                        $nextUrl = null;
                    } else {
                        $nextUrl = null;
                    }
                } else {
                    if ($newAdded > 0 && count($pageUsers) > 0) {
                        $page = ((int) $page) + 1;
                        $nextUrl = null;
                    } else {
                        $nextUrl = null;
                    }
                }

                if ($nextUrl && ! preg_match('#^https?://#i', $nextUrl)) {
                    $nextUrl = rtrim($base, '/').'/'.ltrim($nextUrl, '/');
                }

                $iterations++;
                if ($newAdded === 0 && ! $nextUrl) {
                    break;
                }

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

            $mapped = array_values(array_filter($mapped, fn ($x) => isset($x['id'])));

            Log::info('fetchExternalUsers mapped count', ['count' => count($mapped)]);

            if (empty($mapped)) {
                Log::info('fetchExternalUsers returned empty or unexpected payload', ['url' => $url, 'body' => isset($resp) ? $resp->body() : null]);

                return response()->json(['ok' => false, 'message' => 'No se encontraron usuarios en la respuesta del servicio externo'], 200);
            }

            return response()->json(['ok' => true, 'users' => $mapped]);

        } catch (\Throwable $e) {
            Log::error('fetchExternalUsers error: '.$e->getMessage(), ['exception' => $e]);

            return response()->json(['ok' => false, 'message' => 'Error al consultar servicio externo: '.$e->getMessage()], 500);
        }
    }

    /**
     * Registrar entregas propuestas para una requisición (API).
     * Inserta registros en tabla 'entrega' y actualiza estatus a 12 (movimiento parcial registrado).
     *
     * @param  int  $requisicionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function entregarRequisicion(Request $request, $requisicionId)
    {
        try {
            $reqId = (int) $requisicionId;
            $payload = $request->all();
            $items = $payload['items'] ?? [];
            if (! is_array($items) || empty($items)) {
                return response()->json(['message' => 'Debe enviar al menos un item válido.'], 422);
            }

            $productoIds = array_values(array_unique(array_map(fn ($i) => (int) ($i['producto_id'] ?? 0), $items)));
            $validProductoIds = DB::table('productos')->whereIn('id', $productoIds)->pluck('id')->toArray();

            $now = now();
            DB::beginTransaction();

            $insertados = 0;
            $resumenItems = [];
            $entregaIds = [];
            foreach ($items as $it) {
                $pid = (int) ($it['producto_id'] ?? 0);
                $cant = (int) ($it['cantidad'] ?? 0);
                if ($pid <= 0 || $cant <= 0) {
                    continue;
                }
                if (! in_array($pid, $validProductoIds, true)) {
                    continue;
                }

                $eid = DB::table('entrega')->insertGetId([
                    'requisicion_id' => $reqId,
                    'producto_id' => $pid,
                    'cantidad' => $cant,
                    'cantidad_recibido' => null,
                    'fecha' => $now->toDateString(),
                    'user_name' => session('user.name') ?? (string) (session('user.email') ?? 'sistema'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $insertados++;
                $entregaIds[] = (int) $eid;

                // armar resumen
                try {
                    $nombre = DB::table('productos')->where('id', $pid)->value('name_produc') ?: ('Producto #'.$pid);
                } catch (\Throwable $e) {
                    $nombre = 'Producto #'.$pid;
                }
                $resumenItems[] = ['id' => $pid, 'nombre' => $nombre, 'cantidad' => $cant];
            }

            if ($insertados === 0) {
                DB::rollBack();

                return response()->json(['message' => 'No se pudo registrar ninguna entrega válida.'], 422);
            }

            // Set estatus 12 (movimiento parcial registrado) y enlazar cada entrega
            try {
                DB::table('estatus_requisicion')
                    ->where('requisicion_id', $reqId)
                    ->where('estatus', 1)
                    ->update(['estatus' => 0, 'updated_at' => $now]);

                $lastIndex = count($entregaIds) - 1;
                foreach ($entregaIds as $idx => $eid) {
                    DB::table('estatus_requisicion')->insert([
                        'requisicion_id' => $reqId,
                        'estatus_id' => 12,
                        'estatus' => ($idx === $lastIndex) ? 1 : 0,
                        'comentario' => 'Entrega registrada',
                        'entrega_id' => $eid,
                        'date_update' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo actualizar estatus a 12 para requisicion '.$reqId.': '.$e->getMessage());
            }

            DB::commit();

            // Despachar notificación por correo al solicitante
            try {
                $requisicion = Requisicion::find($reqId);
                if ($requisicion) {
                    RequisicionEntregaRegistradaJob::dispatch($requisicion, $resumenItems);
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo despachar correo de entrega registrada para requisicion '.$reqId.': '.$e->getMessage());
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en entregarRequisicion: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al registrar entregas'], 500);
        }
    }

    /**
     * Convertir montos a COP consultando tabla `trm` (maneja monedas iguales y fallos).
     *
     * @param  mixed  $amount
     * @param  string|null  $currency
     * @return float|null
     */
    public static function convertToCop($amount, $currency = 'COP')
    {
        try {
            $cur = strtoupper(trim((string) ($currency ?? 'COP')));
            $amt = is_numeric($amount) ? (float) $amount : floatval(str_replace(',', '.', preg_replace('/[^0-9,\.\-]/', '', (string) $amount)));
            if ($cur === 'COP') {
                return round($amt, 2);
            }

            // Intentar obtener tasa desde tabla `trm` (precio por 1 USD)
            try {
                $rowFrom = DB::table('trm')->where('moneda', $cur)->orderByDesc('update_date')->orderByDesc('id')->first();
                $rowTo = DB::table('trm')->where('moneda', 'COP')->orderByDesc('update_date')->orderByDesc('id')->first();
                if ($rowFrom && $rowTo && isset($rowFrom->price) && isset($rowTo->price) && (float) $rowFrom->price > 0) {
                    $pFrom = (float) $rowFrom->price;
                    $pTo = (float) $rowTo->price;
                    $rate = $pTo / $pFrom; // from -> COP

                    return round($amt * $rate, 2);
                }
            } catch (\Throwable $e) {
                // noop
            }
        } catch (\Throwable $e) {
            // noop
        }

        return null;
    }
}
