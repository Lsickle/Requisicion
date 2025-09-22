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
                    <th class="p-3 text-left">Requisición</th>
                    <th class="p-3 text-left">Orden</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Proveedor</th>
                    <th class="p-3 text-left">Método</th>
                    <th class="p-3 text-left">Plazo</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach($ordenes as $oc)
                @php
                $proveedor = optional($oc->ordencompraProductos->first())->proveedor;
                $requisicionId = $oc->requisicion->id ?? ($oc->requisicion_id ?? null);
                @endphp
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3">#{{ $oc->requisicion->id ?? '-' }}</td>
                    <td class="p-3">{{ $oc->order_oc ?? ('OC-' . $oc->id) }}</td>
                    <td class="p-3">{{ optional($oc->created_at)->format('d/m/Y') }}</td>
                    <td class="p-3">{{ $proveedor->prov_name ?? '—' }}</td>
                    <td class="p-3">{{ $oc->methods_oc ?? '—' }}</td>
                    <td class="p-3">{{ $oc->plazo_oc ?? '—' }}</td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 flex-wrap">
                            <button onclick="toggleModal('modal-{{ $oc->id }}')"
                                class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-1">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button type="button" class="bg-green-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-green-700 transition flex items-center gap-1 btn-open-entrega" data-oc-id="{{ $oc->id }}">
                                <i class="fas fa-truck"></i> Entregar
                            </button>
                            <button type="button" class="bg-yellow-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-yellow-700 transition flex items-center gap-1 btn-open-recibir" data-oc-id="{{ $oc->id }}">
                                <i class="fas fa-box"></i> Recibir
                            </button>
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
        @php
            $requisicionId = $oc->requisicion->id ?? ($oc->requisicion_id ?? null);
            // Datos para modal Entregar
            $ocpLineas = DB::table('ordencompra_producto as ocp')
                ->join('orden_compras as oc2','oc2.id','=','ocp.orden_compras_id')
                ->join('productos as p','p.id','=','ocp.producto_id')
                ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
                ->whereNull('ocp.deleted_at')
                ->where('ocp.requisicion_id', $requisicionId)
                ->where('ocp.orden_compras_id', $oc->id)
                ->select('ocp.id as ocp_id','oc2.order_oc','oc2.id as oc_id','p.id as producto_id','p.name_produc','p.unit_produc','prov.prov_name','ocp.total')
                ->orderBy('ocp.id','desc')
                ->get();
            $reqCantPorProducto = DB::table('centro_producto')
                ->where('requisicion_id', $requisicionId)
                ->select('producto_id', DB::raw('SUM(amount) as req'))
                ->groupBy('producto_id')
                ->pluck('req','producto_id');
            if ($reqCantPorProducto->isEmpty()) {
                $reqCantPorProducto = DB::table('producto_requisicion')
                    ->where('id_requisicion', $requisicionId)
                    ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                    ->groupBy('id_producto')
                    ->pluck('req','producto_id');
            }
            $recibidoPorProducto = DB::table('entrega')
                ->where('requisicion_id', $requisicionId)
                ->whereNull('deleted_at')
                ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as rec'))
                ->groupBy('producto_id')
                ->pluck('rec','producto_id');
            $recibidoStockPorProducto = DB::table('recepcion as r')
                ->join('orden_compras as oc3','oc3.id','=','r.orden_compra_id')
                ->where('oc3.requisicion_id', $requisicionId)
                ->whereNull('r.deleted_at')
                ->select('r.producto_id', DB::raw('SUM(COALESCE(r.cantidad_recibido,0)) as rec'))
                ->groupBy('r.producto_id')
                ->pluck('rec','producto_id');
            $pendNoConfPorProducto = DB::table('entrega')
                ->where('requisicion_id', $requisicionId)
                ->whereNull('deleted_at')
                ->where(function($q){ $q->whereNull('cantidad_recibido')->orWhere('cantidad_recibido', 0); })
                ->select('producto_id', DB::raw('SUM(cantidad) as pend'))
                ->groupBy('producto_id')
                ->pluck('pend', 'producto_id');
            // Datos para modal Recibir
            $recListEntrega = DB::table('entrega as e')
                ->join('productos as p','p.id','=','e.producto_id')
                ->select('e.id','p.name_produc','e.cantidad','e.cantidad_recibido')
                ->where('e.requisicion_id', $requisicionId)
                ->whereNull('e.deleted_at')
                ->where(function($q){ $q->whereNull('e.cantidad_recibido')->orWhere('e.cantidad_recibido', 0); })
                ->orderBy('e.id','asc')
                ->get();
            $recListRecep = DB::table('recepcion as r')
                ->join('orden_compras as oc4','oc4.id','=','r.orden_compra_id')
                ->join('productos as p','p.id','=','r.producto_id')
                ->select('r.id','p.name_produc','r.cantidad','r.cantidad_recibido')
                ->where('oc4.requisicion_id', $requisicionId)
                ->whereNull('r.deleted_at')
                ->orderBy('r.id','asc')
                ->get();
            $tipoRec = $recListEntrega->count() ? 'entrega' : 'recepcion';
        @endphp
        <div id="modal-entrega-oc-{{ $oc->id }}" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-oc-id="{{ $oc->id }}" data-requisicion-id="{{ $requisicionId }}">
            <div class="absolute inset-0 bg-black/50" data-close="1"></div>
            <div class="relative bg-white w-full max-w-4xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Entregar productos ({{ $oc->order_oc ?? ('OC-'.$oc->id) }})</h3>
                    <button type="button" class="text-gray-600 hover:text-gray-800 ent-close" data-oc-id="{{ $oc->id }}">✕</button>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="border rounded ent-select-all" data-oc-id="{{ $oc->id }}">
                            Seleccionar todos
                        </label>
                        <span class="text-xs text-gray-500">Estatus resultante: 8 (Material recibido por coordinador)</span>
                    </div>
                    <div class="max-h-[55vh] overflow-y-auto border rounded bg-white">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-center"><input type="checkbox" class="ent-chk-header" data-oc-id="{{ $oc->id }}"></th>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-left">Proveedor</th>
                                    <th class="px-3 py-2 text-left">OC</th>
                                    <th class="px-3 py-2 text-center">Cantidad OC</th>
                                    <th class="px-3 py-2 text-center">Pendiente</th>
                                    <th class="px-3 py-2 text-center">Entregar</th>
                                </tr>
                            </thead>
                            <tbody id="ent-tbody-{{ $oc->id }}">
                                @forelse($ocpLineas as $l)
                                @php
                                    $reqTot = (int) ($reqCantPorProducto[$l->producto_id] ?? 0);
                                    $recEntregas = (int) ($recibidoPorProducto[$l->producto_id] ?? 0);
                                    $recStock = (int) ($recibidoStockPorProducto[$l->producto_id] ?? 0);
                                    $recTotal = $recEntregas + $recStock;
                                    $faltTot = max(0, $reqTot - $recTotal);
                                    $isDone = $faltTot <= 0;
                                    $pendLock = (int) ($pendNoConfPorProducto[$l->producto_id] ?? 0);
                                    $maxEntregar = min((int)$l->total, $faltTot);
                                @endphp
                                <tr class="border-t">
                                    <td class="px-3 py-2 text-center"><input type="checkbox" class="ent-row-chk" data-ocp-id="{{ $l->ocp_id }}" data-producto-id="{{ $l->producto_id }}" data-rem="{{ $faltTot }}" {{ ($isDone || $pendLock>0) ? 'disabled' : '' }}></td>
                                    <td class="px-3 py-2">{{ $l->name_produc }}</td>
                                    <td class="px-3 py-2">{{ $l->prov_name ?? 'Proveedor' }}</td>
                                    <td class="px-3 py-2">{{ $l->order_oc ?? ('OC-'.$l->oc_id) }}</td>
                                    <td class="px-3 py-2 text-center">{{ $l->total }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($isDone)
                                             <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Completado</span>
                                        @elseif($pendLock>0)
                                            <span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Enviado, esperando confirmación ({{ $pendLock }})</span>
                                         @else
                                             <span class="text-xs">{{ $recTotal }} / {{ $reqTot }} recibidos · Falta {{ $faltTot }}</span>
                                         @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input type="number" min="0" max="{{ $maxEntregar }}" value="{{ $maxEntregar }}" class="w-24 border rounded p-1 text-center ent-cant-input" {{ ($isDone || $pendLock>0) ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="px-3 py-3 text-center text-gray-500">No hay líneas de esta orden.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" class="px-4 py-2 border rounded ent-cancel" data-oc-id="{{ $oc->id }}">Cancelar</button>
                    <button type="button" class="px-4 py-2 bg-green-600 text-white rounded ent-save" data-oc-id="{{ $oc->id }}">Realizar entrega</button>
                </div>
            </div>
        </div>

        <div id="modal-recibir-oc-{{ $oc->id }}" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-oc-id="{{ $oc->id }}" data-requisicion-id="{{ $requisicionId }}" data-tipo="{{ $tipoRec }}">
            <div class="absolute inset-0 bg-black/50" data-close="1"></div>
            <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Recibir productos (Req #{{ $requisicionId }})</h3>
                    <button type="button" class="text-gray-600 hover:text-gray-800 rc-close" data-oc-id="{{ $oc->id }}">✕</button>
                </div>
                <div class="p-6">
                    @php $recRows = $tipoRec === 'entrega' ? $recListEntrega : $recListRecep; @endphp
                    @if(($recRows ?? collect())->count())
                    <table class="w-full text-sm border rounded overflow-hidden bg-white">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Producto</th>
                                <th class="p-2 text-center">Entregado</th>
                                <th class="p-2 text-center">Cantidad recibida</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recRows as $r)
                            <tr class="border-t" data-item-id="{{ $r->id }}">
                                <td class="p-2">{{ $r->name_produc }}</td>
                                <td class="p-2 text-center">{{ $r->cantidad }}</td>
                                <td class="p-2 text-center">
                                    <input type="number" min="0" max="{{ $r->cantidad }}" value="{{ $r->cantidad_recibido ?? 0 }}" class="w-24 border rounded p-1 text-center rcx-input">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="flex justify-end gap-3 mt-4">
                        <button type="button" class="px-4 py-2 border rounded rc-cancel" data-oc-id="{{ $oc->id }}">Cancelar</button>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded rc-save" data-oc-id="{{ $oc->id }}">Guardar todo</button>
                    </div>
                    @else
                        <div class="text-gray-600">No hay registros para esta requisición.</div>
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
                            <div><span class="font-medium">Fecha:</span> {{ optional($oc->created_at)->format('d/m/Y') }}</div>
                            <div><span class="font-medium">Fecha OC:</span> {{ $oc->date_oc ? \Carbon\Carbon::parse($oc->date_oc)->format('d/m/Y') : '—' }}</div>
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
                                <table class="w-full text-sm bg-white">
                                    <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                                        <tr class="border-b">
                                            <th class="p-3 text-left">Producto</th>
                                            <th class="p-3 text-center">Cantidad</th>
                                            <th class="p-3 text-left">Distribución por Centro</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($oc->ordencompraProductos as $linea)
                                        @if($linea->producto)
                                        <tr class="border-b">
                                            <td class="p-3 font-medium text-gray-800 align-top">{{ $linea->producto->name_produc }}</td>
                                            <td class="p-3 text-center align-top">{{ (int)$linea->total }}</td>
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
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
                <div class="sticky bottom-0 left-0 bg-white pt-4 pb-4 px-8 flex flex-wrap gap-3 justify-end border-t z-20">
                    <button type="button" class="bg-yellow-500 text-white px-5 py-2 rounded-lg hover:bg-yellow-600 transition flex items-center gap-1 btn-open-recibir-from-view" data-oc-id="{{ $oc->id }}">
                        <i class="fas fa-box"></i> Recibir productos
                    </button>
                    <a href="{{ route('ordenes_compra.pdf', $oc->id) }}" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-1">
                        <i class="fas fa-file-pdf"></i> Descargar PDF
                    </a>
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

        // Abrir/Cerrar Entrega
        document.querySelectorAll('.btn-open-entrega').forEach(btn => {
            btn.addEventListener('click', () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-entrega-oc-${ocId}`);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            });
        });
        document.querySelectorAll('.ent-close, .ent-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-entrega-oc-${ocId}`);
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            });
        });
        document.querySelectorAll('[id^="modal-entrega-oc-"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target?.dataset?.close === '1') {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });
        document.querySelectorAll('.ent-chk-header').forEach(chk => {
            chk.addEventListener('change', () => {
                const ocId = chk.dataset.ocId;
                const tbody = document.getElementById(`ent-tbody-${ocId}`);
                tbody?.querySelectorAll('.ent-row-chk').forEach(c => { if (!c.disabled) c.checked = chk.checked; });
            });
        });
        document.querySelectorAll('.ent-select-all').forEach(chk => {
            chk.addEventListener('change', () => {
                const ocId = chk.dataset.ocId;
                const tbody = document.getElementById(`ent-tbody-${ocId}`);
                tbody?.querySelectorAll('.ent-row-chk').forEach(c => { if (!c.disabled) c.checked = chk.checked; });
            });
        });
        document.querySelectorAll('[id^="ent-tbody-"]').forEach(tb => {
            tb.addEventListener('input', (e) => {
                if (e.target && e.target.classList.contains('ent-cant-input')){
                    const mx = parseInt(e.target.max || '0', 10);
                    let v = parseInt(e.target.value || '0', 10);
                    if (isNaN(v) || v < 0) v = 0;
                    if (mx > 0 && v > mx) v = mx;
                    e.target.value = v;
                }
            });
        });
        document.querySelectorAll('.ent-save').forEach(btn => {
            btn.addEventListener('click', async () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-entrega-oc-${ocId}`);
                const reqId = modal?.dataset?.requisicionId;
                const tbody = document.getElementById(`ent-tbody-${ocId}`);
                const rows = Array.from(tbody?.querySelectorAll('tr')||[]);
                const items = [];
                const porProducto = {};
                rows.forEach(tr => {
                    const chk = tr.querySelector('.ent-row-chk');
                    const inp = tr.querySelector('.ent-cant-input');
                    if (!chk || !inp || chk.disabled || !chk.checked) return;
                    const prodId = Number(chk.dataset.productoId);
                    const rem = parseInt(chk.dataset.rem || '0', 10);
                    const actual = parseInt(inp.value||'0',10);
                    const ya = porProducto[prodId] || 0;
                    const permitido = Math.max(0, rem - ya);
                    const aEnviar = Math.min(Math.max(0, actual), permitido);
                    if (aEnviar > 0) {
                        items.push({ producto_id: prodId, ocp_id: Number(chk.dataset.ocpId), cantidad: aEnviar });
                        porProducto[prodId] = ya + aEnviar;
                    }
                });
                if (items.length === 0) { Swal.fire({icon:'info', title:'Sin selección', text:'Seleccione al menos un producto con cantidad > 0.'}); return; }
                try {
                    const resp = await fetch(`{{ route('entregas.storeMasiva') }}`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
                        body: JSON.stringify({ requisicion_id: reqId, items, comentario: null })
                    });
                    const data = await resp.json();
                    if (!resp.ok) throw new Error(data.message || 'Error al registrar entregas');
                    try { await fetch(`{{ route('recepciones.completarSiListo') }}`, { method:'POST', headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' }, body: JSON.stringify({ requisicion_id: reqId }) }); } catch(e) {}
                    modal.classList.add('hidden'); modal.classList.remove('flex');
                    Swal.fire({icon:'success', title:'Éxito', text:'Entregas registradas.'}).then(()=> location.reload());
                } catch(e){
                    Swal.fire({icon:'error', title:'Error', text:e.message});
                }
            });
        });

        // Abrir/Cerrar Recibir
        document.querySelectorAll('.btn-open-recibir').forEach(btn => {
            btn.addEventListener('click', () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            });
        });
        document.querySelectorAll('.rc-close, .rc-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            });
        });
        document.querySelectorAll('[id^="modal-recibir-oc-"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target?.dataset?.close === '1') {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });
        document.addEventListener('input', function(e){
            if (e.target && e.target.classList && e.target.classList.contains('rcx-input')){
                const max = parseInt(e.target.max || '0', 10);
                let v = parseInt(e.target.value || '0', 10);
                if (isNaN(v) || v < 0) v = 0;
                if (v > max) v = max;
                e.target.value = v;
            }
        });
        document.querySelectorAll('.rc-save').forEach(btn => {
            btn.addEventListener('click', async () => {
                const ocId = btn.dataset.ocId;
                const modal = document.getElementById(`modal-recibir-oc-${ocId}`);
                const reqId = modal?.dataset?.requisicionId;
                const tipo = modal?.dataset?.tipo === 'recepcion' ? 'recepcion' : 'entrega';
                const rows = Array.from(modal.querySelectorAll('tbody tr[data-item-id]'));
                if (rows.length === 0) { Swal.fire({icon:'info', title:'Sin registros', text:'No hay filas para guardar.'}); return; }
                const items = rows.map(tr => {
                    const id = parseInt(tr.dataset.itemId, 10);
                    const inp = tr.querySelector('.rcx-input');
                    const max = parseInt(inp?.max || '0', 10);
                    let val = parseInt(inp?.value || '0', 10);
                    if (isNaN(val) || val < 0) val = 0;
                    if (val > max) val = max;
                    if (inp) inp.value = val;
                    return { id, cantidad: val };
                });
                const total = items.length;
                const confirm = await Swal.fire({ title: 'Guardar recepciones', text: `Se actualizarán ${total} registro(s). ¿Desea continuar?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, guardar', cancelButtonText: 'Cancelar' });
                if (!confirm.isConfirmed) return;
                Swal.fire({ title: 'Guardando', text: 'Procesando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                try {
                    for (const it of items){
                        const payload = (tipo === 'entrega') ? { entrega_id: it.id, cantidad: it.cantidad } : { recepcion_id: it.id, cantidad: it.cantidad };
                        const url = (tipo === 'entrega') ? "{{ route('entregas.confirmar') }}" : "{{ route('recepciones.confirmar') }}";
                        const resp = await fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                        const data = await resp.json();
                        if (!resp.ok) throw new Error(data.message || 'Error al confirmar');
                    }
                    modal.classList.add('hidden'); modal.classList.remove('flex');
                    Swal.fire({icon:'success', title:'¡Guardado!', text:'Recepciones actualizadas.'}).then(()=> location.reload());
                } catch (e) {
                    Swal.fire({icon:'error', title:'Error', text: e.message || 'Ocurrió un error al guardar.'});
                }
            });
        });

        // Cierre por backdrop para modales de Ver y abrir Recibir desde Ver
        document.querySelectorAll('[data-modal="ver"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target?.dataset?.close === '1') {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    document.body.style.overflow = '';
                }
            });
        });
        document.querySelectorAll('.btn-open-recibir-from-view').forEach(btn => {
            btn.addEventListener('click', () => {
                const ocId = btn.dataset.ocId;
                const vModal = document.getElementById(`modal-${ocId}`);
                vModal?.classList.add('hidden');
                vModal?.classList.remove('flex');
                const rModal = document.getElementById(`modal-recibir-oc-${ocId}`);
                rModal?.classList.remove('hidden');
                rModal?.classList.add('flex');
            });
        });
    });
</script>
@endsection