@extends('layouts.app')

@section('title', 'Aprobación de Requisición')

@section('content')
<x-sidebar />

<div class="max-w-4xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">
        Aprobación de la Requisición #{{ $requisicion->id }}
    </h1>

    <!-- Información básica de la requisición -->
    <div class="mb-6 p-4 bg-white rounded-lg shadow">
        <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
        <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
        <p><strong>Prioridad:</strong> {{ $requisicion->prioridad_requisicion }}</p>
        <p><strong>Monto:</strong> ${{ number_format($requisicion->amount_requisicion, 2) }}</p>
    </div>

    <!-- Estatus de la requisición -->
    <ul class="space-y-3">
        @foreach($estatusOrdenados as $item)
            <li class="p-3 rounded-lg shadow-sm
                @if($item->id == $estatusActual->id) bg-blue-600 text-white font-semibold
                @else bg-gray-300 text-gray-700
                @endif">
                {{ $item->status_name }}
                <span class="text-xs block">{{ $item->pivot->created_at->format('d/m/Y') }}</span>
            </li>
        @endforeach
    </ul>

    <!-- Botones de acción SOLO si está en aprobación -->
    @if(in_array($estatusActual->status_name, ['Aprobación Gerencia', 'Aprobación Financiera']))
        <div class="mt-6 flex gap-3">
            <!-- Botón Aprobar -->
            <form action="{{ route('requisiciones.aprobar', $requisicion->id) }}" method="POST">
                @csrf
                <button type="submit"
                        class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Aprobar
                </button>
            </form>

            <!-- Botón Rechazar -->
            <form action="{{ route('requisiciones.rechazar', $requisicion->id) }}" method="POST">
                @csrf
                <button type="submit"
                        class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Rechazar
                </button>
            </form>
        </div>
    @endif

    <!-- Botón Volver -->
    <div class="mt-6">
        <a href="{{ route('requisiciones.historial') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Volver
        </a>
    </div>
</div>
@endsection
