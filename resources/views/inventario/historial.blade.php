@extends('layouts.app')

@section('title', 'Historial de Inventario')

<style>
    .badge-entrada { background-color: #d1fae5; color: #065f46; }
    .badge-salida { background-color: #fee2e2; color: #991b1b; }
    .badge-eliminacion { background-color: #f3e8ff; color: #6b21a8; }
    .badge-ajuste { background-color: #fef3c7; color: #92400e; }
    .historow-row:hover { background-color: #f9fafb; }
</style>

@section('content')
<x-sidebar />

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-history text-blue-600"></i>
                Historial de Movimientos
            </h1>
            <p class="text-gray-500 text-sm mt-1">Registro de entradas y salidas de inventario</p>
        </div>
        <a href="{{ route('inventario.index') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-1"></i> Volver al Inventario
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-center">Tipo</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        @if($verTodas)
                        <th class="px-4 py-3 text-left">Bodega</th>
                        @endif
                        <th class="px-4 py-3 text-center">Cantidad</th>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Comentario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movimientos as $mov)
                    @php
                        $tipoBadge = $mov->tipo === 'entrada' ? 'badge-entrada' : ($mov->tipo === 'eliminacion' ? 'badge-eliminacion' : ($mov->tipo === 'ajuste' ? 'badge-ajuste' : 'badge-salida'));
                        $tipoTexto = $mov->tipo === 'entrada' ? 'Entrada' : ($mov->tipo === 'eliminacion' ? 'Eliminación' : ($mov->tipo === 'ajuste' ? 'Ajuste' : 'Salida/Retiro'));
                        $signo = $mov->tipo === 'entrada' ? '+' : '-';
                    @endphp
                    <tr class="border-b historow-row">
                        <td class="px-4 py-3 text-gray-600">
                            {{ $mov->created_at ? $mov->created_at->format('d/m/Y H:i') : '-' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-1 rounded-full text-xs font-semibold {{ $tipoBadge }}">
                                {{ $tipoTexto }}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-800">
                            {{ $mov->inventarioBodega->producto->name_produc ?? 'N/A' }}
                        </td>
                        @if($verTodas)
                        <td class="px-4 py-3 text-gray-600">
                            {{ $mov->inventarioBodega->bodega->name_centro ?? 'N/A' }}
                        </td>
                        @endif
                        <td class="px-4 py-3 text-center font-semibold {{ $signo === '+' ? 'text-green-600' : 'text-red-600' }}">
                            {{ $signo }}{{ $mov->cantidad }}
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            {{ $mov->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $mov->comentario ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $verTodas ? 7 : 6 }}" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-history text-3xl mb-2 block"></i>
                            No hay movimientos registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movimientos->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">
            {!! $movimientos->links() !!}
        </div>
        @endif
    </div>
</div>
@endsection