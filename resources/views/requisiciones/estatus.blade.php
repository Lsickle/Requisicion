@extends('layouts.app')

@section('title', 'Estatus de Requisición')

@section('content')
<x-sidebar />

<div class="max-w-4xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-xl">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Historial de Estatus - Requisición #{{ $requisicion->id }}
    </h1>

    <div class="relative border-l-2 border-blue-400 ml-4">
        @php
            $estatusIds = $estatusOrdenados->pluck('id')->toArray();
            $currentId  = $estatusActual->id;

            // Filtrar "Iniciado": solo conservar el último
            $ultimoIniciadoIndex = $estatusOrdenados->keys()->filter(function($i) use ($estatusOrdenados) {
                return $estatusOrdenados[$i]->id == 1;
            })->last();

            $estatusFiltrados = collect();
            foreach ($estatusOrdenados as $index => $item) {
                if ($item->id == 1 && $index !== $ultimoIniciadoIndex) {
                    continue; // ignorar los anteriores
                }
                $estatusFiltrados->push($item);
            }

            // Flujo normal
            $flujo = [
                1 => 2,
                2 => 3,
                3 => 4,
                4 => 5,
                5 => 7,
                7 => 8,
                8 => 10
            ];

            // Calcular siguiente
            if (in_array($currentId, [1, 2, 3])) {
                $siguiente = 'pendiente';
            } elseif (isset($flujo[$currentId])) {
                $siguiente = $flujo[$currentId];
            } else {
                $siguiente = null;
            }

            // Cancelado, rechazado, corregir o completado: termina
            if (in_array($currentId, [6, 9, 10, 11])) {
                $siguiente = null;
            }

            // Mostrar anteriores + actual
            $idsMostrar = array_filter($estatusIds, fn($id) => $id <= $currentId);
        @endphp

        {{-- Mostrar estatus anteriores y actual --}}
        @foreach($estatusFiltrados as $item)
            @if(!in_array($item->id, $idsMostrar))
                @continue
            @endif

            @php
                $isCompleted = $item->id < $currentId;
                $isCurrent   = $item->id === $currentId;

                // Colores especiales
                $isRejected  = $item->id === 9;
                $isCorregir  = $item->id === 11;
            @endphp

            <div class="mb-6 ml-6 relative">
                {{-- Punto indicador --}}
                @if($isCompleted)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white shadow-md">
                        <i class="fas fa-check text-xs"></i>
                    </span>
                @elseif($isCurrent && $isRejected)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-red-600 text-white shadow-md">
                        <i class="fas fa-times text-xs"></i>
                    </span>
                @elseif($isCurrent && $isCorregir)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-yellow-500 text-white shadow-md">
                        <i class="fas fa-exclamation text-xs"></i>
                    </span>
                @elseif($isCurrent)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white shadow-md">
                        <i class="fas fa-check text-xs"></i>
                    </span>
                @endif

                {{-- Tarjeta --}}
                <div class="p-4 rounded-lg shadow-sm border 
                    @if($isCompleted) bg-green-50 border-green-300
                    @elseif($isCurrent && $isRejected) bg-red-50 border-red-300
                    @elseif($isCurrent && $isCorregir) bg-yellow-50 border-yellow-300
                    @elseif($isCurrent) bg-blue-50 border-blue-300
                    @endif">
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold 
                                @if($isCompleted) text-green-800
                                @elseif($isCurrent && $isRejected) text-red-800
                                @elseif($isCurrent && $isCorregir) text-yellow-800
                                @elseif($isCurrent) text-blue-800
                                @endif">
                                {{ $item->status_name }}
                            </h3>
                            
                            @if(isset($item->pivot->created_at))
                                <p class="text-sm 
                                    @if($isCompleted) text-green-600
                                    @elseif($isCurrent && $isRejected) text-red-600
                                    @elseif($isCurrent && $isCorregir) text-yellow-600
                                    @elseif($isCurrent) text-blue-600
                                    @endif mt-1">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ $item->pivot->created_at->format('d/m/Y H:i') }}
                                </p>
                            @endif

                            @if(isset($item->pivot->comentario) && $item->pivot->comentario)
                                <div class="mt-2 p-2 bg-white rounded border text-sm text-gray-700">
                                    <strong>Comentario:</strong> {{ $item->pivot->comentario }}
                                </div>
                            @endif
                        </div>

                        @if($isCurrent)
                            <span class="text-xs font-medium px-2.5 py-0.5 rounded-full
                                @if($isRejected) bg-red-100 text-red-800
                                @elseif($isCorregir) bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                Estatus actual
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Pendiente por respuesta --}}
        @if($siguiente === 'pendiente')
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 text-white shadow-md">
                    <i class="fas fa-hourglass-half text-xs"></i>
                </span>

                <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-700">
                                Pendiente por respuesta
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i>
                                En espera por aprobación
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Siguiente real --}}
        @if(is_numeric($siguiente))
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 text-white shadow-md">
                    <i class="fas fa-hourglass-half text-xs"></i>
                </span>

                <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-700">
                                {{ $estatusOrdenados->firstWhere('id', $siguiente)->status_name ?? 'Pendiente siguiente' }}
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="far fa-clock mr-1"></i>
                                En espera de que avance el proceso
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-8 flex gap-3">
        <a href="{{ route('requisiciones.historial') }}"
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition duration-200 flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Volver al historial
        </a>

        <a href="{{ route('pdf.generar', ['tipo' => 'estatus', 'id' => $requisicion->id]) }}"
           target="_blank"
           class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition duration-200 flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
        </a>
    </div>
</div>
@endsection