@extends('layouts.app')

@section('title', 'Historial de Órdenes de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Historial de Órdenes de Compra</h1>

    <div class="mb-6 flex justify-between items-center">
        <input type="text" id="busqueda" placeholder="Buscar orden..."
            class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
    </div>

    @if($ordenes->isEmpty())
    <p class="text-gray-500 text-center py-6">No hay órdenes de compra registradas.</p>
    @else
    <div class="overflow-x-auto">
        <table id="tablaOC" class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
            <thead class="bg-blue-50 text-gray-700 uppercase text-sm font-semibold">
                <tr>
                    <th class="p-3 text-left w-24" style="width:100px;">Requisición</th>
                    <th class="p-3 text-left">Orden</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Proveedor</th>
                    <th class="p-3 text-left">Método</th>
                    <th class="p-3 text-left">Plazo</th>
                    <th class="p-3 text-right">Total OC</th>
                    <th class="p-3 text-left">Estatus</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            @php $ordersGrandTotal = 0; @endphp
            <tbody class="text-gray-700">
                @foreach($ordenes as $oc)
                @php
                    // calcular total de la OC sumando price_produc * cantidad por línea
                    $ocTotal = 0;
                    foreach($oc->ordencompraProductos as $ln) {
                        $price = optional($ln->producto)->price_produc ?? 0;
                        $qty = (int)($ln->total ?? 0);
                        $ocTotal += $price * $qty;
                    }
                    $ordersGrandTotal += $ocTotal;
                @endphp
                @php
                $proveedor = optional($oc->ordencompraProductos->first())->proveedor;
                $requisicionId = $oc->requisicion->id ?? ($oc->requisicion_id ?? null);
                // Obtener estatus activo desde orden_compra_estatus. Si no existe, fallback a cómputo por recepciones
                try {
                    $activeEstatusId = DB::table('orden_compra_estatus')
                        ->where('orden_compra_id', $oc->id)
                        ->where('activo', 1)
                        ->whereNull('deleted_at')
                        ->orderByDesc('created_at')
                        ->value('estatus_id');

                    if ($activeEstatusId) {
                        $estatusText = DB::table('estatus_orden_compra')->where('id', $activeEstatusId)->value('status_name') ?? '—';
                        $isTerminada = ((int)$activeEstatusId === 3);
                    } else {
                        $totOrdered = (int) DB::table('ordencompra_producto')->where('orden_compras_id', $oc->id)->whereNull('deleted_at')->sum('total');
                        $totReceived = (int) DB::table('recepcion')->where('orden_compra_id', $oc->id)->whereNull('deleted_at')->sum(DB::raw('COALESCE(cantidad_recibido,0)'));
                        if ($totReceived <= 0) {
                            $estatusText = 'Orden creada';
                        } elseif ($totReceived >= $totOrdered && $totOrdered > 0) {
                            $estatusText = 'Completada';
                        } else {
                            $estatusText = 'Pendiente';
                        }
                        $isTerminada = false;
                    }
                } catch (\Throwable $e) {
                    $estatusText = '—';
                    $isTerminada = false;
                }
                @endphp
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3 whitespace-nowrap text-sm" style="width:100px;">#{{ $oc->requisicion->id ?? '-' }}</td>
                    <td class="p-3">{{ $oc->order_oc ?? ('OC-' . $oc->id) }}</td>
                    <td class="p-3">{{ optional($oc->created_at)->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $proveedor->prov_name ?? '—' }}</td>
                    <td class="p-3">{{ $oc->methods_oc ?? '—' }}</td>
                    <td class="p-3">{{ $oc->plazo_oc ?? '—' }}</td>
                    <td class="p-3 text-right font-semibold">{{ number_format($ocTotal, 2) }}</td>
                    @php
                        // Mostrar etiqueta corta y elegir color
                        $estatusDisplay = $estatusText;
                        if ($estatusText === 'Orden creada') $estatusDisplay = 'Creada';
                        // clase por defecto
                        $badgeClass = 'bg-gray-100 text-gray-800';
                        if (strtolower($estatusDisplay) === 'completada') $badgeClass = 'bg-green-100 text-green-700';
                        elseif (strtolower($estatusDisplay) === 'pendiente') $badgeClass = 'bg-amber-100 text-amber-700';
                        elseif (strtolower($estatusDisplay) === 'creada') $badgeClass = 'bg-blue-100 text-blue-800';
                    @endphp
                    <td class="p-3">
                        <span class="inline-flex items-center whitespace-nowrap px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClass }}">{{ $estatusDisplay }}</span>
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 items-center">
                            <button type="button" data-oc-id="{{ $oc->id }}" class="btn-open-ver bg-blue-600 hover:bg-blue-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Ver OC" aria-label="Ver OC">
                                <i class="fas fa-eye"></i>
                            </button>
                            @if(!($isTerminada ?? false))
                            <button type="button" data-oc-id="{{ $oc->id }}" class="btn-open-recibir bg-yellow-600 hover:bg-yellow-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Recibir productos" aria-label="Recibir productos">
                                <i class="fas fa-box"></i>
                            </button>
                            @else
                            <button type="button" disabled class="bg-gray-300 text-gray-600 rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Orden terminada" aria-label="Orden terminada">
                                <i class="fas fa-ban"></i>
                            </button>
                            @endif
                            @if(!($isTerminada ?? false))
                            <button type="button" data-oc-id="{{ $oc->id }}" class="btn-terminar-oc bg-red-600 hover:bg-red-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Terminar OC" aria-label="Terminar OC">
                                <i class="fas fa-flag-checkered"></i>
                            </button>
                            @endif
                            <a href="{{ route('ordenes_compra.pdf', $oc->id) }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Descargar PDF" aria-label="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </div>
                     </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación cliente -->
    <div class="flex items-center justify-between mt-4" id="paginationBarOC">
        <div class="text-sm text-gray-600">
            Mostrar
            <select id="pageSizeSelectOC" class="border rounded px-2 py-1">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
            por página
        </div>
        <div class="flex flex-wrap gap-1" id="paginationControlsOC"></div>
    </div>

    <!-- Modales fuera de la tabla para evitar problemas de layout -->
    @foreach($ordenes as $oc)
        @php $requisicionId = $oc->requisicion->id ?? ($oc->requisicion_id ?? null); @endphp

        <!-- El modal anterior que listaba únicamente filas desde 'recepcion' fue eliminado porque ocultaba la posibilidad de crear recepciones para líneas de la OC. Se conserva el modal que muestra las líneas de la OC y permite anotar cantidades a recibir. -->

        <div id="modal-recibir-oc-{{ $oc->id }}" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-oc-id="{{ $oc->id }}" data-requisicion-id="{{ $requisicionId }}">
            <div class="absolute inset-0 bg-black/50" data-close="1"></div>
            <div class="relative bg-white w-full max-w-3xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Recibir productos de la OC {{ $oc->order_oc ?? ('OC-'.$oc->id) }}</h3>
                    <button type="button" class="text-gray-600 hover:text-gray-800 rc-close" data-oc-id="{{ $oc->id }}">✕</button>
                </div>
                <div class="p-6">
                    @php
                        // Subconsulta para sumar las cantidades recibidas por producto en esta OC
                        $recSum = DB::table('recepcion')
                            ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as recibido'), DB::raw('MIN(id) as recepcion_id'))
                            ->where('orden_compra_id', $oc->id)
                            ->whereNull('deleted_at')
                            ->groupBy('producto_id');

                        // Traer también precio y unidad para mostrar totales
                        $recRows = DB::table('ordencompra_producto as ocp')
                            ->join('productos as p','p.id','=','ocp.producto_id')
                            ->leftJoinSub($recSum, 'r', function($j){
                                $j->on('r.producto_id','=','ocp.producto_id');
                            })
                            ->select(
                                'p.id as producto_id',
                                'p.name_produc',
                                'p.price_produc as price_produc',
                                'p.unit_produc as unit_produc',
                                'ocp.total as cantidad_total',
                                'r.recepcion_id as recepcion_id',
                                DB::raw('COALESCE(r.recibido,0) as recibido')
                            )
                            ->where('ocp.orden_compras_id', $oc->id)
                            ->whereNull('ocp.deleted_at')
                            ->orderBy('p.name_produc','asc')
                            ->get();
                    @endphp
                    @if(($recRows ?? collect())->count())
                    @php $grandRecTotal = 0; @endphp
                    <table class="w-full text-sm border rounded overflow-hidden bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Producto</th>
                                <th class="p-2 text-center">Cant. OC</th>
                                <th class="p-2 text-center">Unidad</th>
                                <th class="p-2 text-center">Precio U.</th>
                                <th class="p-2 text-center">Total</th>
                                <th class="p-2 text-center">Recibido</th>
                                <th class="p-2 text-center">Pendiente</th>
                                <th class="p-2 text-center">A recibir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recRows as $r)
                            @php
                                $pend = max(0, (int)$r->cantidad_total - (int)$r->recibido);
                                $price = (float)($r->price_produc ?? 0);
                                $lineTotal = $price * (int)$r->cantidad_total;
                                $grandRecTotal += $lineTotal;
                            @endphp
                            <tr class="border-t rc-row" data-rec-id="{{ $r->recepcion_id ?? '' }}" data-producto-id="{{ $r->producto_id }}" data-total="{{ (int)$r->cantidad_total }}" data-current="{{ (int)$r->recibido }}">
                                <td class="p-2">{{ $r->name_produc }}</td>
                                <td class="p-2 text-center">{{ (int)$r->cantidad_total }}</td>
                                <td class="p-2 text-center">{{ $r->unit_produc ?? '—' }}</td>
                                <td class="p-2 text-center">{{ number_format($price, 2) }}</td>
                                <td class="p-2 text-center">{{ number_format($lineTotal, 2) }}</td>
                                <td class="p-2 text-center">{{ (int)$r->recibido }}</td>
                                <td class="p-2 text-center">{{ $pend }} @if($pend === 0) <span class="ml-2 px-2 py-1 text-xs bg-green-100 text-green-700 rounded">Recepción completada</span> @endif</td>
                                <td class="p-2 text-center">
                                    <input type="number" min="0" max="{{ $pend }}" value="{{ $pend }}" class="w-24 border rounded p-1 text-center rcx-input" {{ $pend === 0 ? 'disabled' : '' }}>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-50 font-semibold border-t">
                                <td colspan="4" class="p-2 text-right">Total general</td>
                                <td class="p-2 text-center">{{ number_format($grandRecTotal, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" class="px-4 py-2 border rounded rc-cancel" data-oc-id="{{ $oc->id }}">Cancelar</button>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded rc-save" data-oc-id="{{ $oc->id }}">Guardar recepción</button>
                    </div>
                    @else
                        <div class="text-gray-600">Esta orden no tiene líneas.</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- Modales de Ver (detalle de la OC) fuera de la tabla --}}
    @foreach($ordenes as $oc)
    <div id="modal-{{ $oc->id }}" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-modal="ver">
        <div class="absolute inset-0 bg-black/50" data-close="1"></div>
        <div class="relative w-full max-w-4xl">
            <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] flex flex-col relative">
                <button onclick="toggleModal('modal-{{ $oc->id }}')"
                    class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl z-10"
                    aria-label="Cerrar modal">&times;</button>
                <div class="overflow-y-auto p-8" style="max-height:calc(85vh - 92px);">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Orden {{ $oc->order_oc ?? ('OC-' . $oc->id) }}</h2>

                    <section class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Información General</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-gray-50 rounded-lg p-4">
                            <div><span class="font-medium">Número de Orden:</span> {{ $oc->order_oc ?? ('OC-' . $oc->id) }}</div>
                            <div><span class="font-medium">Fecha de creación:</span> {{ optional($oc->created_at)->format('d/m/Y H:i') }}</div>
                            <div><span class="font-medium">Requisición:</span> #{{ $oc->requisicion->id ?? '-' }}</div>
                            <div><span class="font-medium">Proveedor:</span> {{ optional(optional($oc->ordencompraProductos->first())->proveedor)->prov_name ?? '—' }}</div>
                            <div><span class="font-medium">Método de pago:</span> {{ $oc->methods_oc ?? '—' }}</div>
                            <div><span class="font-medium">Plazo de pago:</span> {{ $oc->plazo_oc ?? '—' }}</div>
                            @if(!empty($oc->observaciones))
                            <div class="md:col-span-2"><span class="font-medium">Observaciones:</span> {{ $oc->observaciones }}</div>
                            @endif
                        </div>
                    </section>

                    <section class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Productos</h3>
                        <div class="border rounded-lg overflow-hidden">
                            <div>
                                @php
                                    // calcular total general de la orden (suma de price_produc * cantidad)
                                    $grandTotal = 0;
                                    foreach($oc->ordencompraProductos as $__ln) {
                                        $up = $__ln->producto->price_produc ?? 0;
                                        $grandTotal += $up * (int)$__ln->total;
                                    }
                                @endphp
                                 <table class="w-full text-sm bg-white">
                                     <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                                         <tr class="border-b">
                                             <th class="p-3 text-left">Producto</th>
                                             <th class="p-3 text-center">Cant.</th>
                                             <th class="p-3 text-center">Unidad</th>
                                             <th class="p-3 text-center">Precio U.</th>
                                             <th class="p-3 text-center">Total</th>
                                             <th class="p-3 text-left">Distribución por Centro</th>
                                         </tr>
                                     </thead>
                                     <tbody>
                                         @foreach($oc->ordencompraProductos as $linea)
                                         @if($linea->producto)
                                         @php
                                             $unitPrice = $linea->producto->price_produc ?? 0;
                                             $unitName = $linea->producto->unit_produc ?? '—';
                                             $lineTotal = $unitPrice * (int)$linea->total;
                                         @endphp
                                         <tr class="border-b">
                                             <td class="p-3 font-medium text-gray-800 align-top">{{ $linea->producto->name_produc }}</td>
                                             <td class="p-3 text-center align-top">{{ (int)$linea->total }}</td>
                                             <td class="p-3 text-center align-top">{{ $unitName }}</td>
                                             <td class="p-3 text-center align-top">{{ number_format($unitPrice, 2) }}</td>
                                             <td class="p-3 text-center align-top">{{ number_format($lineTotal, 2) }}</td>
                                             <td class="p-3 align-top">
                                                 @php
                                                 $dist = DB::table('ordencompra_centro_producto as ocp')
                                                     ->join('centro as c', 'ocp.centro_id', '=', 'c.id')
                                                     ->select('c.name_centro', 'ocp.amount')
                                                     ->where('ocp.orden_compra_id', $oc->id)
                                                     ->where('ocp.producto_id', $linea->producto_id)
                                                     ->get();
                                                 @endphp
                                                 <ul class="list-disc list-inside text-sm text-gray-700 space-y-0.5">
                                                     @forelse($dist as $d)
                                                     <li>{{ $d->name_centro }} ({{ $d->amount }})</li>
                                                     @empty
                                                     <li>No hay distribución registrada</li>
                                                     @endforelse
                                                 </ul>
                                             </td>
                                         </tr>
                                         @endif
                                         @endforeach
                                     </tbody>
                                    <tfoot>
                                        <tr class="bg-gray-50 font-semibold border-t">
                                            <td colspan="4" class="p-3 text-right">Total general</td>
                                            <td class="p-3 text-center">{{ number_format($grandTotal, 2) }}</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                 </table>
                             </div>
                         </div>
                     </section>
                </div>
                <div class="sticky bottom-0 left-0 bg-white pt-4 pb-4 px-8 flex flex-wrap gap-3 justify-end border-t z-20">
                    <button type="button" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-1 btn-open-estatus-oc" data-oc-id="{{ $oc->id }}">
                        <i class="fas fa-info-circle"></i> Ver Estatus
                    </button>
                    <button type="button" class="bg-yellow-500 text-white px-5 py-2 rounded-lg hover:bg-yellow-600 transition flex items-center gap-1 btn-open-recibir-from-view" data-oc-id="{{ $oc->id }}">
                        <i class="fas fa-box"></i> Recibir productos
                    </button>
                    <a href="{{ route('ordenes_compra.pdf', $oc->id) }}" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-1">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
                </div>
                <!-- Modal Estatus para esta OC -->
                @php
                    $recepcionesOC = DB::table('recepcion as r')
                        ->join('productos as p','p.id','=','r.producto_id')
                        ->select('r.id','r.created_at','r.cantidad','r.cantidad_recibido','r.reception_user','p.name_produc')
                         ->where('r.orden_compra_id', $oc->id)
                         ->whereNull('r.deleted_at')
                         ->orderBy('r.created_at','asc')
                         ->get();
                @endphp
                <div id="modal-estatus-oc-{{ $oc->id }}" class="fixed inset-0 z-[10000] hidden items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/50" data-close="1"></div>
                    <div class="relative w-full max-w-3xl">
                        <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-6 relative">
                            <button onclick="toggleModal('modal-estatus-oc-{{ $oc->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl">✕</button>

                            @php
                                try {
                                    $totOrdered = (int) DB::table('ordencompra_producto')->where('orden_compras_id', $oc->id)->whereNull('deleted_at')->sum('total');
                                    $totReceived = (int) DB::table('recepcion')->where('orden_compra_id', $oc->id)->whereNull('deleted_at')->sum(DB::raw('COALESCE(cantidad_recibido,0)'));
                                    $lastReception = DB::table('recepcion')->where('orden_compra_id', $oc->id)->whereNull('deleted_at')->orderBy('created_at','desc')->value('created_at');
                                    if ($totReceived <= 0) {
                                        $estatusTextModal = 'Orden creada';
                                    } elseif ($totReceived >= $totOrdered && $totOrdered > 0) {
                                        $estatusTextModal = 'Completada';
                                    } else {
                                        $estatusTextModal = 'Pendiente';
                                    }
                                } catch (\Throwable $e) {
                                    $estatusTextModal = '—';
                                    $lastReception = null;
                                }

                                $estatusHist = DB::table('orden_compra_estatus as oce')
                                    ->join('estatus_orden_compra as e','e.id','=','oce.estatus_id')
                                    ->leftJoin('recepcion as r','r.id','=','oce.recepcion_id')
                                    ->leftJoin('productos as p','p.id','=','r.producto_id')
                                    ->where('oce.orden_compra_id', $oc->id)
                                    ->whereNull('oce.deleted_at')
                                    ->select('oce.*','e.status_name','oce.estatus_id', 'r.id as recepcion_id', 'p.name_produc as producto_nombre', 'r.cantidad_recibido', 'r.reception_user')
                                    ->orderBy('oce.created_at','asc')
                                    ->get();

                                $recepcionesOC = DB::table('recepcion as r')
                                    ->join('productos as p','p.id','=','r.producto_id')
                                    ->select('r.id','r.created_at','r.cantidad','r.cantidad_recibido','r.reception_user','p.name_produc')
                                    ->where('r.orden_compra_id', $oc->id)
                                    ->whereNull('r.deleted_at')
                                    ->orderBy('r.created_at','asc')
                                    ->get();

                                $recepcionEnEstatus = $estatusHist->pluck('recepcion_id')->filter()->unique()->toArray();
                                if (!empty($recepcionEnEstatus)) {
                                    $recepcionesOC = collect($recepcionesOC)->filter(function($r) use ($recepcionEnEstatus) {
                                        return !in_array($r->id, $recepcionEnEstatus);
                                    })->values();
                                } else {
                                    $recepcionesOC = collect($recepcionesOC);
                                }
                                $hasRecepcionInEstatus = !empty($recepcionEnEstatus);

                                // primeras/últimas fechas para mostrar junto al estatus 3
                                $firstStatusDate = null;
                                $lastStatusDate = null;
                                if(isset($estatusHist) && $estatusHist->count()){
                                    $first = $estatusHist->first();
                                    $last = $estatusHist->last();
                                    $firstStatusDate = !empty($first->date_update) ? \Carbon\Carbon::parse($first->date_update) : (!empty($first->created_at) ? \Carbon\Carbon::parse($first->created_at) : null);
                                    $lastStatusDate = !empty($last->date_update) ? \Carbon\Carbon::parse($last->date_update) : (!empty($last->created_at) ? \Carbon\Carbon::parse($last->created_at) : null);
                                }
                            @endphp

                            <h3 class="text-xl font-semibold mb-4">Historial de estatus - OC {{ $oc->order_oc ?? ('OC-'.$oc->id) }}</h3>

                            <div class="mb-4">
                                @if(isset($estatusHist) && $estatusHist->count())
                                    <div class="mb-4">
                                        <h4 class="font-semibold mb-2">Estatus registrados</h4>
                                        @foreach($estatusHist as $eh)
                                            <details class="mb-2">
                                                <summary class="p-4 bg-gray-50 border rounded flex justify-between items-center cursor-pointer">
                                                    <div class="font-medium">{{ $eh->status_name }}</div>
                                                    <div class="flex items-center gap-2">
                                                        <div class="text-sm text-gray-600">{{ !empty($eh->date_update) ? \Carbon\Carbon::parse($eh->date_update)->format('d/m/Y H:i') : (!empty($eh->created_at) ? \Carbon\Carbon::parse($eh->created_at)->format('d/m/Y H:i') : '—') }}</div>
                                                        <svg class="details-summary-arrow w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"></path></svg>
                                                    </div>
                                                </summary>
                                                <div class="p-4 border rounded mt-2 bg-white">
                                                    @if(!empty($eh->recepcion_id))
                                                        <table class="w-full text-sm bg-white">
                                                            <thead class="bg-gray-100">
                                                                <tr>
                                                                    <th class="p-2 text-left">Producto</th>
                                                                    <th class="p-2 text-center">Cantidad recibida</th>
                                                                    <th class="p-2 text-left">Recibido por</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="p-2">{{ $eh->producto_nombre ?? '—' }}</td>
                                                                    <td class="p-2 text-center">{{ $eh->cantidad_recibido ?? 0 }}</td>
                                                                    <td class="p-2">{{ $eh->reception_user ?? '—' }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>

                                                        {{-- Mostrar sólo comentario para entradas de recepción; el usuario ya aparece en la tabla (reception_user) --}}
                                                        @if($eh->estatus_id != 3 && !empty($eh->comentario))
                                                            <div class="mt-3 text-sm text-gray-700">Comentario: {{ $eh->comentario }}</div>
                                                        @endif
                                                    @else
                                                        @if(isset($eh->estatus_id) && (int)$eh->estatus_id !== 3)
                                                            <div class="text-sm text-gray-700">Usuario: {{ $eh->user_name ?? ($eh->user_id ?? '—') }}</div>
                                                        @endif
                                                        @if(!empty($eh->comentario))
                                                            <div class="text-sm text-gray-700 mt-2">Comentario: {{ $eh->comentario }}</div>
                                                        @endif
                                                    @endif

                                                    {{-- Si este estatus es el 3, mostrar fechas dentro del mismo panel del estatus --}}
                                                    @if(isset($eh->estatus_id) && (int)$eh->estatus_id === 3)
                                                        <div class="mt-3 p-3 bg-green-50 border rounded text-sm text-gray-700">
                                                            <div>Fecha primer estatus: {{ $firstStatusDate ? $firstStatusDate->format('d/m/Y H:i') : '—' }}</div>
                                                            <div>Fecha último estatus: {{ $lastStatusDate ? $lastStatusDate->format('d/m/Y H:i') : '—' }}</div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </details>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="p-4 bg-gray-50 border rounded flex justify-between items-center mb-3">
                                        <div class="font-semibold">Orden creada</div>
                                        <div class="flex items-center gap-2">
                                            <div class="text-sm text-gray-600">{{ optional($oc->created_at)->format('d/m/Y H:i') }}</div>
                                        </div>
                                    </div>
                                    <div class="p-4 border rounded bg-white mb-3">
                                        <div class="text-sm text-gray-700 font-medium">Usuario</div>
                                        <div class="text-sm text-gray-700 mt-1">{{ session('user.name') ?? session('user.email') ?? '—' }}</div>
                                    </div>
                                @endif

                                @if($recepcionesOC->count() && empty($hasRecepcionInEstatus))
                                    @foreach($recepcionesOC as $rec)
                                        <details class="mb-3">
                                            <summary class="p-4 bg-gray-50 border rounded flex justify-between items-center cursor-pointer">
                                                <div class="font-semibold">Recepción</div>
                                                <div class="flex items-center gap-2">
                                                    <div class="text-sm text-gray-600">{{ !empty($rec->created_at) ? \Carbon\Carbon::parse($rec->created_at)->format('d/m/Y H:i') : (!empty($rec->fecha) ? \Carbon\Carbon::parse($rec->fecha)->format('d/m/Y') : '—') }}</div>
                                                    <svg class="details-summary-arrow w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd"></path></svg>
                                                </div>
                                            </summary>
                                            <div class="p-4 border rounded mt-2 bg-white">
                                                <table class="w-full text-sm bg-white">
                                                    <thead class="bg-gray-100">
                                                        <tr>
                                                            <th class="p-2 text-left">Producto</th>
                                                            <th class="p-2 text-center">Cantidad recibida</th>
                                                            <th class="p-2 text-left">Recibido por</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td class="p-2">{{ $rec->name_produc }}</td>
                                                            <td class="p-2 text-center">{{ $rec->cantidad_recibido ?? $rec->cantidad ?? 0 }}</td>
                                                            <td class="p-2">{{ $rec->reception_user ?? '—' }}</td>
                                                        </tr>
                                                    </tbody>
                                                </div>
                                            </details>
                                    @endforeach
                                @endif

                            </div>
                        </div>
                    </div>
                </div>
     </div>
     @endforeach

     @endif
</div>

<script>
    function toggleModal(id){
        const modal = document.getElementById(id);
        const isHidden = modal.classList.contains('hidden');
        if (isHidden) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-"]').forEach(m => {
                if (!m.classList.contains('hidden')) { m.classList.add('hidden'); m.classList.remove('flex'); }
            });
            document.body.style.overflow = '';
        }
    });

    // Búsqueda + paginación cliente
    const input = document.getElementById('busqueda');
    input?.addEventListener('keyup', () => {
        const filtro = input.value.toLowerCase();
        document.querySelectorAll('#tablaOC tbody tr').forEach(row => {
            row.dataset.match = row.textContent.toLowerCase().includes(filtro) ? '1' : '0';
        });
        ocShowPage(1);
    });

    let ocCurrentPage = 1;
    let ocPageSize = 10;

    function ocGetMatchedRows(){
        return Array.from(document.querySelectorAll('#tablaOC tbody tr'))
            .filter(r => (r.dataset.match ?? '1') !== '0');
    }

    function ocShowPage(page = 1){
        const rows = ocGetMatchedRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / ocPageSize));
        ocCurrentPage = Math.min(Math.max(1, page), totalPages);
        const start = (ocCurrentPage - 1) * ocPageSize;
        const end = start + ocPageSize;

        const allRows = Array.from(document.querySelectorAll('#tablaOC tbody tr'));
        allRows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        ocRenderPagination(totalPages);
    }

    function ocRenderPagination(totalPages){
        const container = document.getElementById('paginationControlsOC');
        if (!container) return;
        container.innerHTML = '';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (ocCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = ocCurrentPage === 1;
        btnPrev.onclick = () => ocShowPage(ocCurrentPage - 1);
        container.appendChild(btnPrev);

        const start = Math.max(1, ocCurrentPage - 2);
        const end = Math.min(totalPages, ocCurrentPage + 2);
        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1 rounded text-sm ' + (p === ocCurrentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
            btn.onclick = () => ocShowPage(p);
            container.appendChild(btn);
        }

        const btnNext = document.createElement('button');
        btnNext.textContent = 'Siguiente';
        btnNext.className = 'px-3 py-1 border rounded text-sm ' + (ocCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnNext.disabled = ocCurrentPage === totalPages;
        btnNext.onclick = () => ocShowPage(ocCurrentPage + 1);
        container.appendChild(btnNext);
    }

    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('#tablaOC tbody tr').forEach(r => r.dataset.match = '1');
        const sel = document.getElementById('pageSizeSelectOC');
        if (sel) {
            ocPageSize = parseInt(sel.value, 10) || 10;
            sel.addEventListener('change', (e) => {
                ocPageSize = parseInt(e.target.value, 10) || 10;
                ocShowPage(1);
            });
        }
        ocShowPage(1);

        // Input sanitization delegated
        document.addEventListener('input', function(e){
            if (e.target && e.target.classList && e.target.classList.contains('rcx-input')){
                const max = parseInt(e.target.max || '0', 10);
                let v = parseInt(e.target.value || '0', 10);
                if (isNaN(v) || v < 0) v = 0;
                if (v > max) v = max;
                e.target.value = v;
            }
        });

        // Delegated click handler for modal actions, rc-save and terminar
        document.addEventListener('click', async function(e){
            // open recibir modal
            const btnRec = e.target.closest('.btn-open-recibir');
            if (btnRec) {
                const ocId = btnRec.dataset.ocId;
                const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                if (modal) {
                    if (modal.parentNode !== document.body) document.body.appendChild(modal);
                    modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden';
                }
                return;
            }
            // open ver modal
            const btnVer = e.target.closest('.btn-open-ver');
            if (btnVer) {
                const ocId = btnVer.dataset.ocId;
                const modal = document.getElementById(`modal-${ocId}`);
                if (modal) { if (modal.parentNode !== document.body) document.body.appendChild(modal); modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
                return;
            }
            // open estatus modal
            const btnEstatus = e.target.closest('.btn-open-estatus-oc');
            if (btnEstatus) {
                const ocId = btnEstatus.dataset.ocId;
                const modal = document.getElementById(`modal-estatus-oc-${ocId}`);
                if (modal) { if (modal.parentNode !== document.body) document.body.appendChild(modal); modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
                return;
            }
            // open recibir from view
            const btnRecFromView = e.target.closest('.btn-open-recibir-from-view');
            if (btnRecFromView) {
                const ocId = btnRecFromView.dataset.ocId;
                const vModal = document.getElementById(`modal-${ocId}`);
                if (vModal) { vModal.classList.add('hidden'); vModal.classList.remove('flex'); }
                const rModal = document.getElementById(`modal-recibir-oc-${ocId}`);
                if (rModal) { if (rModal.parentNode !== document.body) document.body.appendChild(rModal); rModal.classList.remove('hidden'); rModal.classList.add('flex'); document.body.style.overflow = 'hidden'; }
                return;
            }
            // close recibir modal via buttons
            const btnClose = e.target.closest('.rc-close, .rc-cancel');
            if (btnClose) {
                const ocId = btnClose.dataset.ocId;
                if (ocId) {
                    const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                    if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow = ''; }
                }
                return;
            }
            // backdrop close for any modal with data-close="1"
            if (e.target && e.target.dataset && e.target.dataset.close === '1') {
                const parent = e.target.closest('[id^="modal-"]');
                if (parent) { parent.classList.add('hidden'); parent.classList.remove('flex'); document.body.style.overflow = ''; }
                return;
            }

            // rc-save: guardar recepciones
            const btnSave = e.target.closest('.rc-save');
            if (btnSave) {
                const ocId = btnSave.dataset.ocId;
                const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                const rows = Array.from(modal.querySelectorAll('.rc-row'));
                if (rows.length === 0) {
                    await Swal.fire({icon:'info', title:'Sin registros', text:'No hay filas para guardar.'});
                    return;
                }
                const items = rows.map(tr => {
                    const recId = tr.dataset.recId || null;
                    const prodId = parseInt(tr.dataset.productoId, 10);
                    const total = parseInt(tr.dataset.total || '0', 10);
                    const current = parseInt(tr.dataset.current || '0', 10);
                    const inp = tr.querySelector('.rcx-input');
                    if (!inp || inp.disabled) return null;
                    const max = parseInt(inp.max || '0', 10);
                    let inc = parseInt(inp.value || '0', 10);
                    if (isNaN(inc) || inc < 0) inc = 0;
                    if (inc > max) inc = max;
                    const nuevoAcumulado = Math.min(total, current + inc);
                    return { recId, prodId, total, current, inc, nuevoAcumulado };
                }).filter(Boolean).filter(it => it.inc > 0);
                if (items.length === 0) {
                    await Swal.fire({icon:'info', title:'Sin cantidades', text:'No hay cantidades a recibir.'});
                    return;
                }
                const confirm = await Swal.fire({ title: 'Confirmar recepción', text: 'Se registrarán las cantidades recibidas seleccionadas. ¿Desea continuar?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, guardar', cancelButtonText: 'Cancelar' });
                if (!confirm.isConfirmed) return;
                Swal.fire({ title: 'Guardando', text: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                try {
                    const receptionUser = {!! json_encode(session('user.name') ?? session('user.email') ?? session('user.id') ?? '') !!};
                    const payload = { items: items.map(it => ({
                        recepcion_id: it.recId || undefined,
                        orden_compra_id: it.recId ? undefined : ocId,
                        producto_id: it.prodId,
                        cantidad: it.total,
                        cantidad_recibido: it.nuevoAcumulado,
                        reception_user: receptionUser
                    })) };

                    const resp = await fetch("{{ route('recepciones.confirmar') }}", {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al guardar recepciones');
                    Swal.close();
                    await Swal.fire({icon:'success', title:'¡Recibido!', text:'Recepciones registradas y stock actualizado.'});
                    location.reload();
                } catch (err) {
                    Swal.close();
                    await Swal.fire({icon:'error', title:'Error', text: err.message || 'Ocurrió un error al guardar.'});
                }
                return;
            }

            // Terminar OC
            const btnTerm = e.target.closest('.btn-terminar-oc');
            if (btnTerm) {
                const ocId = btnTerm.dataset.ocId;
                const confirmed = await Swal.fire({ title: 'Terminar orden', text: 'Al terminar la orden no se podrán registrar más recepciones. ¿Desea continuar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, terminar', cancelButtonText: 'Cancelar' });
                if (!confirmed.isConfirmed) return;
                Swal.fire({ title: 'Procesando', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                try {
                    const resp = await fetch("{{ url('/ordenes_compra/terminar') }}/"+ocId, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al terminar la orden');
                    Swal.close();
                    await Swal.fire({ icon: 'success', title: 'Orden terminada', text: 'La orden ha sido marcada como terminada.' });
                    location.reload();
                } catch (err) {
                    Swal.close();
                    await Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Ocurrió un error' });
                }
                return;
            }
        });
    });
</script>
@endsection

<style>
        /* Flecha rotatoria para summaries */
        .details-summary-arrow{ transition: transform .18s ease; }
        details[open] .details-summary-arrow{ transform: rotate(180deg); }
    </style>