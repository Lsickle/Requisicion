@extends('layouts.app')

@section('title', 'Orden de Compra')

@section('content')
<x-sidebar />

<div class="container mx-auto px-4 py-8 mt-16">
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Orden de Compra: {{ $ordenCompra->order_oc ?? ('OC-'.$ordenCompra->id) }}</h1>
                <p class="text-sm text-gray-600">Fecha: {{ optional($ordenCompra->date_oc)->toDateString() ?? 'N/A' }}</p>
            </div>
            <div class="text-right">
                <a href="{{ url()->previous() }}" class="inline-block bg-gray-200 text-gray-700 px-3 py-1 rounded">Volver</a>
            </div>
        </div>

        <div class="mb-4">
            <h3 class="font-semibold text-gray-700">Detalles</h3>
            <p class="text-gray-600">Observaciones: {{ $ordenCompra->observaciones ?? '-' }}</p>
            <p class="text-gray-600">Fecha estimada recepción: {{ optional($ordenCompra->fecha_estimada_recepcion)->toDateString() ?? '-' }}</p>
        </div>

        <div>
            <h3 class="font-semibold text-gray-700 mb-2">Productos</h3>
            @if($ordenCompra->ordencompraProductos->isEmpty())
                <p class="text-gray-600">No hay líneas en esta orden.</p>
            @else
                <table class="w-full table-auto border-collapse">
                    <thead class="bg-gray-50 text-left">
                        <tr>
                            <th class="p-2">Producto</th>
                            <th class="p-2">Proveedor</th>
                            <th class="p-2">Cantidad</th>
                            <th class="p-2">Precio factura</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ordenCompra->ordencompraProductos as $line)
                        <tr class="border-t">
                            <td class="p-2">{{ optional($line->producto)->name_produc ?? ($line->producto_id ?? '-') }}</td>
                            <td class="p-2">{{ optional($line->proveedor)->prov_name ?? ($line->proveedor_id ?? '-') }}</td>
                            <td class="p-2">{{ $line->total ?? '-' }} {{ optional($line->producto)->unit_produc ?? '' }}</td>
                            <td class="p-2">{{ $line->precio_factura ?? ($line->precio_original ?? '-') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
