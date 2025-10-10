@extends('layouts.app')

@section('title', 'Todas las Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Todas las Requisiciones</h1>

    <style>
        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: normal;
            padding: 0.25rem 0.75rem;
            border-radius: 0.75rem;
            line-height: 1.1;
            max-width: 220px;
            word-break: break-word;
            vertical-align: middle;
            margin: 0 auto;
        }
        .status-badge.text-xs { font-size: 0.75rem; }
        .max-w-7xl { margin-left: auto; margin-right: auto; }
        #tablaRequisiciones thead th { text-transform: uppercase; letter-spacing: 0.02em; }
        #tablaRequisiciones td .flex { justify-content: center; }
    </style>

    <!-- 🔍 Barra de búsqueda -->
    <div class="mb-6 flex justify-between items-center">
        <input type="text" id="busqueda" placeholder="Buscar requisición..."
            class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
    </div>

    @if($requisiciones->isEmpty())
        <p class="text-gray-500 text-center py-6">No hay requisiciones registradas.</p>
    @else
    <div class="overflow-x-auto">
        <table id="tablaRequisiciones" class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
            <thead class="bg-blue-50 text-gray-700 uppercase text-sm font-semibold">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Solicitante</th>
                    <th class="p-3 text-left">Prioridad</th>
                    <th class="p-3 text-left">Recobrable</th>
                    <th class="p-3 text-left">Productos</th>
                    <th class="p-3 text-center">Estatus</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach($requisiciones as $req)
                @php
                    $esPropietario = (session('user.id') == $req->user_id);
                    // Normalizar roles desde la sesión (user_roles preferido, fallback a user.roles / user.role)
                    $rolesRaw = session('user_roles') ?? (session('user.roles') ?? session('user.role') ?? []);
                    $rolesArr = [];
                    if (is_string($rolesRaw)) {
                        $rolesArr = preg_split('/\s*,\s*/', $rolesRaw);
                    } elseif (is_array($rolesRaw)) {
                        foreach ($rolesRaw as $r) {
                            if (is_string($r)) { $rolesArr[] = $r; }
                            elseif (is_array($r)) { if (isset($r['name'])) $rolesArr[] = $r['name']; elseif (isset($r['slug'])) $rolesArr[] = $r['slug']; }
                            elseif (is_object($r)) { if (isset($r->name)) $rolesArr[] = $r->name; elseif (isset($r->slug)) $rolesArr[] = $r->slug; }
                        }
                    }
                    $rolesLower = array_map(function($v){ return strtolower(trim((string)$v)); }, $rolesArr);
                    $canManage = in_array('area de compras', $rolesLower) || in_array('admin requisicion', $rolesLower);
                    $isAdmin = in_array('admin requisicion', $rolesLower);
                @endphp
                <tr class="border-b hover:bg-gray-50 transition">
                    <td class="p-3">#{{ $req->id }}</td>
                    <td class="p-3">{{ $req->created_at->format('d/m/Y') }}</td>
                    <td class="p-3">{{ $req->name_user ?? 'Desconocido' }}</td>
                    <td class="p-3">
                        <span class="px-3 py-1 text-xs font-medium rounded-full text-white
                            @if($req->prioridad_requisicion=='alta') bg-red-600
                            @elseif($req->prioridad_requisicion=='media') bg-yellow-500
                            @else bg-green-600 @endif">
                            {{ ucfirst($req->prioridad_requisicion) }}
                        </span>
                    </td>
                    <td class="p-3">{{ $req->Recobrable }}</td>
                    <td class="p-3">
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            @foreach($req->productos as $prod)
                                <li>{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="p-3 text-center">
                        @php
                            $colorEstatus = 'bg-gray-500';
                            $ultimoEstatus = ($req->estatusHistorial && $req->estatusHistorial->count() > 0)
                                ? $req->estatusHistorial->sortByDesc('created_at')->first()
                                : null;
                            $ultimoEstatusId = $ultimoEstatus->estatus_id ?? null;
                            $nombreEstatus = $ultimoEstatus && $ultimoEstatus->estatusRelation ? $ultimoEstatus->estatusRelation->status_name : 'Pendiente';
                            switch($ultimoEstatusId) {
                                case 1: $colorEstatus = 'bg-blue-600'; break;
                                case 2: case 3: case 4: $colorEstatus = 'bg-yellow-500'; break;
                                case 5: $colorEstatus = 'bg-purple-600'; break;
                                case 6: case 9: case 13: $colorEstatus = 'bg-red-600'; break;
                                case 7: case 8: $colorEstatus = 'bg-indigo-600'; break;
                                case 10: $colorEstatus = 'bg-green-600'; break;
                                case 11: $colorEstatus = 'bg-orange-500'; break;
                            }
                            $descripcionesEstatus = [
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
                            $tooltip = $descripcionesEstatus[$ultimoEstatusId] ?? 'Pendiente por gestión.';
                        @endphp
                        <span class="status-badge px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorEstatus }} cursor-help" title="{{ $tooltip }}">
                            <span>{{ $nombreEstatus }}</span>
                        </span>
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 items-center">
                            <!-- Ver y PDF visibles para todos -->
                            <button onclick="toggleModal('modal-{{ $req->id }}')" class="btn-open-ver bg-blue-600 hover:bg-blue-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Ver requisición" aria-label="Ver requisición">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="{{ route('requisiciones.pdf', $req->id) }}" class="bg-green-600 hover:bg-green-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Descargar PDF" aria-label="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>

                            @if($canManage)
                                @if($isAdmin && $ultimoEstatusId == 11)
                                <a href="{{ route('requisiciones.edit', $req->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Editar requisición" aria-label="Editar requisición">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif

                                @if($isAdmin && !in_array($ultimoEstatusId, [6,10,5]))
                                <button onclick="cancelarRequisicion({{ $req->id }})" class="bg-red-600 hover:bg-red-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Cancelar requisición" aria-label="Cancelar requisición">
                                    <i class="fas fa-times"></i>
                                </button>
                                @endif

                                @if($ultimoEstatusId == 6)
                                <button onclick="reenviarRequisicion({{ $req->id }})" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Reenviar requisición" aria-label="Reenviar requisición">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                @endif

                                @if(in_array($ultimoEstatusId, [4, 5, 7, 8, 12]))
                                <button type="button" class="bg-teal-600 hover:bg-teal-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow btn-open-entrega-req" data-req-id="{{ $req->id }}" title="Entregar" aria-label="Entregar">
                                    <i class="fas fa-truck"></i>
                                </button>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="flex items-center justify-between mt-4" id="paginationBar">
        <div class="text-sm text-gray-600">
            Mostrar
            <select id="pageSizeSelect" class="border rounded px-2 py-1">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="20">20</option>
                <option value="50">50</option>
            </select>
            por página
        </div>
        <div class="flex flex-wrap gap-1" id="paginationControls"></div>
    </div>
 
    <!-- ===== Modales fuera de la tabla ===== -->
    @foreach($requisiciones as $req)
        @php
            $histX = $req->estatusHistorial;
            $ultimoActivoX = ($histX && $histX->count()) ? ($histX->firstWhere('estatus', 1) ?? $histX->sortByDesc('created_at')->first()) : null;
            $estatusIdX = $ultimoActivoX->estatus_id ?? null;
            // Recalcular roles para modal
            $rolesRaw = session('user_roles') ?? (session('user.roles') ?? session('user.role') ?? []);
            $rolesArr = [];
            if (is_string($rolesRaw)) {
                $rolesArr = preg_split('/\s*,\s*/', $rolesRaw);
            } elseif (is_array($rolesRaw)) {
                foreach ($rolesRaw as $r) {
                    if (is_string($r)) { $rolesArr[] = $r; }
                    elseif (is_array($r)) { if (isset($r['name'])) $rolesArr[] = $r['name']; elseif (isset($r['slug'])) $rolesArr[] = $r['slug']; }
                    elseif (is_object($r)) { if (isset($r->name)) $rolesArr[] = $r->name; elseif (isset($r->slug)) $rolesArr[] = $r->slug; }
                }
            }
            $rolesLower = array_map(function($v){ return strtolower(trim((string)$v)); }, $rolesArr);
            $canManage = in_array('area de compras', $rolesLower) || in_array('admin requisicion', $rolesLower);
        @endphp

        <!-- Modal de Ver Requisición -->
        <div id="modal-{{ $req->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50" onclick="toggleModal('modal-{{ $req->id }}')"></div>
            <div class="relative w-full max-w-4xl">
                <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-8 relative">
                    <button onclick="toggleModal('modal-{{ $req->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-3xl" aria-label="Cerrar modal">&times;</button>
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-3">Requisición #{{ $req->id }}</h2>

                    <section class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3">Información General</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm bg-gray-50 rounded-lg p-4">
                            <div><span class="font-medium">Solicitante:</span> {{ $req->name_user ?? 'Desconocido' }}</div>
                            <div><span class="font-medium">Fecha:</span> {{ $req->created_at->format('d/m/Y') }}</div>
                            <div><span class="font-medium">Prioridad:</span> {{ ucfirst($req->prioridad_requisicion) }}</div>
                            <div><span class="font-medium">Recobrable:</span> {{ $req->Recobrable }}</div>
                            @php
                                $ultimoActivo = ($req->estatusHistorial && $req->estatusHistorial->count()) ? ($req->estatusHistorial->firstWhere('estatus', 1) ?? $req->estatusHistorial->sortByDesc('created_at')->first()) : null;
                                $estatusActualId = $ultimoActivo->estatus_id ?? null;
                                $estatusActualNombre = $ultimoActivo && $ultimoActivo->estatusRelation ? $ultimoActivo->estatusRelation->status_name : 'Pendiente';
                                $colorActual = 'bg-gray-500';
                                switch($estatusActualId) {
                                    case 1: $colorActual = 'bg-blue-600'; break;
                                    case 2: case 3: case 4: $colorActual = 'bg-yellow-500'; break;
                                    case 5: $colorActual = 'bg-purple-600'; break;
                                    case 6: case 9: case 13: $colorActual = 'bg-red-600'; break;
                                    case 7: case 8: $colorActual = 'bg-indigo-600'; break;
                                    case 10: $colorActual = 'bg-green-600'; break;
                                    case 11: $colorActual = 'bg-orange-500'; break;
                                }
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
                                <span class="status-badge ml-2 px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorActual }} cursor-help" title="{{ $tooltipModal }}">{{ $estatusActualNombre }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="mb-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Detalle</h3>
                            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">{{ $req->detail_requisicion }}</div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Justificación</h3>
                            <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-700">{{ $req->justify_requisicion }}</div>
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
                                            <th class="p-3 text-center">Cantidad Total</th>
                                            <th class="p-3 text-center">Unidad</th>
                                            <th class="p-3 text-center">Precio unitario</th>
                                            <th class="p-3 text-center">Precio total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $grandTotalModal = 0; @endphp
                                        @foreach($req->productos as $prod)
                                            @php
                                                $unitPrice = (float) ($prod->price_produc ?? 0);
                                                $lineTotal = ($prod->pivot->pr_amount ?? 0) * $unitPrice;
                                                $grandTotalModal += $lineTotal;
                                            @endphp
                                            <tr class="border-b">
                                                <td class="p-3 font-medium text-gray-800 align-top">{{ $prod->name_produc }}</td>
                                                <td class="p-3 text-center align-top">{{ $prod->pivot->pr_amount }}</td>
                                                <td class="p-3 text-center align-top">{{ $prod->unit_produc ?? '-' }}</td>
                                                <td class="p-3 text-center align-top">${{ number_format($unitPrice, 2) }}</td>
                                                <td class="p-3 text-center align-top">${{ number_format($lineTotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-gray-50 border-t">
                                            <td colspan="4" class="p-3 text-right font-semibold">Total general</td>
                                            <td class="p-3 text-center font-semibold">${{ number_format($grandTotalModal, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        <!-- Fin Modal Ver Requisición -->

        @if($canManage && in_array($estatusIdX, [4, 5, 7, 8, 12]))
        <!-- Modal de Entrega para Requisición -->
        <div id="modal-entrega-req-{{ $req->id }}" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4" data-req-id="{{ $req->id }}">
            <div class="absolute inset-0 bg-black/50" data-close="1"></div>
            <div class="relative bg-white w-full max-w-4xl rounded-lg shadow-lg overflow-hidden flex flex-col">
                <div class="flex justify-between items-center px-6 py-4 border-b">
                    <h3 class="text-lg font-semibold">Entregar productos - Requisición #{{ $req->id }}</h3>
                    <button type="button" class="text-gray-600 hover:text-gray-800 ent-req-close" data-req-id="{{ $req->id }}">✕</button>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" class="border rounded ent-req-select-all" data-req-id="{{ $req->id }}">
                            Seleccionar todos
                        </label>
                        <span class="text-xs text-gray-500">Estatus resultante: 8 (Material recibido por coordinador)</span>
                    </div>
                    <div class="max-h-[55vh] overflow-y-auto border rounded bg-white">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-center"><input type="checkbox" class="ent-req-chk-header" data-req-id="{{ $req->id }}"></th>
                                    <th class="px-3 py-2 text-left">Producto</th>
                                    <th class="px-3 py-2 text-center">Unidad</th>
                                    <th class="px-3 py-2 text-center">Cantidad Requerida</th>
                                    <th class="px-3 py-2 text-center">Precio unitario</th>
                                    <th class="px-3 py-2 text-center">Precio total</th>
                                    <th class="px-3 py-2 text-center">Ya Entregado</th>
                                    <th class="px-3 py-2 text-center">Pendiente</th>
                                    <th class="px-3 py-2 text-center">Entregar</th>
                                </tr>
                            </thead>
                            <tbody id="ent-req-tbody-{{ $req->id }}">
                                @php
                                    $productosReq = DB::table('producto_requisicion')
                                        ->join('productos', 'producto_requisicion.id_producto', '=', 'productos.id')
                                        ->where('producto_requisicion.id_requisicion', $req->id)
                                        ->select('productos.id', 'productos.name_produc', 'productos.unit_produc', 'productos.price_produc', 'producto_requisicion.pr_amount as cantidad_requerida')
                                        ->get();

                                    $entregasPorProducto = DB::table('entrega')
                                        ->where('requisicion_id', $req->id)
                                        ->whereNull('deleted_at')
                                        ->select('producto_id', DB::raw('SUM(COALESCE(cantidad_recibido,0)) as entregado'))
                                        ->groupBy('producto_id')
                                        ->pluck('entregado', 'producto_id');
                                @endphp
                                @forelse($productosReq as $producto)
                                @php
                                    $productoId = $producto->id;
                                    $cantidadRequerida = (int)$producto->cantidad_requerida;
                                    $entregado = (int)($entregasPorProducto[$productoId] ?? 0);
                                    $pendiente = max(0, $cantidadRequerida - $entregado);
                                    $isDone = ($pendiente <= 0);
                                    $pendientesNoConfirmadas = DB::table('entrega')
                                        ->where('requisicion_id', $req->id)
                                        ->where('producto_id', $productoId)
                                        ->whereNull('deleted_at')
                                        ->whereNull('cantidad_recibido')
                                        ->sum('cantidad');
                                    $unit = $producto->unit_produc ?? '-';
                                    $unitPrice = (float) ($producto->price_produc ?? 0);
                                    $lineTotalReq = $cantidadRequerida * $unitPrice;
                                @endphp
                                <tr class="border-t">
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" class="ent-req-row-chk" data-producto-id="{{ $productoId }}" data-pendiente="{{ $pendiente }}" {{ ($isDone || $pendientesNoConfirmadas > 0) ? 'disabled' : '' }}>
                                    </td>
                                    <td class="px-3 py-2">{{ $producto->name_produc }}</td>
                                    <td class="px-3 py-2 text-center">{{ $unit }}</td>
                                    <td class="px-3 py-2 text-center">{{ $cantidadRequerida }}</td>
                                    <td class="px-3 py-2 text-center">${{ number_format($unitPrice,2) }}</td>
                                    <td class="px-3 py-2 text-center">${{ number_format($lineTotalReq,2) }}</td>
                                    <td class="px-3 py-2 text-center">{{ $entregado }}</td>
                                    <td class="px-3 py-2 text-center">
                                        @if($isDone)
                                            <span class="px-2 py-1 rounded text-xs bg-green-100 text-green-700">Completado</span>
                                        @elseif($pendientesNoConfirmadas > 0)
                                            <span class="px-2 py-1 rounded text-xs bg-amber-100 text-amber-700">Enviado, esperando confirmación ({{ $pendientesNoConfirmadas }})</span>
                                        @else
                                            <span class="text-xs">{{ $pendiente }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <input type="number" min="0" max="{{ $pendiente }}" value="{{ $pendiente }}" class="w-24 border rounded p-1 text-center ent-req-cant-input" {{ ($isDone || $pendientesNoConfirmadas > 0) ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="px-3 py-3 text-center text-gray-500">No hay productos en esta requisición.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="flex justify-end gap-3 px-6 py-4 border-t bg-gray-50">
                    <button type="button" class="px-4 py-2 border rounded ent-req-cancel" data-req-id="{{ $req->id }}">Cancelar</button>
                    <button type="button" class="px-4 py-2 bg-green-600 text-white rounded ent-req-save" data-req-id="{{ $req->id }}">Realizar entrega</button>
                </div>
            </div>
        </div>
        @endif
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

    document.getElementById('busqueda').addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaRequisiciones tbody tr').forEach(row => {
            row.dataset.match = row.textContent.toLowerCase().includes(filtro) ? '1' : '0';
        });
        showPage(1);
    });

    let currentPage = 1;
    let pageSize = 10;

    function getMatchedRows(){
        return Array.from(document.querySelectorAll('#tablaRequisiciones tbody tr'))
            .filter(r => (r.dataset.match ?? '1') !== '0');
    }

    function showPage(page = 1){
        const rows = getMatchedRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / pageSize));
        currentPage = Math.min(Math.max(1, page), totalPages);
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;

        const allRows = Array.from(document.querySelectorAll('#tablaRequisiciones tbody tr'));
        allRows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        renderPagination(totalPages);
        const infoEl = document.getElementById('paginationInfoAll');
        if (infoEl) {
            const total = rows.length;
            const showing = Math.min(end, total);
            infoEl.textContent = `Mostrando ${showing} de ${total}`;
        }
    }

    function renderPagination(totalPages){
        const container = document.getElementById('paginationControls');
        if (!container) return;
        container.innerHTML = '';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = currentPage === 1;
        btnPrev.onclick = () => showPage(currentPage - 1);
        container.appendChild(btnPrev);

        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, currentPage + 2);
        for (let p = start; p <= end; p++) {
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

    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('#tablaRequisiciones tbody tr').forEach(r => r.dataset.match = '1');
        const sel = document.getElementById('pageSizeSelect');
        if (sel) {
            pageSize = parseInt(sel.value, 10) || 10;
            sel.addEventListener('change', (e) => {
                pageSize = parseInt(e.target.value, 10) || 10;
                showPage(1);
            });
        }
        showPage(1);

        document.querySelectorAll('.btn-open-entrega-req').forEach(btn => {
            btn.addEventListener('click', () => {
                const reqId = btn.dataset.reqId;
                const modal = document.getElementById(`modal-entrega-req-${reqId}`);
                modal?.classList.remove('hidden');
                modal?.classList.add('flex');
            });
        });

        document.querySelectorAll('.ent-req-close, .ent-req-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                const reqId = btn.dataset.reqId;
                const modal = document.getElementById(`modal-entrega-req-${reqId}`);
                modal?.classList.add('hidden');
                modal?.classList.remove('flex');
            });
        });

        document.querySelectorAll('[id^="modal-entrega-req-"]').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal || e.target?.dataset?.close === '1') {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });
        });

        document.querySelectorAll('.ent-req-chk-header').forEach(chk => {
            chk.addEventListener('change', () => {
                const reqId = chk.dataset.reqId;
                const tbody = document.getElementById(`ent-req-tbody-${reqId}`);
                tbody?.querySelectorAll('.ent-req-row-chk').forEach(c => { 
                    if (!c.disabled) c.checked = chk.checked; 
                });
            });
        });

        document.querySelectorAll('.ent-req-select-all').forEach(chk => {
            chk.addEventListener('change', () => {
                const reqId = chk.dataset.reqId;
                const tbody = document.getElementById(`ent-req-tbody-${reqId}`);
                tbody?.querySelectorAll('.ent-req-row-chk').forEach(c => { 
                    if (!c.disabled) c.checked = chk.checked; 
                });
            });
        });

        document.querySelectorAll('[id^="ent-req-tbody-"]').forEach(tb => {
            tb.addEventListener('input', (e) => {
                if (e.target && e.target.classList.contains('ent-req-cant-input')){
                    const mx = parseInt(e.target.max || '0', 10);
                    let v = parseInt(e.target.value || '0', 10);
                    if (isNaN(v) || v < 0) v = 0;
                    if (mx > 0 && v > mx) v = mx;
                    e.target.value = v;
                }
            });
        });

        document.querySelectorAll('.ent-req-save').forEach(btn => {
            btn.addEventListener('click', async () => {
                const reqId = btn.dataset.reqId;
                const modal = document.getElementById(`modal-entrega-req-${reqId}`);
                const tbody = document.getElementById(`ent-req-tbody-${reqId}`);
                const rows = Array.from(tbody?.querySelectorAll('tr')||[]);
                const items = [];
                rows.forEach(tr => {
                    const chk = tr.querySelector('.ent-req-row-chk');
                    const inp = tr.querySelector('.ent-req-cant-input');
                    if (!chk || !inp) return;
                    if (!chk.checked) return;
                    const cant = parseInt(inp.value||'0',10);
                    // Solo requerir cantidad positiva; no comparar contra 'pendiente' aquí
                    if (cant > 0) {
                        items.push({ producto_id: Number(chk.dataset.productoId), cantidad: cant, cantidad_recibido: null });
                    }
                });
                if (items.length === 0) { 
                    Swal.fire({ icon:'info', title:'Sin selección', text:'Seleccione al menos un producto con cantidad > 0.' });
                    return; 
                }
                try {
                    Swal.fire({ title: 'Procesando', html: 'Registrando entregas, por favor espere...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                    const resp = await fetch(`/requisiciones/${reqId}/entregar`, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
                        body: JSON.stringify({ requisicion_id: reqId, items, comentario: null, fecha: new Date().toISOString().slice(0,19).replace('T',' ') })
                    });
                    const data = await resp.json();
                    if (!resp.ok) { Swal.close(); throw new Error(data.message || 'Error al registrar entregas'); }
                    Swal.close();
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    Swal.fire({ icon:'success', title:'Éxito', text:'Entregas registradas correctamente.' }).then(() => location.reload());
                } catch(e) {
                    Swal.close();
                    Swal.fire({ icon:'error', title:'Error', text: e.message || 'Ocurrió un error al procesar la entrega' });
                }
            });
        });
    });

    function cancelarRequisicion(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción cancelará la requisición',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, volver'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Procesando', text: 'Cancelando requisición...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() } });
                fetch(`/requisiciones/${id}/cancelar`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
                .then(r => r.json())
                .then((data) => {
                    if (data.success) { Swal.fire('Cancelada!', data.message, 'success').then(() => location.reload()); }
                    else { Swal.fire('Error!', data.message, 'error'); }
                })
                .catch(() => { Swal.fire('Error!', 'Error al cancelar', 'error'); });
            }
        });
    }

    function reenviarRequisicion(id) {
        Swal.fire({
            title: '¿Reenviar requisición?',
            text: 'Esta acción reenviará la requisición para su aprobación',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, reenviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;
            Swal.fire({ title: 'Procesando', text: 'Reenviando requisición...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
            fetch(`/requisiciones/${id}/reenviar`, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) { Swal.fire('Reenviada!', data.message, 'success').then(() => location.reload()); }
                else { Swal.fire('Error!', data.message || 'No se pudo reenviar', 'error'); }
            })
            .catch(() => { Swal.fire('Error!', 'Error al reenviar', 'error'); });
        });
    }
</script>

<!-- Agregar span dentro de la barra de paginación -->
<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const bar = document.getElementById('paginationBar');
    if (bar && !document.getElementById('paginationInfoAll')) {
        const leftBox = bar.querySelector('div.text-sm');
        if (leftBox) {
            const span = document.createElement('span');
            span.id = 'paginationInfoAll';
            span.className = 'ml-4 text-sm text-gray-600';
            leftBox.appendChild(span);
        }
    }
});
</script>
@endsection