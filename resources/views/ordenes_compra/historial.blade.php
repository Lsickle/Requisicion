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
                    <th class="p-3 text-left">Orden</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Requisición</th>
                    <th class="p-3 text-left">Proveedor</th>
                    <th class="p-3 text-left">Método</th>
                    <th class="p-3 text-left">Plazo</th>
                    <th class="p-3 text-left">Productos</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach($ordenes as $oc)
                @php
                    $proveedor = optional($oc->ordencompraProductos->first())->proveedor;
                @endphp
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3">{{ $oc->order_oc ?? ('OC-' . $oc->id) }}</td>
                    <td class="p-3">{{ optional($oc->created_at)->format('d/m/Y') }}</td>
                    <td class="p-3">#{{ $oc->requisicion->id ?? '-' }}</td>
                    <td class="p-3">{{ $proveedor->prov_name ?? '—' }}</td>
                    <td class="p-3">{{ $oc->methods_oc ?? '—' }}</td>
                    <td class="p-3">{{ $oc->plazo_oc ?? '—' }}</td>
                    <td class="p-3">
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            @foreach($oc->ordencompraProductos as $linea)
                                @if($linea->producto)
                                    <li>{{ $linea->producto->name_produc }} ({{ (int)$linea->total }})</li>
                                @endif
                            @endforeach
                        </ul>
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 flex-wrap">
                            <button onclick="toggleModal('modal-{{ $oc->id }}')"
                                class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-1">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <a href="{{ route('ordenes_compra.pdf', $oc->id) }}"
                                class="hidden"></a>
                            <a href="{{ route('ordenes_compra.download', $oc->requisicion->id ?? ($oc->requisicion_id ?? 0)) }}"
                                class="bg-green-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-green-700 transition flex items-center gap-1">
                                <i class="fas fa-file-pdf"></i> PDF
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

    <!-- Modales -->
    @foreach($ordenes as $oc)
    <div id="modal-{{ $oc->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="toggleModal('modal-{{ $oc->id }}')"></div>
        <div class="relative w-full max-w-4xl">
            <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-8 relative">
                <button onclick="toggleModal('modal-{{ $oc->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl" aria-label="Cerrar modal">&times;</button>
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
                        <div class="max-h-80 overflow-y-auto">
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

                <section class="mt-6">
                    <a href="{{ route('ordenes_compra.pdf', $oc->id) }}" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition">Descargar PDF</a>
                </section>
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
            document.body.style.overflow = 'hidden';
        } else {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-"]').forEach(m => {
                if (!m.classList.contains('hidden')) m.classList.add('hidden');
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
    });
</script>
@endsection