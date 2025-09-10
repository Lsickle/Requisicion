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
            <form action="{{ route('ordenes_compra.update', $ordenCompra->id) }}" method="POST">
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
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordenCompra->requisicion->productos as $prod)
                            @php
                                $distribucion = DB::table('centro_producto')
                                    ->where('requisicion_id', $ordenCompra->requisicion->id)
                                    ->where('producto_id', $prod->id)
                                    ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                    ->select('centro.id','centro.name_centro','centro_producto.amount')
                                    ->get();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 border">{{ $prod->name_produc }}</td>
                                <td class="px-4 py-3 border text-center">
                                    <input type="number" name="productos[{{ $prod->id }}][cantidad]"
                                           value="{{ old('productos.'.$prod->id.'.cantidad', $prod->pivot->pr_amount) }}"
                                           class="w-20 text-center border-gray-300 rounded">
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
                                                       class="w-20 text-center border-gray-300 rounded">
                                            </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
@endsection
