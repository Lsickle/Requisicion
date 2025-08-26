@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-5xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">
        Requisiciones Pendientes de Aprobación
    </h1>

    @forelse($requisiciones as $requisicion)
        <div class="mb-8 p-4 bg-white rounded-lg shadow-md">
            <h2 class="text-xl font-semibold mb-3">
                Requisición #{{ $requisicion->id }}
            </h2>

            <div class="mb-4">
                <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
                <p><strong>Prioridad:</strong> {{ $requisicion->prioridad_requisicion }}</p>
                <p><strong>Monto:</strong> ${{ number_format($requisicion->amount_requisicion, 2) }}</p>
            </div>

            @php
                $estatusOrdenados = $requisicion->estatusHistorial->sortBy('pivot.created_at');
                $estatusActual = $estatusOrdenados->last();
            @endphp

            <ul class="space-y-2 mb-4">
                @foreach($estatusOrdenados as $item)
                    <li class="p-2 rounded 
                        @if($item->id == $estatusActual->id) bg-blue-600 text-white font-semibold
                        @else bg-gray-200 text-gray-700
                        @endif">
                        {{ $item->status_name }}
                        <span class="text-xs block">{{ $item->pivot->created_at->format('d/m/Y') }}</span>
                    </li>
                @endforeach
            </ul>

            @if(in_array($estatusActual->status_name, ['Iniciada', 'Aprobación Gerencia', 'Aprobación Financiera']))
                <div class="flex gap-3">
                    <form action="{{ route('requisiciones.aprobar', $requisicion->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                            Aprobar
                        </button>
                    </form>

                    <form action="{{ route('requisiciones.rechazar', $requisicion->id) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                            Rechazar
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <p class="text-gray-600">No hay requisiciones pendientes de aprobación.</p>
    @endforelse

    <div class="mt-6">
        <a href="{{ route('requisiciones.historial') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Volver
        </a>
    </div>
</div>
@endsection
