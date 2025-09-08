@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Crear Orden de Compra - Requisición #{{ $requisicion->id ?? '' }}
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
    <div class="mb-8 border rounded-lg bg-gray-50 p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Detalles de la Requisición</h2>

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

    <!-- Selector de Proveedor -->
    <div class="mb-6 p-4 border rounded-lg bg-gray-50">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Seleccionar Proveedor</h2>
        <form method="GET" action="{{ route('ordenes_compra.create') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">

            @if($proveedoresDisponibles->count() > 0)
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
                <select name="proveedor_id" class="w-full border rounded-lg p-2" required>
                    <option value="0">Seleccione un proveedor</option>
                    @foreach($proveedoresDisponibles as $proveedor)
                    <option value="{{ $proveedor->id }}" 
                        {{ $proveedorSeleccionado && $proveedorSeleccionado->id == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->prov_name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex items-end">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                    Seleccionar Proveedor
                </button>
            </div>
        </form>
    </div>

    <!-- Formulario para Crear Orden -->
    @if($proveedorSeleccionado && $requisicion)
    <div class="border p-4 mb-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">
            Crear Orden para: {{ $proveedorSeleccionado->prov_name }}
        </h2>

        <form action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
            @csrf

            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
            <input type="hidden" name="proveedor_id" value="{{ $proveedorSeleccionado->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                    <input type="text" name="methods_oc" class="w-full border rounded-lg p-2"
                        placeholder="Ej: Transferencia bancaria">
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                    <select name="plazo_oc" class="w-full border rounded-lg p-2">
                        <option value="Contado">Contado</option>
                        <option value="15 días">15 días</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2" class="w-full border rounded-lg p-2"
                        placeholder="Observaciones adicionales"></textarea>
                </div>
            </div>

            <div class="overflow-x-auto">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Productos del Proveedor</h3>
                <table class="w-full table-fixed border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3 w-1/2 text-left">Producto</th>
                            <th class="p-3 w-1/6 text-center">Cantidad Total</th>
                            <th class="p-3 w-1/6 text-center">Precio Unitario</th>
                            <th class="p-3 w-1/6 text-center">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productosProveedor as $producto)
                        <tr class="border-t">
                            <td class="p-3 truncate">{{ $producto->name_produc }}</td>
                            <td class="p-3 text-center font-semibold">{{ $producto->pivot->pr_amount }}</td>
                            <td class="p-3 text-center">${{ number_format($producto->price_produc, 2) }}</td>
                            <td class="p-3 text-center font-semibold">
                                ${{ number_format($producto->pivot->pr_amount * $producto->price_produc, 2) }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-4">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                    Guardar Orden para {{ $proveedorSeleccionado->prov_name }}
                </button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
