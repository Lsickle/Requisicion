@extends('layouts.app')

@section('title', 'Crear Requisición Especial')
@section('content')
<x-sidebar />
<div class="min-h-screen">
    <!-- wrapper visual distinto -->
    <div class="max-w-5xl mx-auto p-6 mt-20">
        <div class="bg-white/95 shadow-2xl rounded-2xl p-6 border-2 border-amber-400 ring-1 ring-amber-300">
            <div class="flex justify-center items-center gap-6 py-4 mb-6 relative">
                <img src="{{ asset('images/VigiaLogoC.png') }}" alt="Vigía Plus Logistics" class="h-14 w-auto">
                <div class="flex flex-col items-start">
                    <h1 class="text-3xl font-extrabold text-gray-700 tracking-tight">Crear Requisición</h1>
                    <span
                        class="mt-1 inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold uppercase">
                        <!-- pequeña estrella -->
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.802 2.036a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118L10.95 13.8a1 1 0 00-1.176 0l-2.984 2.083c-.784.57-1.838-.197-1.54-1.118l1.07-3.292a1 1 0 00-.364-1.118L3.154 8.72c-.783-.57-.38-1.81.588-1.81h3.462a1 1 0 00.95-.69l1.07-3.292z" />
                        </svg>
                        Especial
                    </span>
                </div>
            </div>

            @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({ icon: 'success', title: '¡Listo!', text: '{{ session('success') }}', confirmButtonText: 'OK', confirmButtonColor: '#d97706' });
            });
            </script>
            @endif

            @if ($errors->any())
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ icon: 'error', title: 'Error', html: `{!! implode('<br>', $errors->all()) !!}`, confirmButtonText: 'OK', confirmButtonColor: '#d97706' });
            });
            </script>
            @endif

            <form id="requisicionForm" action="{{ route('requisiciones.store') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="type" value="Especial">

                <!-- Campo Operación con búsqueda -->
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Centro de costo</label>
                    <div class="relative">
                        <input type="text" id="operacionFilter"
                            class="w-full border-amber-300 focus:ring-amber-300/60 focus:border-amber-400 border rounded-lg p-2"
                            placeholder="Escribe o selecciona la operación" autocomplete="off"
                            value="{{ old('operacion_user') }}">
                        <input type="hidden" name="operacion_user" id="operacionSelect"
                            value="{{ old('operacion_user') }}" required>
                        <div id="operacionesDropdown"
                            class="absolute left-0 w-full bg-white border border-amber-300 rounded-lg shadow-lg mt-1 max-h-56 overflow-y-auto z-50 hidden p-1 text-sm">
                            {!! $operacionesOptionsHtml !!}
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Recobrable</label>
                        <select name="Recobrable"
                            class="w-full border-amber-300 focus:ring-amber-300/60 focus:border-amber-400 border rounded-lg p-2"
                            required>
                            <option value="">-- Selecciona --</option>
                            <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>
                                Recobrable
                            </option>
                            <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No
                                recobrable</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-1">Prioridad</label>
                        <select name="prioridad_requisicion"
                            class="w-full border-amber-300 focus:ring-amber-300/60 focus:border-amber-400 border rounded-lg p-2"
                            required>
                            <option value="">-- Selecciona --</option>
                            <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja
                            </option>
                            <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media
                            </option>
                            <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Justificación</label>
                    <textarea name="justify_requisicion" rows="3"
                        class="w-full border-amber-300 focus:ring-amber-300/60 focus:border-amber-400 border rounded-lg p-2"
                        required>{{ old('justify_requisicion') }}</textarea>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Detalles Adicionales</label>
                    <textarea name="detail_requisicion" rows="3"
                        class="w-full border-amber-300 focus:ring-amber-300/60 focus:border-amber-400 border rounded-lg p-2"
                        required>{{ old('detail_requisicion') }}</textarea>
                </div>

                <hr class="my-4 border-amber-200">

                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                    <button type="button" id="abrirModalBtn"
                        class="bg-amber-600 text-white px-4 py-2 rounded-lg shadow hover:bg-amber-700 focus:ring-2 focus:ring-amber-300">
                        + Añadir Producto
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="productosTable" class="w-full border border-amber-200 rounded-lg overflow-hidden mt-3">
                        <thead class="bg-amber-50 text-amber-800 text-left">
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
                        class="bg-amber-600 text-white px-6 py-2 rounded-lg shadow hover:bg-amber-700 focus:ring-2 focus:ring-amber-300">
                        Guardar Requisición
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 1: Selección de Producto -->
<div id="modalProducto" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 border-t-4 border-amber-500">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Seleccionar Producto</h2>
            <button id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <!-- Filtro de categoría y Cantidad (movido arriba) -->
        <div class="grid grid-cols-3 gap-4 items-end mb-4">
            <div class="relative">
                <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
                <input type="text" id="categoriaFilter" class="w-full border rounded-lg p-2"
                    placeholder="Escribe o selecciona una categoría">
                <div id="categoriasList"
                    class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-50 hidden p-1">
                    {!! $categoriasOptionsHtml !!}
                </div>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Cantidad Total</label>
                <input type="number" id="cantidadTotalInput" class="w-full border rounded-lg p-2" min="1"
                    placeholder="Ej: 100">
            </div>
            <div class="flex items-center">
                <span id="unidadMedida" class="text-gray-600 font-semibold">Unidad: -</span>
            </div>
        </div>

        <!-- Selección de producto (movido abajo, sin cambios de IDs) -->
        <div class="mb-4 relative">
            <label class="block text-gray-600 font-semibold mb-1">Producto</label>
            <input type="text" id="productoSelect" class="w-full border rounded-lg p-2"
                placeholder="Escribe o selecciona un producto">
            <div id="productosList"
                class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                {!! $productosOptionsHtml !!}
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="button" id="siguienteModalBtn"
                class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 focus:ring-2 focus:ring-amber-300">
                Siguiente <i class="ml-1 fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Distribución por Centros de Costo -->
<div id="modalDistribucion" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full p-6 border-t-4 border-amber-500">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800">Distribuir Producto</h2>
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
                        <input type="text" id="centroFilter" class="w-full border rounded-lg p-2"
                            placeholder="Escribe o selecciona un subcentro" autocomplete="off">
                        <input type="hidden" id="centroSelect" name="centroSelectHidden" value="">
                        <div id="centrosDropdown"
                            class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                            {!! $subcentrosOptionsHtml !!}
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
                    class="bg-amber-600 text-white px-6 py-2 rounded-lg hover:bg-amber-700 focus:ring-2 focus:ring-amber-300">
                    Guardar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de carga -->
<div id="cargandoAlert" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-600 mb-4"></div>
        <p class="text-gray-700 font-semibold">Procesando, por favor espere...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    window.PREFILL_DATA = {!! json_encode($prefillData) !!};
    window.CATEGORIAS = @json($categoriasListaFiltrada);
    window.PRODUCTOS_DATA = {!! json_encode($productosFiltrados->map(function($p){
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
<script src="{{ asset('js/requisiciones/especial.js') }}"></script>
@endsection