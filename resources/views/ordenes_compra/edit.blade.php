@extends('layouts.app')

@section('title', 'Editar Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Editar Orden de Compra - {{ $orden->order_oc }}
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

    <div class="border p-4 mb-6 rounded-lg shadow">
        <form action="{{ route('ordenes_compra.update', $orden->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Fecha *</label>
                    <input type="date" name="date_oc" value="{{ $orden->date_oc }}"
                        class="w-full border rounded-lg p-2" required>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
                    <select name="proveedor_id" class="w-full border rounded-lg p-2" required>
                        @foreach($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" 
                                {{ $orden->proveedor_id == $proveedor->id ? 'selected' : '' }}>
                                {{ $proveedor->prov_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                    <input type="text" name="methods_oc" value="{{ $orden->methods_oc }}" 
                        class="w-full border rounded-lg p-2" placeholder="Ej: Transferencia bancaria">
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                    <input type="text" name="plazo_oc" value="{{ $orden->plazo_oc }}" 
                        class="w-full border rounded-lg p-2" placeholder="Ej: 30 días">
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-2"
                    placeholder="Observaciones adicionales">{{ $orden->observaciones }}</textarea>
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
                        @if($orden->requisicion)
                            @foreach($orden->requisicion->productos->where('proveedor_id', $orden->proveedor_id) as $producto)
                            <tr class="border-t">
                                <td class="p-3">{{ $producto->name_produc }}</td>
                                <td class="p-3">{{ $producto->pivot->cantidad }}</td>
                                <td class="p-3">{{ number_format($producto->price_produc, 2) }}</td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end gap-4">
                <a href="{{ route('ordenes_compra.create', ['requisicion_id' => $orden->requisicion_id]) }}" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                    Cancelar
                </a>
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                    Actualizar Orden
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Script para mostrar alerta de errores
    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `@foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach`,
            confirmButtonText: 'Aceptar'
        });
    @endif
</script>
@endsection