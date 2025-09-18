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
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Producto</th>
                                    <th class="px-4 py-2 text-center">Cantidad Total</th>
                                    <th class="px-4 py-2 text-left">Distribución</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisicion->productos as $prod)
                                @php
                                $distribucion = DB::table('centro_producto')
                                ->where('requisicion_id', $requisicion->id)
                                ->where('producto_id', $prod->id)
                                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                ->select('centro.name_centro', 'centro_producto.amount')
                                ->get();
                                @endphp
                                <tr class="border-t">
                                    <td class="px-4 py-3">{{ $prod->name_produc }}</td>
                                    <td class="px-4 py-3 text-center font-medium">{{ $prod->pivot->pr_amount }}</td>
                                    <td class="px-4 py-3">
                                        @if($distribucion->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($distribucion as $centro)
                                            <div class="flex justify-between items-center bg-gray-50 px-3 py-1 rounded">
                                                <span>{{ $centro->name_centro }}</span>
                                                <span
                                                    class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                                                    {{ $centro->amount }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
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
                        <table class="w-full border text-sm rounded-lg overflow-hidden bg-white">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="p-3">Producto</th>
                                    <th class="p-3">Cantidad</th>
                                    <th class="p-3">Unidad</th>
                                    <th class="p-3">Stock Disponible</th>
                                    <th class="p-3">Distribución por Centros</th>
                                    <th class="p-3 text-center">Acciones</th>
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
                    @if($estatusActual == 5)
                        <a href="{{ url('recepciones/create?requisicion_id='.$requisicion->id) }}"
                           class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow mr-2">
                            Recibir productos
                        </a>
                    @endif
                    @if($hayOrdenes || in_array($estatusActual, [5,7,8]))
                        <button type="button" id="btn-abrir-entrega-parcial"
                           class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg shadow mr-2">
                           Realizar entrega parcial de stock
                        </button>
                        <button type="button" id="btn-restaurar-stock"
                           class="px-6 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg shadow mr-2">
                           Restaurar stock
                        </button>
                    @endif
                     <a href="{{ route('ordenes_compra.download', $requisicion->id) }}"
                         class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow">
                         Descargar PDF/ZIP
                     </a>
                </div>

                <!-- Modal Entrega Parcial (funcional) -->
                @php
                    $entregables = DB::table('ordencompra_producto as ocp')
                        ->join('orden_compras as oc','oc.id','=','ocp.orden_compras_id')
                        ->join('productos as p','p.id','=','ocp.producto_id')
                        ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
                        ->whereNull('ocp.deleted_at')
                        ->where('ocp.requisicion_id', $requisicion->id)
                        ->whereNotNull('ocp.orden_compras_id')
                        ->whereNotNull('ocp.stock_e')
                        ->where('ocp.stock_e','>',0)
                        ->select('ocp.id as ocp_id','ocp.stock_e','oc.order_oc','oc.id as oc_id','p.id as producto_id','p.name_produc','p.unit_produc','prov.prov_name')
                        ->orderBy('ocp.id','desc')
                        ->get();
                @endphp
                <div id="modal-entrega-parcial" class="fixed inset-0 z-50 hidden bg-black bg-opacity-50 items-center justify-center p-4">
                    <div class="bg-white w-full max-w-3xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center px-6 py-4 border-b">
                            <h3 class="text-lg font-semibold">Entrega parcial de stock</h3>
                            <button type="button" id="ep-close" class="text-gray-600 hover:text-gray-800">✕</button>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-6">
                            <div class="border rounded-lg overflow-hidden">
                                <div class="px-4 py-2 bg-gray-50 border-b text-sm font-medium">Líneas con stock para entregar</div>
                                <div class="max-h-64 overflow-y-auto">
                                    <table class="min-w-full text-sm">
                                        <thead class="bg-gray-100 sticky top-0">
                                            <tr>
                                                <th class="px-3 py-2 text-left">Producto</th>
                                                <th class="px-3 py-2 text-center">Disponible</th>
                                                <th class="px-3 py-2 text-left">OC</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ep-tbody">
                                            @forelse($entregables as $e)
                                            <tr class="border-t hover:bg-gray-50 cursor-pointer ep-row" data-ocp-id="{{ $e->ocp_id }}" data-producto-id="{{ $e->producto_id }}" data-disponible="{{ $e->stock_e }}" data-nombre="{{ $e->name_produc }}" data-orden="{{ $e->order_oc ?? ('OC-'.$e->oc_id) }}">
                                                <td class="px-3 py-2">{{ $e->name_produc }}</td>
                                                <td class="px-3 py-2 text-center font-semibold">{{ $e->stock_e }}</td>
                                                <td class="px-3 py-2">{{ $e->order_oc ?? ('OC-'.$e->oc_id) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="px-3 py-3 text-center text-gray-500">No hay líneas con stock para entregar</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="border rounded-lg p-4 bg-gray-50" id="ep-detalle">
                                <div class="text-sm text-gray-600 mb-2">Detalle seleccionado</div>
                                <div class="space-y-2">
                                    <div><span class="text-gray-500 text-sm">Producto:</span> <span id="ep-prod" class="font-medium">—</span></div>
                                    <div><span class="text-gray-500 text-sm">Disponible (stock):</span> <span id="ep-disp" class="font-semibold">0</span></div>
                                    <div><span class="text-gray-500 text-sm">Orden:</span> <span id="ep-oc" class="font-medium">—</span></div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad a entregar</label>
                                    <input type="number" id="ep-cant" class="w-full border rounded p-2" min="1" placeholder="Ingrese cantidad" disabled>
                                    <input type="hidden" id="ep-ocp-id" value="">
                                    <input type="hidden" id="ep-producto-id" value="">
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                            <button type="button" id="ep-cancel" class="px-4 py-2 border rounded">Cancelar</button>
                            <button type="button" id="ep-save" class="px-4 py-2 bg-blue-600 text-white rounded" disabled>Realizar entrega</button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Cambiar a llave compuesta para permitir líneas distribuidas del mismo producto
    let productosAgregados = [];
    let centros = @json($centros);
    let proveedoresMap = @json($proveedores->pluck('prov_name','id'));

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
                           data-rowkey="${rowKey}" onchange="actualizarTotal('${rowKey}')">
                </div>
            `;
        });

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
                <input type="number" name="productos[${rowKey}][cantidad]" min="1" value="${cantidadOriginal}" 
                    class="w-20 border rounded p-1 text-center cantidad-total" 
                    id="cantidad-total-${rowKey}" 
                    onchange="onCantidadTotalChange('${rowKey}')" required>
                <input type="hidden" id="base-cantidad-${rowKey}" value="${cantidadOriginal}">
                <input type="hidden" name="productos[${rowKey}][stock_e]" id="stock-e-hidden-${rowKey}" value="">
            </td>
            <td class="p-3 text-center">${unidad}</td>
            <td class="p-3 text-center" id="stock-disponible-${rowKey}">${stockDisponible}</td>
            <td class="p-3">
                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                    ${centrosHtml}
                </div>
            </td>
            <td class="p-3 text-center space-x-2">
                <button type="button" onclick="toggleSacarStock('${rowKey}')" class="bg-gray-600 text-white px-3 py-1 rounded-lg mb-1">Sacar de stock</button>
                <button type="button" onclick="quitarProducto('${rowId}', '${rowKey}', ${ocpId?`'${ocpId}'`:'null'})" 
                    class="bg-red-500 text-white px-3 py-1 rounded-lg mb-1">Quitar</button>
                <div id="sacar-stock-container-${rowKey}" class="mt-2 hidden">
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" id="sacar-stock-${rowKey}" class="w-24 border rounded p-1 text-center" placeholder="" oninput="sanearSacarStock('${rowKey}')">
                        <button type="button" class="px-2 py-1 bg-blue-600 text-white rounded text-sm" onclick="confirmarSacarStock('${rowKey}')">Confirmar</button>
                    </div>
                </div>
            </td>
        `;
        table.appendChild(row);

        productosAgregados.push(rowKey);

        if (esDistribuido) {
            const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
            totalInput.value = cantidadOriginal;
            distribuirAutomaticamente(rowKey);
        } else {
            // Inicializar automáticamente la distribución con la cantidad original
            const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
            totalInput.value = cantidadOriginal;
            distribuirAutomaticamente(rowKey);
        }

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
        const inputs = document.querySelectorAll(`input[name^="productos[${rowKey}][centros]"]`);
        let total = 0;
        inputs.forEach(input => { total += parseInt(input.value) || 0; });
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
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
        if (productosAgregados.length === 0) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe añadir al menos un producto.'});
            return;
        }
        let errores = [];
        productosAgregados.forEach(key => {
            const totalInput = document.getElementById(`cantidad-total-${key}`);
            const total = parseInt(totalInput?.value) || 0;
            const distribucionInputs = document.querySelectorAll(`input[name^="productos[${key}][centros]"]`);
            let distribucionTotal = 0;
            distribucionInputs.forEach(input => { distribucionTotal += parseInt(input.value) || 0; });
            if (total !== distribucionTotal) {
                errores.push(`La distribución de la línea ${key} no coincide con la cantidad total`);
            }
            if (total < 1) {
                errores.push(`La cantidad de la línea ${key} debe ser mayor a cero`);
            }
        });
        if (errores.length > 0) {
            e.preventDefault();
            Swal.fire({ icon: 'error', title: 'Error de validación', html: errores.join('<br>') });
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
                    body: JSON.stringify({ producto_id: prodId, requisicion_id: {{ $requisicion->id }}, distribucion: distribucionData })
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
        if (chkAll) {
            chkAll.addEventListener('change', function() {
                const checked = this.checked;
                document.querySelectorAll('.chk-undo-item').forEach(chk => {
                    chk.checked = checked;
                });
            });
        }

        btnConfirmarUndo.addEventListener('click', async function() {
            const idsSeleccionados = Array.from(document.querySelectorAll('.chk-undo-item:checked')).map(chk => chk.value);
            if (idsSeleccionados.length === 0) {
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione al menos una línea para deshacer'});
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
                        body: JSON.stringify({ requisicion_id: {{ $requisicion->id }}, ocp_ids: idsSeleccionados })
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

        // Modal Entrega Parcial
        (function(){
            const modal = document.getElementById('modal-entrega-parcial');
            const btnOpen = document.getElementById('btn-abrir-entrega-parcial');
            const btnClose = document.getElementById('ep-close');
            const btnCancel = document.getElementById('ep-cancel');
            const btnSave = document.getElementById('ep-save');
            const tbody = document.getElementById('ep-tbody');
            const spanProd = document.getElementById('ep-prod');
            const spanDisp = document.getElementById('ep-disp');
            const spanOc = document.getElementById('ep-oc');
            const inpCant = document.getElementById('ep-cant');
            const inputOcpId = document.getElementById('ep-ocp-id');

            function open(){ if (modal) { modal.classList.remove('hidden'); modal.classList.add('flex'); } }
            function close(){ if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); reset(); } }
            function reset(){
                // Mantener filas; limpiar selección y controles
                if (tbody) tbody.querySelectorAll('.ep-row.bg-blue-50').forEach(r => r.classList.remove('bg-blue-50'));
                if (spanProd) spanProd.textContent = '—';
                if (spanDisp) spanDisp.textContent = '0';
                if (spanOc) spanOc.textContent = '—';
                if (inpCant) { inpCant.value = ''; inpCant.disabled = true; inpCant.removeAttribute('max'); }
                if (inputOcpId) inputOcpId.value = '';
                if (btnSave) btnSave.disabled = true;
            }

            if (btnOpen) btnOpen.addEventListener('click', open);
            if (btnClose) btnClose.addEventListener('click', close);
            if (btnCancel) btnCancel.addEventListener('click', close);
            if (modal) modal.addEventListener('click', (e)=>{ if(e.target===modal) close(); });

            tbody?.addEventListener('click', function(e){
                const row = e.target.closest('.ep-row');
                if (!row) return;
                // Selección única
                tbody.querySelectorAll('.ep-row.bg-blue-50').forEach(r => r.classList.remove('bg-blue-50'));
                row.classList.add('bg-blue-50');
                const ocpId = row.dataset.ocpId;
                const disponible = parseInt(row.dataset.disponible || '0', 10);
                const nombre = row.dataset.nombre;
                const orden = row.dataset.orden;
                spanProd.textContent = nombre;
                spanDisp.textContent = disponible;
                spanOc.textContent = orden;
                inpCant.value = disponible;
                inpCant.disabled = false;
                inpCant.min = 1;
                inpCant.max = disponible > 0 ? disponible : 1;
                inputOcpId.value = ocpId;
                btnSave.disabled = false;
            });

            if (inpCant) inpCant.addEventListener('input', function(){
                let v = parseInt(this.value || '0', 10);
                const mx = parseInt(this.max || '0', 10);
                if (isNaN(v) || v < 1) v = 1;
                if (mx > 0 && v > mx) v = mx;
                this.value = v;
            });

            btnSave.addEventListener('click', async function(){
                const ocpId = inputOcpId.value;
                const cantidad = parseInt(inpCant.value || '0', 10);
                if (!ocpId) return Swal.fire({icon:'warning', title:'Atención', text:'Seleccione una línea para entregar'});
                if (!cantidad || cantidad < 1) return Swal.fire({icon:'warning', title:'Atención', text:'Ingrese una cantidad válida'});

                try {
                    const resp = await fetch(`{{ route('recepciones.storeEntregaParcial') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify({ ocp_id: ocpId, cantidad })
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al registrar la entrega');
                    close();
                    Swal.fire({icon:'success', title:'Éxito', text:'Entrega registrada (estatus 12).'}).then(()=> location.reload());
                } catch (e) {
                    Swal.fire({icon:'error', title:'Error', text: e.message});
                }
            });
        })();
    });

    function onCantidadTotalChange(rowKey) {
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        const baseCantidadInput = document.getElementById(`base-cantidad-${rowKey}`);
        const cantidadBase = parseInt(baseCantidadInput?.value) || 0;
        let nuevaCantidad = parseInt(totalInput?.value) || 0;

        if (nuevaCantidad > cantidadBase) nuevaCantidad = cantidadBase;
        if (nuevaCantidad < 1) nuevaCantidad = 1;
        totalInput.value = nuevaCantidad;

        // Ya no sincronizamos automáticamente "sacar de stock" con la cantidad
        // ni ajustamos el stock visible aquí.

        distribuirAutomaticamente(rowKey);
    }

    function sanearSacarStock(rowKey){
        const baseCantidad = parseInt(document.getElementById(`base-cantidad-${rowKey}`)?.value || '0', 10);
        const input = document.getElementById(`sacar-stock-${rowKey}`);
        if (!input) return;
        let val = input.value.trim();
        if (val === '') return; // permitir vacío
        let n = parseInt(val, 10);
        if (isNaN(n) || n < 0) n = 0;
        const maxASacar = Math.max(0, baseCantidad - 1);
        if (n > maxASacar) n = maxASacar;
        if (String(n) !== val) input.value = n;
    }

    async function confirmarSacarStock(rowKey){
        const baseCantidad = parseInt(document.getElementById(`base-cantidad-${rowKey}`)?.value || '0', 10);
        const input = document.getElementById(`sacar-stock-${rowKey}`);
        const totalInput = document.getElementById(`cantidad-total-${rowKey}`);
        const hiddenStockE = document.getElementById(`stock-e-hidden-${rowKey}`);
        const row = document.getElementById(`producto-${rowKey}`);
        const baseStock = parseInt(row?.querySelector('input[type="hidden"][name$="[id]"]')?.dataset?.stock || '0', 10);
        const stockCell = document.getElementById(`stock-disponible-${rowKey}`);

        if (!input) return;
        let val = input.value.trim();
        let n = val === '' ? 0 : parseInt(val, 10);
        if (isNaN(n) || n < 0) n = 0;
        const maxASacar = Math.max(0, baseCantidad - 1);
        if (n > maxASacar) n = maxASacar;

        const res = await Swal.fire({
            title: 'Confirmar salida de stock',
            text: `¿Desea sacar ${n} unidad(es) del stock para esta línea?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, confirmar',
            cancelButtonText: 'Cancelar'
        });
        if (!res.isConfirmed) return;

        // Aplicar cambios: solo marcar stock_e y actualizar visual del stock.
        input.value = n === 0 ? '' : n;
        if (hiddenStockE) hiddenStockE.value = n > 0 ? n : '';
        if (stockCell) stockCell.textContent = Math.max(0, baseStock - n);

        // No cambiar la cantidad total. Mantener distribución según el total actual.
        distribuirAutomaticamente(rowKey);
    }

    function toggleSacarStock(rowKey) {
        const container = document.getElementById(`sacar-stock-container-${rowKey}`);
        container.classList.toggle('hidden');
        const inputStock = document.getElementById(`sacar-stock-${rowKey}`);
        if (!container.classList.contains('hidden')) {
            inputStock.focus();
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
</script>
@endsection