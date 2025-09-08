@extends('layouts.app')

@section('title', 'Estatus de Requisición')

@section('content')
<x-sidebar />

<div class="max-w-4xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-xl">
    <h1 class="text-2xl font-bold mb-6 text-gray-800 text-center">
        Historial de Estatus - Requisición #{{ $requisicion->id }}
    </h1>

    <div class="relative border-l-2 border-blue-400 ml-4">
        @php
        $estatusIds = $estatusOrdenados->pluck('id')->toArray();
        $currentId = $estatusActual->id;

            // Filtrar "Iniciado": solo conservar el último
            $ultimoIniciadoIndex = $estatusOrdenados->keys()->filter(function($i) use ($estatusOrdenados) {
                return $estatusOrdenados[$i]->id == 1;
            })->last();

            // Filtrar "Cancelada": solo conservar la última
            $ultimaCanceladaIndex = $estatusOrdenados->keys()->filter(function($i) use ($estatusOrdenados) {
                return $estatusOrdenados[$i]->id == 6;
            })->last();

            $estatusFiltrados = collect();
            foreach ($estatusOrdenados as $index => $item) {
                if ($item->id == 1 && $index !== $ultimoIniciadoIndex) {
                    continue; // ignorar iniciados anteriores
                }
                if ($item->id == 6 && $index !== $ultimaCanceladaIndex) {
                    continue; // ignorar canceladas anteriores
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

        // Cancelado, rechazado o completado
        if (in_array($currentId, [6, 9, 10])) {
            $siguiente = null;
        } elseif ($currentId == 11) {
            $siguiente = 'pendiente_gerencia';
        }

        // Texto especial para rechazo
        $textoRechazo = 'Rechazado';
        $indexRechazo = $estatusFiltrados->search(fn($item) => $item->id === 9);

        if ($indexRechazo !== false && $indexRechazo > 0) {
            $anterior = $estatusFiltrados[$indexRechazo - 2] ?? null;
            if ($anterior && $anterior->id == 3) {
                $textoRechazo = 'Rechazado por Gerencia';
            } elseif ($anterior && $anterior->id == 2) {
                $textoRechazo = 'Rechazado por Financiera';
            }
        }
        @endphp 

        {{-- Mostrar estatus --}}
        @foreach($estatusFiltrados as $item) 
            @php
            $isCompleted = $item->id < $currentId; 
            $isCurrent = $item->id === $currentId;

                // Colores especiales
                $isRejected  = $item->id === 9;
                $isCanceled  = $item->id === 6;
                $isCorregir  = $item->id === 11;
            @endphp

            <div class="mb-6 ml-6 relative">
                {{-- Iconos --}}
                @if($isCompleted)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white shadow-md">
                        <i class="fas fa-check text-xs"></i>
                    </span>
                @elseif($isCurrent && ($isRejected || $isCanceled))
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
                    @elseif($isCurrent && ($isRejected || $isCanceled)) bg-red-50 border-red-300
                    @elseif($isCurrent && $isCorregir) bg-yellow-50 border-yellow-300
                    @elseif($isCurrent) bg-blue-50 border-blue-300
                    @endif">
                    
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold 
                                @if($isCompleted) text-green-800
                                @elseif($isCurrent && ($isRejected || $isCanceled)) text-red-800
                                @elseif($isCurrent && $isCorregir) text-yellow-800
                                @elseif($isCurrent) text-blue-800
                                @endif">
                                {{ $item->status_name }}
                            </h3>

                            @if(isset($item->pivot->created_at))
                                <p class="text-sm 
                                    @if($isCompleted) text-green-600
                                    @elseif($isCurrent && ($isRejected || $isCanceled)) text-red-600
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
                                @if($isRejected || $isCanceled) bg-red-100 text-red-800
                                @elseif($isCorregir) bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800 @endif">
                                Estatus actual
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        {{-- Proceso finalizado tras rechazo --}}
        @if($estatusFiltrados->contains('id', 9))
        <div class="mb-6 ml-6 relative">
            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-red-700 text-white shadow-md">
                <i class="fas fa-stop-circle text-xs"></i>
            </span>
            <div class="p-4 rounded-lg shadow-sm border bg-red-50 border-red-300">
                <h3 class="font-semibold text-red-800">Proceso Finalizado</h3>
                <p class="text-sm text-red-600 mt-1"><i class="far fa-clock mr-1"></i>El proceso terminó debido al rechazo.</p>
            </div>
        </div>
        @endif

        {{-- ✅ Proceso finalizado con éxito (estatus 10) --}}
        @if($estatusActual->id === 10)
        <div class="mb-6 ml-6 relative">
            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-green-700 text-white shadow-md">
                <i class="fas fa-flag-checkered text-xs"></i>
            </span>
            <div class="p-4 rounded-lg shadow-sm border bg-green-50 border-green-300">
                <h3 class="font-semibold text-green-800">Proceso Finalizado</h3>
                <p class="text-sm text-green-600 mt-1"><i class="far fa-clock mr-1"></i>El proceso concluyó exitosamente.</p>
            </div>
        </div>
        @endif

        {{-- Pendientes --}}
        @if($siguiente === 'pendiente')
        <div class="mb-6 ml-6 relative">
            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 text-white shadow-md">
                <i class="fas fa-hourglass-half text-xs"></i>
            </span>
            <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                <h3 class="font-semibold text-gray-700">Pendiente por respuesta</h3>
                <p class="text-sm text-gray-500 mt-1"><i class="far fa-clock mr-1"></i>En espera por aprobación</p>
            </div>
        </div>
        @endif

        @if($siguiente === 'pendiente_gerencia')
        <div class="mb-6 ml-6 relative">
            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 text-white shadow-md">
                <i class="fas fa-hourglass-half text-xs"></i>
            </span>
            <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                <h3 class="font-semibold text-gray-700">Pendiente por aprobación gerencia</h3>
                <p class="text-sm text-gray-500 mt-1"><i class="far fa-clock mr-1"></i>En espera de corrección y aprobación</p>
            </div>
        </div>
        @endif

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

        {{-- Proceso finalizado --}}
        @if(in_array($currentId, [6, 9, 10, 11]))
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-600 text-white shadow-md">
                    <i class="fas fa-flag-checkered text-xs"></i>
                </span>

                <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-700">
                                Proceso finalizado
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                La requisición ya no avanzará en el flujo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-8 flex gap-3 justify-center">
        <a href="{{ route('requisiciones.historial') }}"
            class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Volver al historial
        </a>
        <a href="{{ route('pdf.generar', ['tipo' => 'estatus', 'id' => $requisicion->id]) }}" target="_blank"
            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
        </a>
    </div>
</div>
@endsection
