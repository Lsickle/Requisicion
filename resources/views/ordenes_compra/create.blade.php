@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        @if(isset($requisicion))
        Crear Orden de Compra - Requisición #{{ $requisicion->id }}
        @else
        Crear Orden de Compra
        @endif
    </h1>

    <form action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6" id="ordenCompraForm">
        @csrf

        <input type="hidden" name="requisicion_id" value="{{ $requisicion->id ?? 0 }}">
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
                @foreach($proveedoresProductos as $proveedor)
                <option value="{{ $proveedor->id }}" @if($proveedorPreseleccionado==$proveedor->id) selected @endif>
                    {{ $proveedor->prov_name }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Productos -->
        @if(isset($requisicion) && $requisicion->productos->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">Producto</th>
                        <th class="p-3">Proveedor</th>
                        <th class="p-3">Cantidad Total</th>
                        <th class="p-3">Distribución Centros</th>
                        <th class="p-3">Precio Unitario *</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisicion->productos as $index => $producto)
                    @php
                    $cantidadTotal = 0;
                    if (isset($distribucionCentros[$producto->id])) {
                    $cantidadTotal = $distribucionCentros[$producto->id]->sum('amount');
                    }
                    @endphp
                    <tr class="border-t">
                        <td class="p-3">
                            {{ $producto->name_produc }}
                            <input type="hidden" name="productos[{{ $index }}][id]" value="{{ $producto->id }}">
                        </td>
                        <td class="p-3">{{ $producto->proveedor->prov_name ?? 'Sin proveedor' }}</td>
                        <td class="p-3">
                            <!-- Solo mostrar cantidad total como texto -->
                            <span>{{ $cantidadTotal }}</span>
                            <input type="hidden" name="productos[{{ $index }}][cantidad]" value="{{ $cantidadTotal }}">
                        </td>
                        <td class="p-3">
                            @if(isset($distribucionCentros[$producto->id]))
                            @foreach($distribucionCentros[$producto->id] as $centroIndex => $distribucion)
                            <div class="flex items-center justify-between mb-1">
                                <span class="mr-2">{{ $distribucion->name_centro }}:</span>
                                <!-- Solo mostrar cantidad por centro como texto -->
                                <span>{{ $distribucion->amount }}</span>
                                <input type="hidden"
                                    name="productos[{{ $index }}][centros][{{ $centroIndex }}][cantidad]"
                                    value="{{ $distribucion->amount }}">
                                <input type="hidden" name="productos[{{ $index }}][centros][{{ $centroIndex }}][id]"
                                    value="{{ $distribucion->centro_id }}">
                            </div>
                            @endforeach
                            @endif
                        </td>
                        <td class="p-3">
                            <span>{{ number_format($producto->price_produc, 2) }}</span>
                            <input type="hidden" name="productos[{{ $index }}][precio]"
                                value="{{ $producto->price_produc }}">
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
            <a href="{{ isset($requisicion) ? route('ordenes_compra.lista') : route('ordenes_compra.index') }}"
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
        const form = document.getElementById('ordenCompraForm');
        
        // Validación del formulario
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const precioInputs = document.querySelectorAll('input[name$="[precio]"]');
            const cantidadInputs = document.querySelectorAll('input[name$="[cantidad]"]');
            
            // Validar precios
            precioInputs.forEach(input => {
                if (!input.value || parseFloat(input.value) <= 0) {
                    isValid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            // Validar cantidades
            cantidadInputs.forEach(input => {
                if (!input.value || parseInt(input.value) <= 0) {
                    isValid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos correctamente.');
            }
        });

        // Seleccionar automáticamente el proveedor si hay solo uno
        @if(isset($proveedorPreseleccionado) && $proveedorPreseleccionado)
            document.querySelector('select[name="proveedor_id"]').value = "{{ $proveedorPreseleccionado }}";
        @endif
    });
</script>
@endsection