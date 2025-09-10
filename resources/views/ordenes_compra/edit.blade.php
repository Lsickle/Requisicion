@extends('layouts.app')

@section('title', 'Editar Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    Editar Orden de Compra (Requisición #{{ $ordenCompra->requisicion->id }})
                </h1>
                <a href="{{ route('ordenes_compra.lista') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    ← Volver
                </a>
            </div>

            <!-- Formulario -->
            <form id="ordenCompraForm" action="{{ route('ordenes_compra.update', $ordenCompra->id) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Información general -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Información del Solicitante</h3>
                        <p><strong>Nombre:</strong> {{ $ordenCompra->requisicion->name_user }}</p>
                        <p><strong>Email:</strong> {{ $ordenCompra->requisicion->email_user }}</p>
                        <p><strong>Operación:</strong> {{ $ordenCompra->requisicion->operacion_user }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Detalles de la Requisición</h3>
                        <p><strong>Prioridad:</strong> {{ ucfirst($ordenCompra->requisicion->prioridad_requisicion) }}</p>
                        <p><strong>Recobrable:</strong> {{ $ordenCompra->requisicion->Recobrable }}</p>
                    </div>
                </div>

                <!-- Productos -->
                <h3 class="text-xl font-semibold mt-6 mb-3">Productos</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left">Producto</th>
                                <th class="px-4 py-2 text-center">Cantidad Total</th>
                                <th class="px-4 py-2 text-left">Distribución por Centros</th>
                                <th class="px-4 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="productosTable">
                            @foreach($ordenCompra->requisicion->productos as $prod)
                            @php
                                $distribucion = DB::table('centro_producto')
                                    ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                    ->where('centro_producto.requisicion_id', $ordenCompra->requisicion->id)
                                    ->where('centro_producto.producto_id', $prod->id)
                                    ->whereNull('centro_producto.deleted_at')
                                    ->whereNull('centro.deleted_at')
                                    ->select('centro.id','centro.name_centro','centro_producto.amount')
                                    ->get();
                            @endphp
                            <tr data-producto-id="{{ $prod->id }}">
                                <td class="px-4 py-3 border">{{ $prod->name_produc }}</td>
                                <td class="px-4 py-3 border text-center">
                                    <input type="hidden" name="productos[{{ $prod->id }}][id]" value="{{ $prod->id }}">
                                    <input type="number" name="productos[{{ $prod->id }}][cantidad]"
                                           value="{{ old('productos.'.$prod->id.'.cantidad', $prod->pivot->pr_amount) }}"
                                           class="w-20 text-center border-gray-300 rounded cantidad-total">
                                </td>
                                <td class="px-4 py-3 border">
                                    @if($distribucion->count() > 0)
                                        <div class="space-y-2">
                                            @foreach($distribucion as $centro)
                                            <div class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded">
                                                <span class="font-medium text-sm">{{ $centro->name_centro }}</span>
                                                <input type="number" 
                                                       name="productos[{{ $prod->id }}][centros][{{ $centro->id }}]"
                                                       value="{{ old('productos.'.$prod->id.'.centros.'.$centro->id, $centro->amount) }}"
                                                       class="w-20 text-center border-gray-300 rounded distribucion-input">
                                            </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 border text-center">
                                    <button type="button" class="bg-red-500 text-white px-3 py-1 rounded"
                                        onclick="confirmDelete({{ $prod->id }})">
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Botón para añadir producto -->
                <div class="mt-4">
                    <button type="button" onclick="addProductoRow()"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        + Añadir Producto
                    </button>
                </div>

                <!-- Botón Guardar -->
                <div class="mt-6 flex justify-end">
                    <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg shadow hover:bg-blue-700 transition">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div id="deleteModal" class="hidden fixed inset-0 flex items-center justify-center bg-gray-900 bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-96">
        <h2 class="text-lg font-bold mb-4">¿Eliminar producto?</h2>
        <p class="text-sm text-gray-600">Se marcará como eliminado y se registrará nuevamente si es necesario.</p>
        <div class="mt-6 flex justify-end space-x-4">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
            <button onclick="deleteProducto()" class="px-4 py-2 bg-red-600 text-white rounded">Eliminar</button>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let productoAEliminar = null;

    function confirmDelete(productoId) {
        productoAEliminar = productoId;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeModal() {
        productoAEliminar = null;
        document.getElementById('deleteModal').classList.add('hidden');
    }

    function deleteProducto() {
        if (productoAEliminar) {
            const form = document.querySelector('form');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `productos[${productoAEliminar}][eliminar]`;
            input.value = 1;
            form.appendChild(input);
        }
        closeModal();
    }

    function addProductoRow() {
        const table = document.getElementById('productosTable');
        const row = document.createElement('tr');
        const id = Date.now();
        row.innerHTML = `
            <td class="px-4 py-3 border">
                <input type="text" name="nuevos[${id}][nombre]" placeholder="Nombre producto" class="border rounded px-2 py-1">
            </td>
            <td class="px-4 py-3 border text-center">
                <input type="number" name="nuevos[${id}][cantidad]" value="0" class="w-20 text-center border-gray-300 rounded cantidad-total">
            </td>
            <td class="px-4 py-3 border text-gray-500">Nueva distribución al guardar</td>
            <td class="px-4 py-3 border text-center">Nuevo</td>
        `;
        table.appendChild(row);
    }

    // Validar distribución al enviar el formulario con SweetAlert
    document.getElementById('ordenCompraForm').addEventListener('submit', function (e) {
        let valido = true;
        let mensajesError = [];

        document.querySelectorAll('#productosTable tr[data-producto-id]').forEach(row => {
            const productoId = row.getAttribute('data-producto-id');
            const cantidadTotal = parseInt(row.querySelector('.cantidad-total')?.value || 0, 10);
            let sumaDistribucion = 0;

            row.querySelectorAll('.distribucion-input').forEach(input => {
                sumaDistribucion += parseInt(input.value || 0, 10);
            });

            if (cantidadTotal !== sumaDistribucion) {
                valido = false;
                mensajesError.push(`Producto ID ${productoId}: cantidad total ${cantidadTotal}, distribución ${sumaDistribucion}`);
            }
        });

        if (!valido) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error en las distribuciones',
                html: mensajesError.join('<br>'),
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
        }
    });
</script>
@endsection