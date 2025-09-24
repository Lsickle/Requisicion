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
                                    $precioUnit = (float) ($prod->price_produc ?? 0);
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
                                <option value="{{ $producto->id }}"
                                    data-cantidad="{{ $producto->pivot->pr_amount ?? 1 }}"
                                    data-nombre="{{ $producto->name_produc }}"
                                    data-unidad="{{ $producto->unit_produc }}"
                                    data-stock="{{ $producto->stock_produc }}"
                                    data-proveedor="{{ $producto->proveedor_id ?? '' }}">
                                    {{ $producto->name_produc }} ({{ $producto->unit_produc }}) - Cantidad: {{
                                    $producto->pivot->pr_amount ?? 1 }}
                                </option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if(isset($lineasDistribuidas) && $lineasDistribuidas->count())
                                <optgroup label="Líneas distribuidas pendientes">
                                    @foreach($lineasDistribuidas as $ld)
                                        <option value="{{ $ld->producto_id }}" data-distribuido="1" data-ocp-id="{{ $ld->ocp_id }}" data-proveedor="{{ $ld->proveedor_id }}" data-nombre="{{ $ld->name_produc }}" data-unidad="{{ $ld->unit_produc }}" data-stock="{{ $ld->stock_produc }}" data-cantidad="{{ $ld->cantidad }}">
                                            {{ $ld->name_produc }} - {{ $ld->prov_name ?? 'Proveedor' }} - Cant: {{ $ld->cantidad }}
                                        </option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                            <button type="button" onclick="agregarProducto()"
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
                                        @foreach($lineasDistribuidas as $ld)
                                        <tr class="border-t">
                                            <td class="p-2 text-center">
                                                <input type="checkbox" class="chk-undo-item" value="{{ $ld->ocp_id }}">
                                            </td>
                                            <td class="p-2">{{ $ld->name_produc }}</td>
                                            <td class="p-2">{{ $ld->prov_name ?? 'Proveedor' }}</td>
                                            <td class="p-2 text-center">{{ $ld->cantidad }}</td>
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

                    <!-- Tabla productos -->
                    <div class="overflow-x-auto mt-6 max-h-[60vh] overflow-y-auto">
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Productos en la Orden</h3>
                        <table class="w-full border text-sm rounded-lg overflow-hidden bg-white table-fixed">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="p-3 text-left" style="width:48%">Producto</th>
                                    <th class="p-3 text-center" style="width:70px">Total</th>
                                    <th class="p-3 text-center" style="width:90px">Unidad</th>
                                    <th class="p-3 text-center" style="width:100px">Sacado</th>
                                    <th class="p-3 text-center" style="width:110px">Stock</th>
                                    <th class="p-3" style="width:30%">Distribución por Centros</th>
                                    <th class="p-3 text-center" style="width:90px">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productos-table"></tbody>
                        </table>
                     </div>

                    <!-- Botón submit -->
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow">
                            Crear Orden de Compra
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de órdenes creadas -->
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
                                    <button type="button" onclick="confirmarAnulacion(this)"
                                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
                                        Anular
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Botón descargar PDF/ZIP (siempre visible) -->
                <div class="mt-6 text-right" id="zip-container">
                    @php
                        $estatusActual = DB::table('estatus_requisicion')
                            ->where('requisicion_id', $requisicion->id)
                            ->whereNull('deleted_at')
                            ->where('estatus', 1)
                            ->value('estatus_id');
                        $hayOrdenes = ($ordenes ?? collect())->count() > 0;
                    @endphp
                    <a href="{{ route('ordenes_compra.download', $requisicion->id) }}"
                         class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow" id="btn-download-zip" data-hay="{{ $hayOrdenes ? 1 : 0 }}">
                         Descargar PDF/ZIP
                     </a>
                </div>

                {{-- Se removieron los modales de entrega y recibir de esta vista --}}
                @php
                    // Productos de todas las órdenes para el modal de entrega (estatus 5)
                    $ocpLineas = DB::table('ordencompra_producto as ocp')
                        ->join('orden_compras as oc','oc.id','=','ocp.orden_compras_id')
                        ->join('productos as p','p.id','=','ocp.producto_id')
                        ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
                        ->whereNull('ocp.deleted_at')
                        ->where('ocp.requisicion_id', $requisicion->id)
                        ->whereNotNull('ocp.orden_compras_id')
                        ->select('ocp.id as ocp_id','oc.order_oc','oc.id as oc_id','p.id as producto_id','p.name_produc','p.unit_produc','prov.prov_name','ocp.total')
                        ->orderBy('ocp.id','desc')
                        ->get();
                    // Totales requeridos por producto (desde la distribución de centros) with fallback
                    $reqCantPorProducto = DB::table('centro_producto')
                        ->where('requisicion_id', $requisicion->id)
                        ->select('producto_id', DB::raw('SUM(amount) as req'))
                        ->groupBy('producto_id')
                        ->pluck('req','producto_id');
                    if ($reqCantPorProducto->isEmpty()) {
                        $reqCantPorProducto = DB::table('producto_requisicion')
                            ->where('id_requisicion', $requisicion->id)
                            ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                            ->groupBy('id_producto')
                            ->pluck('req','producto_id');
                    }
                    // Totales recibidos por producto (confirmados en entrega)
                    $recibidoPorProducto = DB::table('entrega')
                        ->where('requisicion_id', $requisicion->id)
                        ->whereNull('deleted_at')
                        ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as rec'))
                        ->groupBy('producto_id')
                        ->pluck('rec','producto_id');
                    // No considerar recepciones desde stock en esta vista
                    $recibidoStockPorProducto = collect();
                    // Sumar ambos para obtener total confirmado por producto
                    $totalConfirmadoPorProducto = [];
                    foreach ($recibidoPorProducto as $pid => $val) {
                        $totalConfirmadoPorProducto[$pid] = ($totalConfirmadoPorProducto[$pid] ?? 0) + (int)$val;
                    }
                    foreach ($recibidoStockPorProducto as $pid => $val) {
                        $totalConfirmadoPorProducto[$pid] = ($totalConfirmadoPorProducto[$pid] ?? 0) + (int)$val;
                    }
                    // Entregas enviadas y pendientes de confirmación por producto (bloquean reenvío)
                    $pendNoConfPorProducto = DB::table('entrega')
                        ->where('requisicion_id', $requisicion->id)
                        ->whereNull('deleted_at')
                        ->where(function($q){ $q->whereNull('cantidad_recibido')->orWhere('cantidad_recibido', 0); })
                        ->select('producto_id', DB::raw('SUM(cantidad) as pend'))
                        ->groupBy('producto_id')
                        ->pluck('pend', 'producto_id');
                @endphp
                <div id="modal-entrega-oc" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4">
                    <div class="bg-white w-full max-w-4xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold">Entregar productos de órdenes de compra</h3>
                            <button type="button" id="ent-close" class="text-gray-600 hover:text-gray-800">✕</button>
                        </div>
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-3">
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" id="ent-select-all" class="border rounded">
                                    Seleccionar todos
                                </label>
                                <span class="text-xs text-gray-500">Estatus resultante: 8 (Material recibido por coordinador)</span>
                            </div>
                            <div class="max-h-[55vh] overflow-y-auto border rounded">
                                <table class="min-w-full text-sm">
                                    <thead class="bg-gray-100 sticky top-0 z-10">
                                        <tr>
                                            <th class="px-3 py-2 text-center"><input type="checkbox" id="ent-chk-header"></th>
                                            <th class="px-3 py-2 text-left">Producto</th>
                                            <th class="px-3 py-2 text-left">Proveedor</th>
                                            <th class="px-3 py-2 text-left">OC</th>
                                            <th class="px-3 py-2 text-center">Cantidad OC</th>
                                            <th class="px-3 py-2 text-center">Pendiente</th>
                                            <th class="px-3 py-2 text-center">Entregar</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ent-tbody">
                                        @forelse($ocpLineas as $l)
                                        @php
                                            $reqTot = (int) ($reqCantPorProducto[$l->producto_id] ?? 0);
                                            $recEntregas = (int) ($recibidoPorProducto[$l->producto_id] ?? 0);
                                            $recStock = (int) ($recibidoStockPorProducto[$l->producto_id] ?? 0);
                                            $recTotal = $recEntregas + $recStock;
                                            $faltTot = max(0, $reqTot - $recTotal);
                                            $isDone = $faltTot <= 0;
                                            $pendLock = (int) ($pendNoConfPorProducto[$l->producto_id] ?? 0);
                                            $maxEntregar = min((int)$l->total, $faltTot);
                                        @endphp
                                        <tr class="border-t">
                                            <td class="px-3 py-2 text-center"><input type="checkbox" class="ent-row-chk" data-ocp-id="{{ $l->ocp_id }}" data-producto-id="{{ $l->producto_id }}" data-rem="{{ $faltTot }}" {{ ($isDone || $pendLock>0) ? 'disabled' : '' }}></td>
                                            <td class="px-3 py-2">{{ $l->name_produc }}</td>
                                            <td class="px-3 py-2">{{ $l->prov_name ?? 'Proveedor' }}</td>
                                            <td class="px-3 py-2">{{ $l->order_oc ?? ('OC-'.$l->oc_id) }}</td>
                                            <td class="px-3 py-2 text-center">{{ $l->total }}</td>
                                            <td class="px-3 py-2 text-center">
                                                @if($isDone)
                                                     <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Completado</span>
                                                @elseif($pendLock>0)
                                                    <span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Enviado, esperando confirmación ({{ $pendLock }})</span>
                                                 @else
                                                     <span class="text-xs">{{ $recTotal }} / {{ $reqTot }} recibidos · Falta {{ $faltTot }}</span>
                                                 @endif
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <input type="number" min="0" max="{{ $maxEntregar }}" value="{{ $maxEntregar }}" class="w-24 border rounded p-1 text-center ent-cant-input" {{ ($isDone || $pendLock>0) ? 'disabled' : '' }}>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="7" class="px-3 py-3 text-center text-gray-500">No hay líneas de órdenes para esta requisición.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                            <button type="button" id="ent-cancel" class="px-4 py-2 border rounded">Cancelar</button>
                            <button type="button" id="ent-save" class="px-4 py-2 bg-green-600 text-white rounded">Realizar entrega</button>
                        </div>
                    </div>
                </div>

                @php
                    // Lista para ‘Recibir productos’ (pendientes por confirmar)
                    $hist = DB::table('estatus_requisicion')
                        ->where('requisicion_id', $requisicion->id)
                        ->whereNull('deleted_at')
                        ->where('estatus', 1)
                        ->orderByDesc('created_at')
                        ->first();
                    $estatusActualId = $hist->estatus_id ?? null;
                    $usarEntregaRec = in_array($estatusActualId, [8,12]);
                    if ($usarEntregaRec) {
                        $recListRec = DB::table('entrega as e')
                            ->join('productos as p','p.id','=','e.producto_id')
                            ->select('e.id','p.name_produc','e.cantidad','e.cantidad_recibido')
                            ->where('e.requisicion_id', $requisicion->id)
                            ->whereNull('e.deleted_at')
                            ->where(function($q){ $q->whereNull('e.cantidad_recibido')->orWhere('e.cantidad_recibido', 0); })
                            ->orderBy('e.id','asc')
                            ->get();
                    } else {
                        $recListRec = DB::table('recepcion as r')
                            ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
                            ->join('productos as p','p.id','=','r.producto_id')
                            ->select('r.id','p.name_produc','r.cantidad','r.cantidad_recibido')
                            ->where('oc.requisicion_id', $requisicion->id)
                            ->whereNull('r.deleted_at')
                            ->orderBy('r.id','asc')
                            ->get();
                    }
                @endphp
                <div id="modal-recibir-oc" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4" data-tipo="{{ $usarEntregaRec ? 'entrega' : 'recepcion' }}">
                    <div class="bg-white w-full max-w-2xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold">Recibir productos</h3>
                            <button type="button" id="rc-close" class="text-gray-600 hover:text-gray-800">✕</button>
                        </div>
                        <div class="p-6">
                            @if(($recListRec ?? collect())->count())
                            <table class="w-full text-sm border rounded overflow-hidden bg-white">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="p-2 text-left">Producto</th>
                                        <th class="p-2 text-center">Entregado</th>
                                        <th class="p-2 text-center">Cantidad recibida</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recListRec as $r)
                                    <tr class="border-t" data-item-id="{{ $r->id }}">
                                        <td class="p-2">{{ $r->name_produc }}</td>
                                        <td class="p-2 text-center">{{ $r->cantidad }}</td>
                                        <td class="p-2 text-center">
                                            <input type="number" min="0" max="{{ $r->cantidad }}" value="{{ $r->cantidad_recibido ?? 0 }}" class="w-24 border rounded p-1 text-center rcx-input">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="flex justify-end gap-3 mt-4">
                                <button type="button" class="px-4 py-2 border rounded" id="rc-cancel">Cancelar</button>
                                <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded" id="rc-save">Guardar todo</button>
                            </div>
                            @else
                                <div class="text-gray-600">No hay registros para esta requisición.</div>
                            @endif
                        </div>
                    </div>
                </div>
                {{-- cierre del bloque @if($requisicion) del formulario y órdenes --}}
                @endif
            </div>
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
        <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
            <button type="button" id="rs-cancel" class="px-4 py-2 border rounded">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal de carga para operaciones de sacar/restaurar stock -->
<div id="modal-loading-stock" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4" aria-hidden="true">
    <div class="bg-white rounded-lg shadow-lg p-6 flex flex-col items-center gap-4 max-w-sm w-full">
        <div class="loader w-16 h-16 border-4 border-blue-300 border-t-blue-600 rounded-full animate-spin" aria-hidden="true"></div>
        <div class="text-center">
            <div class="text-lg font-medium loader-text">Creando orden de compra</div>
            <div class="text-sm text-gray-500">Espere por favor</div>
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
        const ocpId = selectedOption.dataset.ocpId || null;
        const rowKey = `${productoId}-${ocpId||'0'}`;

        if (productosAgregados.includes(rowKey)) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Esta línea ya fue agregada'});
            return;
        }

        if (selectedOption.dataset.distribuido === '1' && proveedorId) {
            proveedorSelect.value = proveedorId;
        }

        agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector, ocpId, selectedOption.dataset.distribuido === '1');
    }

    function agregarProductoFinal(rowKey, productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector, ocpId = null, esDistribuido = false) {
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
                <div class="mb-2">
                    <label class="block text-sm text-gray-600">${centro.name_centro}:</label>
                    <input type="number" name="productos[${rowKey}][centros][${centro.id}]" 
                           min="0" value="${cantidadCentro}" class="w-20 border rounded p-1 text-center distribucion-centro"
                           data-rowkey="${rowKey}" onchange="actualizarTotal('${rowKey}', this)">
                </div>
            `;
        });

        let cantidadParaComprar = cantidadOriginal;

        const row = document.createElement('tr');
        row.id = rowId;
        row.innerHTML = `
            <td class="p-3">
                ${productoNombre} ${esDistribuido && proveedorId ? `<span class="text-xs text-gray-500">(Distribuido)</span>`:''}
                <input type="hidden" name="productos[${rowKey}][id]" value="${productoId}" 
                    data-proveedor="${proveedorId||''}" data-unidad="${unidad}" data-nombre="${productoNombre}" data-cantidad="${cantidadOriginal}" data-stock="${stockDisponible}">
                ${ocpId ? `<input type=\"hidden\" name=\"productos[${rowKey}][ocp_id]\" value=\"${ocpId}\">` : ``}
            </td>
            <td class="p-3 text-center">
                <input type="number" name="productos[${rowKey}][cantidad]" min="1" value="${cantidadParaComprar}" 
                    class="w-14 border rounded p-1 text-center cantidad-total" 
                    id="cantidad-total-${rowKey}" 
                    onchange="onCantidadTotalChange('${rowKey}')" required>
            </td>
            <td class="p-3 text-center" id="sacado-stock-${rowKey}">${( (totalConfirmadoPorProducto[productoId] || 0) > 0 ? (totalConfirmadoPorProducto[productoId] + ' Entregado') : '0' )}</td>
             <td class="p-3 text-center">${unidad}</td>
             <td class="p-3 text-center" id="stock-disponible-${rowKey}">${stockDisponible}</td>
             <td class="p-3">
                 <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                    ${centrosHtml}
                 </div>
             </td>
             <td class="p-3 text-center space-x-2">
                 <button type="button" id="btn-quitar-${rowKey}" onclick="quitarProducto('${rowId}', '${rowKey}', ${ocpId?`'${ocpId}'`:'null'})" 
                     class="bg-red-500 text-white px-3 py-1 rounded-lg mb-1">Quitar</button>
             </td>
         `;
        table.appendChild(row);

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
            if (esDistribuido) {
                opt.dataset.distribuido = '1';
                opt.dataset.ocpId = ocpId;
                const provName = proveedoresMap?.[proveedorId] || 'Proveedor';
                opt.textContent = `${nombre} - ${provName} - Cant: ${cantidad}`;
            } else {
                opt.textContent = `${nombre} (${unidad}) - Cantidad: ${cantidad}`;
            }
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
    ordenForm.addEventListener('submit', function(e) {
        // Si hay salidas pendientes de confirmar, impedir continuar y avisar que se debe esperar la confirmación del usuario
        if (window.haySalidasPendientes) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Acción bloqueada', text: 'Espere la confirmación de recibido del usuario para continuar.' });
            return;
        }

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
    });

    function configurarAutoCargaProveedor() {
        const productoSelector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        productoSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.proveedor) {
                proveedorSelect.value = selectedOption.dataset.proveedor;
            }
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

        function abrirModal() { modal.classList.remove('hidden'); }
        function cerrarModal() { modal.classList.add('hidden'); limpiarModal(); }

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
                    <input type="number" min="1" max="${restante}" value="${Math.min(1, restante)}" class="w-24 border rounded p-1 cant-item" />
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
            if (filas.length < 2) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe distribuir al menos en 2 proveedores'});
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
        });

        btnCerrarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
        });

        btnCancelarUndo.addEventListener('click', function() {
            modalUndo.classList.add('hidden');
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
            const idsSeleccionados = Array.from(document.querySelectorAll('.chk-undo-item:checked')).map(chk => chk.value);
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
                    }).then(() => {
                        location.reload();
                    });
                } catch (e) {
                    Swal.fire({icon:'error', title:'Error', text: e.message});
                }
            }
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
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
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
</script>
@endsection