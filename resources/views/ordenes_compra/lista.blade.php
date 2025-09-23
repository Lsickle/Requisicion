@extends('layouts.app')

@section('title', 'Requisiciones Aprobadas para Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado: Título a la izquierda, Volver a la derecha -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Requisiciones Aprobadas para Orden de Compra</h1>
                <a href="{{ route('requisiciones.menu') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow transition">← Volver</a>
            </div>

            <!-- Barra de búsqueda -->
            <div class="mb-4">
                <input type="text" id="busquedaLista" placeholder="Buscar requisición..."
                    class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
            </div>

            <!-- Mostrar solo requisiciones con estatus permitidos y EXCLUIR explícitamente estatus 10 -->
            @php
                $permitidos = [4,5,7,8,12];
                $requisicionesFiltradas = ($requisiciones ?? collect())->filter(function($r) use ($permitidos) {
                    $hist = $r->estatusHistorial ?? collect();
                    $ultimo = $hist->sortByDesc('created_at')->first();
                    $ultimoId = $ultimo->estatus_id ?? null;
                    return $ultimoId !== 10 && in_array($ultimoId, $permitidos, true);
                })->values();
            @endphp

            <!-- Contenedor scrollable -->
            <div class="flex-1 overflow-y-auto">
                <!-- Tabla en escritorio -->
                <div class="bg-white rounded-lg shadow overflow-x-auto hidden md:block">
                    <table id="tablaRequisicionesLista" class="min-w-full table-auto border-collapse">
                        <thead class="bg-gray-200 text-gray-700 text-sm uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-2 text-left">#</th>
                                <th class="px-4 py-2 text-left">Detalle</th>
                                <th class="px-4 py-2 text-left">Prioridad</th>
                                <th class="px-4 py-2 text-left">Solicitante</th>
                                <th class="px-4 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisicionesFiltradas as $req)
                            @php
                                // Calcular si ya está completa: entregas confirmadas + recepciones confirmadas vs requeridos
                                $reqPorProducto = DB::table('centro_producto')
                                    ->where('requisicion_id', $req->id)
                                    ->select('producto_id', DB::raw('SUM(amount) as req'))
                                    ->groupBy('producto_id')->pluck('req','producto_id');
                                if ($reqPorProducto->isEmpty()) {
                                    $reqPorProducto = DB::table('producto_requisicion')
                                        ->where('id_requisicion', $req->id)
                                        ->select('id_producto as producto_id', DB::raw('SUM(pr_amount) as req'))
                                        ->groupBy('id_producto')->pluck('req','producto_id');
                                }
                                $recEnt = DB::table('entrega')->where('requisicion_id', $req->id)->whereNull('deleted_at')
                                    ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as rec'))
                                    ->groupBy('producto_id')->pluck('rec','producto_id');
                                $recStock = DB::table('recepcion as r')
                                    ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
                                    ->where('oc.requisicion_id', $req->id)->whereNull('r.deleted_at')
                                    ->select('r.producto_id', DB::raw('SUM(COALESCE(r.cantidad_recibido,0)) as rec'))
                                    ->groupBy('r.producto_id')->pluck('rec','producto_id');
                                $isComplete = !$reqPorProducto->isEmpty();
                                foreach ($reqPorProducto as $pid => $need) {
                                    $got = (int)($recEnt[$pid] ?? 0) + (int)($recStock[$pid] ?? 0);
                                    if ($got < (int)$need) { $isComplete = false; break; }
                                }
                                $estatusActivo = optional(($req->estatusHistorial ?? collect())->sortByDesc('created_at')->first())->estatus_id;
                            @endphp
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2">{{ $req->id }}</td>
                                <td class="px-4 py-2">{{ $req->detail_requisicion }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        {{ $req->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' : 
                                           ($req->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-green-100 text-green-800') }}">
                                        {{ ucfirst($req->prioridad_requisicion) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $req->name_user }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button onclick="toggleModal('modal-{{ $req->id }}')"
                                        class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">
                                        Ver
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No hay requisiciones aprobadas para orden de compra</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil como tarjetas -->
                <div id="listaMobile" class="md:hidden space-y-4">
                    @forelse($requisicionesFiltradas as $req)
                    <div class="bg-white rounded-lg shadow p-4 req-card">
                        <h2 class="font-bold text-lg mb-2">#{{ $req->id }} - {{ $req->detail_requisicion }}</h2>
                        <div class="grid grid-cols-2 gap-2 mb-2">
                            <div>
                                <p class="text-sm text-gray-600">Prioridad:</p>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                    {{ $req->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' : 
                                       ($req->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-green-100 text-green-800') }}">
                                    {{ ucfirst($req->prioridad_requisicion) }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600">Solicitante: {{ $req->name_user }}</p>
                        <div class="mt-3">
                            <button onclick="toggleModal('modal-{{ $req->id }}')"
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition text-sm">
                                Ver Detalles
                            </button>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500">No hay requisiciones aprobadas para orden de compra</p>
                    @endforelse
                </div>
            </div>

            <!-- Paginación inferior: selector y controles -->
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-600">Mostrar
                    <select id="pageSizeSelectLista" class="border rounded px-2 py-1 ml-2">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    por página
                </div>
                <div id="paginationControlsLista" class="flex flex-wrap gap-1"></div>
            </div>

        </div>
    </div>
</div>

<!-- Modales para cada requisición -->
@foreach($requisicionesFiltradas as $req)
<div id="modal-{{ $req->id }}" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
        <div class="flex-1 overflow-y-auto p-6 relative">
            <button onclick="toggleModal('modal-{{ $req->id }}')"
                class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 font-bold text-2xl">&times;</button>

            <h2 class="text-2xl font-bold mb-4">Requisición #{{ $req->id }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">Información del Solicitante</h3>
                    <p><strong>Nombre:</strong> {{ $req->name_user }}</p>
                    <p><strong>Email:</strong> {{ $req->email_user }}</p>
                    <p><strong>Operación:</strong> {{ $req->operacion_user }}</p>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-gray-700 mb-2">Detalles de la Requisición</h3>
                    <p><strong>Prioridad:</strong> 
                        <span class="px-2 py-1 rounded-full text-xs font-semibold 
                            {{ $req->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' : 
                               ($req->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' : 
                               'bg-green-100 text-green-800') }}">
                            {{ ucfirst($req->prioridad_requisicion) }}
                        </span>
                    </p>
                    <p><strong>Recobrable:</strong> {{ $req->Recobrable }}</p>
                </div>
            </div>

            <div class="mb-4">
                <p><strong>Detalle:</strong> {{ $req->detail_requisicion }}</p>
                <p><strong>Justificación:</strong> {{ $req->justify_requisicion }}</p>
            </div>

            <h3 class="text-xl font-semibold mt-6 mb-3">Productos</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-left">Cantidad Total</th>
                            <th class="px-4 py-2 text-left">Distribución por Centros</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($req->productos as $prod)
                        @php
                        $distribucion = DB::table('centro_producto')
                            ->where('requisicion_id', $req->id)
                            ->where('producto_id', $prod->id)
                            ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                            ->select('centro.name_centro', 'centro_producto.amount')
                            ->get();
                        $confirmadoEntrega = (int) DB::table('entrega')->where('requisicion_id', $req->id)->where('producto_id', $prod->id)->whereNull('deleted_at')->sum(DB::raw('COALESCE(cantidad_recibido,0)'));
                        $confirmadoStock = (int) DB::table('recepcion as r')->join('orden_compras as oc','oc.id','=','r.orden_compra_id')->where('oc.requisicion_id',$req->id)->where('r.producto_id',$prod->id)->whereNull('r.deleted_at')->sum(DB::raw('COALESCE(r.cantidad_recibido,0)'));
                        $totalConfirmado = $confirmadoEntrega + $confirmadoStock;
                        @endphp

                        <tr>
                            <td class="px-4 py-3 border">{{ $prod->name_produc }}</td>
                            <td class="px-4 py-3 border text-center font-semibold">{{ $prod->pivot->pr_amount }} @if($totalConfirmado>0)<span class="text-xs text-gray-500">({{ $totalConfirmado }} recibido)</span>@endif</td>
                            <td class="px-4 py-3 border">
                                @if($distribucion->count() > 0)
                                <div class="space-y-2">
                                    @foreach($distribucion as $centro)
                                    <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded">
                                        <span class="font-medium text-sm">{{ $centro->name_centro }}</span>
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">{{ $centro->amount }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                @endif

                                @php
                                    $salidas = DB::table('entrega as e')
                                        ->where('e.requisicion_id', $req->id)
                                        ->where('e.producto_id', $prod->id)
                                        ->whereNull('e.deleted_at')
                                        ->orderBy('e.id','asc')
                                        ->get();
                                @endphp
                                @if($salidas->count())
                                <div class="mt-3">
                                    <div class="text-sm font-semibold text-gray-700 mb-1">Salidas de stock</div>
                                    <ul class="text-xs text-gray-700 space-y-1">
                                        @foreach($salidas as $s)
                                            <li class="flex justify-between items-center bg-white border rounded px-2 py-1">
                                                <span>Cantidad entregada: {{ $s->cantidad }}</span>
                                                @if(is_null($s->cantidad_recibido) || (int)$s->cantidad_recibido === 0)
                                                    <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700">Esperando confirmación</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded bg-green-100 text-green-700">Comfirmado: {{ (int)$s->cantidad_recibido }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botón Crear Orden de Compra (DENTRO del modal) - CORREGIDO -->
        <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
            <form action="{{ route('ordenes_compra.create') }}" method="GET">
                <input type="hidden" name="requisicion_id" value="{{ $req->id }}">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Crear Orden de Compra
                </button>
            </form>
            <button type="button" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition" data-btn-sacar-stock data-requisicion-id="{{ $req->id }}">
                Sacar productos de stock
            </button>
        </div>
        @php
            $prodsData = ($req->productos ?? collect())->map(function($p){
                return [
                    'id' => $p->id,
                    'name_produc' => $p->name_produc,
                    'unit_produc' => $p->unit_produc,
                    'stock_produc' => $p->stock_produc,
                    'pr_amount' => $p->pivot->pr_amount ?? 0,
                ];
            });
            // Añadir también líneas distribuidas pendientes (ocp) para que aparezcan en el selector
            $lineasDistribuidasJson = DB::table('ordencompra_producto as ocp')
                ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
                ->leftJoin('productos as p','p.id','=','ocp.producto_id')
                ->whereNull('ocp.deleted_at')
                ->where('ocp.requisicion_id', $req->id)
                ->whereNull('ocp.orden_compras_id')
                ->select('ocp.id as ocp_id','ocp.producto_id','ocp.total as cantidad','p.name_produc','p.unit_produc','prov.prov_name')
                ->get()
                ->map(function($row){
                    return [
                        'ocp_id' => $row->ocp_id,
                        'producto_id' => $row->producto_id,
                        'cantidad' => $row->cantidad,
                        'name_produc' => $row->name_produc,
                        'unit_produc' => $row->unit_produc,
                        'prov_name' => $row->prov_name,
                    ];
                });
            $salidasIds = DB::table('entrega')
                ->where('requisicion_id', $req->id)
                ->whereNull('deleted_at')
                ->pluck('producto_id')
                ->unique()
                ->values();
        @endphp
        <script type="application/json" id="req-products-{{ $req->id }}">{!! $prodsData->toJson() !!}</script>
        <script type="application/json" id="req-products-ocp-{{ $req->id }}">{!! $lineasDistribuidasJson->toJson() !!}</script>
        <script type="application/json" id="req-products-out-{{ $req->id }}">{!! $salidasIds->toJson() !!}</script>
    </div>
</div>
@endforeach

<!-- Modal global para sacar de stock (inicialmente oculto) -->
<div id="modal-sacar-stock-global" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
        <div class="px-5 py-3 border-b flex justify-between items-center">
            <h3 class="font-semibold">Sacar productos de stock</h3>
            <button type="button" class="text-gray-600 hover:text-gray-800" id="ss-close">✕</button>
        </div>
        <div class="p-5 space-y-3">
            <input type="hidden" id="ss-requisicion-id" value="">
            <div>
                <label class="block text-sm text-gray-600 mb-1">Producto</label>
                <select id="ss-producto" class="w-full border rounded p-2">
                    <option value="">Seleccione producto</option>
                </select>
                <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3 rounded-lg bg-blue-50 border border-blue-200">
                        <div class="text-[10px] tracking-wide text-blue-700 uppercase">Stock actual</div>
                        <div class="text-2xl font-bold text-blue-900"><span id="ss-stock">0</span></div>
                    </div>
                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-200">
                        <div class="text-[10px] tracking-wide text-amber-700 uppercase">Solicitado</div>
                        <div class="text-2xl font-bold text-amber-900"><span id="ss-req">0</span></div>
                    </div>
                    <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 hidden sm:block">
                        <div class="text-[10px] tracking-wide text-gray-600 uppercase">Unidad</div>
                        <div class="text-xl font-semibold text-gray-800"><span id="ss-unit">—</span></div>
                    </div>
                </div>
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">Cantidad a sacar</label>
                <input type="number" id="ss-cantidad" class="w-full border rounded p-2" min="1" placeholder="Ingrese cantidad">
                <div class="mt-1 text-xs text-gray-500">Máximo permitido: <span id="ss-max">0</span></div>
            </div>
        </div>
        <div class="px-5 py-3 border-t flex justify-end gap-2">
            <button type="button" class="px-4 py-2 border rounded" id="ss-cancel">Cancelar</button>
            <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded" id="ss-save">Guardar</button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function toggleModal(id){
        const modal = document.getElementById(id);
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
        if (modal.classList.contains('hidden')) {
            document.body.style.overflow = 'auto';
        } else {
            document.body.style.overflow = 'hidden';
        }
    }
    document.addEventListener('click', function(event) {
        // Cerrar solo overlays/modales específicos (evitar afectar nav/sidebars u otros elementos 'fixed')
        const el = event.target;
        if (el && el.id && (el.id.startsWith('modal-') || el.id === 'modal-sacar-stock-global')) {
            el.classList.add('hidden');
            el.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    });
    // Paginación cliente para lista
    let listaCurrentPage = 1;
    let listaPageSize = 10;

    function listaGetMatchedRows() {
        return Array.from(document.querySelectorAll('#tablaRequisicionesLista tbody tr'))
            .filter(r => (r.dataset.match ?? '1') !== '0');
    }

    function listaShowPage(page = 1) {
        const rows = listaGetMatchedRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / listaPageSize));
        listaCurrentPage = Math.min(Math.max(1, page), totalPages);
        const start = (listaCurrentPage - 1) * listaPageSize;
        const end = start + listaPageSize;

        // Tabla desktop
        const allRows = Array.from(document.querySelectorAll('#tablaRequisicionesLista tbody tr'));
        allRows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        // Mobile cards
        const cards = Array.from(document.querySelectorAll('#listaMobile .req-card'));
        cards.forEach(c => c.style.display = 'none');
        cards.slice(start, end).forEach(c => c.style.display = '');

        listaRenderPagination(totalPages);
    }

    function listaRenderPagination(totalPages){
        const container = document.getElementById('paginationControlsLista');
        if (!container) return;
        container.innerHTML = '';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (listaCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = listaCurrentPage === 1;
        btnPrev.onclick = () => listaShowPage(listaCurrentPage - 1);
        container.appendChild(btnPrev);

        const start = Math.max(1, listaCurrentPage - 2);
        const end = Math.min(totalPages, listaCurrentPage + 2);
        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1 rounded text-sm ' + (p === listaCurrentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
            btn.onclick = () => listaShowPage(p);
            container.appendChild(btn);
        }

        const btnNext = document.createElement('button');
        btnNext.textContent = 'Siguiente';
        btnNext.className = 'px-3 py-1 border rounded text-sm ' + (listaCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnNext.disabled = listaCurrentPage === totalPages;
        btnNext.onclick = () => listaShowPage(listaCurrentPage + 1);
        container.appendChild(btnNext);
    }

    document.addEventListener('DOMContentLoaded', function(){
        // Inicializar matching y pageSize
        document.querySelectorAll('#tablaRequisicionesLista tbody tr, #listaMobile .req-card').forEach(el => el.dataset.match = '1');

        const selLista = document.getElementById('pageSizeSelectLista');
        if (selLista) {
            listaPageSize = parseInt(selLista.value, 10) || 10;
            selLista.addEventListener('change', (e) => {
                listaPageSize = parseInt(e.target.value, 10) || 10;
                listaShowPage(1);
            });
        }

        // Búsqueda
        const search = document.getElementById('busquedaLista');
        if (search) {
            search.addEventListener('keyup', () => {
                const filtro = (search.value || '').toLowerCase();
                document.querySelectorAll('#tablaRequisicionesLista tbody tr').forEach(r => {
                    r.dataset.match = r.textContent.toLowerCase().includes(filtro) ? '1' : '0';
                });
                document.querySelectorAll('#listaMobile .req-card').forEach(c => {
                    c.dataset.match = c.textContent.toLowerCase().includes(filtro) ? '1' : '0';
                });
                listaShowPage(1);
            });
        }

        // Inicial show
        listaShowPage(1);
    });

    async function completarReq(id){
        try{
            const resp = await fetch(`{{ route('recepciones.completarSiListo') }}`, {
                method:'POST',
                headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
                body: JSON.stringify({ requisicion_id: id })
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'Error al completar');
            if (data.ok) {
                Swal.fire({icon:'success', title:'Completada', text:'La requisición fue marcada como completa (estatus 10).'}).then(()=> location.reload());
            } else {
                Swal.fire({icon:'info', title:'Pendiente', text: data.message || 'Aún falta por recibir.'});
            }
        } catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
        }
    }

    // Modal global para sacar de stock
    document.addEventListener('DOMContentLoaded', function(){
        const modalId = 'modal-sacar-stock-global';
        let cont = document.getElementById(modalId);
        if (!cont){
            // Crear si no existe con el nuevo diseño
            cont = document.createElement('div');
            cont.id = modalId;
            cont.className = 'fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4';
            cont.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md">
                    <div class="px-5 py-3 border-b flex justify-between items-center">
                        <h3 class="font-semibold">Sacar productos de stock</h3>
                        <button type="button" class="text-gray-600 hover:text-gray-800" id="ss-close">✕</button>
                    </div>
                    <div class="p-5 space-y-3">
                        <input type="hidden" id="ss-requisicion-id" value="">
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Producto</label>
                            <select id="ss-producto" class="w-full border rounded p-2">
                                <option value="">Seleccione producto</option>
                            </select>
                            <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div class="p-3 rounded-lg bg-blue-50 border border-blue-200">
                                    <div class="text-[10px] tracking-wide text-blue-700 uppercase">Stock actual</div>
                                    <div class="text-2xl font-bold text-blue-900"><span id="ss-stock">0</span></div>
                                </div>
                                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200">
                                    <div class="text-[10px] tracking-wide text-amber-700 uppercase">Solicitado</div>
                                    <div class="text-2xl font-bold text-amber-900"><span id="ss-req">0</span></div>
                                </div>
                                <div class="p-3 rounded-lg bg-gray-50 border border-gray-200 hidden sm:block">
                                    <div class="text-[10px] tracking-wide text-gray-600 uppercase">Unidad</div>
                                    <div class="text-xl font-semibold text-gray-800"><span id="ss-unit">—</span></div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-600 mb-1">Cantidad a sacar</label>
                            <input type="number" id="ss-cantidad" class="w-full border rounded p-2" min="1" placeholder="Ingrese cantidad">
                            <div class="mt-1 text-xs text-gray-500">Máximo permitido: <span id="ss-max">0</span></div>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t flex justify-end gap-2">
                        <button type="button" class="px-4 py-2 border rounded" id="ss-cancel">Cancelar</button>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded" id="ss-save">Guardar</button>
                    </div>
                </div>`;
            document.body.appendChild(cont);
        }

        const overlay = cont;
        const sel = cont.querySelector('#ss-producto');
        const stockLbl = cont.querySelector('#ss-stock');
        const reqLbl = cont.querySelector('#ss-req');
        const unitLbl = cont.querySelector('#ss-unit');
        const maxLbl = cont.querySelector('#ss-max');
        const inpCant = cont.querySelector('#ss-cantidad');
        const btnClose = cont.querySelector('#ss-close');
        const btnCancel = cont.querySelector('#ss-cancel');
        const btnSave = cont.querySelector('#ss-save');

        function close(){ overlay.classList.add('hidden'); overlay.classList.remove('flex'); }
        function open(requisicionId){
            cont.querySelector('#ss-requisicion-id').value = requisicionId;
            // Poblar productos desde JSON embebido por requisición
            const jsonEl = document.getElementById(`req-products-${requisicionId}`);
            const outEl = document.getElementById(`req-products-out-${requisicionId}`);
            let items = [];
            let outIds = [];
            if (jsonEl) {
                try { items = JSON.parse(jsonEl.textContent || '[]'); } catch(e) { items = []; }
            }
            if (outEl) {
                try { outIds = JSON.parse(outEl.textContent || '[]'); } catch(e) { outIds = []; }
            }
            sel.innerHTML = '<option value="">Seleccione producto</option>';
            // Poblar únicamente con los productos de la requisición (ignorar líneas distribuidas)
            items.forEach(p => {
                if (outIds.includes(p.id)) return; // ya tiene salida, no permitir otra
                const opt = document.createElement('option');
                opt.value = `prod_${p.id}`;
                opt.dataset.productoId = String(p.id);
                opt.textContent = `${p.name_produc} - Cant: ${p.pr_amount ?? 0} ${p.unit_produc ? '(' + p.unit_produc + ')' : ''}`;
                opt.dataset.stock = p.stock_produc ?? 0;
                opt.dataset.req = p.pr_amount ?? 0;
                opt.dataset.unit = p.unit_produc || '';
                sel.appendChild(opt);
            });
            stockLbl.textContent = '0';
            reqLbl.textContent = '0';
            if (unitLbl) unitLbl.textContent = '—';
            if (maxLbl) maxLbl.textContent = '0';
            inpCant.value='';
            overlay.classList.remove('hidden'); overlay.classList.add('flex');
        }

        window.openSalidaStockModal = open;

        btnClose?.addEventListener('click', close);
        btnCancel?.addEventListener('click', close);
        overlay.addEventListener('click', (e)=>{ if (e.target === overlay) close(); });
        sel.addEventListener('change', function(){
            const opt = this.options[this.selectedIndex];
            const stock = parseInt(opt?.dataset?.stock||'0',10) || 0;
            const req = parseInt(opt?.dataset?.req||'0',10) || 0;
            stockLbl.textContent = String(stock);
            reqLbl.textContent = String(req);
            if (unitLbl) unitLbl.textContent = opt?.dataset?.unit || '—';
            // establecer máximo según tipo: para ocp y también para producto usamos la cantidad requerida; si hay stock disponible limitar por stock
            if (opt && opt.value && opt.value.startsWith('ocp_')) {
                // línea distribuida: máximo es la cantidad de la línea
                inpCant.max = req > 0 ? String(req) : '';
                if (maxLbl) maxLbl.textContent = String(req);
            } else {
                // producto normal: si hay stock disponible, limitar por stock; de lo contrario usar la cantidad requerida
                if (stock > 0) {
                    inpCant.max = stock > 0 ? String(stock) : '';
                    if (maxLbl) maxLbl.textContent = String(stock);
                } else {
                    inpCant.max = req > 0 ? String(req) : '';
                    if (maxLbl) maxLbl.textContent = String(req);
                }
            }
        });
        btnSave.addEventListener('click', async function(){
            const requisicionId = parseInt(cont.querySelector('#ss-requisicion-id').value||'0',10);
            const rawVal = sel.value || '';
            if (!rawVal) { Swal.fire({icon:'warning', title:'Datos incompletos', text:'Seleccione producto y cantidad válida'}); return; }

            let productoId = 0;
            let ocpId = null;
            if (rawVal.startsWith('ocp_')) {
                ocpId = parseInt(sel.options[sel.selectedIndex].dataset.ocpId || '0', 10) || null;
                productoId = parseInt(sel.options[sel.selectedIndex].dataset.productoId || '0', 10);
            } else if (rawVal.startsWith('prod_')) {
                productoId = parseInt(sel.options[sel.selectedIndex].dataset.productoId || '0', 10);
            } else {
                productoId = parseInt(rawVal||'0',10);
            }

            const cantidad = parseInt(inpCant.value||'0',10);
            if (!requisicionId || !productoId || !cantidad || cantidad<1){
                Swal.fire({icon:'warning', title:'Datos incompletos', text:'Seleccione producto y cantidad válida'}); return;
            }
            try {
                const body = { requisicion_id: requisicionId, producto_id: productoId, cantidad };
                if (ocpId) body.ocp_id = ocpId; // enviar ocp_id para que backend lo use si está implementado

                const resp = await fetch(`{{ route('recepciones.storeSalidaStockEnEntrega') }}`, {
                    method:'POST',
                    headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await resp.json();
                if (!resp.ok) throw new Error(data.message || 'Error al guardar');
                close();
                Swal.fire({icon:'success', title:'Listo', text:'Salida de stock registrada.'}).then(()=> location.reload());
            } catch(e){
                Swal.fire({icon:'error', title:'Error', text:e.message});
            }
        });

        document.querySelectorAll('[data-btn-sacar-stock]').forEach(btn => {
            btn.addEventListener('click', function(){
                const reqId = this.getAttribute('data-requisicion-id');
                window.openSalidaStockModal(reqId);
            });
        });
    });
</script>
@endsection