@extends('layouts.app')

@section('title', 'Crear Requisición')
@section('content')
@php
    $prefillData = null;
    $fromId = request()->query('from');
    if (!empty($fromId)) {
        try {
            $__req = \App\Models\Requisicion::with('productos')->find($fromId);
            if ($__req) {
                $distRows = DB::table('centro_producto as cp')
                    ->join('centro as c','c.id','=','cp.centro_id')
                    ->where('cp.requisicion_id', $__req->id)
                    ->select('cp.producto_id','cp.centro_id','cp.amount','c.name_centro')
                    ->get();
                $distByProd = [];
                foreach ($distRows as $r) {
                    $distByProd[$r->producto_id][] = [
                        'id' => (string)$r->centro_id,
                        'nombre' => $r->name_centro,
                        'cantidad' => (int)$r->amount,
                    ];
                }
                $prods = [];
                foreach ($__req->productos as $p) {
                    $prods[] = [
                        'id' => (int)$p->id,
                        'nombre' => $p->name_produc,
                        'unidad' => $p->unit_produc,
                        'proveedorId' => $p->proveedor_id ?? null,
                        'cantidadTotal' => (int)($p->pivot->pr_amount ?? 0),
                        'centros' => $distByProd[$p->id] ?? [],
                    ];
                }
                $prefillData = [
                    'operacion_user' => $__req->operacion_user ?? '',
                    'Recobrable' => $__req->Recobrable ?? '',
                    'prioridad_requisicion' => $__req->prioridad_requisicion ?? '',
                    'justify_requisicion' => $__req->justify_requisicion ?? '',
                    'detail_requisicion' => $__req->detail_requisicion ?? '',
                    'productos' => $prods,
                ];
            }
        } catch (\Throwable $e) {
            $prefillData = null;
        }
    }
@endphp
<x-sidebar />
<div class="max-w-5xl mx-auto p-6 mt-20">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <div class="flex justify-center items-center gap-8 py-4 mb-8">
            <img src="{{ asset('images/VigiaLogoC.png') }}" alt="Vigía Plus Logistics" class="h-16 w-auto">
            <h1 class="text-3xl font-bold text-gray-700">Crear Requisición</h1>
        </div>

        @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        <form id="requisicionForm" action="{{ route('requisiciones.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Campo Operación con búsqueda -->
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Centro de costo</label>
                <div class="relative">
                    <input type="text" id="operacionFilter" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona la operación" autocomplete="off" value="{{ old('operacion_user') }}">
                    <input type="hidden" name="operacion_user" id="operacionSelect" value="{{ old('operacion_user') }}" required>
                    <div id="operacionesDropdown" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto z-50 hidden p-1 text-sm">
                        @php
                            $operacionesLista = collect($centros ?? [])
                                ->pluck('centro_nombre')
                                ->filter(fn($v)=> !empty($v))
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        @forelse($operacionesLista as $op)
                            <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" data-value="{{ $op }}" onclick="seleccionarOperacion(event,this)">{{ $op }}</div>
                        @empty
                            <div class="p-2 text-gray-500">No hay centros de costo asignados.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Recobrable</label>
                    <select name="Recobrable" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>Recobrable
                        </option>
                        <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No
                            recobrable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Prioridad</label>
                    <select name="prioridad_requisicion" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media
                        </option>
                        <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Justificación</label>
                <textarea name="justify_requisicion" rows="3" class="w-full border rounded-lg p-2"
                    required>{{ old('justify_requisicion') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Detalles Adicionales</label>
                <textarea name="detail_requisicion" rows="3" class="w-full border rounded-lg p-2"
                    required>{{ old('detail_requisicion') }}</textarea>
            </div>

            <hr class="my-4">

            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                <button type="button" id="abrirModalBtn"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700">
                    + Añadir Producto
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="productosTable" class="w-full border border-gray-200 rounded-lg overflow-hidden mt-3">
                    <thead class="bg-gray-100 text-gray-600 text-left">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Cantidad Total</th>
                            <th class="p-3">Distribución por Centros</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="submitBtn"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Guardar Requisición
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 1: Selección de Producto -->
<div id="modalProducto" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">Seleccionar Producto</h2>
            <button id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <!-- Selección de producto (mayor ancho) -->
        <div class="mb-4 relative">
            <label class="block text-gray-600 font-semibold mb-1">Producto</label>
            <input type="text" id="productoSelect" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona un producto">
            <div id="productosList" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                @foreach ($productos as $p)
                <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded whitespace-normal break-words"
                    onclick="seleccionarOpcion(event, this, 'productoSelect')" data-id="{{ $p->id }}"
                    data-sku="{{ $p->sku ?? '' }}" data-nombre="{{ $p->name_produc }}" data-proveedor="{{ $p->proveedor_id ?? '' }}"
                    data-categoria="{{ $p->categoria_produc }}" data-unidad="{{ $p->unit_produc }}">
                    ({{ $p->sku ?? $p->id }}) {{ $p->name_produc }} ({{ $p->unit_produc }})
                </div>
                @endforeach
            </div>
        </div>

        <!-- Filtro de categoría y Cantidad -->
        <div class="grid grid-cols-3 gap-4 items-end mb-4">
            <div class="relative">
                <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
                <input type="text" id="categoriaFilter" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona una categoría">
                <div id="categoriasList" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-50 hidden p-1">
                    @php
                    // Normalizar (trim + lowercase) y eliminar duplicados preservando la primera presentación encontrada
                    $categoriasUnicas = collect($productos ?? [])->pluck('categoria_produc')
                        ->map(fn($c) => trim((string)$c))
                        ->filter()
                        ->mapWithKeys(fn($c) => [mb_strtolower($c, 'UTF-8') => $c])
                        ->values()
                        ->sort()
                        ->values();
                    @endphp
                    @foreach ($categoriasUnicas as $categoria)
                    <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" onclick="seleccionarOpcion(event, this, 'categoriaFilter')">
                        {{ $categoria }}
                    </div>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Cantidad Total</label>
                <input type="number" id="cantidadTotalInput" class="w-full border rounded-lg p-2" min="1" placeholder="Ej: 100">
            </div>
            <div class="flex items-center">
                <span id="unidadMedida" class="text-gray-600 font-semibold">Unidad: -</span>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="button" id="siguienteModalBtn"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                Siguiente <i class="ml-1 fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Distribución por Centros de Costo -->
<div id="modalDistribucion" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">Distribuir Producto</h2>
            <button id="cerrarModalDistribucionBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="font-semibold" id="productoSeleccionadoNombre"></p>
            <p class="text-sm">Cantidad total: <span id="productoSeleccionadoCantidad" class="font-bold">0</span> <span
                    id="productoSeleccionadoUnidad"></span></p>
        </div>

        <!-- Distribución por centros -->
        <div id="centrosSection" class="mt-4">
            <h4 class="text-lg font-semibold text-gray-700 mb-2">Distribución por Centros de Costo</h4>
            <p class="text-sm text-gray-500 mb-4">Distribuya la cantidad total entre los centros de costo</p>

            <div class="grid grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Subcentros</label>
                    <div class="relative">
                        <input type="text" id="centroFilter" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona un centro" autocomplete="off">
                        <input type="hidden" id="centroSelect" name="centroSelectHidden" value="">
                        <div id="centrosDropdown" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                            @php
                                $centrosUnicos = collect($centros ?? [])
                                    ->filter(function($x){ return !empty($x->centro_id); })
                                    ->unique('centro_id')
                                    ->values();
                            @endphp
                            @foreach ($centrosUnicos as $c)
                                <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" data-id="{{ $c->centro_id }}" data-nombre="{{ $c->centro_nombre }}" onclick="seleccionarCentro(event, this)">
                                    {{ $c->centro_nombre }}
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Cantidad</label>
                    <input type="number" id="cantidadCentroInput" class="w-full border rounded-lg p-2" min="1"
                        placeholder="Ej: 50">
                </div>
                <div>
                    <button type="button" id="agregarCentroBtn"
                        class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Agregar
                    </button>
                </div>
            </div>

            <div class="mt-4 text-sm font-semibold text-gray-600">
                Total asignado: <span id="totalAsignado">0</span> de <span id="cantidadDisponible">0</span> <span
                    id="unidadDisponible"></span>
            </div>

            <ul id="centrosList" class="divide-y divide-gray-200 mt-3 border rounded-lg p-2 max-h-40 overflow-y-auto">
            </ul>

            <div class="flex justify-between mt-6">
                <button type="button" id="volverModalBtn"
                    class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </button>
                <button type="button" id="guardarProductoBtn"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                    Guardar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de carga -->
<div id="cargandoAlert" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
        <p class="text-gray-700 font-semibold">Procesando, por favor espere...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Exponer datos globales para el script externo
    window.PREFILL_DATA = {!! json_encode($prefillData) !!};
    window.CATEGORIAS = @json($productos->pluck('categoria_produc')->unique()->sort()->values());
    window.PRODUCTOS_DATA = {!! json_encode($productos->map(function($p){
        return [
            'id' => $p->id,
            'sku' => $p->sku ?? '',
            'nombre' => $p->name_produc,
            'unidad' => $p->unit_produc,
            'proveedor' => $p->proveedor_id,
            'categoria' => $p->categoria_produc,
            'display' => '(' . ($p->sku ?? $p->id) . ') ' . $p->name_produc . ' (' . $p->unit_produc . ')'
        ];
    })->values()) !!};
    window.IS_REREQUEST = !!(window.PREFILL_DATA && Array.isArray(window.PREFILL_DATA.productos) && window.PREFILL_DATA.productos.length);
</script>
<script src="{{ asset('js/requisiciones/create.js') }}"></script>
@endsection