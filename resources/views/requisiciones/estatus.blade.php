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
        $estatusOrdenados = $estatusOrdenados ?? collect();
        $currentId = optional($estatusActual)->id ?? 0;

        // Determinar timestamp actual de forma segura:
        // - Construir lista de timestamps con isset() para evitar Optional falsos
        $allTimestamps = $estatusOrdenados->map(function($i){
            $date = (isset($i->pivot) && isset($i->pivot->created_at)) ? $i->pivot->created_at : null;
            return $date ? strtotime((string)$date) : null;
        })->filter()->values();

        // - Buscar el estatus activo (pivot.estatus == 1) de forma segura
        $active = $estatusOrdenados->first(function($i){
            return isset($i->pivot) && isset($i->pivot->estatus) && $i->pivot->estatus == 1;
        });
        $activeTimestamp = ($active && isset($active->pivot->created_at)) ? strtotime((string)$active->pivot->created_at) : null;

        $currentTs = $activeTimestamp ?? ($allTimestamps->isNotEmpty() ? $allTimestamps->max() : null);

        // Deduplicar: conservar solo el más reciente por id, luego ordenar cronológicamente
        // Mantener múltiples registros para estatus 12 (entrega parcial).
        // Para los demás estatus, conservar sólo el más reciente por id.
        $estatusSortedDesc = $estatusOrdenados->sortByDesc(function($i){
            return (isset($i->pivot) && isset($i->pivot->created_at)) ? strtotime((string)$i->pivot->created_at) : null;
        })->values();

        $seenIds = collect();
        $filtered = collect();
        foreach ($estatusSortedDesc as $e) {
            if (isset($e->id) && (int)$e->id === 12) {
                // conservar todas las entradas 12
                $filtered->push($e);
            } else {
                if (!$seenIds->contains($e->id)) {
                    $seenIds->push($e->id);
                    $filtered->push($e);
                }
            }
        }

        // Ordenar cronológicamente ascendente para mostrar en timeline
        $estatusFiltrados = $filtered->sortBy(function($i){
            return (isset($i->pivot) && isset($i->pivot->created_at)) ? strtotime((string)$i->pivot->created_at) : null;
        })->values();

        // Traer todas las entregas de la requisición para poder vincular a estatus 12 repetidos
        $entregasAll = DB::table('entrega')
            ->where('requisicion_id', $requisicion->id)
            ->whereNull('entrega.deleted_at')
            ->join('productos', 'entrega.producto_id', '=', 'productos.id')
            ->select('entrega.*', 'productos.name_produc')
            ->orderBy('entrega.created_at', 'asc')
            ->get();

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
        if (in_array($currentId, [6, 9, 10, 13])) {
            $siguiente = null;
        } elseif ($currentId == 11) {
            $siguiente = 'pendiente_gerencia';
        }
        @endphp 

        {{-- Mostrar estatus --}}
        @foreach($estatusFiltrados as $item)
            @php
                // Flags y timestamps
                $itemCreated = (isset($item->pivot) && isset($item->pivot->created_at)) ? $item->pivot->created_at : null;
                $itemTs = $itemCreated ? strtotime((string)$itemCreated) : null;
                if ($itemTs !== null && $currentTs !== null) {
                    $isCompleted = $itemTs <= $currentTs;
                } else {
                    $isCompleted = ($item->id == 7) || ($item->id < $currentId);
                }
                $isCurrent = $item->id === $currentId;
                $isRejected  = in_array($item->id, [9,13]);
                $isCanceled  = $item->id === 6;
                $isCorregir  = $item->id === 11;

                // Preparar entregas relacionadas (solo para estatus 12)
                $entregasRelacionadas = collect();
                if (isset($item->id) && $item->id == 12) {
                    $statusDate = isset($item->pivot->created_at) ? \Carbon\Carbon::parse($item->pivot->created_at)->toDateString() : null;
                    if ($statusDate) {
                        $entregasRelacionadas = $entregasAll->filter(function($e) use ($statusDate){
                            return \Carbon\Carbon::parse($e->created_at)->toDateString() === $statusDate;
                        })->values();
                    }
                }
            @endphp

            <div class="mb-6 ml-6 relative">
                {{-- Icono izquierdo --}}
                @if($isRejected || $isCanceled)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-red-600 text-white shadow-md">
                        <i class="fas fa-times text-xs"></i>
                    </span>
                @elseif($isCurrent && $isCorregir)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-yellow-500 text-white shadow-md">
                        <i class="fas fa-exclamation text-xs"></i>
                    </span>
                @elseif($isCompleted)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-green-500 text-white shadow-md">
                        <i class="fas fa-check text-xs"></i>
                    </span>
                @elseif($isCurrent)
                    <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-blue-600 text-white shadow-md">
                        <i class="fas fa-check text-xs"></i>
                    </span>
                @endif

                <div class="p-4 rounded-lg shadow-sm border 
                    @if($isRejected || $isCanceled) bg-red-50 border-red-300
                    @elseif($isCorregir && $isCurrent) bg-yellow-50 border-yellow-300
                    @elseif($isCompleted) bg-green-50 border-green-300
                    @elseif($isCurrent) bg-blue-50 border-blue-300
                    @endif">

                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold 
                                @if($isRejected || $isCanceled) text-red-800
                                @elseif($isCorregir && $isCurrent) text-yellow-800
                                @elseif($isCompleted) text-green-800
                                @elseif($isCurrent) text-blue-800
                                @endif">
                                {{ $item->status_name }}
                            </h3>

                            @if(isset($item->pivot->created_at))
                                <p class="text-sm 
                                    @if($isRejected || $isCanceled) text-red-600
                                    @elseif($isCorregir && $isCurrent) text-yellow-600
                                    @elseif($isCompleted) text-green-600
                                    @elseif($isCurrent) text-blue-600
                                    @endif mt-1">
                                    <i class="far fa-clock mr-1"></i>
                                    {{ optional($item->pivot->created_at)->format('d/m/Y H:i') }}
                                </p>
                            @endif

                            @if(isset($item->pivot->comentario) && $item->pivot->comentario)
                                <div class="mt-2 p-2 bg-white rounded border text-sm text-gray-700">
                                    <strong>Comentario:</strong> {{ $item->pivot->comentario }}
                                </div>
                            @endif

                            {{-- Entregas parciales: mostrar dentro de un <details> para comportamiento nativo --}}
                            @if($entregasRelacionadas->isNotEmpty())
                                <details class="mt-3 border rounded bg-white overflow-hidden">
                                    <summary class="px-3 py-2 bg-blue-50 text-blue-800 cursor-pointer font-medium">Ver entregas parciales</summary>
                                    <div class="p-3 text-sm bg-gray-50">
                                        <table class="w-full text-sm bg-white rounded overflow-hidden border">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="p-2 text-left">Producto</th>
                                                    <th class="p-2 text-center">Cantidad total</th>
                                                    <th class="p-2 text-center">Cantidad recibida</th>
                                                    <th class="p-2 text-center">Cantidad faltante</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($entregasRelacionadas->groupBy('producto_id') as $productoId => $coleccion)
                                                    @php
                                                        $productoNombre = optional($coleccion->first())->name_produc ?? '—';
                                                        $cantidadTotal = (int) (DB::table('producto_requisicion')
                                                            ->where('id_requisicion', $requisicion->id)
                                                            ->where('id_producto', $productoId)
                                                            ->value('pr_amount') ?? 0);
                                                        $cantidadRecibida = (int) DB::table('entrega')
                                                            ->where('requisicion_id', $requisicion->id)
                                                            ->where('producto_id', $productoId)
                                                            ->whereNotNull('cantidad_recibido')
                                                            ->sum('cantidad_recibido');
                                                        $faltante = max(0, $cantidadTotal - $cantidadRecibida);
                                                    @endphp
                                                    <tr class="border-t">
                                                        <td class="p-2">{{ $productoNombre }}</td>
                                                        <td class="p-2 text-center">{{ $cantidadTotal }}</td>
                                                        <td class="p-2 text-center">{{ $cantidadRecibida }}</td>
                                                        <td class="p-2 text-center">{{ $faltante }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </details>
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

        @php
            $hasRechazo = $estatusFiltrados->contains('id', 9) || $estatusFiltrados->contains('id', 13);
            $isCompletado = optional($estatusActual)->id === 10;
            $showRed = $hasRechazo;
            $showGreen = !$hasRechazo && $isCompletado;
            $showGray = !$hasRechazo && !$isCompletado && in_array($currentId, [6, 11]);
        @endphp

        @if($showRed)
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-red-700 text-white shadow-md">
                    <i class="fas fa-stop-circle text-xs"></i>
                </span>
                <div class="p-4 rounded-lg shadow-sm border bg-red-50 border-red-300">
                    <h3 class="font-semibold text-red-800">Proceso Finalizado</h3>
                    <p class="text-sm text-red-600 mt-1"><i class="far fa-clock mr-1"></i>El proceso terminó debido al rechazo.</p>
                </div>
            </div>
        @elseif($showGreen)
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-green-700 text-white shadow-md">
                    <i class="fas fa-flag-checkered text-xs"></i>
                </span>
                <div class="p-4 rounded-lg shadow-sm border bg-green-50 border-green-300">
                    <h3 class="font-semibold text-green-800">Proceso Finalizado</h3>
                    <p class="text-sm text-green-600 mt-1"><i class="far fa-clock mr-1"></i>El proceso concluyó exitosamente.</p>
                </div>
            </div>
        @elseif($showGray)
            <div class="mb-6 ml-6 relative">
                <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full bg-gray-600 text-white shadow-md">
                    <i class="fas fa-flag-checkered text-xs"></i>
                </span>
                <div class="p-4 rounded-lg shadow-sm border bg-gray-100 border-gray-400">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="font-semibold text-gray-700">Proceso finalizado</h3>
                            <p class="text-sm text-gray-500 mt-1">La requisición ya no avanzará en el flujo.</p>
                        </div>
                    </div>
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
                            @php
                                $sigNombre = optional($estatusOrdenados->firstWhere('id', $siguiente))->status_name
                                    ?? (DB::table('estatus')->where('id', $siguiente)->value('status_name') ?? 'Pendiente siguiente');
                            @endphp
                            <h3 class="font-semibold text-gray-700">{{ $sigNombre }}</h3>
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

    <div class="mt-8 flex gap-3 justify-center">
        <a href="{{ route('pdf.generar', ['tipo' => 'estatus', 'id' => $requisicion->id]) }}" target="_blank"
            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center">
            <i class="fas fa-file-pdf mr-2"></i> Descargar PDF
        </a>
    </div>
</div>
@endsection
