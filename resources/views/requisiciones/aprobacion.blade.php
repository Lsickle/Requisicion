@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Panel de Aprobación de Requisiciones</h1>
                <div>
                    <a href="{{ route('requisiciones.menu') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow transition">
                        ← Volver
                    </a>
                </div>
            </div>

            <!-- Búsqueda -->
            <div class="mb-4">
                <input type="text" id="busquedaAprob" placeholder="Buscar requisición..."
                    class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
            </div>

            <!-- Contenedor scrollable -->
            <div class="flex-1 overflow-y-auto">
                <!-- Tabla en escritorio -->
                <div class="bg-white rounded-lg shadow overflow-x-auto hidden md:block">
                    <table class="min-w-full table-auto border-collapse">
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
                            <tr class="aprob-item border-b hover:bg-gray-50 transition" data-id="{{ $req->id }}">
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
                                        class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">Ver</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No hay requisiciones para sus
                                    operaciones</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil -->
                <div class="md:hidden space-y-4">
                    @forelse($requisicionesFiltradas as $req)
                    <div class="aprob-item bg-white rounded-lg shadow p-4" data-id="{{ $req->id }}">
                        <h2 class="font-bold text-lg mb-2">#{{ $req->id }} - {{ $req->detail_requisicion }}</h2>
                        <p><strong>Solicitante:</strong> {{ $req->name_user }}</p>
                        <p><strong>Prioridad:</strong> {{ ucfirst($req->prioridad_requisicion) }}</p>
                        <div class="mt-3">
                            <button onclick="toggleModal('modal-{{ $req->id }}')"
                                class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">Ver</button>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500">No hay requisiciones para sus operaciones</p>
                    @endforelse
                </div>

                <!-- Paginación inferior -->
                <div class="flex items-center justify-between mt-4" id="paginationBarAprob">
                    <div class="text-sm text-gray-600">
                        Mostrar
                        <select id="pageSizeSelectAprob" class="border rounded px-2 py-1">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        por página
                        <span id="paginationInfoAprob" class="ml-4 text-sm text-gray-600"></span>
                    </div>
                    <div class="flex flex-wrap gap-1" id="paginationControlsAprob"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    @foreach($requisicionesFiltradas as $req)
    <div id="modal-{{ $req->id }}"
        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
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
                        <p><strong>Prioridad:</strong> {{ ucfirst($req->prioridad_requisicion) }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Detalles de la Requisición</h3>
                        <p><strong>Detalle:</strong> {{ $req->detail_requisicion }}</p>
                        <p><strong>Justificación:</strong> {{ $req->justify_requisicion }}</p>
                    </div>
                </div>


                <h3 class="text-xl font-semibold mt-6 mb-3">Productos</h3>
                <div class="overflow-x-auto">
                    @php $totalGeneral = 0; @endphp
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">Producto</th>
                                <th class="px-4 py-2 text-center">Cantidad</th>
                                <th class="px-4 py-2 text-right">Precio</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-left">Distribución por Centros</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($req->productos as $prod)
                            @php
                            $precio = (float) ($prod->price_produc ?? 0);
                            $cantidad = (float) ($prod->pivot->pr_amount ?? 0);
                            $totalProd = round($precio * $cantidad, 2);
                            $totalGeneral = round($totalGeneral + $totalProd, 2);
                            $distribucion = DB::table('centro_producto')
                            ->where('requisicion_id', $req->id)
                            ->where('producto_id', $prod->id)
                            ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                            ->select('centro.name_centro', 'centro_producto.amount')
                            ->get();
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3">{{ $prod->name_produc }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ number_format($cantidad, 0, ',', '.')
                                    }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($precio, 2, ',', '.') }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ number_format($totalProd, 2, ',', '.')
                                    }}</td>
                                <td class="px-4 py-3">
                                    @if($distribucion->count() > 0)
                                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                        @foreach($distribucion as $centro)
                                        <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded">
                                            <span class="font-medium text-sm truncate">{{ $centro->name_centro }}</span>
                                            <span
                                                class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">{{
                                                $centro->amount }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-right font-semibold" colspan="3">Total general</td>
                                <td class="px-4 py-3 text-right font-bold">{{ number_format($totalGeneral, 2, ',', '.')
                                    }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Botones Aprobar/Rechazar -->
            <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                @php
                $estatusActual = $req->ultimoEstatus->estatus_id ?? null;
                $opNorm = mb_strtolower(trim($req->operacion_user ?? ''), 'UTF-8');
                $opNorm = strtr($opNorm, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
                $especial = in_array($opNorm, ['tecnologia','compras']);
                $estatusAprobar = null;
                if ($estatusActual === 1) {
                $estatusAprobar = $especial ? 3 : 2; // salto directo si operación especial
                } elseif ($estatusActual === 2) {
                $estatusAprobar = 3;
                } elseif ($estatusActual === 3) {
                $estatusAprobar = 4;
                }
                $estatusRechazar = 9; // siempre se envía como 9 y la lógica del backend decide la transición final
                @endphp
                @if($estatusAprobar)
                <button class="status-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusAprobar }}" data-action="aprobar">Aprobar</button>
                @endif
                <button class="status-btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusRechazar }}"
                    data-action="rechazar">Rechazar</button>
            </div>
        </div>
    </div>
    @endforeach

    @endsection

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function toggleModal(id){
    const modal = document.getElementById(id);
    if(modal.classList.contains('hidden')){
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    } else {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }
}

// Aprobar / Rechazar
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const requisicionId = this.dataset.id;
        const estatusId = parseInt(this.dataset.estatus);
        const accion = this.dataset.action;

        if (accion === "rechazar") {
            // Comentario opcional para todos los roles
            Swal.fire({
                title: "Motivo de rechazo (opcional)",
                input: "textarea",
                inputPlaceholder: "Escribe el motivo...",
                inputAttributes: { 'aria-label': 'Motivo de rechazo' },
                showCancelButton: true,
                confirmButtonText: "Rechazar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#dc2626",
                cancelButtonColor: "#6b7280"
            }).then((result) => {
                if (!result.isConfirmed) return;
                const comentario = (result.value || '').trim();
                if (!comentario) {
                    Swal.fire({
                        title: 'Enviar rechazo sin comentario',
                        text: '¿Deseas continuar sin comentario?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, rechazar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280'
                    }).then((r2) => {
                        if (r2.isConfirmed) {
                            confirmarCambioEstatus(requisicionId, estatusId, null);
                        }
                    });
                } else {
                    confirmarCambioEstatus(requisicionId, estatusId, comentario);
                }
            });
        } else {
            Swal.fire({
                title: `¿Seguro que deseas aprobar la requisición #${requisicionId}?`,
                icon: "success",
                showCancelButton: true,
                confirmButtonText: "Sí, aprobar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#16a34a",
                cancelButtonColor: "#6b7280"
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmarCambioEstatus(requisicionId, estatusId, null);
                }
            });
        }
    });
});

function confirmarCambioEstatus(requisicionId, estatusId, comentario = null) {
    const data = { estatus_id: estatusId, comentario: comentario };
    Swal.fire({
        title: 'Procesando...',
        html: 'Enviando solicitud, por favor espere.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    // Ruta relativa para evitar http absoluto generado por route() con APP_URL incorrecto
    fetch(`/requisiciones/${requisicionId}/estatus`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => { if (!response.ok) throw new Error('Error en la respuesta del servidor: ' + response.status); return response.json(); })
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message || 'Estatus actualizado', 'success').then(()=> location.reload());
        } else {
            Swal.fire('Error', data.message || 'No se pudo actualizar el estatus', 'error');
        }
    })
    .catch(error => { Swal.fire('Error', 'No se pudo actualizar el estatus: ' + error.message, 'error'); });
}

// Paginación y búsqueda para Aprobación
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('busquedaAprob');
    const pageSizeSel = document.getElementById('pageSizeSelectAprob');
    let currentPage = 1;
    let pageSize = parseInt(pageSizeSel?.value || '10', 10) || 10;

    function getMatchedItems(){
        return Array.from(document.querySelectorAll('.aprob-item')).filter(el => (el.dataset.match ?? '1') !== '0');
    }

    function showPage(page = 1){
        const items = getMatchedItems();
        const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
        currentPage = Math.min(Math.max(1, page), totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        // hide all
        document.querySelectorAll('.aprob-item').forEach(el => el.style.display = 'none');
        // show slice
        items.slice(start, end).forEach(el => el.style.display = '');

        renderPagination(totalPages);
        const info = document.getElementById('paginationInfoAprob');
        if (info) {
            const total = items.length;
            const showing = Math.min(end, total);
            info.textContent = `Mostrando ${showing} de ${total}`;
        }
    }

    function renderPagination(totalPages){
        const container = document.getElementById('paginationControlsAprob');
        container.innerHTML = '';
        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, currentPage + 2);

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = currentPage === 1;
        btnPrev.onclick = () => showPage(currentPage - 1);
        container.appendChild(btnPrev);

        for (let p = start; p <= end; p++){
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1 rounded text-sm ' + (p === currentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
            btn.onclick = () => showPage(p);
            container.appendChild(btn);
        }

        const btnNext = document.createElement('button');
        btnNext.textContent = 'Siguiente';
        btnNext.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnNext.disabled = currentPage === totalPages;
        btnNext.onclick = () => showPage(currentPage + 1);
        container.appendChild(btnNext);
    }

    // initialize
    document.querySelectorAll('.aprob-item').forEach(el => el.dataset.match = '1');
    if (pageSizeSel) pageSizeSel.addEventListener('change', (e)=>{ pageSize = parseInt(e.target.value,10)||10; showPage(1); });
    if (input) input.addEventListener('keyup', function(){
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('.aprob-item').forEach(el => {
            el.dataset.match = el.textContent.toLowerCase().includes(filtro) ? '1' : '0';
        });
        showPage(1);
    });

    showPage(1);
});
    </script>
    @endsection