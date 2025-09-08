@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Crear Orden de Compra - Requisición #{{ $requisicion->id }}
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

    <!-- Selector de Proveedor (solo si hay proveedores disponibles) -->
    @if($proveedoresDisponibles->count() > 0)
    <div class="mb-6 p-4 border rounded-lg bg-gray-50">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Seleccionar Proveedor</h2>
        <form method="GET" action="{{ route('ordenes_compra.create') }}" class="flex items-end gap-4">
            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
            
            <div class="flex-1">
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
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg shadow">
                Seleccionar
            </button>
        </form>
    </div>
    @endif

    <!-- Lista de Órdenes Creadas -->
    @if($requisicion->ordenesCompra && $requisicion->ordenesCompra->count() > 0)
    <div class="mb-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Órdenes de Compra Creadas</h2>
            
            <!-- Botón de Descargar (sin funcionalidad por ahora) -->
            @if($proveedoresDisponibles->count() == 0)
            <button class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-2 rounded-lg shadow flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Descargar Órdenes de Compra (ZIP)
            </button>
            @endif
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full border text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">Número de Orden</th>
                        <th class="p-3">Proveedor</th>
                        <th class="p-3">Fecha</th>
                        <th class="p-3">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisicion->ordenesCompra as $orden)
                    <tr class="border-t">
                        <td class="p-3">{{ $orden->order_oc }}</td>
                        <td class="p-3">{{ $orden->proveedor->prov_name }}</td>
                        <td class="p-3">{{ $orden->date_oc }}</td>
                        <td class="p-3">
                            <a href="{{ route('ordenes_compra.edit', $orden->id) }}" class="text-blue-600 hover:text-blue-800 mr-2">Editar</a>
                            <form action="{{ route('ordenes_compra.destroy', $orden->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Estás seguro de eliminar esta orden?')">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Formulario para Crear Orden (solo si hay proveedor seleccionado) -->
    @if($proveedorSeleccionado)
    <div class="border p-4 mb-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Crear Orden para: {{ $proveedorSeleccionado->prov_name }}</h2>

        <form action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
            @csrf

            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
            <input type="hidden" name="proveedor_id" value="{{ $proveedorSeleccionado->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Fecha *</label>
                    <input type="date" name="date_oc" value="{{ date('Y-m-d') }}"
                        class="w-full border rounded-lg p-2" required>
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

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-2"
                    placeholder="Observaciones adicionales"></textarea>
            </div>

            <div class="overflow-x-auto">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Productos del Proveedor</h3>
                <table class="w-full border text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Cantidad</th>
                            <th class="p-3">Precio Unitario</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($requisicion->productos->where('proveedor_id', $proveedorSeleccionado->id) as $producto)
                        <tr class="border-t">
                            <td class="p-3">{{ $producto->name_produc }}</td>
                            <td class="p-3">{{ $producto->pivot->cantidad }}</td>
                            <td class="p-3">{{ number_format($producto->price_produc, 2) }}</td>
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

    <!-- Mensaje cuando ya se crearon órdenes para todos los proveedores -->
    @if($proveedoresDisponibles->count() == 0 && $requisicion->ordenesCompra->count() > 0)
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6">
        <strong class="font-bold">¡Completado!</strong>
        <span class="block sm:inline">Se han creado órdenes de compra para todos los proveedores de esta requisición.</span>
    </div>
    @endif
</div>

<script>
    // Script para mostrar alerta cuando se intenta crear una orden duplicada
    @if($errors->has('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ $errors->first('error') }}',
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>
@endsection