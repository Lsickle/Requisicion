@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Crear Orden de Compra
    </h1>

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

    @if($requisicion)
    <!-- ================= Datos de la Requisición ================= -->
    <div class="mb-8 border rounded-lg bg-gray-50 p-6 relative">
        <div class="absolute top-4 right-4">
            @if($requisicion->ordenCompra?->id)
            <a href="{{ route('ordenes_compra.edit', $requisicion->ordenCompra->id) }}"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center">
                <i class="fas fa-edit mr-2"></i> Editar Orden de Compra
            </a>
            @endif
        </div>

        <h2 class="text-xl font-semibold text-gray-700 mb-4">Detalles de la Requisición #{{ $requisicion->id }}</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div class="bg-white p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-700 mb-2">Información del Solicitante</h3>
                <p><strong>Nombre:</strong> {{ $requisicion->name_user }}</p>
                <p><strong>Email:</strong> {{ $requisicion->email_user }}</p>
                <p><strong>Operación:</strong> {{ $requisicion->operacion_user }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg border">
                <h3 class="font-semibold text-gray-700 mb-2">Información General</h3>
                <p><strong>Prioridad:</strong>
                    <span class="px-2 py-1 rounded-full text-xs font-semibold
                        {{ $requisicion->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' :
                           ($requisicion->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' :
                           'bg-green-100 text-green-800') }}">
                        {{ ucfirst($requisicion->prioridad_requisicion) }}
                    </span>
                </p>
                <p><strong>Recobrable:</strong> {{ $requisicion->Recobrable }}</p>
            </div>
        </div>

        <div class="mb-4">
            <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
            <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
        </div>
    </div>
    @endif

    <!-- Formulario para Crear Orden -->
    @if($requisicion)
    <div class="border p-4 mb-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">
            Crear Orden de Compra
        </h2>

        <form id="orden-form" action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
                    <select id="proveedor_id" name="proveedor_id" class="w-full border rounded-lg p-2" required>
                        <option value="">Seleccione un proveedor</option>
                        @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                    <select name="plazo_oc" class="w-full border rounded-lg p-2">
                        <option value="Contado">Efectivo</option>
                        <option value="30 días">Transferencia</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                    <select name="plazo_oc" class="w-full border rounded-lg p-2">
                        <option value="Contado">Contado</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2" class="w-full border rounded-lg p-2"></textarea>
                </div>
            </div>

            <!-- Productos -->
            <div class="mt-6">
                <label class="block text-gray-600 font-semibold mb-1">Añadir Producto</label>
                <div class="flex gap-3">
                    <select id="producto-selector" class="w-full border rounded-lg p-2">
                        <option value="">Seleccione un producto</option>
                        @foreach($productosDisponibles as $producto)
                        <option value="{{ $producto->id }}" data-proveedor="{{ $producto->proveedor_id ?? '' }}"
                            data-unidad="{{ $producto->unit_produc }}">
                            {{ $producto->name_produc }} ({{ $producto->unit_produc }})
                        </option>
                        @endforeach
                    </select>
                    <button type="button" onclick="agregarProducto()"
                        class="bg-green-500 text-white px-4 py-2 rounded-lg">➕ Añadir</button>
                </div>
            </div>

            <!-- Tabla productos -->
            <div class="overflow-x-auto mt-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Productos en la Orden</h3>
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Distribución</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productos-table"></tbody>
                </table>
            </div>

            <div class="flex justify-end gap-4 mt-6">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Crear Orden de Compra
                </button>
            </div>
        </form>

    </div>

    <!-- Tabla de órdenes creadas -->
    <div class="border p-4 mt-10 rounded-lg shadow">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Órdenes de Compra Creadas</h2>
        <table class="w-full border text-sm" id="ordenes-table">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Proveedor</th>
                    <th class="p-3">Productos</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <!-- Botón descargar ZIP (oculto hasta que ya no queden productos) -->
        <div class="mt-6 text-right hidden" id="zip-container">
            <a href="#" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                Descargar ZIP de Órdenes
            </a>
        </div>
    </div>
    @endif
</div>

<script>
    function agregarProducto() {
    let selector = document.getElementById('producto-selector');
    let table = document.getElementById('productos-table');
    let proveedorSelect = document.getElementById('proveedor_id');

    let productoId = selector.value;
    let productoNombre = selector.options[selector.selectedIndex]?.text;
    let proveedorId = selector.options[selector.selectedIndex]?.dataset.proveedor;
    let unidad = selector.options[selector.selectedIndex]?.dataset.unidad || '';

    if (!productoId) {
        Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
        return;
    }

    let row = document.createElement('tr');
    row.innerHTML = `
        <td class="p-3">
            ${productoNombre}
            <input type="hidden" name="productos[${productoId}][id]" value="${productoId}">
        </td>
        <td class="p-3">
            <input type="number" name="productos[${productoId}][cantidad]" min="1" class="w-24 border rounded p-1 text-center" placeholder="0"> ${unidad}
        </td>
        <td class="p-3 text-center">
            <button type="button" onclick="this.closest('tr').remove()" class="bg-red-500 text-white px-3 py-1 rounded-lg">➖ Quitar</button>
        </td>
    `;
    table.appendChild(row);

    if (proveedorId) {
        proveedorSelect.value = proveedorId;
    }

    selector.remove(selector.selectedIndex);
    selector.value = "";
}

// Capturar envío del form y actualizar la tabla de órdenes creadas
document.getElementById('orden-form').addEventListener('submit', function(e) {
    e.preventDefault();

    let proveedor = document.getElementById('proveedor_id');
    let productos = document.querySelectorAll('#productos-table tr');

    if (productos.length === 0) {
        Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe añadir al menos un producto.'});
        return;
    }

    // Construir listado de productos
    let productosTexto = Array.from(productos).map(row => {
        let nombre = row.querySelector('td:first-child').innerText.trim();
        let cantidad = row.querySelector('input[type="number"]').value;
        return `${nombre} - ${cantidad}`;
    }).join('<br>');

    // Simular guardado de la orden
    let ordenesTable = document.querySelector('#ordenes-table tbody');
    let row = document.createElement('tr');
    row.innerHTML = `
        <td class="p-3">${ordenesTable.rows.length + 1}</td>
        <td class="p-3">${proveedor.options[proveedor.selectedIndex].text}</td>
        <td class="p-3">${productosTexto}</td>
    `;
    ordenesTable.appendChild(row);

    // Vaciar productos añadidos
    document.getElementById('productos-table').innerHTML = "";
    this.reset();

    // Si ya no hay productos disponibles -> mostrar botón ZIP
    let selector = document.getElementById('producto-selector');
    if (selector.options.length === 1) {
        document.getElementById('zip-container').classList.remove('hidden');
    }

    Swal.fire({icon: 'success', title: 'Orden creada', text: 'La orden de compra fue generada.'});
});
</script>
@endsection