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
                @foreach($proveedores as $proveedor)
                <option value="{{ $proveedor->id }}">
                    {{ $proveedor->prov_name }}
                </option>
                @endforeach
            </select>
        </div>

        <!-- Productos de la requisición -->
        @if(isset($requisicion) && $requisicion->productos->count() > 0)
        <div class="overflow-x-auto">
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Productos de la Requisición</h2>
            <table class="w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">Producto</th>
                        <th class="p-3">Proveedor</th>
                        <th class="p-3">Cantidad</th>
                        <th class="p-3">Precio Unitario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisicion->productos as $producto)
                    <tr class="border-t">
                        <td class="p-3">{{ $producto->name_produc }}</td>
                        <td class="p-3">{{ $producto->proveedor->prov_name ?? 'Sin proveedor' }}</td>
                        <td class="p-3">{{ $producto->pivot->cantidad ?? '-' }}</td>
                        <td class="p-3">{{ number_format($producto->price_produc, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Botones -->
        <div class="flex justify-end gap-4">
            <a href="{{ url()->previous() }}"
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

@if(session('success'))
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    Swal.fire({
        icon: 'success',
        title: '¡Éxito!',
        text: '{{ session("success") }}',
        confirmButtonText: 'Aceptar'
    });
</script>
@endif

@endsection
