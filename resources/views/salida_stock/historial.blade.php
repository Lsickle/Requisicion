@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Historial de Salidas de Stock</h1>
                <a href="{{ route('salida_stock.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    Nueva Salida
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Fecha</th>
                            <th class="px-4 py-2 text-left">Usuario</th>
                            <th class="px-4 py-2 text-center">Productos</th>
                            <th class="px-4 py-2 text-center">Total Unidades</th>
                            <th class="px-4 py-2 text-left">Firmado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salidas as $salida)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $salida->fecha->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $salida->user_name }}</td>
                            <td class="px-4 py-2 text-center">{{ $salida->productos_count ?? '-' }}</td>
                            <td class="px-4 py-2 text-center">{{ $salida->cantidad_total ?? '-' }}</td>
                            <td class="px-4 py-2">{{ $salida->firma_nombre }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No hay salidas de stock registradas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection