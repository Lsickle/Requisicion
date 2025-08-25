@extends('layouts.app')

@section('title', 'Estatus de Requisición')

@section('content')
<x-sidebar />

<div class="max-w-4xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">
        Estatus de la Requisición #{{ $requisicion->id }}
    </h1>

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

    <div class="mt-6 flex gap-3">
        <!-- Botón Volver -->
        <a href="{{ route('requisiciones.historial') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
            Volver
        </a>

        <!-- Botón Descargar PDF -->
        <a href="{{ route('pdf.generar', ['tipo' => 'estatus', 'id' => $requisicion->id]) }}"
           target="_blank"
           class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
            Descargar PDF
        </a>
    </div>
</div>
@endsection
