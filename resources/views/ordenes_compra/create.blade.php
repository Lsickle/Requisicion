@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Crear Orden de Compra - Requisición #{{ $requisicion->id }}
    </h1>

    <form action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
        @csrf

        <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
        <input type="hidden" name="order_oc" value="{{ $orderNumber }}">

        <!-- Datos generales de la orden -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Número de Orden</label>
                <input type="text" value="{{ $orderNumber }}" class="w-full border rounded-lg p-2 bg-gray-100" readonly>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Fecha *</label>
                <input type="date" name="date_oc" value="{{ date('Y-m-d') }}" class="w-full border rounded-lg p-2"
                    required>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                <input type="text" name="methods_oc" class="w-full border rounded-lg p-2"
                    placeholder="Ej: Transferencia bancaria">
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                <input type="text" name="plazo_oc" class="w-full border rounded-lg p-2" placeholder="Ej: 30 días">
            </div>
        </div>

        <!-- Observaciones -->
        <div>
            <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-2"
                placeholder="Observaciones adicionales para la orden de compra"></textarea>
        </div>

        <!-- Proveedor -->
        <div>
            <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
            <select name="proveedor_id" class="w-full border rounded-lg p-2" required>
                <option value="">-- Selecciona un proveedor --</option>
                @if($proveedoresProductos->count() > 0)
                <!-- Si tenemos proveedores específicos de la requisición -->
                @foreach($proveedoresProductos as $proveedor)
                <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $proveedorPreseleccionado)==$proveedor->id ?
                    'selected' : '' }}>
                    {{ $proveedor->name_proveedor }}
                </option>
                @endforeach
                @else
                <!-- Si no hay requisición, mostrar todos los proveedores -->
                @foreach($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}" {{ old('proveedor_id')==$proveedor->id ? 'selected' : '' }}>
                    {{ $proveedor->name_proveedor }}
                </option>
                @endforeach
                @endif
            </select>
        </div>

        <!-- Tabla de productos -->
        @if(isset($requisicion) && $requisicion->productos->count() > 0)
        <!-- Mostrar productos de la requisición -->
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg text-sm">
                <!-- ... código de la tabla para requisición ... -->
            </table>
        </div>
        @elseif($productos->count() > 0)
        <!-- Mostrar todos los productos para crear orden desde cero -->
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Producto</th>
                        <th class="p-3 text-left">Cantidad</th>
                        <th class="p-3 text-left">Precio Unitario *</th>
                        <th class="p-3 text-left">Centro</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productos as $index => $producto)
                    <tr class="border-t">
                        <td class="p-3 border">
                            {{ $producto->name_produc }}
                            <input type="hidden" name="productos[{{ $index }}][id]" value="{{ $producto->id }}">
                        </td>
                        <td class="p-3 border">
                            <input type="number" name="productos[{{ $index }}][cantidad]" value="1"
                                class="w-24 border rounded p-1" required min="1">
                        </td>
                        <td class="p-3 border">
                            <input type="number" step="0.01" name="productos[{{ $index }}][precio]"
                                class="w-32 border rounded p-1" required min="0" placeholder="0.00">
                        </td>
                        <td class="p-3 border">
                            <select name="productos[{{ $index }}][centro_id]" class="w-full border rounded p-1">
                                <option value="">-- Seleccionar centro --</option>
                                @foreach($centros as $centro)
                                <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <p>No hay productos disponibles.</p>
        </div>
        @endif

        <!-- Botones -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('ordenes_compra.lista') }}"
                class="bg-gray-600 text-white px-6 py-2 rounded-lg shadow hover:bg-gray-700">
                Cancelar
            </a>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                Guardar Orden de Compra
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Validación básica del formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        const precioInputs = document.querySelectorAll('input[name$="[precio]"]');
        
        precioInputs.forEach(input => {
            if (!input.value || parseFloat(input.value) <= 0) {
                isValid = false;
                input.style.borderColor = 'red';
            } else {
                input.style.borderColor = '';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Por favor, ingrese precios válidos para todos los productos.');
        }
    });
});
</script>
@endsection