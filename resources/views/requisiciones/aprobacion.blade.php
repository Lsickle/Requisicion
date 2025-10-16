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
    @php
        // Detectar si el usuario pertenece a Área de compras o es Admin requisicion
        $rolesRaw = session('user_roles') ?? (session('user.roles') ?? []);
        $rolesNorm = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) { $rolesNorm[] = mb_strtolower($r, 'UTF-8'); }
                elseif (is_array($r) && isset($r['roles'])) { $rolesNorm[] = mb_strtolower($r['roles'], 'UTF-8'); }
                elseif (is_object($r) && isset($r->roles)) { $rolesNorm[] = mb_strtolower($r->roles, 'UTF-8'); }
            }
        }
        $singleRole = session('user.role') ?? null;
        $singleRoleNorm = $singleRole ? mb_strtolower($singleRole, 'UTF-8') : null;
        $isComprasOrAdmin = in_array('area de compras', $rolesNorm, true) || in_array('admin requisicion', $rolesNorm, true) || $singleRoleNorm === 'admin requisicion';
        $estatusActual = $req->ultimoEstatus->estatus_id ?? null;
    @endphp
    <div id="modal-{{ $req->id }}" data-estatus-actual="{{ $estatusActual }}"
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
                                <th class="px-4 py-2 text-left">Proveedor</th>
                                <th class="px-4 py-2 text-right">Precio</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-left">Distribución por Centros</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($req->productos as $prod)
                            @php
                                $cantidad = (float) ($prod->pivot->pr_amount ?? 0);
                                // traer proveedores y precios disponibles
                                $provList = collect();
                                try {
                                    $provList = DB::table('productoxproveedor as pxp')
                                        ->join('proveedores as prov','pxp.proveedor_id','=','prov.id')
                                        ->where('pxp.producto_id', $prod->id)
                                        ->whereNull('pxp.deleted_at')
                                        ->select('prov.id','prov.prov_name','pxp.price_produc','pxp.moneda')
                                        ->orderBy('prov.prov_name')
                                        ->get();
                                } catch (\Throwable $e) { $provList = collect(); }

                                // detectar proveedor ya seleccionado (si el backend lo guardó en pivot)
                                $selProvId = $prod->pivot->proveedor_id ?? $prod->pivot->prov_id ?? $prod->proveedor_id ?? null;
                                $selPrice = $prod->pivot->price_produc ?? $prod->pivot->price ?? $prod->price_produc ?? 0;

                                $totalProd = round(((float)$selPrice ?: 0) * $cantidad, 2);
                                $totalGeneral = round($totalGeneral + $totalProd, 2);
                                $distribucion = DB::table('centro_producto')
                                    ->where('requisicion_id', $req->id)
                                    ->where('producto_id', $prod->id)
                                    ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                    ->select('centro.name_centro', 'centro_producto.amount')
                                    ->get();
                            @endphp
                            <tr class="align-top" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}">
                                <td class="px-4 py-3">{{ $prod->name_produc }}</td>
                                <td class="px-4 py-3 text-center font-semibold">{{ number_format($cantidad, 0, ',', '.') }}</td>
                                <td class="px-4 py-3">
                                    @if($isComprasOrAdmin && (int)$estatusActual === 1)
                                        @if($provList->count() === 1)
                                            @php $only = $provList->first(); @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="text-sm font-medium">{{ $only->prov_name }}</div>
                                                <div class="text-sm text-gray-500">({{ number_format($only->price_produc ?? $only->price_produc ?? 0, 2, ',', '.') }})</div>
                                            </div>
                                            <select class="prov-select hidden" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-qty="{{ $cantidad }}">
                                                <option value="{{ $only->id }}" data-price="{{ (float)($only->price_produc ?? 0) }}" selected>{{ $only->prov_name }}</option>
                                            </select>
                                        @else
                                            <div class="flex items-center gap-3">
                                                <div class="flex flex-col items-center gap-1">
                                                    <button type="button" title="Seleccionar proveedor" class="open-prov-modal-btn inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-700" data-providers='@json($provList)' data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-selected="{{ $selProvId ?? '' }}" aria-label="Seleccionar proveedor">
                                                        <i class="fas fa-store"></i>
                                                    </button>
                                                    <div class="selected-prov-name text-xs text-center truncate w-28" id="selprov-{{ $req->id }}-{{ $prod->id }}">{{ $provList->firstWhere('id', $selProvId)->prov_name ?? 'No seleccionado' }}</div>
                                                </div>
                                            </div>

                                            <select class="prov-select hidden" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-qty="{{ $cantidad }}">
                                                <option value="">Seleccione</option>
                                                @foreach($provList as $pv)
                                                    <option value="{{ $pv->id }}" data-price="{{ (float)($pv->price_produc ?? 0) }}" data-currency="{{ $pv->moneda ?? 'COP' }}" {{ $selProvId && $selProvId == $pv->id ? 'selected' : '' }}>{{ $pv->prov_name }} @if(isset($pv->price_produc)) ({{ number_format($pv->price_produc,2) }}) @endif</option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @else
                                        @php
                                            $provName = null;
                                            if ($selProvId) {
                                                $provName = DB::table('proveedores')->where('id', $selProvId)->value('prov_name');
                                            }
                                        @endphp
                                        <div class="text-sm">{{ $provName ?? ($provList->first()->prov_name ?? 'Proveedor') }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right"><span class="precio-cell" id="precio-{{ $req->id }}-{{ $prod->id }}">{{ number_format($selPrice,2,',','.') }}</span></td>
                                <td class="px-4 py-3 text-right font-semibold"><span class="total-cell" id="total-{{ $req->id }}-{{ $prod->id }}">{{ number_format($totalProd,2,',','.') }}</span></td>
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
                                <td class="px-4 py-3 text-right font-semibold" colspan="4">Total general</td>
                                <td class="px-4 py-3 text-right font-bold"><span id="total-general-{{ $req->id }}">{{ number_format($totalGeneral, 2, ',', '.') }}</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Botones Aprobar/Rechazar -->
            <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                @php
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
                $estatusRechazar = 9;
                @endphp
                @if($estatusAprobar)
                <button class="status-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusAprobar }}" data-action="aprobar" data-estatus-actual="{{ $estatusActual }}" data-requires-providers="{{ ($isComprasOrAdmin && (int)$estatusActual === 1) ? '1' : '0' }}">Aprobar</button>
                @endif
                <button class="status-btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusRechazar }}" data-action="rechazar">Rechazar</button>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Modal global para elegir proveedor -->
    <div id="providerChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-60 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-xl max-h-[80vh] overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Seleccionar proveedor</h3>
                <button onclick="closeProviderChoiceModal()" class="text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <div id="providerChoiceList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t flex justify-end gap-2">
                <button onclick="confirmProviderChoice()" class="px-4 py-2 bg-green-600 text-white rounded">Confirmar</button>
                <button onclick="closeProviderChoiceModal()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
            </div>
        </div>
    </div>

    @endsection

    @section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Formato a 2 decimales
    function format2(n){ try{ const v=Number(n||0); return new Intl.NumberFormat('es-CO',{minimumFractionDigits:2,maximumFractionDigits:2}).format(v);}catch(_){const v=Number(n||0); return (Math.round(v*100)/100).toFixed(2);} }

    // Exponer toggleModal en window para onclick inline
    window.toggleModal = function(id){
        try{
            const modal = document.getElementById(id);
            if(!modal) return;
            if(modal.classList.contains('hidden')){
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.overflow = 'hidden';
            } else {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.overflow = 'auto';
            }
        } catch(e){ console.warn('toggleModal error', e); }
    };

    // Recalcular total general de una requisición
    function recomputeTotals(reqId){
        try{
            const totals = Array.from(document.querySelectorAll(`#modal-${reqId} .total-cell`));
            let sum = 0;
            totals.forEach(el => {
                const raw = (el.textContent || '').trim().replace(/\./g,'').replace(',','.');
                const num = Number(raw);
                if(!isNaN(num)) sum += num;
            });
            const span = document.getElementById(`total-general-${reqId}`);
            if(span) span.textContent = format2(sum);
        } catch(e){ console.warn('recomputeTotals', e); }
    }

    // Inicializar selects de proveedores: fijar precio/total inicial y bind change
    function initProviderSelections(){
        document.querySelectorAll('.prov-select').forEach(sel => {
            const reqId = sel.dataset.req;
            const prodId = sel.dataset.prod;
            const qty = Number(sel.dataset.qty || 0);

            const setFromOption = (opt) => {
                const price = Number(opt?.dataset?.price || 0);
                const precioEl = document.getElementById(`precio-${reqId}-${prodId}`);
                const totalEl = document.getElementById(`total-${reqId}-${prodId}`);
                if (precioEl) precioEl.textContent = format2(price);
                if (totalEl) totalEl.textContent = format2(price * qty);
            };

            // aplicar opción seleccionada o la primera con precio
            const cur = sel.options[sel.selectedIndex] || null;
            if (cur && cur.value) setFromOption(cur);
            else {
                const first = Array.from(sel.options).find(o => typeof o.dataset.price !== 'undefined');
                if (first) setFromOption(first);
            }

            sel.addEventListener('change', function(){
                const opt = this.options[this.selectedIndex] || { dataset: { price: 0 } };
                setFromOption(opt);
                recomputeTotals(reqId);
            });
        });

        // Recalcular totales generales iniciales
        document.querySelectorAll('[id^="total-general-"]').forEach(span => {
            const reqId = span.id.replace('total-general-','');
            recomputeTotals(reqId);
        });
    }

    // Confirmar cambio de estatus (envía proveedores si existen)
    function confirmarCambioEstatus(requisicionId, estatusId, comentario = null){
        const data = { estatus_id: estatusId, comentario };
        try{
            const selects = document.querySelectorAll(`#modal-${requisicionId} .prov-select`);
            if (selects.length > 0) {
                const proveedores = Array.from(selects).map(s => ({ producto_id: Number(s.dataset.prod), proveedor_id: Number(s.value || 0), price: Number((s.options[s.selectedIndex] && s.options[s.selectedIndex].dataset.price) || 0) }));
                data.proveedores = proveedores;
            }
        }catch(e){ console.warn('confirmarCambioEstatus gather providers', e); }

        Swal.fire({ title: 'Procesando...', html: 'Enviando solicitud, por favor espere.', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        fetch(`/requisiciones/${requisicionId}/estatus`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(res => {
            if (res.success) Swal.fire('Éxito', res.message || 'Estatus actualizado', 'success').then(()=> location.reload());
            else Swal.fire('Error', res.message || 'No se pudo actualizar el estatus', 'error');
        }).catch(err => { Swal.fire('Error', 'No se pudo actualizar el estatus: ' + err.message, 'error'); });
    }

    // Bindear botones de aprobar/rechazar
    function bindStatusButtons(){
        document.querySelectorAll('.status-btn').forEach(btn => {
            btn.addEventListener('click', function(){
                const requisicionId = this.dataset.id;
                const estatusId = parseInt(this.dataset.estatus);
                const accion = this.dataset.action;
                const requiresProviders = this.dataset.requiresProviders === '1';
                const estatusActual = parseInt(this.dataset.estatusActual || this.dataset.estatus_actual || '0');

                if (accion === 'rechazar'){
                    Swal.fire({ title: 'Motivo de rechazo (opcional)', input: 'textarea', inputPlaceholder: 'Escribe el motivo...', showCancelButton: true, confirmButtonText: 'Rechazar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626' })
                    .then(r => {
                        if (!r.isConfirmed) return;
                        const comentario = (r.value || '').trim();
                        if (!comentario) {
                            Swal.fire({ title: 'Enviar rechazo sin comentario', text: '¿Deseas continuar sin comentario?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, rechazar' })
                            .then(c => { if (c.isConfirmed) confirmarCambioEstatus(requisicionId, estatusId, null); });
                        } else confirmarCambioEstatus(requisicionId, estatusId, comentario);
                    });
                    return;
                }

                // aprobar
                if (requiresProviders && estatusActual === 1) {
                    const selects = Array.from(document.querySelectorAll(`#modal-${requisicionId} .prov-select`));
                    const faltantes = selects.filter(s => !s.value);
                    if (faltantes.length > 0) { Swal.fire({ icon: 'warning', title: 'Seleccione proveedores', text: 'Debe seleccionar un proveedor por cada producto antes de aprobar.'}); return; }
                }

                Swal.fire({ title: `¿Seguro que deseas aprobar la requisición #${requisicionId}?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, aprobar', cancelButtonText: 'Cancelar', confirmButtonColor: '#16a34a' })
                .then(r => { if (r.isConfirmed) confirmarCambioEstatus(requisicionId, estatusId, null); });
            });
        });
    }

    // Paginación y búsqueda
    function setupPaginationAndSearch(){
        const input = document.getElementById('busquedaAprob');
        const pageSizeSel = document.getElementById('pageSizeSelectAprob');
        let currentPage = 1;
        let pageSize = parseInt(pageSizeSel?.value || '10', 10) || 10;

        function getMatched(){ return Array.from(document.querySelectorAll('.aprob-item')).filter(el => (el.dataset.match ?? '1') !== '0'); }
        function render(totalPages){
            const container = document.getElementById('paginationControlsAprob'); container.innerHTML = '';
            const start = Math.max(1, currentPage - 2); const end = Math.min(totalPages, currentPage + 2);
            const btnPrev = document.createElement('button'); btnPrev.textContent = 'Anterior'; btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (currentPage===1? 'opacity-50 cursor-not-allowed':'hover:bg-gray-100'); btnPrev.disabled = currentPage===1; btnPrev.onclick = () => showPage(currentPage-1); container.appendChild(btnPrev);
            for(let p=start;p<=end;p++){ const btn = document.createElement('button'); btn.textContent = p; btn.className = 'px-3 py-1 rounded text-sm ' + (p===currentPage? 'bg-blue-600 text-white':'border hover:bg-gray-100'); btn.onclick = () => showPage(p); container.appendChild(btn); }
            const btnNext = document.createElement('button'); btnNext.textContent = 'Siguiente'; btnNext.className = 'px-3 py-1 border rounded text-sm ' + (currentPage===totalPages? 'opacity-50 cursor-not-allowed':'hover:bg-gray-100'); btnNext.disabled = currentPage===totalPages; btnNext.onclick = () => showPage(currentPage+1); container.appendChild(btnNext);
        }
        function showPage(page=1){
            const items = getMatched(); const totalPages = Math.max(1, Math.ceil(items.length / pageSize)); currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage-1)*pageSize; const end = start + pageSize;
            document.querySelectorAll('.aprob-item').forEach(el => el.style.display = 'none'); items.slice(start,end).forEach(el => el.style.display = '');
            render(totalPages);
            const info = document.getElementById('paginationInfoAprob'); if (info){ const total = items.length; const showing = Math.min(end, total); info.textContent = `Mostrando ${showing} de ${total}`; }
        }

        document.querySelectorAll('.aprob-item').forEach(el => el.dataset.match = '1');
        if (pageSizeSel) pageSizeSel.addEventListener('change', e => { pageSize = parseInt(e.target.value,10)||10; showPage(1); });
        if (input) input.addEventListener('keyup', function(){
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('.aprob-item').forEach(el => {
                el.dataset.match = el.textContent.toLowerCase().includes(filtro) ? '1' : '0';
            });
            showPage(1);
            });

        showPage(1);
    }

    // DOM ready
    document.addEventListener('DOMContentLoaded', function(){
        try{ initProviderSelections(); }catch(e){console.warn(e);} 
        try{ bindStatusButtons(); }catch(e){console.warn(e);} 
        try{ setupPaginationAndSearch(); }catch(e){console.warn(e);} 
    });

    // Proveedor modal functions
    function openProviderChoiceModal(reqId, prodId, providers, selectedId = null) {
        const modal = document.getElementById('providerChoiceModal');
        const list = document.getElementById('providerChoiceList');
        list.innerHTML = '';

        providers.forEach(prov => {
            const item = document.createElement('div');
            item.className = 'flex justify-between items-center p-2 border-b';

            const left = document.createElement('div');
            left.innerHTML = `<div class="font-medium">${prov.prov_name}</div><div class="text-sm text-gray-500">Precio: ${format2(prov.price_produc)} ${prov.moneda || ''}</div>`;

            const btn = document.createElement('button');
            // minimal button: small circular icon
            btn.className = 'select-prov-btn inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white';
            btn.setAttribute('data-id', prov.id);
            btn.setAttribute('data-req', reqId);
            btn.setAttribute('data-prod', prodId);
            btn.setAttribute('aria-label', 'Seleccionar proveedor');
            btn.textContent = '✓';

            // click handler directo: actualizar select oculto, UI y cerrar modal
            btn.addEventListener('click', function(e){
                e.stopPropagation();
                const provId = this.getAttribute('data-id');
                const rId = this.getAttribute('data-req');
                const pId = this.getAttribute('data-prod');

                const hiddenSelect = document.querySelector(`.prov-select[data-req="${rId}"][data-prod="${pId}"]`);
                if (hiddenSelect) {
                    const opt = Array.from(hiddenSelect.options).find(o => String(o.value) === String(provId));
                    if (opt) {
                        hiddenSelect.value = opt.value;
                        hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    } else {
                        const priceText = left.querySelector('.text-sm') ? (left.querySelector('.text-sm').textContent.match(/[-0-9.,]+/) || ['0'])[0] : '0';
                        const parsedPrice = priceText.replace(/\./g,'').replace(',','.');
                        const newOpt = document.createElement('option');
                        newOpt.value = provId;
                        newOpt.dataset.price = parsedPrice;
                        newOpt.text = prov.prov_name || 'Proveedor';
                        hiddenSelect.appendChild(newOpt);
                        hiddenSelect.value = newOpt.value;
                        hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                const selDiv = document.getElementById(`selprov-${rId}-${pId}`);
                if (selDiv) selDiv.textContent = prov.prov_name || 'Seleccionado';

                // cerrar modal
                closeProviderChoiceModal();
            });

            item.appendChild(left);
            item.appendChild(btn);
            list.appendChild(item);
        });

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeProviderChoiceModal() {
        const modal = document.getElementById('providerChoiceModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = 'auto';
    }

    // confirmProviderChoice queda disponible pero la selección es inmediata al pulsar el botón minimalista
    function confirmProviderChoice(){
        // no-op fallback (se usa selección inmediata)
        closeProviderChoiceModal();
    }

    // Abrir modal de proveedores cuando se pulsa el botón "Seleccionar proveedor"
    document.addEventListener('click', function(e){
        const btn = e.target.closest && e.target.closest('.open-prov-modal-btn');
        if (!btn) return;
        e.preventDefault();
        let providers = [];
        try { providers = JSON.parse(btn.getAttribute('data-providers') || '[]'); } catch(err) { providers = []; }
        const reqId = btn.getAttribute('data-req');
        const prodId = btn.getAttribute('data-prod');
        const selectedId = btn.getAttribute('data-selected') || null;
        openProviderChoiceModal(reqId, prodId, providers, selectedId);
    });
    </script>
    @endsection