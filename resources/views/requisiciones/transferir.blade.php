@extends('layouts.app')

@section('title', 'Transferir Titularidad')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Transferir Titularidad de Requisiciones</h1>

    <div class="mb-6 flex justify-between items-center">
        <input type="text" id="busquedaTransferir" placeholder="Buscar requisición..."
            class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
    </div>

    @if($requisiciones->isEmpty())
    <p class="text-gray-500 text-center py-6">No hay requisiciones registradas.</p>
    @else
    <div class="overflow-x-auto">
        <table id="tablaTransferir" class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
            <thead class="bg-blue-50 text-gray-700 uppercase text-sm font-semibold">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Solicitante</th>
                    <th class="p-3 text-left">Prioridad</th>
                    <th class="p-3 text-left">Productos</th>
                    <th class="p-3 text-center">Estatus</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach($requisiciones as $req)
                @php
                    $hist = $req->estatusHistorial;
                    $ultimoActivo = ($hist && $hist->count()) ? ($hist->firstWhere('estatus', 1) ?? $hist->sortByDesc('created_at')->first()) : null;
                    $estatusActualId = $ultimoActivo->estatus_id ?? null;
                    $estatusActualNombre = $ultimoActivo && $ultimoActivo->estatusRelation ? $ultimoActivo->estatusRelation->status_name : 'Pendiente';
                @endphp
                <tr class="border-b hover:bg-gray-50 transition" data-req-id="{{ $req->id }}" @if($req->name_user) data-owner-name="{{ $req->name_user }}" @endif>
                    <td class="p-3">#{{ $req->id }}</td>
                    <td class="p-3">{{ $req->created_at->format('d/m/Y') }}</td>
                    <td class="p-3">{{ $req->name_user ?? 'Desconocido' }}</td>
                    <td class="p-3">
                        <span class="px-3 py-1 text-xs font-medium rounded-full text-white @if($req->prioridad_requisicion=='alta') bg-red-600 @elseif($req->prioridad_requisicion=='media') bg-yellow-500 @else bg-green-600 @endif">{{ ucfirst($req->prioridad_requisicion) }}</span>
                    </td>
                    <td class="p-3">
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            @foreach($req->productos as $prod)
                            <li>{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="p-3 text-center">
                        <span class="status-badge px-3 py-1 text-xs font-semibold rounded-full text-white bg-blue-600">{{ $estatusActualNombre }}</span>
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 items-center">
                            <button onclick="toggleModal('modal-{{ $req->id }}')" class="btn-open-ver bg-blue-600 hover:bg-blue-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Ver requisición">
                                <i class="fas fa-eye"></i>
                            </button>
                            <!-- Transfer button: abrir modal de transferencia directamente -->
                            <button onclick="openTransferModal({{ $req->id }})" class="bg-amber-500 hover:bg-amber-600 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Transferir titularidad">
                                <i class="fas fa-exchange-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>

                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<!-- Paginación similar a todas.blade.php -->
@if(!$requisiciones->isEmpty())
<div class="flex items-center justify-between mt-4" id="paginationBarTransferir">
    <div class="text-sm text-gray-600">
        Mostrar
        <select id="pageSizeSelectTransferir" class="border rounded px-2 py-1">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
        </select>
        por página
    </div>
    <div class="flex flex-wrap gap-1" id="paginationControlsTransferir"></div>
</div>
@endif

@section('scripts')
<script>
function toggleModal(id){
    const modal = document.getElementById(id);
    if(!modal) return;
    if(modal.classList.contains('hidden')){
        modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden';
    } else {
        modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto';
    }
}

// Transfer modal helpers
function openTransferModal(id){
    const modal = document.getElementById(`transfer-modal-${id}`);
    if(!modal) return;

    // populate current owner from server-rendered span or row data attributes
    const currentSpan = document.getElementById(`current-owner-${id}`);
    if (!currentSpan || !currentSpan.textContent.trim()){
        const row = document.querySelector(`tr[data-req-id="${id}"]`);
        if (row && row.dataset && row.dataset.ownerName) {
            if (currentSpan) currentSpan.textContent = row.dataset.ownerName;
        }
    }

    // reset select to a placeholder state; actual user loading can be implemented later
    const sel = document.getElementById(`new-owner-select-${id}`);
    const confirmBtn = document.getElementById(`confirm-transfer-${id}`);
    if (sel) { sel.innerHTML = '<option value="">-- Cargar usuarios --</option>'; sel.disabled = false; }
    if (confirmBtn) confirmBtn.disabled = false;

    // open modal
    modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden';
}

function closeTransferModal(id){
    const modal = document.getElementById(`transfer-modal-${id}`);
    if(!modal) return;
    modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto';
}

function submitTransfer(id){
    const sel = document.getElementById(`new-owner-select-${id}`);
    if(!sel) return Swal.fire({ icon: 'error', title: 'Error', text: 'Selector no encontrado' });
    const newUserId = sel.value;
    if(!newUserId) return Swal.fire({ icon: 'warning', title: 'Selecciona un usuario', text: 'Por favor selecciona un usuario destino.' });

    Swal.fire({
        title: 'Confirmar transferencia',
        text: `¿Deseas transferir la requisición #${id}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, transferir',
        cancelButtonText: 'Cancelar'
    }).then(res => {
        if(!res.isConfirmed) return;
        Swal.fire({ title: 'Procesando...', html: 'Realizando transferencia', allowOutsideClick:false, didOpen: ()=>Swal.showLoading() });
        fetch(`/requisiciones/${id}/transferir`, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ new_user_id: newUserId })
        }).then(async r => {
            const json = await r.json().catch(()=>({ success:false, message:'Respuesta inválida' }));
            Swal.close();
            if(r.ok && json.success){
                closeTransferModal(id);
                Swal.fire({ icon:'success', title:'Transferido', text: json.message || 'Transferencia exitosa' }).then(()=>location.reload());
            } else {
                Swal.fire({ icon:'error', title:'Error', text: json.message || 'No se pudo completar la transferencia' });
            }
        }).catch(()=>{ Swal.close(); Swal.fire({ icon:'error', title:'Error', text:'Error de comunicación' }); });
    });
}

// Paginación y búsqueda igual que en todas.blade.php
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('busquedaTransferir');
    const pageSizeSel = document.getElementById('pageSizeSelectTransferir');
    let currentPage = 1;
    let pageSize = parseInt(pageSizeSel?.value || '10', 10) || 10;

    function getMatchedItems(){
        return Array.from(document.querySelectorAll('#tablaTransferir tbody tr')).filter(el => (el.dataset.match ?? '1') !== '0');
    }

    function showPage(page = 1){
        const items = getMatchedItems();
        const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
        currentPage = Math.min(Math.max(1, page), totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        // hide all
        document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => el.style.display = 'none');
        // show slice
        items.slice(start, end).forEach(el => el.style.display = '');

        renderPagination(totalPages);
    }

    function renderPagination(totalPages){
        const container = document.getElementById('paginationControlsTransferir');
        if(!container) return;
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
    document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => el.dataset.match = '1');
    if (pageSizeSel) pageSizeSel.addEventListener('change', (e)=>{ pageSize = parseInt(e.target.value,10)||10; showPage(1); });
    if (input) input.addEventListener('keyup', function(){
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => {
            el.dataset.match = el.textContent.toLowerCase().includes(filtro) ? '1' : '0';
        });
        showPage(1);
    });

    showPage(1);
});

// Reuse transfer modal functions already defined below (openTransferModal, closeTransferModal, submitTransfer)
</script>
@endsection

<!-- Render modals AFTER table to keep table structure valid -->
@foreach($requisiciones as $req)
    <!-- Modal Ver -->
    <div id="modal-{{ $req->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
         <!-- Fondo -->
         <div class="absolute inset-0 bg-black/50" onclick="toggleModal('modal-{{ $req->id }}')"></div>

         <!-- Contenido -->
         <div class="relative w-full max-w-4xl">
             <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-8 relative">

                 <!-- Botón cerrar -->
                 <button onclick="toggleModal('modal-{{ $req->id }}')"
                     class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl"
                     aria-label="Cerrar modal">&times;</button>

                 <!-- Título -->
                 <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">
                     Requisición #{{ $req->id }}
                 </h2>

                 <!-- Información general -->
                 <section class="mb-8">
                     <h3 class="text-lg font-semibold text-gray-700 mb-3">Información General</h3>
                     <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-gray-50 rounded-lg p-4">
                         <div><span class="font-medium">Solicitante:</span> {{ $req->name_user ?? $req->user->name ??
                             'Desconocido' }}</div>
                         <div><span class="font-medium">Fecha:</span> {{ $req->created_at->format('d/m/Y') }}</div>
                         <div><span class="font-medium">Prioridad:</span> {{ ucfirst($req->prioridad_requisicion) }}
                         </div>
                         <div><span class="font-medium">Recobrable:</span> {{ $req->Recobrable }}</div>
                         @php
                             $hist = $req->estatusHistorial;
                             $ultimoActivo = ($hist && $hist->count()) ? ($hist->firstWhere('estatus', 1) ?? $hist->sortByDesc('created_at')->first()) : null;
                             $estatusActualId = $ultimoActivo->estatus_id ?? null;
                             $estatusActualNombre = $ultimoActivo && $ultimoActivo->estatusRelation ? $ultimoActivo->estatusRelation->status_name : 'Pendiente';
                             $colorActual = 'bg-gray-500';
                             switch($estatusActualId) {
                                 case 1: $colorActual = 'bg-blue-600'; break;
                                 case 2: case 3: case 4: $colorActual = 'bg-yellow-500'; break;
                                 case 5: $colorActual = 'bg-purple-600'; break;
                                 case 6: case 9: $colorActual = 'bg-red-600'; break;
                                 case 7: case 8: $colorActual = 'bg-indigo-600'; break;
                                 case 10: $colorActual = 'bg-green-600'; break;
                                 case 11: $colorActual = 'bg-orange-500'; break;
                             }
                             // Descripciones iguales a la tabla (IDs 1-13)
                             $descripcionesEstatusModal = [
                                 1 => 'Requisición creada por el solicitante.',
                                 2 => 'Revisado por compras; en espera de aprobación.',
                                 3 => 'Aprobado por Gerencia; pasa a financiera.',
                                 4 => 'Aprobado por Financiera; listo para generar OC.',
                                 5 => 'Orden de compra generada.',
                                 6 => 'Requisición cancelada.',
                                 7 => 'Material recibido en bodega.',
                                 8 => 'Material recibido por coordinador.',
                                 9 => 'Rechazado por financiera.',
                                 10 => 'Proceso completado.',
                                 11 => 'Corregir la requisición.',
                                 12 => 'Solo se ha entregado una parte de la requisición.',
                                 13 => 'Rechazado por gerencia.',
                             ];
                             $tooltipModal = $descripcionesEstatusModal[$estatusActualId] ?? 'Pendiente por gestión.';
                         @endphp
                         <div>
                             <span class="font-medium">Estatus actual:</span>
                             <span class="ml-2 px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorActual }} cursor-help" title="{{ $tooltipModal }}">{{ $estatusActualNombre }}</span>
                         </div>
                         @php
                             $registroComentarioModal = null;
                             if ($req->estatusHistorial && $req->estatusHistorial->count()) {
                                 $registroComentarioModal = $req->estatusHistorial->whereIn('estatus_id', [11, 9, 13])->sortByDesc('created_at')->first();
                             }
                             $boxClasses = '';
                             if ($registroComentarioModal) {
                                 $boxClasses = in_array($registroComentarioModal->estatus_id, [9,13])
                                     ? 'bg-red-50 border border-red-200 text-red-800'
                                     : 'bg-amber-50 border border-amber-200 text-amber-800';
                             }
                         @endphp
                         @if(!empty($registroComentarioModal?->comentario))
                             <div class="col-span-1 md:col-span-2 mt-2 rounded-lg p-3 text-sm {{ $boxClasses }}">
                                 <strong>Motivo:</strong> {{ $registroComentarioModal->comentario }}
                             </div>
                         @endif
                     </div>
                 </section>

                 <!-- Detalle y Justificación lado a lado -->
                 <section class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                     <!-- Detalle -->
                     <div>
                         <h3 class="text-lg font-semibold text-gray-700 mb-3">Detalle</h3>
                         <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                             {{ $req->detail_requisicion }}
                         </div>
                     </div>

                     <!-- Justificación -->
                     <div>
                         <h3 class="text-lg font-semibold text-gray-700 mb-3">Justificación</h3>
                         <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">
                             {{ $req->justify_requisicion }}
                         </div>
                     </div>
                 </section>

                <section class="mb-6">
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
                                        <td class="px-4 py-3 text-center font-semibold">{{ number_format($cantidad, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right">{{ number_format($precio, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">{{ number_format($totalProd, 2, ',', '.') }}</td>
                                        <td class="px-4 py-3">
                                            @if($distribucion->count() > 0)
                                                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                                    @foreach($distribucion as $centro)
                                                        <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded">
                                                            <span class="font-medium text-sm truncate">{{ $centro->name_centro }}</span>
                                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">{{ $centro->amount }}</span>
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
                                    <td class="px-4 py-3 text-right font-bold">{{ number_format($totalGeneral, 2, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                    <a href="{{ route('requisiciones.estatus', ['requisicion' => $req->id]) }}" class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">Ver Estatus</a>
                    <button class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600" onclick="openTransferModal({{ $req->id }})">Transferir titularidad</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Transferir por requisición -->
    <div id="transfer-modal-{{ $req->id }}" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="closeTransferModal({{ $req->id }})"></div>
        <div class="relative w-full max-w-2xl bg-white rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Transferir titularidad - Requisición #{{ $req->id }}</h3>
            <p class="mb-3"><strong>Propietario actual:</strong> <span id="current-owner-{{ $req->id }}">{{ $req->name_user }}</span></p>

            <label class="block text-sm font-medium mb-2">Selecciona nuevo propietario</label>
            <select id="new-owner-select-{{ $req->id }}" class="w-full border rounded px-3 py-2">
                <option value="">-- Seleccionar usuario --</option>
            </select>

            <div class="flex justify-end gap-3 mt-4">
                <button class="px-4 py-2 border rounded" onclick="closeTransferModal({{ $req->id }})">Cancelar</button>
                <button class="px-4 py-2 bg-green-600 text-white rounded" id="confirm-transfer-{{ $req->id }}" onclick="submitTransfer({{ $req->id }})">Confirmar Transferencia</button>
            </div>
        </div>
    </div>
@endforeach
