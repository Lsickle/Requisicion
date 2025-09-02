@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />
<div class="max-w-5xl mx-auto p-6 mt-20">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <h1 class="text-2xl font-bold text-gray-700 mb-6">
            @isset($requisicion)
                Crear Orden de Compra desde Requisición #{{ $requisicion->id }}
            @else
                Crear Nueva Orden de Compra
            @endisset
        </h1>

        <form action="{{ route('ordenes-compra.store') }}" method="POST" class="space-y-6">
            @csrf
            
            @isset($requisicion)
                <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
                
                <!-- Información de la requisición -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Información de la Requisición</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p><strong>Solicitante:</strong> {{ $requisicion->name_user }}</p>
                            <p><strong>Email:</strong> {{ $requisicion->email_user }}</p>
                        </div>
                        <div>
                            <p><strong>Operación:</strong> {{ $requisicion->operacion_user }}</p>
                            <p><strong>Prioridad:</strong> {{ $requisicion->prioridad_requisicion }}</p>
                        </div>
                    </div>
                    <div class="mt-3">
                        <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
                        <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                    </div>
                </div>
            @else
                <input type="hidden" name="requisicion_id" value="0"> <!-- Valor por defecto -->
            @endisset

            <!-- Información de la orden de compra -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Información de la Orden de Compra</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
                    <select name="proveedor_id" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}">{{ $proveedor->name_proveedor }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Fecha de Orden *</label>
                    <input type="date" name="date_oc" class="w-full border rounded-lg p-2" required>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Método de Pago *</label>
                    <input type="text" name="methods_oc" class="w-full border rounded-lg p-2" required>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Plazo de Entrega *</label>
                    <input type="text" name="plazo_oc" class="w-full border rounded-lg p-2" required>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Número de Orden *</label>
                    <input type="number" name="order_oc" class="w-full border rounded-lg p-2" required>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-2"></textarea>
            </div>

            <!-- Productos -->
            <h3 class="text-lg font-semibold text-gray-700 mb-3">Productos</h3>
            <div class="overflow-x-auto">
                <table class="w-full border border-gray-200 rounded-lg">
                    <thead class="bg-gray-100 text-gray-600 text-left">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Cantidad</th>
                            <th class="p-3">Precio Unitario</th>
                            <th class="p-3">Distribución por Centros</th>
                        </tr>
                    </thead>
                    <tbody id="productos-container">
                        @isset($requisicion)
                            @foreach($requisicion->productos as $index => $producto)
                            <tr>
                                <td class="p-3 border">
                                    {{ $producto->name_produc }}
                                    <input type="hidden" name="productos[{{ $index }}][id]" value="{{ $producto->id }}">
                                </td>
                                <td class="p-3 border">
                                    <input type="number" name="productos[{{ $index }}][cantidad]" 
                                           value="{{ $producto->pivot->pr_amount }}" 
                                           class="w-full border rounded p-1" required min="1">
                                </td>
                                <td class="p-3 border">
                                    <input type="number" step="0.01" name="productos[{{ $index }}][precio]" 
                                           class="w-full border rounded p-1" required min="0">
                                </td>
                                <td class="p-3 border">
                                    <select name="productos[{{ $index }}][centros][0][id]" class="w-full border rounded p-1 mb-2" required>
                                        <option value="">-- Selecciona centro --</option>
                                        @foreach($centros as $centro)
                                            <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="productos[{{ $index }}][centros][0][cantidad]" 
                                           value="{{ $producto->pivot->pr_amount }}" 
                                           class="w-full border rounded p-1" required min="1">
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <!-- Aquí iría la lógica para agregar productos manualmente si no viene de una requisición -->
                            <tr id="no-products">
                                <td colspan="4" class="p-3 text-center text-gray-500">
                                    No hay productos agregados. Use el botón "Añadir Producto" para comenzar.
                                </td>
                            </tr>
                        @endisset
                    </tbody>
                </table>
            </div>

            @empty($requisicion)
            <div class="flex justify-end mt-4">
                <button type="button" id="agregar-producto" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                    + Añadir Producto
                </button>
            </div>
            @endempty

            <div class="flex justify-end gap-4">
                <a href="{{ isset($requisicion) ? route('ordenes-compra.lista') : url()->previous() }}" 
                   class="bg-gray-600 text-white px-6 py-2 rounded-lg shadow hover:bg-gray-700">
                    Cancelar
                </a>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Crear Orden de Compra
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@empty($requisicion)
<script>
document.addEventListener('DOMContentLoaded', function() {
    let productIndex = 0;
    const productosContainer = document.getElementById('productos-container');
    const noProductsRow = document.getElementById('no-products');
    const agregarProductoBtn = document.getElementById('agregar-producto');
    
    agregarProductoBtn.addEventListener('click', function() {
        if (noProductsRow) {
            noProductsRow.remove();
        }
        
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td class="p-3 border">
                <select name="productos[${productIndex}][id]" class="w-full border rounded p-1" required>
                    <option value="">-- Selecciona producto --</option>
                    @foreach($productos as $producto)
                    <option value="{{ $producto->id }}">{{ $producto->name_produc }}</option>
                    @endforeach
                </select>
            </td>
            <td class="p-3 border">
                <input type="number" name="productos[${productIndex}][cantidad]" 
                       class="w-full border rounded p-1" required min="1">
            </td>
            <td class="p-3 border">
                <input type="number" step="0.01" name="productos[${productIndex}][precio]" 
                       class="w-full border rounded p-1" required min="0">
            </td>
            <td class="p-3 border">
                <select name="productos[${productIndex}][centros][0][id]" class="w-full border rounded p-1 mb-2" required>
                    <option value="">-- Selecciona centro --</option>
                    @foreach($centros as $centro)
                    <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                    @endforeach
                </select>
                <input type="number" name="productos[${productIndex}][centros][0][cantidad]" 
                       class="w-full border rounded p-1" required min="1">
            </td>
        `;
        
        productosContainer.appendChild(newRow);
        productIndex++;
    });
});
</script>
@endempty
@endsection