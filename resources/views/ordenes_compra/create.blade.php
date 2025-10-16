@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-md p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Crear Orden de Compra</h1>
                <a href="{{ route('ordenes_compra.lista') }}"
                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100">
                    Volver
                </a>
            </div>

            <!-- Mensaje éxito -->
            @if(session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'Aceptar'
                });
            </script>
            @endif

            <!-- ================= Datos de la Requisición ================= -->
            @if($requisicion)
            <div class="mb-8 border rounded-lg bg-gray-50 p-6 shadow-sm">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Requisición #{{ $requisicion->id }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Solicitante</h3>
                        <p><strong>Nombre:</strong> {{ $requisicion->name_user }}</p>
                        <p><strong>Email:</strong> {{ $requisicion->email_user }}</p>
                        <p><strong>Operación:</strong> {{ $requisicion->operacion_user }}</p>
                    </div>
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Información General</h3>
                        <p>
                            <strong>Prioridad:</strong>
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $requisicion->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-700' :
                                   ($requisicion->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-700' :
                                   'bg-green-100 text-green-700') }}">
                                {{ ucfirst($requisicion->prioridad_requisicion) }}
                            </span>
                        </p>
                        <p><strong>Recobrable:</strong> {{ $requisicion->Recobrable }}</p>
                    </div>
                </div>

                <div class="mb-4 text-sm text-gray-700">
                    <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
                    <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                </div>

                <!-- Distribución -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-3">Distribución Original por Centros</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm table-fixed">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left" style="width:20%">Producto</th>
                                    <th class="px-3 py-2 text-center" style="width:90px">Unidad</th>
                                    <th class="px-3 py-2 text-center" style="width:70px">Total</th>
                                    <th class="px-3 py-2 text-center" style="width:110px">Precio unitario</th>
                                    <th class="px-3 py-2 text-center" style="width:120px">Precio total</th>
                                    <th class="px-4 py-2 text-left" style="width:30%">Distribución</th>
                                </tr>
                            </thead>
                            <tbody>
                                 @php $grandTotal = 0; @endphp
                                @foreach($requisicion->productos as $prod)
                                @php
                                $distribucion = DB::table('centro_producto')
                                ->where('requisicion_id', $requisicion->id)
                                ->where('producto_id', $prod->id)
                                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                ->select('centro.name_centro', 'centro_producto.amount')
                                ->get();
                                $confirmadoEntrega = (int) DB::table('entrega')->where('requisicion_id', $requisicion->id)->where('producto_id', $prod->id)->whereNull('deleted_at')->sum(DB::raw('COALESCE(cantidad_recibido,0)'));
                                // Ignorar tabla `recepcion` aquí: considerar solo entregas
                                $confirmadoStock = 0;
                                $totalConfirmado = $confirmadoEntrega + $confirmadoStock;
                                @endphp
                                @php
                                    // Obtener precio desde productoxproveedor (nuevo esquema)
                                    try {
                                        $pp = \Illuminate\Support\Facades\DB::table('productoxproveedor')
                                            ->where('producto_id', $prod->id)
                                            ->orderBy('id')
                                            ->first();
                                        $precioUnit = (float) ($pp->price_produc ?? 0);
                                    } catch (\Throwable $e) {
                                        $precioUnit = 0.0;
                                    }
                                 $precioTotal = $precioUnit * (int)($prod->pivot->pr_amount ?? 0);
                                 $grandTotal += $precioTotal;
                                @endphp
                                <tr class="border-t">
                                    <td class="px-4 py-2">{{ $prod->name_produc }}</td>
                                    <td class="px-3 py-2 text-center">{{ $prod->unit_produc ?? '-' }}</td>
                                    <td class="px-3 py-2 text-center font-medium w-20">{{ $prod->pivot->pr_amount }} @if($totalConfirmado>0)<span class="text-xs text-gray-500">({{ $totalConfirmado }} recibido)</span>@endif</td>
                                    <td class="px-3 py-2 text-center">${{ number_format($precioUnit,2) }}</td>
                                    <td class="px-3 py-2 text-center font-semibold">${{ number_format($precioTotal,2) }}</td>
                                    <td class="px-4 py-2">
                                         @if($distribucion->count() > 0)
                                         <div class="max-h-36 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-2 p-1">
                                             @foreach($distribucion as $centro)
                                             <div class="flex justify-between items-center bg-gray-50 px-2 py-1 rounded text-sm">
                                                 <span class="truncate mr-2">{{ $centro->name_centro }}</span>
                                                 <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">{{ $centro->amount }}</span>
                                             </div>
                                             @endforeach
                                         </div>
                                         @else
                                         <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                         @endif
                                     </td>
                                 </tr>
                                 @endforeach
                                <tr class="border-t bg-gray-50">
                                    <td class="px-4 py-3 font-semibold">Total general</td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td class="px-3 py-3 font-semibold">${{ number_format($grandTotal,2) }}</td>
                                    <td></td>
                                </tr>
                             </tbody>
                        </table>
                     </div>
                </div>
            </div>
            @endif

            <!-- Mensajes de error -->
            @if($errors->any())
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700">
                <ul class="list-disc ml-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
                {{ session('error') }}
            </div>
            @endif

            <!-- Formulario para Crear Orden -->
            @if($requisicion)
            <div class="border p-6 mb-6 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Nueva Orden de Compra</h2>

                <form id="orden-form" action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Proveedor *</label>
                            <select id="proveedor_id" name="proveedor_id"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400" required>
                                <option value="">Seleccione un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Fecha de la OC *</label>
                            <input type="date" name="date_oc" value="{{ old('date_oc', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Método de Pago</label>
                            <select name="methods_oc"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Plazo de Pago</label>
                            <select name="plazo_oc"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="Contado">Contado</option>
                                <option value="30 días">30 días</option>
                                <option value="45 días">45 días</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="2"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400"></textarea>
                        </div>
                    </div>

                    <!-- Selector de productos -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Añadir Producto</label>
                        <div class="flex gap-3">
                            <select id="producto-selector"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="">Seleccione un producto</option>
                                @if($productosDisponibles->count())
                                <optgroup label="Productos sin distribuir">
                                @foreach($productosDisponibles as $producto)
                                @php
                                    // obtener lista de proveedores para este producto desde productoxproveedor
                                    try {
                                        $ppList = \Illuminate\Support\Facades\DB::table('productoxproveedor as pxp')
                                            ->join('proveedores as prov','pxp.proveedor_id','=','prov.id')
                                            ->where('pxp.producto_id', $producto->id)
                                            ->whereNull('pxp.deleted_at')
                                            ->select('pxp.id as pxp_id','pxp.proveedor_id','prov.prov_name','pxp.price_produc','pxp.moneda')
                                            ->orderBy('prov.prov_name')
                                            ->get();
                                        // Precalcular price_cop usando TRM más reciente enviado a la vista
                                        try {
                                            $trmIdx = collect($trmLatest ?? [])->keyBy(function($r){ return strtoupper($r->moneda ?? ''); });
                                            $pTo = (float) ($trmIdx->get('COP')->price ?? 0);
                                            if ($pTo > 0) {
                                                $ppList = $ppList->map(function($r) use ($trmIdx, $pTo){
                                                    try {
                                                        $from = strtoupper($r->moneda ?? 'COP');
                                                        $unit = (float) ($r->price_produc ?? 0);
                                                        if ($from === 'COP') {
                                                            $r->price_cop = round($unit, 2);
                                                        } else {
                                                            $pFrom = (float) ($trmIdx->get($from)->price ?? 0);
                                                            if ($pFrom > 0) {
                                                                $rate = $pTo / $pFrom; // FROM->COP
                                                                $r->price_cop = round($unit * $rate, 2);
                                                            } else { $r->price_cop = null; }
                                                        }
                                                    } catch (\Throwable $e) { $r->price_cop = null; }
                                                    return $r;
                                                });
                                            }
                                        } catch (\Throwable $e) { /* ignore */ }
                                    } catch (\Throwable $e) {
                                        $ppList = collect();
                                    }
                                @endphp
                                <option value="{{ $producto->id }}"
                                     data-cantidad="{{ $producto->pivot->pr_amount ?? 1 }}"
                                     data-nombre="{{ $producto->name_produc }}"
                                     data-unidad="{{ $producto->unit_produc }}"
                                     data-stock="{{ $producto->stock_produc }}"
                                     data-proveedor="{{ $producto->proveedor_id ?? '' }}"
                                     data-iva="{{ $producto->iva ?? 0 }}"
                                     data-price="{{ ($ppList->first()->price_produc ?? 0) }}"
                                     data-price-currency="{{ ($ppList->first()->moneda ?? 'COP') }}"
                                     data-providers='@json($ppList)'>
                                      {{ $producto->name_produc }} ({{ $producto->unit_produc }}) - Cantidad: {{
                                      $producto->pivot->pr_amount ?? 1 }}
                                 </option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if(isset($lineasDistribuidas) && $lineasDistribuidas->count())
                                <optgroup label="Líneas distribuidas pendientes">
                                    @foreach($lineasDistribuidas as $ld)
                                        @php $ldPrice = $ld->price_produc ?? 0; @endphp
                                        <option value="{{ $ld->producto_id }}" data-distribuido="1" data-ocp-id="{{ $ld->ocp_id }}" data-proveedor="{{ $ld->proveedor_id }}" data-nombre="{{ $ld->name_produc }}" data-unidad="{{ $ld->unit_produc }}" data-stock="{{ $ld->stock_produc }}" data-cantidad="{{ $ld->cantidad }}"
                                            data-iva="{{ $ld->iva ?? 0 }}" data-price="{{ $ldPrice }}" data-price-currency="{{ $ld->moneda ?? 'COP' }}">
                                             {{ $ld->name_produc }} - {{ $ld->prov_name ?? 'Proveedor' }} - Cant: {{ $ld->cantidad }}
                                         </option>
                                     @endforeach
                                 </optgroup>
                                 @endif
                            </select>
                            <button type="button" id="btn-add-product" onclick="if(window.openProvidersModal){ window.openProvidersModal(); } else { Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'}); }"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                ➕ Añadir
                            </button>
                            <button type="button" id="btn-abrir-modal"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                📊 Distribuir entre Proveedores
                            </button>
                            <button type="button" id="btn-abrir-undo-dist" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition">
                                ↩️ Deshacer distribución
                            </button>
                        </div>
                    </div>

                    <!-- Tabla productos (editable para crear la orden) -->
                    <div class="overflow-x-auto mt-6 max-h-[60vh] overflow-y-auto">
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Productos en la Orden</h3>
                        <table class="w-full border text-sm rounded-lg overflow-hidden bg-white table-fixed">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="p-3 text-left" style="width:30%">Producto</th>
                                    <th class="p-3 text-center" style="width:70px">Total</th>
                                    <th class="p-3 text-center" style="width:90px">Unidad</th>
                                    <th class="p-3 text-center" style="width:80px">Moneda</th>
                                    <th class="p-3 text-center" style="width:110px">Precio unitario</th>
                                    <th class="p-3 text-center" style="width:70px">IVA</th>
                                    <th class="p-3 text-center" style="width:100px">Sacado</th>
                                    <th class="p-3 text-center" style="width:110px">Stock</th>
                                    <th class="p-3" style="width:30%">Distribución</th>
                                    <th class="p-3 text-center" style="width:90px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productos-table"></tbody>
                        </table>
                    </div>

                    <!-- Modal Proveedores por Producto -->
                    <div id="modal-proveedores" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[70vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Proveedores disponibles</h3>
                                <button type="button" id="btn-cerrar-proveedores" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-4 overflow-y-auto" id="prov-list-container">
                                <div class="text-sm text-gray-500">Seleccione un proveedor para el producto seleccionado.</div>
                                <div id="prov-list" class="mt-3 space-y-2"></div>
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-3 border-t bg-gray-50">
                                <button type="button" id="btn-cancel-proveedores" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-select-prov" class="px-4 py-2 bg-indigo-600 text-white rounded">Seleccionar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Distribución -->
                    <div id="modal-distribucion" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-3xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[85vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Distribuir producto entre Proveedores</h3>
                                <button type="button" id="btn-cerrar-modal" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-6 space-y-4 grow overflow-y-auto">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Producto a distribuir</label>
                                    <select id="dist-producto-id" class="w-full border rounded-lg p-2">
                                        <option value="">Seleccione un producto</option>
                                        @foreach($productosDisponibles as $producto)
                                        <option value="{{ $producto->id }}" data-max="{{ $producto->pivot->pr_amount ?? 1 }}" data-nombre="{{ $producto->name_produc }}" data-unidad="{{ $producto->unit_produc }}">
                                            {{ $producto->name_produc }} ({{ $producto->unit_produc }}) - Cantidad: {{ $producto->pivot->pr_amount ?? 1 }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <small class="text-gray-500">Cantidad total disponible: <span id="dist-max">0</span> <span id="dist-unidad"></span></small>
                                </div>

                                <div class="overflow-x-auto max-h-[50vh] overflow-y-auto">
                                    <table class="w-full border text-sm rounded-lg bg-white">
                                        <thead class="bg-gray-100 sticky top-0 z-10">
                                            <tr>
                                                <th class="p-2 text-left">Proveedor</th>
                                                <th class="p-2 text-center">Cantidad</th>
                                                <th class="p-2 text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tabla-items-dist"></tbody>
                                        <tfoot>
                                            <tr>
                                                <td class="p-2 font-semibold">Total distribuido</td>
                                                <td class="p-2 text-center"><span id="dist-total">0</span> / <span id="dist-total-max">0</span></td>
                                                <td class="p-2 text-center">
                                                    <button type="button" id="btn-add-fila" class="px-3 py-1 bg-green-600 text-white rounded text-sm">+ Agregar</button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                <button type="button" id="btn-cancelar-modal" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-guardar-dist" class="px-4 py-2 bg-blue-600 text-white rounded">Guardar</button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Deshacer Distribución -->
                    <div id="modal-undo-distribucion" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-start justify-center overflow-y-auto">
                        <div class="bg-white w-11/12 sm:max-w-3xl my-10 rounded-lg shadow-lg overflow-hidden max-h-[85vh] flex flex-col">
                            <div class="flex justify-between items-center px-6 py-4 border-b">
                                <h3 class="text-lg font-semibold">Deshacer distribución</h3>
                                <button type="button" id="btn-cerrar-undo" class="text-gray-600 hover:text-gray-800">✕</button>
                            </div>
                            <div class="p-6 space-y-4 grow overflow-y-auto">
                                @if(($lineasDistribuidas ?? collect())->count() > 0)
                                @php
                                    $agrupadas = ($lineasDistribuidas ?? collect())
                                        ->groupBy('producto_id')
                                        ->map(function($g){
                                            return (object) [
                                                'producto_id' => $g->first()->producto_id,
                                                'name_produc' => $g->first()->name_produc,
                                                'cantidad_total' => $g->sum('cantidad'),
                                                'proveedores' => $g->pluck('prov_name')->filter()->unique()->values()->all(),
                                                'ocp_ids' => $g->pluck('ocp_id')->filter()->values()->all(),
                                            ];
                                        })->values();
                                @endphp
                                <table class="w-full border text-sm rounded bg-white">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-center"><input type="checkbox" id="chk-undo-all"></th>
                                            <th class="p-2 text-left">Producto</th>
                                            <th class="p-2 text-left">Proveedor</th>
                                            <th class="p-2 text-center">Cantidad</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($agrupadas as $grp)
                                        <tr class="border-t">
                                            <td class="p-2 text-center">
                                                <input type="checkbox" class="chk-undo-item" value="{{ $grp->producto_id }}" data-ocp-ids="{{ implode(',', $grp->ocp_ids) }}">
                                            </td>
                                            <td class="p-2">{{ $grp->name_produc }}</td>
                                            <td class="p-2">{{ count($grp->proveedores) > 1 ? 'Varios' : ($grp->proveedores[0] ?? 'Proveedor') }}</td>
                                            <td class="p-2 text-center">{{ $grp->cantidad_total }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @else
                                <div class="text-gray-600 text-sm">No hay líneas distribuidas pendientes.</div>
                                @endif
                            </div>
                            <div class="flex justify-end gap-3 px-6 py-4 border-t">
                                <button type="button" id="btn-cancelar-undo" class="px-4 py-2 border rounded">Cancelar</button>
                                <button type="button" id="btn-confirmar-undo" class="px-4 py-2 bg-orange-600 text-white rounded">Deshacer seleccionados</button>
                            </div>
                        </div>
                    </div>

                    <!-- Botón submit -->
                    <div class="flex justify-end mt-4">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow">
                            Crear Orden de Compra
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de órdenes creadas (sección aparte) -->
            <div class="border p-6 mt-10 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Órdenes de Compra Creadas</h2>
                <table class="w-full border text-sm rounded-lg overflow-hidden bg-white" id="ordenes-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Número</th>
                            <th class="p-3">Proveedor</th>
                            <th class="p-3">Productos</th>
                            <th class="p-3">Fecha de creación</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(($ordenes ?? collect()) as $orden)
                        <tr class="border-t">
                            <td class="p-3">{{ $loop->iteration }}</td>
                            <td class="p-3">{{ $orden->order_oc ?? 'N/A' }}</td>
                            <td class="p-3">
                                @php $prov = optional($orden->ordencompraProductos->first())->proveedor; @endphp
                                {{ $prov ? $prov->prov_name : 'Proveedor no disponible' }}
                            </td>
                            <td class="p-3">
                                @foreach($orden->ordencompraProductos as $p)
                                    @if($p->producto)
                                        {{ $p->producto->name_produc }} ({{ $p->total }} {{ $p->producto->unit_produc }})<br>
                                    @else
                                        Producto eliminado ({{ $p->total }})<br>
                                    @endif
                                @endforeach
                            </td>
                            <td class="p-3">{{ $orden->created_at ? $orden->created_at->format('d/m/Y') : 'Sin fecha' }}</td>
                            <td class="p-3 text-center">
                                <form action="{{ route('ordenes_compra.anular', $orden->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" onclick="confirmarAnulacion(this)" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">Anular</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Botón descargar PDF/ZIP -->
                <div class="mt-6 text-right" id="zip-container">
                    @php
                        $estatusActual = DB::table('estatus_requisicion')
                            ->where('requisicion_id', $requisicion->id)
                            ->whereNull('deleted_at')
                            ->where('estatus', 1)
                            ->value('estatus_id');
                        $hayOrdenes = ($ordenes ?? collect())->count() > 0;
                    @endphp
                    <a href="{{ route('ordenes_compra.download', $requisicion->id) }}" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow" id="btn-download-zip" data-hay="{{ $hayOrdenes ? 1 : 0 }}">
                        Descargar PDF/ZIP
                    </a>
                </div>
            </div>
            {{-- cierre del bloque @if($requisicion) del formulario y órdenes --}}
            @endif
        </div>
    </div>
</div>

@php
    // Listado para modal Restaurar stock
    $restaurables = [];
    $recepcionesRestaurables = collect();
    if (!empty($requisicion?->id)) {
        $restaurables = DB::table('ordencompra_producto as ocp')
            ->join('productos as p','p.id','=','ocp.producto_id')
            ->join('orden_compras as oc','oc.id','=','ocp.orden_compras_id')
            ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
            ->whereNull('ocp.deleted_at')
            ->where('ocp.requisicion_id', $requisicion->id)
            ->whereNotNull('ocp.stock_e')
            ->where('ocp.stock_e','>',0)
            ->select('ocp.id as ocp_id','p.name_produc','ocp.stock_e','oc.order_oc','oc.id as oc_id','prov.prov_name')
            ->orderBy('ocp.id','desc')
            ->get();
        // Recepciones desde stock (para permitir restaurar lo que se sacó)
        $recepcionesRestaurables = DB::table('recepcion as r')
            ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
            ->join('productos as p','p.id','=','r.producto_id')
            ->where('oc.requisicion_id', $requisicion->id)
            ->whereNull('r.deleted_at')
            ->select('r.id as recep_id','p.id as producto_id','p.name_produc','oc.id as oc_id','oc.order_oc','r.cantidad','r.cantidad_recibido')
            ->orderBy('r.id','desc')
            ->get();
        // Mapear cada recepción a una línea ocp con stock_e disponible (si existe)
        foreach ($recepcionesRestaurables as $idx => $r) {
            $ocpId = DB::table('ordencompra_producto as ocp')
                ->whereNull('ocp.deleted_at')
                ->where('ocp.orden_compras_id', $r->oc_id)
                ->where('ocp.producto_id', $r->producto_id)
                ->orderByDesc('ocp.stock_e')
                ->value('ocp.id');
            $recepcionesRestaurables[$idx]->ocp_id = $ocpId; // puede ser null
        }
    }
@endphp

<div id="modal-restaurar-stock" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4">
    <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg overflow-hidden flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b">
            <h3 class="text-lg font-semibold">Restaurar stock</h3>
            <button type="button" id="rs-close" class="text-gray-600 hover:text-gray-800">✕</button>
        </div>
        <div class="p-6 space-y-6">
            <div class="max-h-[40vh] overflow-y-auto border rounded">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-center">Reservado</th>
                            <th class="px-3 py-2 text-left">OC</th>
                            <th class="px-3 py-2 text-center">Restaurar</th>
                        </tr>
                    </thead>
                    <tbody id="rs-tbody">
                        @forelse($restaurables as $r)
                        <tr class="border-t">
                            <td class="px-3 py-2">{{ $r->name_produc }}</td>
                            <td class="px-3 py-2 text-center font-semibold">{{ $r->stock_e }}</td>
                            <td class="px-3 py-2">{{ $r->order_oc ?? ('OC-'.$r->oc_id) }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <input type="number" min="1" value="{{ $r->stock_e }}" class="w-24 border rounded p-1 text-center rs-cant-input" data-ocp-id="{{ $r->ocp_id }}">
                                    <button type="button" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-sm rs-restore-btn">Restaurar</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="px-3 py-3 text-center text-gray-500">No hay stock reservado para restaurar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="max-h-[40vh] overflow-y-auto border rounded">
                <div class="px-3 py-2 bg-gray-50 border-b text-sm font-medium">Salidas de stock realizadas</div>
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left">Producto</th>
                            <th class="px-3 py-2 text-left">OC</th>
                            <th class="px-3 py-2 text-center">Cantidad</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                            <th class="px-3 py-2 text-center">Restaurar</th>
                        </tr>
                    </thead>
                    <tbody id="rs-tbody-salidas">
                        @forelse($recepcionesRestaurables as $rr)
                        <tr class="border-t">
                            <td class="px-3 py-2">{{ $rr->name_produc }}</td>
                            <td class="px-3 py-2">{{ $rr->order_oc ?? ('OC-'.$rr->oc_id) }}</td>
                            <td class="px-3 py-2 text-center">{{ $rr->cantidad }}</td>
                            <td class="px-3 py-2">
                                @if(is_null($rr->cantidad_recibido) || (int)$rr->cantidad_recibido === 0)
                                    <span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Esperando confirmación</span>
                                @else
                                    <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Confirmado por {{ (int)$rr->cantidad_recibido }}</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="inline-flex items-center gap-2">
                                    <input type="number" min="1" value="{{ $rr->cantidad }}" class="w-24 border rounded p-1 text-center rs-cant-input" data-ocp-id="{{ $rr->ocp_id }}">
                                    <button type="button" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded text-sm rs-restore-btn" @if(empty($rr->ocp_id)) disabled title="Sin línea asociada" @endif>Restaurar</button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-3 py-3 text-center text-gray-500">No hay salidas de stock registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@php
    // Variables para bloquear sacar de stock por producto ya recibido y totales previos
    $productosConRecepcionIds = [];
    $entregasPrevPorProducto = [];
    // Nota: no se consultará la tabla `recepcion` en esta vista para evitar que sus registros influyan en los totales mostrados.
@endphp

<script>
    // Cambiar a llave compuesta para permitir líneas distribuidas del mismo producto
     let productosAgregados = [];
     let centros = @json($centros);
     let proveedoresMap = @json($proveedores->pluck('prov_name','id'));
     let productosConRecepcion = @json($productosConRecepcionIds);
     let entregasPrevPorProducto = @json($entregasPrevPorProducto);
     let totalConfirmadoPorProducto = @json($totalConfirmadoPorProducto ?? []);
     // Flag global: hay stock reservado sin entregar (impide anular)
     window.hayReservadoSinEntrega = @json(($entregables ?? collect())->count() > 0);
    // Flag global: existen salidas de stock (entrega) pendientes de confirmación para esta requisición
    window.haySalidasPendientes = @json(isset($requisicion) ? DB::table('entrega')->where('requisicion_id', $requisicion->id)->whereNull('deleted_at')->where(function($q){ $q->whereNull('cantidad_recibido')->orWhere('cantidad_recibido', 0); })->exists() : false);
    
    function yaTuvoEntrega(productoId){
        return productosConRecepcion.includes(Number(productoId));
    }

    function showYaEntregadoAlert(){
        Swal.fire({icon:'info', title:'Aviso', text:'Ya se realizó una entrega de stock para este producto en esta requisición.'});
    }

    // Preparar la distribución original de la requisición
    let distribucionOriginal = {};
    @if($requisicion)
        @foreach($requisicion->productos as $prod)
            distribucionOriginal[{{ $prod->id }}] = {
                @foreach(DB::table('centro_producto')->where('requisicion_id', $requisicion->id)->where('producto_id', $prod->id)->get() as $dist)
                    {{ $dist->centro_id }}: {{ $dist->amount }},
                @endforeach
            };
        @endforeach
    @endif

    function agregarProducto() {
        const selector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        const selectedOption = selector.options[selector.selectedIndex];
        const productoId = selector.value;
        if (!selectedOption || !productoId) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
            return;
        }

        const productoNombre = selectedOption.dataset.nombre;
        const proveedorId = selectedOption.dataset.proveedor;
        const unidad = selectedOption.dataset.unidad || '';
        const cantidadOriginal = parseInt(selectedOption.dataset.cantidad || '1', 10);
        const stockDisponible = parseInt(selectedOption.dataset.stock ?? '0', 10);
        const iva = parseFloat(selectedOption.dataset.iva ?? '0');
        const precio = parseFloat(selectedOption.dataset.price ?? '0');
        const precioCurrency = selectedOption.dataset.priceCurrency || selectedOption.dataset['price-currency'] || 'COP';
        const currency = selectedOption.dataset.priceCurrency || 'COP';
        const ocpId = selectedOption.dataset.ocpId || null;
        const rowKey = `${productoId}-${ocpId||'0'}`;

        if (productosAgregados.includes(rowKey)) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Esta línea ya fue agregada'});
            return;
        }

        if (selectedOption.dataset.distribuido === '1' && proveedorId) {
            proveedorSelect.value = proveedorId;
        }

        agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector, ocpId, selectedOption.dataset.distribuido === '1', iva, precio, selectedOption.dataset.priceCurrency || selectedOption.dataset['price-currency'] || 'COP');
    }

    function agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector, ocpId = null, esDistribuido = false, iva = 0, precio = 0, precioCurrency = 'COP', providersJson = '', priceCop = '') {
         const table = document.getElementById('productos-table');
         const rowId = `producto-${rowKey}`;
         if (document.getElementById(rowId)) return;

         let distribucionProducto = distribucionOriginal[productoId] || {};
         let centrosHtml = '';
         let centrosConDistribucion = [];
         for (let centroId in distribucionProducto) {
             if (distribucionProducto[centroId] > 0) {
                 let centro = centros.find(c => c.id == centroId);
                 if (centro) centrosConDistribucion.push(centro);
             }
         }
         if (centrosConDistribucion.length === 0) centrosConDistribucion = centros;
         centrosConDistribucion.forEach(centro => {
             let cantidadCentro = distribucionProducto[centro.id] || 0;
             centrosHtml += `
                 <div class="flex items-center justify-between bg-gray-50 px-2 py-1 rounded">
                     <span class="font-medium text-sm truncate">${centro.name_centro}</span>
                     <input type="number" name="productos[${rowKey}][centros][${centro.id}]" 
                            min="0" value="${cantidadCentro}" class="w-24 border rounded p-1 text-center ml-3 distribucion-centro"
                            data-rowkey="${rowKey}" onchange="actualizarTotal('${rowKey}', this)">
                 </div>
             `;
         });

         let cantidadParaComprar = cantidadOriginal;
         const precioNum = isNaN(precio) ? 0 : precio;
         const precioStr = precioNum.toFixed(2);

         const row = document.createElement('tr');
         row.id = rowId;
         row.innerHTML = `
             <td class="p-3">
                 <div class="flex items-start gap-3">
                     <div class="flex-1 min-w-0">
                         <div class="font-semibold text-gray-800 truncate">${productoNombre} ${esDistribuido && proveedorId ? `<span class=\"text-xs text-gray-500\">(Distribuido)</span>`:''}</div>
                     </div>
                 </div>
                 <input type="hidden" name="productos[${rowKey}][id]" value="${productoId}" 
                     data-proveedor="${proveedorId||''}" data-unidad="${unidad}" data-nombre="${productoNombre}" data-cantidad="${cantidadOriginal}" data-stock="${stockDisponible}" data-iva="${iva}" data-price="${precioNum}" data-price-currency="${precioCurrency}">
                 <input type="hidden" name="productos[${rowKey}][trm_oc]" id="trm_oc-${rowKey}" value="">
                 <input type="hidden" name="productos[${rowKey}][iva]" value="${iva}">
                 <input type="hidden" name="productos[${rowKey}][price]" value="${precioNum}">
                 <input type="hidden" name="productos[${rowKey}][currency]" value="${precioCurrency}">
                 ${ocpId ? `<input type=\"hidden\" name=\"productos[${rowKey}][ocp_id]\" value=\"${ocpId}\">` : ``}
             </td>
             <td class="p-3 text-center align-middle">
                 <input type="number" name="productos[${rowKey}][cantidad]" min="1" value="${cantidadParaComprar}" 
                     class="w-16 border rounded p-1 text-center cantidad-total" 
                     id="cantidad-total-${rowKey}" 
                     onchange="onCantidadTotalChange('${rowKey}')" required>
             </td>
             <td class="p-3 text-center">${unidad || '-'}</td>
             <td class="p-3 text-center" id="moneda-${rowKey}">${precioCurrency}</td>
             <td class="p-3 text-center" id="precio-${rowKey}">
                <div>${precioStr}</div>
                <div class="text-xs text-gray-500 precio-cop-span"></div>
             </td>
             <td class="p-3 text-center" id="iva-${rowKey}">${iva}%</td>
             <td class="p-3 text-center" id="sacado-stock-${rowKey}">${( (totalConfirmadoPorProducto[productoId] || 0) > 0 ? (totalConfirmadoPorProducto[productoId] + ' Entregado') : '0' )}</td>
             <td class="p-3 text-center" id="stock-disponible-${rowKey}">${stockDisponible}</td>
             <td class="p-3">
                 <div class="max-h-40 overflow-y-auto">
                     <div class="space-y-2 text-sm pr-1">
                         ${centrosHtml}
                     </div>
                 </div>
             </td>
             <td class="p-3 text-center align-middle">
                 <div class="flex flex-col items-center gap-2">
                   <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                     <input type="checkbox" name="productos[${rowKey}][apply_iva]" class="apply-iva-checkbox form-checkbox h-5 w-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500" value="1">
                     <span class="ml-1">Aplicar IVA</span>
                   </label>
                   <button type="button" id="btn-quitar-${rowKey}" onclick="quitarProducto('${rowId}', '${rowKey}', ${ocpId?`'${ocpId}'`:'null'})" title="Quitar producto" class="flex items-center gap-2 px-4 py-1 bg-red-600 hover:bg-red-700 text-white rounded-full shadow-md text-sm">
                    <span class="font-medium">Quitar</span>
                  </button>
                </div>
             </td>
         `;
        table.appendChild(row);

        // Guardar providers JSON en el input oculto para poder restaurarlo al quitar
        try {
            const inputHiddenStored = row.querySelector('input[type="hidden"][name$="[id]"]');
            // preferir el providersJson pasado como parámetro (flujo modal), si no, intentar leer del selector
            let storedProviders = '';
            let storedPriceCop = '';
            try {
                const selOpt = selector?.options?.[selector.selectedIndex];
                storedProviders = selOpt?.dataset?.providers || '';
                storedPriceCop = selOpt?.dataset?.priceCop || '';
            } catch(e) { /* ignore */ }
            if (providersJson && String(providersJson).trim() !== '') storedProviders = providersJson;
            if (priceCop && String(priceCop).trim() !== '') storedPriceCop = priceCop;
            if (inputHiddenStored) inputHiddenStored.dataset.providers = storedProviders;
            if (inputHiddenStored) inputHiddenStored.dataset.priceCop = storedPriceCop || '';

            // Si se recibió un priceCop (precio ya convertido a COP), poblar el input trm_oc y la vista inmediatamente
            try {
                if (storedPriceCop && storedPriceCop !== '') {
                    const trmInput = document.getElementById(`trm_oc-${rowKey}`);
                    const spanCop = document.querySelector(`#precio-${rowKey} .precio-cop-span`);
                    const numeric = parseLocalizedNumber(storedPriceCop) ?? 0;
                    const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                    if (trmInput) trmInput.value = rounded;
                    if (spanCop) {
                        const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                        spanCop.textContent = `COP ${formatted}`;
                    }
                }
            } catch(e) { /* ignore */ }
        } catch(e) { /* ignore */ }
         
        // Actualizar precio mostrado incluyendo conversión a COP (async) — llamada segura
        if (typeof updatePriceToCOP === 'function') {
            try {
                const maybePromise = updatePriceToCOP(rowKey, precioNum, precioCurrency);
                if (maybePromise && typeof maybePromise.then === 'function') {
                    maybePromise.catch(()=>{});
                }
            } catch(e) { /* ignore */ }
        }
 
         productosAgregados.push(rowKey);

        // Establecer la cantidad inicial sin modificar la distribución
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        totalInput.value = cantidadParaComprar;

        // Quitar opción usada del selector
        if (esDistribuido && ocpId) {
            const opts = Array.from(selector.options);
            const idx = opts.findIndex(o => o.value == String(productoId) && (o.dataset.ocpId || '') == String(ocpId));
            if (idx > -1) selector.remove(idx);
        } else {
            for (let i = 0; i < selector.options.length; i++) {
                const o = selector.options[i];
                if (o.value == String(productoId) && !o.dataset.distribuido) {
                    selector.remove(i);
                    break;
                }
            }
        }
        selector.value = "";
    }

    // Helper robusto para parsear números localizados (e.g., 1.808.795,81 o 1,808,795.81)
    function parseLocalizedNumber(val){
        try {
            if (val === null || val === undefined) return null;
            if (typeof val === 'number') return isFinite(val) ? val : null;
            let s = String(val).trim();
            if (s === '') return null;
            // Mantener solo dígitos, coma, punto y signo
            s = s.replace(/[^0-9,\.\-]/g, '');
            if (s === '' || s === '-' ) return null;
            const hasComma = s.indexOf(',') > -1;
            const hasDot = s.indexOf('.') > -1;
            if (hasComma && hasDot) {
                // Asumir punto = miles, coma = decimales
                s = s.replace(/\./g,'').replace(/,/g,'.');
            } else if (hasComma && !hasDot) {
                // Solo coma => decimales
                s = s.replace(/,/g,'.');
            } else if (!hasComma && hasDot) {
                // Si hay múltiples puntos, asumir puntos de miles y eliminar todos menos el último como decimal
                if ((s.match(/\./g) || []).length > 1) {
                    const last = s.lastIndexOf('.');
                    s = s.slice(0, last).replace(/\./g,'') + '.' + s.slice(last+1);
                }
            }
            const n = Number(s);
            return isNaN(n) ? null : n;
        } catch (_) { return null; }
    }

    function actualizarTotal(rowKey) {
        // backward compatible: allow calling without changed element
        const changedInput = arguments[1] || null;
        const inputs = document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`);
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        const maxAllowed = totalInput ? parseInt(totalInput.value || totalInput.getAttribute('data-base') || '0', 10) : 0;
        let total = 0;
        inputs.forEach(input => { total += parseInt(input.value) || 0; });
        if (maxAllowed && total > maxAllowed) {
            // reduce the changed input if provided, otherwise reduce the last input
            if (changedInput) {
                const current = parseInt(changedInput.value) || 0;
                // compute sum of others
                let others = 0;
                inputs.forEach(i => { if (i !== changedInput) others += parseInt(i.value) || 0; });
                const allowed = Math.max(0, maxAllowed - others);
                if (current > allowed) changedInput.value = allowed;
            } else {
                // fallback: shrink last input
                const last = inputs[inputs.length - 1];
                if (last) {
                    const excess = total - maxAllowed;
                    const curr = parseInt(last.value) || 0;
                    last.value = Math.max(0, curr - excess);
                }
            }
            // recompute total
            total = 0;
            inputs.forEach(input => { total += parseInt(input.value) || 0; });
        }
        if (totalInput) totalInput.value = total;
    }

    function distribuirAutomaticamente(rowKey) {
        const total = parseInt(document.getElementById(`cantidad-total-${rowKey}`)?.value) || 0;
        const inputs = Array.from(document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`));
        if (inputs.length > 0 && total >= 0) {
            // distribuir proporcionalmente si hay valores base; si no, uniforme
            const base = inputs.map(i => parseInt(i.value)||0);
            const baseSum = base.reduce((a,b)=>a+b,0);
            let asignaciones = new Array(inputs.length).fill(0);
            if (baseSum > 0) {
                let asignado = 0;
                for (let i=0;i<inputs.length;i++) {
                    asignaciones[i] = Math.floor((base[i] / baseSum) * total);
                    asignado += asignaciones[i];
                }
                let resto = total - asignado;
                for (let i=0; i<inputs.length && resto>0; i++, resto--) asignaciones[i]++;
            } else {
                const porCentro = Math.floor(total / inputs.length);
                let resto = total % inputs.length;
                asignaciones = inputs.map((_, idx) => idx < resto ? porCentro + 1 : porCentro);
            }
            inputs.forEach((input,i)=> input.value = asignaciones[i]);
        }
    }

    function quitarProducto(rowId, rowKey, ocpId = null) {
        // No permitir quitar si ya se confirmó sacar de stock
        const cont = document.getElementById(`sacar-stock-container-${rowKey}`);
        if (cont?.dataset.confirmed === '1') {
            Swal.fire({icon:'info', title:'No permitido', text:'No puede quitar el producto porque ya se confirmó la salida de stock.'});
            return;
        }
        const row = document.getElementById(rowId);
        const selector = document.getElementById('producto-selector');
        if (row) {
            const inputHidden = row.querySelector('input[type="hidden"][name$="[id]"]');
            const proveedorId = inputHidden?.dataset?.proveedor || '';
            const unidad = inputHidden?.dataset?.unidad || '';
            const nombre = inputHidden?.dataset?.nombre || '';
            const cantidad = inputHidden?.dataset?.cantidad || 0;
            const stock = inputHidden?.dataset?.stock || 0;
            const iva = inputHidden?.dataset?.iva || 0;
            const price = inputHidden?.dataset?.price || 0;
            const esDistribuido = !!ocpId;
            const productoId = inputHidden?.value;

            row.remove();
            productosAgregados = productosAgregados.filter(k => k !== rowKey);

            // Volver a agregar la opción al selector
            const opt = document.createElement('option');
            opt.value = productoId;
            opt.dataset.proveedor = proveedorId;
            opt.dataset.unidad = unidad;
            opt.dataset.nombre = nombre;
            opt.dataset.cantidad = cantidad;
            opt.dataset.stock = stock;
            opt.dataset.iva = iva;
            opt.dataset.price = price;
            if (esDistribuido) {
                opt.dataset.distribuido = '1';
                opt.dataset.ocpId = ocpId;
                const provName = proveedoresMap?.[proveedorId] || 'Proveedor';
                opt.textContent = `${nombre} - ${provName} - Cant: ${cantidad}`;
            } else {
                opt.textContent = `${nombre} (${unidad}) - Cantidad: ${cantidad}`;
            }
            // Restaurar lista de proveedores original si estaba guardada
            if (inputHidden?.dataset?.providers) opt.dataset.providers = inputHidden.dataset.providers;
            if (inputHidden?.dataset?.priceCurrency) opt.dataset.priceCurrency = inputHidden.dataset.priceCurrency;
             selector.appendChild(opt);
         }
     }

    function confirmarAnulacion(button) {
        // Bloquear anulación si hay stock reservado sin entregar
        if (window.hayReservadoSinEntrega) {
            Swal.fire({
                icon: 'warning',
                title: 'Acción no permitida',
                text: 'No puede anular mientras existan productos sacados de stock sin entregar. Realice primero la entrega parcial de stock.'
            });
            return;
        }
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción anulará la orden de compra.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    }

    // Validación al enviar con rowKey
    const ordenForm = document.getElementById('orden-form');
    ordenForm.addEventListener('submit', async function(e) {
        // Si hay salidas pendientes de confirmar, impedir continuar y avisar que se debe esperar la confirmación del usuario
        if (window.haySalidasPendientes) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Acción bloqueada', text: 'Espere la confirmación de recibido del usuario para continuar.' });
            return;
        }

        // Si no hay productos agregados, intentar añadir el producto seleccionado automáticamente
        if (productosAgregados.length === 0) {
            const sel = document.getElementById('producto-selector');
            const opt = sel?.options?.[sel.selectedIndex];
            if (opt && opt.value) {
                // intentar leer lista de proveedores del option
                let provs = [];
                try { provs = JSON.parse(opt.dataset.providers || '[]'); } catch(_) { provs = []; }
                // Si no hay proveedores asociados, agregar directamente
                if (!provs || provs.length === 0) {
                    try { agregarProducto(); } catch(_) { }
                } else if (provs.length === 1) {
                    // Si hay exactamente 1 proveedor, usarlo automáticamente
                    const p = provs[0];
                    const provId = p.proveedor_id ?? p.proveedorId ?? p.id;
                    const price = p.price_produc ?? p.price ?? 0;
                    const currency = p.moneda ?? p.currency ?? 'COP';
                    // calcular precio en COP inmediatamente
                    let priceCop = '';
                    try {
                        const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                        if (r) priceCop = String(Number(price||0) * Number(r));
                    } catch(_) {}
                    const productoId = opt.value;
                    const productoNombre = opt.dataset.nombre || '';
                    const unidad = opt.dataset.unidad || '';
                    const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
                    const stockDisponible = parseInt(opt.dataset.stock || '0', 10);
                    const ocpId = opt.dataset.ocpId || null;
                    const rowKey = `${productoId}-${ocpId||'0'}`;
                    try {
                        // remove option so it no longer appears
                        try { opt.remove(); } catch(e) {}
                        agregarProductoFinal(rowKey, productoId, productoNombre, provId, unidad, cantidadOriginal, stockDisponible, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, opt.dataset.providers || '', priceCop);
                    } catch(e) { console.error('auto add single prov failed', e); }
                } else {
                    // Múltiples proveedores: abrir modal y cancelar envío
                    e.preventDefault();
                    try { openProvidersModal(); } catch(_) {}
                    Swal.fire({ icon: 'info', title: 'Seleccione proveedor', text: 'Elija un proveedor antes de crear la orden.' });
                    return;
                }
            }
        }

        // Re-evaluar productosAgregados después del intento automático
        if (productosAgregados.length === 0) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe añadir al menos un producto.'});
            return;
        }

        const mismatches = [];

        productosAgregados.forEach(key => {
            const totalInput = document.getElementById(`cantidad-total-${key}`);
            const expected = parseInt(totalInput?.value) || 0;
            const distribucionInputs = Array.from(document.querySelectorAll(`input[name^="productos[${key}][centros]"]`));
            let distribucionTotal = 0;
            const detalles = [];
            distribucionInputs.forEach(input => {
                const val = parseInt(input.value) || 0;
                distribucionTotal += val;
                // obtener nombre del centro desde la etiqueta en la misma estructura
                const label = input.parentElement?.querySelector('label')?.textContent?.trim() || '';
                const centroName = label.replace(/:$/,'');
                detalles.push({ centro: centroName, cantidad: val });
            });

            if (expected < 1) {
                mismatches.push({ key, type: 'invalid', expected, distribucionTotal, detalles });
            } else if (expected !== distribucionTotal) {
                mismatches.push({ key, type: 'mismatch', expected, distribucionTotal, detalles });
            }
        });

        if (mismatches.length > 0) {
            e.preventDefault();
            // Construir lista simple de productos con mismatch
            const items = mismatches.map(m => {
                const nameInput = document.querySelector(`input[name="productos[${m.key}][id]"]`);
                const prodName = nameInput?.dataset?.nombre || m.key;
                return `<li style="margin-bottom:6px;">${prodName} - La cantidad no concuerda con la distribución</li>`;
            }).join('');
            const html = `<div class="text-left"><p>Corrija los siguientes productos:</p><ul style="text-align:left;margin-top:8px;">${items}</ul></div>`;
            Swal.fire({ icon: 'error', title: 'Error de validación', html: html });
            return;
        }

         // Asegurar que todos los inputs hidden trmm_oc-... estén calculados (espera las conversiones async)
         await ensureTrmInputsFilled();
    });

    function configurarAutoCargaProveedor() {
        const productoSelector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        productoSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.proveedor) {
                proveedorSelect.value = selectedOption.dataset.proveedor;
            }
            const ivaSpan = document.getElementById('producto-iva');
            if (ivaSpan) ivaSpan.textContent = `${parseFloat(selectedOption?.dataset?.iva || '0')}%`;
        });
    }

    // Modal: abrir, cerrar, validar y guardar por AJAX
    document.addEventListener('DOMContentLoaded', function() {
        configurarAutoCargaProveedor();
        // Bloquear descarga si no hay datos o si existen salidas pendientes por confirmar
        const btnZip = document.getElementById('btn-download-zip');
        if (btnZip) {
            btnZip.addEventListener('click', async function(e){
                e.preventDefault();
                if (window.haySalidasPendientes) {
                    Swal.fire({ icon:'warning', title:'Acción bloqueada', text:'Existen salidas de stock pendientes de confirmar. Confirme las cantidades recibidas antes de descargar.' });
                    return;
                }
                if ((this.dataset?.hay || '0') !== '1') {
                    Swal.fire({ icon:'info', title:'Sin datos', text:'No hay órdenes para descargar.' });
                    return;
                }

                try {
                    showStockLoader('Generando hashes y preparando descarga...');
                    const resp = await fetch(`{{ route('ordenes_compra.ensure_hashes', $requisicion->id) }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({})
                    });
                    const data = await resp.json();
                    hideStockLoader();
                    if (!resp.ok) throw new Error(data.message || 'Error preparando la descarga');

                    const href = this.getAttribute('href');
                    if (href) {
                        window.location.href = href;
                    }
                } catch (err) {
                    hideStockLoader();
                    Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Ocurrió un error al preparar la descarga.' });
                }
            });
        }
        const modal = document.getElementById('modal-distribucion');
        const btnAbrir = document.getElementById('btn-abrir-modal');
        const btnCerrar = document.getElementById('btn-cerrar-modal');
        const btnCancelar = document.getElementById('btn-cancelar-modal');
        const selectProd = document.getElementById('dist-producto-id');
        const spanMax = document.getElementById('dist-max');
        const spanTotalMax = document.getElementById('dist-total-max');
        const spanUnidad = document.getElementById('dist-unidad');
        const tbody = document.getElementById('tabla-items-dist');
        const spanTotal = document.getElementById('dist-total');
        const btnAddFila = document.getElementById('btn-add-fila');
        const btnGuardar = document.getElementById('btn-guardar-dist');

        function abrirModal() { modal.classList.remove('hidden'); modal.classList.add('flex'); }
        function cerrarModal() { modal.classList.add('hidden'); modal.classList.remove('flex'); limpiarModal(); }

        btnAbrir?.addEventListener('click', abrirModal);
        btnCerrar?.addEventListener('click', cerrarModal);
        btnCancelar?.addEventListener('click', cerrarModal);

        // Deshabilitar "+ Agregar" hasta seleccionar producto
        if (btnAddFila) btnAddFila.disabled = true;

        selectProd.addEventListener('change', function() {
            const opt = this.options[this.selectedIndex];
            const max = parseInt(opt?.dataset?.max || '0', 10);
            spanMax.textContent = max;
            spanTotalMax.textContent = max;
            spanUnidad.textContent = opt?.dataset?.unidad || '';
            calcularTotal();
            if (btnAddFila) btnAddFila.disabled = !this.value || (parseInt(spanTotal.textContent||'0',10) >= max);
        });

        btnAddFila.addEventListener('click', function() {
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            const totalActual = parseInt(spanTotal.textContent || '0', 10);
            const restante = max - totalActual;
            if (!selectProd.value) {
                Swal.fire({icon: 'info', title: 'Seleccione un producto', text: 'Debe elegir un producto antes de añadir proveedores', confirmButtonText: 'Cerrar'});
                return;
            }
            if (restante <= 0) {
                Swal.fire({icon: 'info', title: 'Cantidad completa', text: 'Ya alcanzó la cantidad total, no puede añadir más proveedores.', confirmButtonText: 'Cerrar'});
                return;
            }
            const fila = document.createElement('tr');
            fila.innerHTML = `

                <td class="p-2">
                    <select class="w-full border rounded p-1 prov-item">
                        <option value="">Seleccione</option>
                        @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="p-2 text-center">
                    <input type="number" min="1" max="${restante}" value="${Math.min(1, restante)}" class="w-24 border rounded p-1 text-center cant-item" />
                </td>
                <td class="p-2 text-center">
                    <button type="button" class="px-2 py-1 bg-red-500 text-white rounded text-sm btn-del-fila">✕</button>
                </td>
            `;
            tbody.appendChild(fila);
            fila.querySelector('.btn-del-fila').addEventListener('click', function(){ fila.remove(); calcularTotal(); });
            fila.querySelector('.cant-item').addEventListener('input', function(e){ onCantInput(e.target); });
            calcularTotal();
        });

        function onCantInput(inp){
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let totalOtros = 0;
            tbody.querySelectorAll('.cant-item').forEach(el => { if (el !== inp) totalOtros += (parseInt(el.value)||0); });
            const permitido = Math.max(0, max - totalOtros);
            let val = parseInt(inp.value)||0;
            if (val < 1 && permitido > 0) val = 1; // mínimo 1 si hay remanente
            if (val > permitido) val = permitido;
            inp.value = val;
            calcularTotal();
        }

        function calcularTotal(){
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let total = 0;
            tbody.querySelectorAll('.cant-item').forEach(inp => total += (parseInt(inp.value)||0));
            spanTotal.textContent = total;
            // Alternar estado del botón agregar según remanente
            if (btnAddFila) btnAddFila.disabled = !selectProd.value || total >= max;
            // Actualizar max de cada input según remanente
            const restante = Math.max(0, max - total);
            tbody.querySelectorAll('.cant-item').forEach(inp => {
                const actual = parseInt(inp.value)||0;
                inp.max = actual + restante; // permite aumentar hasta cubrir remanente
            });
        }

        function limpiarModal(){
            selectProd.value = '';
            spanMax.textContent = '0';
            spanTotalMax.textContent = '0';
            spanUnidad.textContent = '';
            tbody.innerHTML = '';
            spanTotal.textContent = '0';
            if (btnAddFila) btnAddFila.disabled = true;
        }

        btnGuardar.addEventListener('click', async function(){
            const prodId = selectProd.value;
            const max = parseInt(spanTotalMax.textContent || '0', 10);
            let total = 0;
            const filas = Array.from(tbody.querySelectorAll('tr'));

            if (!prodId) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
                return;
            }

            const proveedoresElegidos = [];
            const distribucionData = [];
            for (const tr of filas){
                const prov = tr.querySelector('.prov-item').value;
                const cant = parseInt(tr.querySelector('.cant-item').value||'0', 10);
                if (!prov || cant <= 0){
                    Swal.fire({icon: 'warning', title: 'Atención', text: 'Complete proveedor y cantidad válidos'});
                    return;
                }
                proveedoresElegidos.push(prov);
                total += cant;
                distribucionData.push({proveedor_id: prov, cantidad: cant});
            }
            const setProv = new Set(proveedoresElegidos);
            if (setProv.size !== proveedoresElegidos.length){
                Swal.fire({icon: 'error', title: 'Error', text: 'No puede repetir el mismo proveedor en la distribución'});
                return;
            }

            if (total !== max){
                Swal.fire({icon: 'error', title: 'Error', text: 'El total distribuido debe ser igual a la cantidad disponible'});
                return;
            }

            try {
                                   const resp = await fetch(`{{ route('ordenes_compra.distribuirProveedores') }}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: prodId, requisicion_id: {{ $requisicion->id }}, distribucion: distribucionData, comentario: null })
            });
                                   const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Error al guardar la distribución');

                // No insertar las opciones distribuidas en el selector de la página actual
                // para evitar que se agreguen a la tabla antes de crear la orden principal.
                cerrarModal();
                Swal.fire({icon:'success', title:'Producto(s) añadidos', text:'La distribución se guardó. Se actualizará la página para mostrar las líneas distribuidas.', confirmButtonText:'Aceptar'}).then(()=> {
                    location.reload();
                               });
            } catch (e) {
                Swal.fire({icon:'error', title:'Error', text: e.message});
            }
        });

        // Modal Deshacer Distribución
        const modalUndo = document.getElementById('modal-undo-distribucion');
        const btnAbrirUndo = document.getElementById('btn-abrir-undo-dist');
        const btnCerrarUndo = document.getElementById('btn-cerrar-undo');
        const btnCancelarUndo = document.getElementById('btn-cancelar-undo');
        const btnConfirmarUndo = document.getElementById('btn-confirmar-undo');

        btnAbrirUndo.addEventListener('click', function() {
            modalUndo.classList.remove('hidden');
            modalUndo.classList.add('flex');
        });

        btnCerrarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
            modalUndo.classList.remove('flex');
        });

        btnCancelarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
            modalUndo.classList.remove('flex');
        });

        // Seleccionar/Deseleccionar todos
        const chkAll = document.getElementById('chk-undo-all');
        chkAll?.addEventListener('change', function() {
            const checked = this.checked;
            document.querySelectorAll('.chk-undo-item').forEach(chk => {
                chk.checked = checked;
            });
        });

        btnConfirmarUndo.addEventListener('click', async function() {
            const idsSeleccionados = [];
            document.querySelectorAll('.chk-undo-item:checked').forEach(chk => {
                const raw = (chk.dataset?.ocpIds || '').split(',').map(s => s.trim()).filter(Boolean);
                idsSeleccionados.push(...raw);
            });
            if (idsSeleccionados.length === 0) {
                Swal.fire({icon: 'info', title: 'Sin selección', text: 'Seleccione al menos una línea para deshacer la distribución.'});
                return;
            }

            const confirm = await Swal.fire({
                title: 'Confirmar deshacer',
                text: "Esto deshará la distribución seleccionada(s) y actualizará la orden.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, deshacer',
                cancelButtonText: 'Cancelar'
            });

            if (confirm.isConfirmed) {
                try {
                    const resp = await fetch(`{{ route('ordenes_compra.undoDistribucion') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ requisicion_id: {{ $requisicion->id }}, ocp_ids: idsSeleccionados, comentario: null })
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al deshacer distribución');

                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'La distribución se deshizo correctamente.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => { location.reload(); });
                } catch (e) {
                    Swal.fire({icon:'error', title:'Error', text: e.message});
                }
            }
        });

        // Enlazar botones 'Ver proveedores' y 'Añadir' a la implementación global (definida más abajo)
        document.getElementById('btn-ver-proveedores')?.addEventListener('click', function(){
            if (typeof window.openProvidersModal === 'function') return window.openProvidersModal();
            Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'});
        });
        document.getElementById('btn-add-product')?.addEventListener('click', function(){
            if (typeof window.openProvidersModal === 'function') return window.openProvidersModal();
            Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'});
        });

        // Cuando se confirma un proveedor elegido, propagar currency al option (se asume handler global más abajo)
        document.getElementById('btn-select-prov')?.addEventListener('click', function(){
            // fallback: if global handler exists it will run; otherwise, try to perform a minimal propagation
            if (typeof window.handleSelectProv === 'function') return window.handleSelectProv();
            const sel = document.getElementById('producto-selector');
            const opt = sel.options[sel.selectedIndex];
            if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Error', text:'No hay producto seleccionado.'}); return; }
            const chosen = document.querySelector('input[name="prov_choice"]:checked');
            if (!chosen) { Swal.fire({icon:'info', title:'Error', text:'Seleccione un proveedor.'}); return; }
            const provId = chosen.value;
            const price = chosen.dataset.price || 0;
            const currency = chosen.dataset.currency || 'COP';
            // calcular priceCop si aún no está en el dataset
            let priceCop = chosen.dataset.priceCop || '';
            if (!priceCop) {
                try {
                    const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                    if (r) priceCop = String(Number(price||0) * Number(r));
                } catch(_) {}
            }
            const provSelect = document.getElementById('proveedor_id');
            if (provSelect) provSelect.value = provId;

            // Gather product info from the selected option BEFORE removing it
            const productoId = opt.value;
            const productoNombre = opt.dataset.nombre || '';
            const unidad = opt.dataset.unidad || '';
            const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
            const stockDisponible = parseInt(opt.dataset.stock || '0', 10);
            const ocpId = opt.dataset.ocpId || null;
            const rowKey = `${productoId}-${ocpId||'0'}`;
            const providersJson = opt.dataset.providers || '';

            // remove option from selector so it no longer appears
            try { opt.remove(); } catch(e) { /* ignore */ }

            // Close modal and add the product row directly with provider info
            if (typeof hideProvidersModal === 'function') hideProvidersModal();
            agregarProductoFinal(rowKey, productoId, productoNombre, provId, unidad, cantidadOriginal, stockDisponible, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, providersJson, priceCop);
        });
    });

    function onCantidadTotalChange(rowKey) {
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        let nuevaCantidad = parseInt(totalInput?.value) || 0;
        if (nuevaCantidad < 1) nuevaCantidad = 1;
        totalInput.value = nuevaCantidad;
        // If distribution sum exceeds new total, reduce last inputs to fit
        const inputs = Array.from(document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`));
        let sum = inputs.reduce((s,i)=> s + (parseInt(i.value)||0), 0);
        if (sum > nuevaCantidad) {
            let excess = sum - nuevaCantidad;
            // reduce from the last input backwards
            for (let i = inputs.length -1; i >=0 && excess>0; i--) {
                const val = parseInt(inputs[i].value)||0;
                const reduce = Math.min(val, excess);
                inputs[i].value = Math.max(0, val - reduce);
                excess -= reduce;
            }
            // update displayed total
            actualizarTotal(rowKey);
        }
    }

    // Fallback de eventos delegados para el modal de entrega parcial
    document.addEventListener('click', function(e){
        const overlay = document.getElementById('modal-entrega-parcial');
        if (!overlay) return;
        const openBtn = e.target.closest('#btn-abrir-entrega-parcial');
        const closeBtn = e.target.closest('#ep-close');
        const cancelBtn = e.target.closest('#ep-cancel');
        if (openBtn) {
            e.preventDefault();
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');
        } else if (closeBtn || cancelBtn || e.target === overlay) {
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        }
    });

    // Modal Restaurar stock
    (function(){
        const modal = document.getElementById('modal-restaurar-stock');
        const btnOpen = document.getElementById('btn-restaurar-stock');
        const btnClose = document.getElementById('rs-close');
        const btnCancel = document.getElementById('rs-cancel');
        const tbody = document.getElementById('rs-tbody');
        const tbodySalidas = document.getElementById('rs-tbody-salidas');
        function open(){ if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); } }
        function close(){ if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } }
        btnOpen?.addEventListener('click', open);
        btnClose?.addEventListener('click', close);
        btnCancel?.addEventListener('click', close);
        modal?.addEventListener('click', (e)=>{ if(e.target===modal) close(); });

        function bindRestore(container){
            container?.addEventListener('click', async function(e){
                const btn = e.target.closest('.rs-restore-btn');
                if (!btn) return;
                const row = btn.closest('tr');
                const inp = row?.querySelector('.rs-cant-input');
                const ocpId = inp?.dataset?.ocpId;
                const cant = parseInt(inp?.value || '0', 10);
                if (!ocpId || cant < 1) return;
                try {
                    const confirm = await Swal.fire({ title:'Confirmar', text:`Restaurar ${cant} unidad(es) al inventario?`, icon:'question', showCancelButton:true, confirmButtonText:'Sí, restaurar', cancelButtonText:'Cancelar' });
                    if (!confirm.isConfirmed) return;
                    const resp = await fetch(`{{ route('recepciones.restaurarStock') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ocp_id: ocpId, cantidad: cant })
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al restaurar');
                    // Marcar visualmente como restaurado para evitar múltiples veces
                    inp.disabled = true;
                    const badge = document.createElement('span');
                    badge.className = 'px-2 py-1 rounded text-xs bg-green-100 text-green-700';
                    badge.textContent = 'Restaurado';
                    btn.replaceWith(badge);
                    Swal.fire({icon:'success', title:'Listo', text:'Stock restaurado.'});
                } catch (err) {
                    Swal.fire({icon:'error', title:'Error', text: err.message});
                }
            });
        }
        bindRestore(tbody);
        bindRestore(tbodySalidas);
    })();

    // Funciones para mostrar/ocultar modal de carga al sacar productos de stock
    function showStockLoader(message = 'Creando orden de compra') {
        const modal = document.getElementById('modal-loading-stock');
        if (!modal) return;
        const txt = modal.querySelector('.loader-text');
        if (txt) txt.textContent = message;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function hideStockLoader() {
        const modal = document.getElementById('modal-loading-stock');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // Mostrar modal de carga cuando se envía el formulario principal (sacar productos de stock)
    ordenForm.addEventListener('submit', function(e){
        // Si no fue prevenido por validaciones, mostrar loader (tiempo breve antes de la navegación)
        // La validación anterior previene el submit cuando hay errores; si llegamos aquí, mostrar loader
        if (!e.defaultPrevented) {
            showStockLoader('Creando orden de compra');
        }
    });

    // Integrar loader en el flujo de recepciones (botones .rc-save)
    document.querySelectorAll('.rc-save').forEach(btn => {
        btn.addEventListener('click', async () => {
            // Mostrar loader inmediatamente
            showStockLoader('Guardando recepciones y actualizando stock...');
            // Dejar que el handler existente realice la lógica; hideStockLoader() se llamará en catch si hay error
            // Nota: el handler re-carga la página en caso de éxito, por lo que no es necesario ocultar el loader en ese caso.
            // Si ocurre un error, el catch del handler ya muestra mensajes; además ocultamos el loader allí.
        }, { once: true });
    });

    // Si el servidor creó la orden y devolvió el hash en sesión, descargarlo automáticamente
    @if(session('created_hash'))
    (function(){
        try {
            const hash = {!! json_encode(session('created_hash')) !!};
            const orderId = {!! json_encode(session('created_order_id') ?? '') !!};
            const filename = orderId ? `orden_${orderId}_validation_hash.txt` : 'validation_hash.txt';
            const content = `Validation Hash: ${hash}\nOrder ID: ${orderId || 'N/A'}\nGenerated: ${new Date().toISOString()}`;
            const blob = new Blob([content], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            Swal.fire({ icon: 'success', title: 'Hash descargado', text: 'Se ha descargado el hash de validación. Guárdelo para futuras verificaciones.' });
        } catch (e) {
            console.error('Error descargando hash:', e);
        }
    })();
    @endif

    // CACHE + helper para obtener tasa de cambio en tiempo real usando open.er-api.com (sin API key)
    const exchangeCache = {};
    const exchangeBaseCache = {}; // cache completo por base (rates map)

    // Seed TRM desde servidor: objeto con filas recientes de la tabla `trm`
    const serverTrmRows = @json($trmLatest ?? []);
    // trmMap: moneda => price (units per 1 USD)
    const trmMap = {};
    try { (serverTrmRows || []).forEach(r => { if (r && r.moneda) trmMap[String(r.moneda).toUpperCase()] = Number(r.price); }); } catch(e) { /* noop */ }
    // Asegurar fallback razonable para USD
    if (trmMap['USD'] == null) { trmMap['USD'] = 1; }

    // Evitar llamadas externas: getExchangeRate consultará solo trmMap
    async function getExchangeRate(from = 'USD', to = 'COP', retries = 0, timeout = 3000){
        from = (from || 'COP').toUpperCase();
        to = (to || 'COP').toUpperCase();
        if (from === to) return 1;
        const key = `${from}_${to}`;
        if (exchangeCache[key]) return exchangeCache[key];

        // Intentar obtener precios desde trmMap
        const pFrom = trmMap[from] ?? null;
        const pTo = trmMap[to] ?? null;
        if (pFrom != null && pTo != null && Number(pFrom) !== 0) {
            const rate = Number(pTo) / Number(pFrom);
            exchangeCache[key] = rate;
            return rate;
        }

        // Si no hay datos suficientes en trmMap, devolver null (no intentar API externa)
        return null;
    }

    // Helper síncrono usando trmMap para obtener una tasa inmediata
    function getExchangeRateSync(from = 'USD', to = 'COP'){
        try {
            from = (from || 'COP').toUpperCase();
            to = (to || 'COP').toUpperCase();
            if (from === to) return 1;
            const pFrom = trmMap[from];
            const pTo = trmMap[to];
            if (pFrom != null && pTo != null && Number(pFrom) !== 0) {
                return Number(pTo) / Number(pFrom);
            }
        } catch(e) { /* noop */ }
        return null;
    }

    // Convierte precio unitario a COP y guarda en el campo hidden trm_oc-{rowKey}; actualiza la vista del precio en COP.
    async function updatePriceToCOP(rowKey, price, currency = 'COP'){
        try {
            const pk = String(rowKey || '');
            const input = document.getElementById(`trm_oc-${pk}`);
            const span = document.querySelector(`#precio-${pk} .precio-cop-span`);

            currency = (currency || 'COP').toString().trim().toUpperCase();
            const numPrice = Number(price || 0);

            // Solo respetar un valor existente si la moneda del producto es COP; de lo contrario, forzar recálculo
            try {
                const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                const prodCurrency = (prodIdInput?.dataset?.priceCurrency || prodIdInput?.dataset?.pricecurrency || prodIdInput?.dataset?.currency || 'COP').toString().toUpperCase();
                if (input && input.value !== '' && !isNaN(Number(input.value)) && prodCurrency === 'COP') {
                    const existing = Number(input.value);
                    if (span) span.textContent = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(existing);
                    return existing;
                }
            } catch(e){ /* ignore and continue */ }

            // Intentar usar priceCop almacenado (ya en COP)
            try {
                const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                const stored = prodIdInput?.dataset?.priceCop || prodIdInput?.dataset?.pricecop || '';
                if (stored && String(stored).trim() !== '') {
                    const numeric = parseLocalizedNumber(stored) ?? 0;
                    const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                    try {
                        input.value = rounded;
                        const span = document.querySelector(`#precio-${pk} .precio-cop-span`);
                        const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                        if (span) span.textContent = `COP ${formatted}`;
                    } catch(e){}
                    return rounded;
                }
            } catch(e){ /* ignore */ }

            if (currency === 'COP' || !currency) {
                const rounded = Math.round((Number(price || 0) + Number.EPSILON) * 100) / 100;
                if (input) input.value = rounded;
                if (span) {
                    const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                    span.textContent = `COP ${formatted}`;
                }
                return rounded;
            }

            // Obtener tasa y convertir a COP
            let rate = getExchangeRateSync(currency, 'COP');
            if (!rate) {
                try { rate = await getExchangeRate(currency, 'COP'); } catch(e){ rate = null; }
                if (!rate) { try { rate = await getExchangeRate(currency, 'COP', 3, 8000); } catch(e){ rate = null; } }
            }

            if (!rate) {
                if (span) span.textContent = '';
                // No establecer el valor oculto cuando no hay tasa para evitar guardar un precio en moneda extranjera
                return null;
            }

            const cop = Math.round((Number(price || 0) * Number(rate) + Number.EPSILON) * 100) / 100;
            if (input) input.value = cop;
            if (span) {
                const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cop);
                span.textContent = `COP ${formatted}`;
            }
            return cop;
        } catch (err) {
            console.warn('updatePriceToCOP error', err);
            // No escribir el precio original en el hidden para evitar guardar moneda extranjera
            return null;
        }
    }

    // Asegura que todos los inputs hidden trm_oc-... estén completados (espera las conversiones async)
    async function ensureTrmInputsFilled(timeoutPer = 3000){
        try {
            const hiddenInputs = Array.from(document.querySelectorAll('input[id^="trm_oc-"]'));
            const promises = hiddenInputs.map(input => {
                return new Promise(async (resolve) => {
                    try {
                        const pk = input.id.replace('trm_oc-','');
                        if (input.value !== '' && !isNaN(Number(input.value))) { return resolve(true); }
                        const prodIdInput = document.querySelector(`input[name="productos[${pk}][id]"]`);
                        const stored = prodIdInput?.dataset?.priceCop || prodIdInput?.dataset?.pricecop || '';
                        if (stored && String(stored).trim() !== '') {
                            const numeric = parseLocalizedNumber(stored) ?? 0;
                            const rounded = Math.round((numeric + Number.EPSILON) * 100) / 100;
                            try {
                                input.value = rounded;
                                const span = document.querySelector(`#precio-${pk} .precio-cop-span`);
                                const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(rounded);
                                if (span) span.textContent = `COP ${formatted}`;
                            } catch(e){}
                            return resolve(true);
                        }
                        // si no hay priceCop, intentar leer price+currency y convertir
                        let price = Number(prodIdInput?.dataset?.price || 0);
                        let currency = (prodIdInput?.dataset?.priceCurrency || prodIdInput?.dataset?.pricecurrency || prodIdInput?.dataset?.currency || 'COP');
                        if (!price || price === 0) {
                            const precioDiv = document.querySelector(`#precio-${pk} div`);
                            if (precioDiv) {
                                const numeric = parseLocalizedNumber(precioDiv.textContent);
                                price = numeric ?? 0;
                            }
                        }
                        let settled = false;
                        const p = updatePriceToCOP(pk, price, currency).then((res)=>{ settled = true; resolve(Boolean(res)); }).catch(()=>{ settled = true; resolve(false); });
                        setTimeout(()=>{ if(!settled) resolve(false); }, timeoutPer);
                    } catch(e){ resolve(false); }
                });
            });
            await Promise.all(promises);
        } catch(e){ /* noop */ }
    }

    // Modificar openProvidersModal: renderiza modal inmediatamente y actualiza COP en background
    async function openProvidersModal(){
        const sel = document.getElementById('producto-selector');
        const opt = sel.options[sel.selectedIndex];
        if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Seleccione', text:'Seleccione un producto primero.'}); return; }
        const provsJson = opt.dataset.providers || '[]';
        let provs = [];
        try { provs = JSON.parse(provsJson); } catch(e){ provs = []; }
        const container = document.getElementById('prov-list');
        container.innerHTML = '';
        if (!provs || provs.length === 0) {
            Swal.fire({icon:'info', title:'Sin proveedores', text:'No hay proveedores asociados a este producto. Se añadirá sin proveedor.'});
            agregarProducto();
            return;
        }

        // Dedupe por proveedor_id
        const map = new Map();
        provs.forEach(p => {
            const id = String(p.proveedor_id ?? p.proveedorId ?? p.id ?? Math.random());
            if (!map.has(id)) map.set(id, p);
            else {
                const existing = map.get(id);
                const curPrice = Number(p.price_produc ?? p.price ?? 0);
                const exPrice = Number(existing.price_produc ?? existing.price ?? 0);
                if (curPrice > exPrice) map.set(id, p);
            }
        });
        const unique = Array.from(map.values());

        function normalizeCurrency(s){
            if(!s) return 'COP';
            s = String(s).trim().toUpperCase();
            if (s === '$' || s === 'US$' || s === 'USD$' || /^U\$[DS]$/.test(s)) return 'USD';
            if (s.includes('€')) return 'EUR';
            if (s.includes('$') && /(COP|COL|COLOMB)/.test(s)) return 'COP';
            if (/(^|\b)COP\b|PESO(S)?\s*COLOMBIAN/.test(s)) return 'COP';
            if (/(^|\b)USD\b|D[OÓ]LAR(ES)?\b|DOLLAR|US\$/.test(s)) return 'USD';
            if (/(^|\b)EUR\b|EURO/.test(s)) return 'EUR';
            if (/^[A-Z]{3}$/.test(s)) return s;
            if (s.includes('$')) return 'USD';
            return s.slice(0,3);
        }

        // Sembrar cache desde tasas base COP si ya están disponibles (conversión X->COP = 1 / ratesCOP[X])
        (function seedFromCOP(){
            const rates = exchangeBaseCache['COP'];
            if (!rates) return;
            try {
                unique.forEach(p => {
                    const cur = normalizeCurrency(p.moneda ?? p.moneda_prov ?? p.currency ?? 'COP');
                    if (cur !== 'COP' && rates[cur] != null && rates[cur] !== 0 && !exchangeCache[`${cur}_COP`]) {
                        exchangeCache[`${cur}_COP`] = 1 / rates[cur];
                    }
                });
            } catch(_){}
        })();

        // Render inmediato usando tasas cacheadas si existen
        unique.forEach((p, idx) => {
             const id = 'prov_choice_' + idx;
             const monedaRaw = p.moneda ?? p.moneda_prov ?? p.currency ?? 'COP';
             const cur = normalizeCurrency(monedaRaw);
             const price = Number(p.price_produc ?? p.price ?? 0);
             // Usar TRM local primero; si no hay, caer a price_cop del servidor
             const rateSync = (cur === 'COP') ? 1 : getExchangeRateSync(cur, 'COP');
             let cop = null;
             if (rateSync != null) {
                 cop = Math.round(((price * Number(rateSync)) + Number.EPSILON) * 100) / 100;
             } else if (p.price_cop != null && !isNaN(Number(p.price_cop))) {
                 cop = Math.round((Number(p.price_cop) + Number.EPSILON) * 100) / 100;
             }
             const formattedCOP = (cop != null) ? new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cop) : '';
             const displayCOP = formattedCOP ? ('precio en COP: ' + formattedCOP) : '';
             let originalStr = '';
             if (/^[A-Z]{3}$/.test(cur)){
                 try { originalStr = new Intl.NumberFormat(undefined, { style:'currency', currency: cur }).format(price); }
                 catch(e) { originalStr = price + ' ' + cur; }
             } else { originalStr = price + ' ' + (String(monedaRaw) || ''); }
 
             const div = document.createElement('div');
             div.className = 'flex items-center justify-between p-2 border rounded';
             div.innerHTML = `
                 <label class="flex items-center gap-3 w-full" for="${id}">
                     <input type="radio" name="prov_choice" id="${id}" value="${p.proveedor_id}" data-price="${price}" data-currency="${cur}" data-price-cop="${cop != null ? cop : ''}" ${idx === 0 ? 'checked' : ''}>
                     <div class="flex-1">
                         <div class="font-medium">${p.prov_name}</div>
                         <div class="text-xs text-gray-500">Precio: ${originalStr} · <span class="prov-cop-amount">${displayCOP}</span></div>
                     </div>
                 </label>
             `;
             div.dataset.currency = cur;
             container.appendChild(div);
         });
 
         // Mostrar modal inmediatamente
         const modal = document.getElementById('modal-proveedores');
         modal.classList.remove('hidden');
         modal.classList.add('flex');

        // Rellenar COP inmediatamente con TRM local (sin esperar async)
        (function immediateFillCOP(){
            try {
                const radios = container.querySelectorAll('input[name="prov_choice"]');
                radios.forEach(r => {
                    const cur = String(r.dataset.currency || 'COP').toUpperCase();
                    const price = Number(r.dataset.price || 0);
                    if (!r.dataset.priceCop || String(r.dataset.priceCop).trim() === '') {
                        const rate = (cur === 'COP') ? 1 : getExchangeRateSync(cur, 'COP');
                        if (rate != null) {
                            const cop = Math.round(((price * Number(rate)) + Number.EPSILON) * 100) / 100;
                            r.dataset.priceCop = String(cop);
                            const span = r.closest('label')?.querySelector('.prov-cop-amount');
                            if (span) span.textContent = 'precio en COP: ' + new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cop);
                        }
                    }
                });
            } catch(_) { /* ignore */ }
        })();

        // Cerrar por clic fuera y botones (se agregan debajo)

        // Obtener monedas faltantes y refrescar en background
        const needed = new Set();
        unique.forEach(p => { const cur = normalizeCurrency(p.moneda ?? p.moneda_prov ?? p.currency ?? 'COP'); if (cur !== 'COP' && !exchangeCache[`${cur}_COP`]) needed.add(cur); });
        if (needed.size === 0) return;

        // Mostrar spinner en header (simple): añadir clase 'loading' y crear elemento si no existe
        let hdr = modal.querySelector('.modal-prov-header');
        if (!hdr) {
            hdr = modal.querySelector('.flex.justify-between');
            if (hdr) hdr.classList.add('modal-prov-header');
        }
        if (hdr) {
            let spin = hdr.querySelector('.prov-spinner');
            if (!spin) {
                spin = document.createElement('span');
                spin.className = 'prov-spinner ml-3 text-sm text-gray-500';
                spin.innerHTML = 'Cargando tasas...';
                hdr.appendChild(spin);
            }
        }

        // fetch each missing currency and update matching rows
        async function fetchAndUpdateCurrency(cur){
             const els = container.querySelectorAll(`[data-currency='${cur}']`);
             try {
                 let rate = await getExchangeRate(cur, 'COP');
                 if (!rate) rate = await getExchangeRate(cur, 'COP', 3, 8000);
                 if (rate){
                    exchangeCache[`${cur}_COP`] = rate;
                    els.forEach(div => {
                        const price = Number(div.querySelector('input[type="radio"]').dataset.price || 0);
                        const r = exchangeCache[`${cur}_COP`] || 0;
                        const cop = Math.round((((r * price) || 0) + Number.EPSILON) * 100) / 100;
                        const formatted = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(cop || 0);
                        const span = div.querySelector('.prov-cop-amount'); if (span) span.textContent = 'precio en COP: ' + formatted;
                        div.querySelector('input[type="radio"]').dataset.priceCop = cop || '';
                    });
                    return true;
                 }
             } catch(e){ /* ignore and retry below */ }
             els.forEach(div => { const span = div.querySelector('.prov-cop-amount'); if (span) span.textContent = ''; });
             return false;
         }
        const promises = Array.from(needed).map(cur => fetchAndUpdateCurrency(cur));
        Promise.all(promises).finally(()=>{
            const spin = modal.querySelector('.prov-spinner'); if (spin) spin.remove();
        });
    }
    // Exponer al global
    window.openProvidersModal = openProvidersModal;
    
    // Función global para confirmar proveedor seleccionado (con guardia y auto-selección)
    window.handleSelectProv = function(){
        if (window.__provSelectBusy) return;
        window.__provSelectBusy = true;
        try {
            const sel = document.getElementById('producto-selector');
            const opt = sel?.options?.[sel.selectedIndex];
            if (!opt || !opt.value) { Swal.fire({icon:'info', title:'Error', text:'No hay producto seleccionado.'}); return; }
            const modal = document.getElementById('modal-proveedores');
            let chosen = modal?.querySelector('input[name="prov_choice"]:checked');
            if (!chosen) {
                chosen = modal?.querySelector('input[name="prov_choice"]');
                if (chosen) chosen.checked = true;
            }
            if (!chosen) { Swal.fire({icon:'info', title:'Error', text:'Seleccione un proveedor.'}); return; }
            const provId = chosen.value;
            const price = chosen.dataset.price || 0;
            const currency = chosen.dataset.currency || 'COP';
            // calcular priceCop si aún no está en el dataset
            let priceCop = chosen.dataset.priceCop || '';
            if (!priceCop) {
                try {
                    const r = getExchangeRateSync(String(currency||'COP'), 'COP');
                    if (r) priceCop = String(Number(price||0) * Number(r));
                } catch(_) {}
            }
            const provSelect = document.getElementById('proveedor_id');
            if (provSelect) provSelect.value = provId;

            // Gather product info from the selected option BEFORE removing it
            const productoId = opt.value;
            const productoNombre = opt.dataset.nombre || '';
            const unidad = opt.dataset.unidad || '';
            const cantidadOriginal = parseInt(opt.dataset.cantidad || '1', 10);
            const stockDisponible = parseInt(opt.dataset.stock || '0', 10);
            const ocpId = opt.dataset.ocpId || null;
            const rowKey = `${productoId}-${ocpId||'0'}`;
            const providersJson = opt.dataset.providers || '';

            // remove option from selector so it no longer appears
            try { opt.remove(); } catch(e) { /* ignore */ }

            // Close modal and add the product row directly with provider info
            if (typeof hideProvidersModal === 'function') hideProvidersModal();
            agregarProductoFinal(rowKey, productoId, productoNombre, provId, unidad, cantidadOriginal, stockDisponible, sel, ocpId, opt.dataset.distribuido === '1', parseFloat(opt.dataset.iva || '0'), Number(price||0), currency, providersJson, priceCop);
        } finally {
            setTimeout(()=>{ window.__provSelectBusy = false; }, 500);
        }
    };

    // Helpers para cerrar y limpiar modal
     function hideProvidersModal(){
         const modal = document.getElementById('modal-proveedores');
         if (!modal) return;
         modal.classList.add('hidden');
         modal.classList.remove('flex');
         const container = document.getElementById('prov-list'); if (container) container.innerHTML = '';
     }
     document.getElementById('btn-cerrar-proveedores')?.addEventListener('click', hideProvidersModal);
     document.getElementById('btn-cancel-proveedores')?.addEventListener('click', hideProvidersModal);
     document.getElementById('modal-proveedores')?.addEventListener('click', function(e){ if (e.target === this) hideProvidersModal(); });
 
     // Cuando se confirma un proveedor elegido, propagar currency al option
     document.getElementById('btn-select-prov')?.addEventListener('click', function(){
        if (typeof window.handleSelectProv === 'function') return window.handleSelectProv();
     });
    </script>
@endsection