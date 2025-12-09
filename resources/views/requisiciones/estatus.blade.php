@extends('layouts.app')

@section('title', 'Estatus de Requisición')

@section('content')
<x-sidebar />

<div class="max-w-4xl mx-auto p-6 mt-20 bg-white/95 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                <i class="fas fa-route text-xl"></i>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800 tracking-tight">
                Historial de Estatus - Requisición #{{ $requisicion->id }}
            </h1>
        </div>
        <a href="{{ url()->previous() }}" class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 shadow-sm transition">Volver</a>
    </div>

    <style>
        /* Timeline mejorada */
        .timeline { position: relative; margin-left: 0.75rem; }
        .timeline:before { content:""; position:absolute; left:0.6rem; top:0; bottom:0; width:3px; background:linear-gradient(to bottom,#6366f1,#6366f1 40%,#cbd5e1); border-radius:4px; }
        .tl-step { position:relative; padding:0.9rem 1rem 0.9rem 2.75rem; margin-bottom:1.2rem; border-radius:1rem; border:1px solid #e2e8f0; background:#f8fafc; box-shadow:0 1px 2px rgba(0,0,0,.05); transition:.25s; }
        .tl-step:last-child { margin-bottom:0; }
        .tl-step.done { background:#ecfdf5; border-color:#10b981; }
        .tl-step.current { background:#eef2ff; border-color:#6366f1; box-shadow:0 0 0 2px rgba(99,102,241,.15); }
        .tl-step.pending { background:#f1f5f9; border-color:#cbd5e1; opacity:.85; }
        .tl-step.reject { background:#fef2f2; border-color:#dc2626; }
        .tl-step.correction { background:#fefce8; border-color:#f59e0b; }
        .tl-bullet { position:absolute; left:0; top:0.85rem; width:1.6rem; height:1.6rem; border-radius:9999px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600; color:#fff; box-shadow:0 2px 4px rgba(0,0,0,.15); }
        .tl-bullet.done { background:#10b981; }
        .tl-bullet.current { background:#6366f1; animation:pulse 1.8s infinite; }
        .tl-bullet.pending { background:#94a3b8; }
        .tl-bullet.reject { background:#dc2626; }
        .tl-bullet.correction { background:#f59e0b; }
        @keyframes pulse { 0%,100% { transform:scale(1); box-shadow:0 0 0 0 rgba(99,102,241,.5);} 50% { transform:scale(1.08); box-shadow:0 0 0 8px rgba(99,102,241,.0);} }
        .status-badge { display:inline-block; padding:0.35rem .9rem; border-radius:0.75rem; line-height:1.1; font-size:.65rem; font-weight:600; white-space:normal; word-break:break-word; }
        .tl-meta { font-size:.7rem; letter-spacing:.03em; font-weight:500; text-transform:uppercase; }
        details summary { list-style:none; }
        details summary::-webkit-details-marker { display:none; }
        .thin-scrollbar { scrollbar-width: thin; scrollbar-color:#94a3b8 #e2e8f0; }
        .thin-scrollbar::-webkit-scrollbar { width:8px; height:8px; }
        .thin-scrollbar::-webkit-scrollbar-track { background:#e2e8f0; border-radius:8px; }
        .thin-scrollbar::-webkit-scrollbar-thumb { background:#94a3b8; border-radius:8px; }
        .thin-scrollbar::-webkit-scrollbar-thumb:hover { background:#64748b; }
        .progress-wrapper { margin-bottom:1.5rem; }
        .progress-bar { position:relative; height:10px; background:#e2e8f0; border-radius:9999px; overflow:hidden; }
        .progress-bar span { position:absolute; left:0; top:0; bottom:0; background:linear-gradient(90deg,#6366f1,#10b981); }
        .legend { display:flex; flex-wrap:wrap; gap:.75rem; font-size:.65rem; margin-bottom:1rem; }
        .legend span { display:inline-flex; align-items:center; gap:.35rem; background:#f1f5f9; padding:.35rem .6rem; border-radius:.6rem; border:1px solid #e2e8f0; }
        .legend i { font-size:.6rem; }
    </style>

    @php
        // Cargar historial real desde estatus_requisicion (join con estatus) en orden cronológico asc
        try {
            $historial = DB::table('estatus_requisicion as er')
                ->where('er.requisicion_id', $requisicion->id)
                ->whereNull('er.deleted_at')
                ->join('estatus as s', 's.id', '=', 'er.estatus_id')
                ->select('er.*', 's.status_name')
                ->orderBy('er.created_at', 'asc')
                ->get();
        } catch (\Throwable $e) {
            $historial = collect();
        }

        // Determinar registro activo (estatus = 1). Si existe, ese es el actual; si no, tomar el último registro del historial
        $activeRow = $historial->firstWhere('estatus', 1) ?? $historial->last();
        $currentId = $activeRow->estatus_id ?? null;
        $currentTs = isset($activeRow->created_at) ? strtotime((string)$activeRow->created_at) : null;

        // Traer todas las entregas para poder vincular a estatus 12
        try {
            $entregasAll = DB::table('entrega')
                ->where('requisicion_id', $requisicion->id)
                ->whereNull('entrega.deleted_at')
                ->join('productos', 'entrega.producto_id', '=', 'productos.id')
                ->select('entrega.*', 'productos.name_produc')
                ->orderBy('entrega.created_at', 'asc')
                ->get();
        } catch (\Throwable $e) {
            $entregasAll = collect();
        }

        // Flujo normal para sugerir siguiente estatus
        $flujo = [1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 7, 7 => 8, 8 => 10];
        if (in_array($currentId, [1,2,3])) $siguiente = 'pendiente';
        elseif (isset($flujo[$currentId])) $siguiente = $flujo[$currentId];
        else $siguiente = null;
        if (in_array($currentId, [6,9,10,13])) $siguiente = null;
        elseif ((int)$currentId === 11) $siguiente = 'pendiente_correccion';

        // Normalizar operación y ocultar visualmente estatus 3 para Tecnologia/Tecnología o Compras
        $rawOp = (string) ($requisicion->operacion_user ?? '');
        $opNorm = strtolower(trim(strtr($rawOp, [
            'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a',
            'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e','É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
            'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i',
            'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o',
            'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u',
            'ñ'=>'n','Ñ'=>'n'
        ])));
        $hideGerenciaApproval = in_array($opNorm, ['tecnologia','compras']);
        // Si el actual es 3 y debe ocultarse, mostrar visualmente el anterior como "actual"
        $displayActiveRow = $activeRow;
        $displayCurrentId = $currentId;
        $displayCurrentTs = $currentTs;
        if ($hideGerenciaApproval && (int)$currentId === 3) {
            $prev = $historial->where('estatus_id', '!=', 3)->last();
            if ($prev) {
                $displayActiveRow = $prev;
                $displayCurrentId = $prev->estatus_id ?? $currentId;
                $displayCurrentTs = isset($prev->created_at) ? strtotime((string)$prev->created_at) : $currentTs;
            }
        }

        // Calcular pasos mostrados (excluyendo el oculto 3 si procede)
        $visibleHistorial = $hideGerenciaApproval ? $historial->filter(fn($r)=> (int)$r->estatus_id !== 3) : $historial;
        $totalSteps = $visibleHistorial->count();
        // Completados: aquellos con timestamp <= del actual visual y diferentes de estados finales negativos
        $completedSteps = $visibleHistorial->filter(function($r) use($displayCurrentTs){
            $ts = isset($r->created_at)? strtotime((string)$r->created_at): null;
            return $ts !== null && $displayCurrentTs !== null && $ts <= $displayCurrentTs; });
        $percent = $totalSteps>0 ? round(($completedSteps->count() / $totalSteps)*100) : 0;
    @endphp

    <!-- Leyenda -->
    <div class="legend">
        <span><i class="fas fa-check text-green-600"></i> Realizado</span>
        <span><i class="fas fa-bolt text-indigo-600"></i> Actual</span>
        <span><i class="fas fa-hourglass-half text-slate-500"></i> Pendiente</span>
        <span><i class="fas fa-times text-red-600"></i> Rechazo/Cancelado</span>
        <span><i class="fas fa-exclamation text-amber-600"></i> Corrección</span>
    </div>

    <div class="timeline">
        {{-- Iteración de estados --}}
        @foreach($historial as $item)
            @if($hideGerenciaApproval && (int)($item->estatus_id ?? 0) === 3)
                @continue
            @endif
            @php
                // Flags y timestamps referenciando el estatus actual a mostrar (display)
                $itemCreated = isset($item->created_at) ? $item->created_at : null;
                $itemTs = $itemCreated ? strtotime((string)$itemCreated) : null;
                $isCompleted = ($displayCurrentTs !== null && $itemTs !== null) ? ($itemTs <= $displayCurrentTs) : false;
                $isCurrent = ((isset($item->estatus) && (int)$item->estatus === 1) || ((int)($item->estatus_id ?? 0) === (int)$displayCurrentId));
                $isRejected = in_array((int)($item->estatus_id ?? 0), [9,13]);
                $isCanceled = ((int)($item->estatus_id ?? 0) === 6);
                $isCorregir = ((int)($item->estatus_id ?? 0) === 11);

                // Preparar entregas relacionadas (solo para estatus 12) vinculadas por entrega_id si existe, si no por fecha
                $entregasRelacionadas = collect();
                if ((int)($item->estatus_id ?? 0) === 12) {
                    if (!empty($item->entrega_id)) {
                        $entregasRelacionadas = collect($entregasAll)->where('id', (int)$item->entrega_id)->values();
                    } elseif (isset($item->created_at)) {
                        $statusDate = \Carbon\Carbon::parse($item->created_at)->toDateString();
                        $entregasRelacionadas = $entregasAll->filter(function($e) use ($statusDate){
                            return \Carbon\Carbon::parse($e->created_at)->toDateString() === $statusDate;
                        })->values();
                    }
                }

                $state = 'pending';
                if($isRejected||$isCanceled) $state = 'reject';
                elseif($isCorregir && $isCurrent) $state = 'correction';
                elseif($isCurrent) $state = 'current';
                elseif($isCompleted) $state = 'done';
                $bulletIcon = 'circle';
                if($state==='done') $bulletIcon='check';
                elseif($state==='current') $bulletIcon='bolt';
                elseif($state==='reject') $bulletIcon='times';
                elseif($state==='correction') $bulletIcon='exclamation';
            @endphp
            <div class="tl-step {{ $state }}">
                <span class="tl-bullet {{ $state }}"><i class="fas fa-{{ $bulletIcon }}"></i></span>
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-sm md:text-base m-0 {{ $state==='reject' ? 'text-red-800' : ($state==='correction' ? 'text-amber-800' : ($state==='done' ? 'text-green-800' : ($state==='current' ? 'text-indigo-800' : 'text-slate-700'))) }}">{{ $item->status_name }}</h3>
                        @if($isCurrent)
                            <span class="status-badge bg-white border border-slate-300 text-slate-700 shadow-sm">Estatus actual</span>
                        @endif
                    </div>
                    @if(isset($item->created_at))
                        <div class="tl-meta {{ $state==='reject' ? 'text-red-600' : ($state==='correction' ? 'text-amber-600' : ($state==='done' ? 'text-green-600' : ($state==='current' ? 'text-indigo-600' : 'text-slate-500'))) }} flex items-center gap-1">
                            <i class="far fa-clock"></i>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}
                        </div>
                    @endif
                    @if(isset($item->comentario) && $item->comentario)
                        <div class="p-2 rounded bg-white border text-xs md:text-sm text-slate-700"><strong>Comentario:</strong> {{ $item->comentario }}</div>
                    @endif

                    @if($entregasRelacionadas->isNotEmpty())
                        <details class="rounded border overflow-hidden mt-2">
                            <summary class="px-3 py-2 bg-indigo-50 text-indigo-700 cursor-pointer text-xs md:text-sm font-medium">Ver entregas parciales</summary>
                            <div class="p-3 text-xs md:text-sm bg-gray-50 thin-scrollbar max-h-60 overflow-y-auto">
                                <table class="w-full text-xs md:text-sm bg-white rounded border">
                                    <thead class="bg-gray-100">
                                        <tr>
                                            <th class="p-2 text-left">Producto</th>
                                            <th class="p-2 text-center">Total</th>
                                            <th class="p-2 text-center">Recibido</th>
                                            <th class="p-2 text-center">Faltante</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $cutoffTs = null;
                                            try { $cutoffTs = \Carbon\Carbon::parse(optional($entregasRelacionadas->last())->created_at)->format('Y-m-d H:i:s'); } catch (\Throwable $e) { $cutoffTs = null; }
                                            $qtyColRec = \Illuminate\Support\Facades\Schema::hasColumn('entrega','cantidad_recibida') ? 'cantidad_recibida' : (\Illuminate\Support\Facades\Schema::hasColumn('entrega','cantidad_recibido') ? 'cantidad_recibido' : 'cantidad');
                                        @endphp
                                        @foreach($entregasRelacionadas->groupBy('producto_id') as $productoId => $coleccion)
                                            @php
                                                $productoNombre = optional($coleccion->first())->name_produc ?? '—';
                                                // Total solicitado (centros -> fallback producto_requisicion)
                                                $totalSolicitado = (int) DB::table('centro_producto')
                                                    ->where('requisicion_id', $requisicion->id)
                                                    ->where('producto_id', $productoId)
                                                    ->sum('amount');
                                                if ($totalSolicitado <= 0) {
                                                    $totalSolicitado = (int) DB::table('producto_requisicion')
                                                        ->where('id_requisicion', $requisicion->id)
                                                        ->where('id_producto', $productoId)
                                                        ->sum('pr_amount');
                                                }
                                                // Recibido acumulado hasta el momento de esta entrega
                                                $recibidoAcumulado = (int) DB::table('entrega')
                                                    ->where('requisicion_id', $requisicion->id)
                                                    ->where('producto_id', $productoId)
                                                    ->whereNull('deleted_at')
                                                    ->when($cutoffTs, function($q) use ($cutoffTs){ $q->where('created_at','<=',$cutoffTs); })
                                                    ->sum(DB::raw('COALESCE('.$qtyColRec.',0)'));
                                                $faltante = max(0, $totalSolicitado - $recibidoAcumulado);
                                            @endphp
                                            <tr class="border-t">
                                                <td class="p-2">{{ $productoNombre }}</td>
                                                <td class="p-2 text-center">{{ $totalSolicitado }}</td>
                                                <td class="p-2 text-center">{{ $recibidoAcumulado }}</td>
                                                <td class="p-2 text-center">{{ $faltante }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        @endforeach

        @php
            $hasRechazo = ($historial->where('estatus_id', 9)->isNotEmpty() || $historial->where('estatus_id', 13)->isNotEmpty());
            $isCompletado = (($displayCurrentId !== null) && ((int)$displayCurrentId === 10));
            $showRed = $hasRechazo;
            $showGreen = !$hasRechazo && $isCompletado;
            // Excluir 11 (Ajustes requeridos) de mostrar como 'Proceso finalizado'
            $showGray = !$hasRechazo && !$isCompletado && in_array((int)$displayCurrentId, [6]);
        @endphp

        {{-- Estados finales / próximos --}}
        @if($showRed)
            <div class="tl-step reject">
                <span class="tl-bullet reject"><i class="fas fa-stop-circle"></i></span>
                <h3 class="font-semibold text-red-800 text-sm md:text-base">Proceso Finalizado</h3>
                <p class="text-xs md:text-sm text-red-600 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>El proceso terminó debido al rechazo.</p>
            </div>
        @elseif($showGreen)
            <div class="tl-step done">
                <span class="tl-bullet done"><i class="fas fa-flag-checkered"></i></span>
                <h3 class="font-semibold text-green-800 text-sm md:text-base">Proceso Finalizado</h3>
                <p class="text-xs md:text-sm text-green-600 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>El proceso concluyó exitosamente.</p>
            </div>
        @elseif($showGray)
            <div class="tl-step pending">
                <span class="tl-bullet pending"><i class="fas fa-flag-checkered"></i></span>
                <h3 class="font-semibold text-slate-700 text-sm md:text-base">Proceso finalizado</h3>
                <p class="text-xs md:text-sm text-slate-500 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>La requisición ya no avanzará en el flujo.</p>
            </div>
        @endif

        @if($siguiente === 'pendiente')
            <div class="tl-step pending">
                <span class="tl-bullet pending"><i class="fas fa-hourglass-half"></i></span>
                <h3 class="font-semibold text-slate-700 text-sm md:text-base">Pendiente por respuesta</h3>
                <p class="text-xs md:text-sm text-slate-500 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>En espera por aprobación</p>
            </div>
        @endif
        @if($siguiente === 'pendiente_gerencia')
            <div class="tl-step pending">
                <span class="tl-bullet pending"><i class="fas fa-hourglass-half"></i></span>
                <h3 class="font-semibold text-slate-700 text-sm md:text-base">Pendiente por aprobación gerencia</h3>
                <p class="text-xs md:text-sm text-slate-500 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>En espera de corrección y aprobación</p>
            </div>
        @endif
        @if($siguiente === 'pendiente_correccion')
            <div class="tl-step correction">
                <span class="tl-bullet correction"><i class="fas fa-exclamation"></i></span>
                <h3 class="font-semibold text-amber-800 text-sm md:text-base">Pendiente por corrección</h3>
                <p class="text-xs md:text-sm text-amber-700 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>La requisición requiere ajustes antes de continuar.</p>
            </div>
        @endif
        @if(is_numeric($siguiente))
            @php
                $sigNombre = optional($historial->firstWhere('id', $siguiente))->status_name
                    ?? (DB::table('estatus')->where('id', $siguiente)->value('status_name') ?? 'Pendiente siguiente');
            @endphp
            <div class="tl-step pending">
                <span class="tl-bullet pending"><i class="fas fa-hourglass-half"></i></span>
                <h3 class="font-semibold text-slate-700 text-sm md:text-base">{{ $sigNombre }}</h3>
                <p class="text-xs md:text-sm text-slate-500 mt-1 flex items-center gap-1"><i class="far fa-clock"></i>En espera de que avance el proceso</p>
            </div>
        @endif
    </div>
</div>
@endsection
