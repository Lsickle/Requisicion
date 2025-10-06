@extends('layouts.app')

@section('title', 'Editar Requisición')
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
@section('content')
<x-sidebar/>
<div class="max-w-5xl mx-auto p-6 mt-20">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <h1 class="text-2xl font-bold text-gray-700 mb-6">Editar Requisición #{{ $requisicion->id }}</h1>

        <!-- Mostrar comentario de rechazo -->
        @if($comentarioRechazo)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>Motivo de corrección:</strong> {{ $comentarioRechazo }}
                    </p>
                </div>
            </div>
        </div>
        @endif

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

        <form id="requisicionForm" action="{{ route('requisiciones.update', $requisicion->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Recobrable</label>
                    <select name="Recobrable" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Recobrable" {{ $requisicion->Recobrable == 'Recobrable' ? 'selected' : '' }}>Recobrable</option>
                        <option value="No recobrable" {{ $requisicion->Recobrable == 'No recobrable' ? 'selected' : '' }}>No recobrable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Prioridad</label>
                    <select name="prioridad_requisicion" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="baja" {{ $requisicion->prioridad_requisicion == 'baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ $requisicion->prioridad_requisicion == 'media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ $requisicion->prioridad_requisicion == 'alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Justificación</label>
                <textarea name="justify_requisicion" rows="3" class="w-full border rounded-lg p-2" required>{{ $requisicion->justify_requisicion }}</textarea>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Detalles Adicionales</label>
                <textarea name="detail_requisicion" rows="3" class="w-full border rounded-lg p-2" required>{{ $requisicion->detail_requisicion }}</textarea>
            </div>

            <hr class="my-4">

            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                <button type="button" id="abrirModalBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700">
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

            <div class="flex justify-end gap-4">
                <a href="{{ route('requisiciones.historial') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg shadow hover:bg-gray-700">
                    Cancelar
                </a>
                <button type="submit" id="submitBtn" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Guardar y Reenviar
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
            <button type="button" id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <!-- Filtro de categoría -->
        <div class="mb-4">
            <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
            <select id="categoriaFilter" class="w-full border rounded-lg p-2">
                <option value="">-- Todas las categorías --</option>
                @php
                    $categoriasUnicas = $productos->pluck('categoria_produc')->unique()->sort();
                @endphp
                @foreach ($categoriasUnicas as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </div>

        <!-- Selección de producto y cantidad -->
        <div class="grid grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Producto</label>
                <select id="productoSelect" class="w-full border rounded-lg p-2">
                    <option value="">-- Selecciona producto --</option>
                    @foreach ($productos as $p)
                    <option value="{{ $p->id }}" 
                            data-nombre="{{ $p->name_produc }}"
                            data-proveedor="{{ $p->proveedor_id ?? '' }}" 
                            data-categoria="{{ $p->categoria_produc }}"
                            data-unidad="{{ $p->unit_produc }}"
                            data-stock="{{ $p->stock_produc }}">
                        {{ $p->name_produc }} ({{ $p->unit_produc }})
                    </option>
                    @endforeach
                </select>
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
            <button type="button" id="siguienteModalBtn" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
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
            <p class="text-sm">Cantidad total: <span id="productoSeleccionadoCantidad" class="font-bold">0</span> <span id="productoSeleccionadoUnidad"></span></p>
        </div>

        <!-- Distribución por centros -->
        <div id="centrosSection" class="mt-4">
            <h4 class="text-lg font-semibold text-gray-700 mb-2">Distribución por Centros de Costo</h4>
            <p class="text-sm text-gray-500 mb-4">Distribuya la cantidad total entre los centros de costo</p>

            <div class="grid grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Centro</label>
                    <select id="centroSelect" class="w-full border rounded-lg p-2">
                        <option value="">-- Selecciona centro --</option>
                        @foreach ($centros as $c)
                        <option value="{{ $c->id }}" data-nombre="{{ $c->name_centro }}">{{ $c->name_centro }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Cantidad</label>
                    <input type="number" id="cantidadCentroInput" class="w-full border rounded-lg p-2" min="1" placeholder="Ej: 50">
                </div>
                <div>
                    <button type="button" id="agregarCentroBtn" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Agregar
                    </button>
                </div>
            </div>

            <div class="mt-4 text-sm font-semibold text-gray-600">
                Total asignado: <span id="totalAsignado">0</span> de <span id="cantidadDisponible">0</span> <span id="unidadDisponible"></span>
            </div>

            <ul id="centrosList" class="divide-y divide-gray-200 mt-3 border rounded-lg p-2 max-h-40 overflow-y-auto"></ul>

            <div class="flex justify-between mt-6">
                <button type="button" id="volverModalBtn" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </button>
                <button type="button" id="guardarProductoBtn" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
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
    // Guard de envío: solo permitir submit al hacer clic en el botón principal
    document.addEventListener('DOMContentLoaded', function(){
        const form = document.getElementById('requisicionForm');
        const submitBtn = document.getElementById('submitBtn');
        let allowSubmit = false;
        if (submitBtn) {
            submitBtn.addEventListener('click', function(){ allowSubmit = true; });
        }
        if (form) {
            // Bloquear Enter para evitar submit accidental (excepto en textarea)
            form.addEventListener('keydown', function(e){
                if (e.key === 'Enter' && e.target && e.target.tagName && e.target.tagName.toLowerCase() !== 'textarea') {
                    e.preventDefault();
                }
            });
            form.addEventListener('submit', function(e){
                if (!allowSubmit) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
<script>
    // Datos iniciales para el script externo (productos de la requisición)
    window.__REQ_EDIT_DATA = {!! json_encode($requisicion->productos->map(function($producto){
        return [
            'id' => $producto->id,
            'nombre' => $producto->name_produc,
            'proveedorId' => $producto->proveedor_id ?? null,
            'cantidadTotal' => (int) ($producto->pivot->pr_amount ?? 0),
            'unidad' => $producto->unit_produc ?? '',
            'stock' => (int) ($producto->stock_produc ?? 0),
            'centros' => $producto->centrosRequisicion->map(function($centro){
                return ['id' => $centro->id, 'nombre' => $centro->name_centro, 'cantidad' => (int) ($centro->pivot->amount ?? 0)];
            })->toArray(),
        ];
    })->toArray()) !!};
</script>
<script src="{{ asset('js/requisiciones_edit.js') }}"></script>
@endsection