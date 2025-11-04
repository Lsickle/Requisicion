@extends('layouts.app')

@section('title', 'Historial de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Historial de mis Requisiciones</h1>

    <!-- 🔍 Barra de búsqueda -->
    <div class="mb-6 flex justify-between items-center">
        <input type="text" id="busqueda" placeholder="Buscar requisición..."
            class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
    </div>

    @if($requisiciones->isEmpty())
    <p class="text-gray-500 text-center py-6">No has realizado ninguna requisición aún.</p>
    @else
    <div class="overflow-x-auto">
        <table id="tablaRequisiciones" class="w-full border-collapse bg-white rounded-lg overflow-hidden shadow-sm">
            <thead class="bg-blue-50 text-gray-700 uppercase text-sm font-semibold">
                <tr>
                    <th class="p-3 text-left">ID</th>
                    <th class="p-3 text-left">Fecha</th>
                    <th class="p-3 text-left">Prioridad</th>
                    <th class="p-3 text-left">Recobrable</th>
                    <th class="p-3 text-left">Productos</th>
                    <th class="p-3 text-left">Estatus</th>
                    <th class="p-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @foreach($requisiciones as $req)
                <tr class="border-b hover:bg-gray-50 transition">

                    <!-- ID -->
                    <td class="p-3">#{{ $req->id }}</td>
                    <!-- Fecha -->
                    <td class="p-3">{{ $req->created_at->format('d/m/Y') }}</td>

                    <!-- Prioridad -->
                    <td class="p-3">
                        <span class="px-3 py-1 text-xs font-medium rounded-full text-white
                            @if($req->prioridad_requisicion=='alta') bg-red-600
                            @elseif($req->prioridad_requisicion=='media') bg-yellow-500
                            @else bg-green-600 @endif">
                            {{ ucfirst($req->prioridad_requisicion) }}
                        </span>
                    </td>

                    <!-- Recobrable -->
                    <td class="p-3">{{ $req->Recobrable }}</td>

                    <!-- Productos -->
                    <td class="p-3">
                        <ul class="list-disc list-inside text-sm text-gray-600">
                            @foreach($req->productos as $prod)
                            <li>{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})</li>
                            @endforeach
                        </ul>
                    </td>

                    <!-- Estatus -->
                    <td class="p-3">
                        @php
                            $colorEstatus = 'bg-gray-500';
                            $ultimoEstatus = ($req->estatusHistorial && $req->estatusHistorial->count() > 0)
                                ? $req->estatusHistorial->sortByDesc('created_at')->first()
                                : null;
                            $ultimoEstatusId = $ultimoEstatus->estatus_id ?? null;
                            $nombreEstatus = $ultimoEstatus && $ultimoEstatus->estatusRelation ? $ultimoEstatus->estatusRelation->status_name : 'Pendiente';
                            // Descripciones por estatus (IDs 1 a 13 en el orden del seeder)
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
                            // Cálculo de color por estatus
                            $calcColor = function($id){
                                switch($id){
                                    case 1: return 'bg-blue-600';
                                    case 2: case 3: case 4: return 'bg-yellow-500';
                                    case 5: return 'bg-purple-600';
                                    case 6: case 9: case 13: return 'bg-red-600';
                                    case 7: case 8: return 'bg-indigo-600';
                                    case 10: return 'bg-green-600';
                                    case 11: return 'bg-orange-500';
                                    default: return 'bg-gray-500';
                                }
                            };
                            // Normalizar operación (remover acentos y minúsculas)
                            $rawOp = (string) ($req->operacion_user ?? '');
                            $opNorm = strtolower(trim(strtr($rawOp, [
                                'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a',
                                'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e','É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
                                'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i',
                                'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o',
                                'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u',
                                'ñ'=>'n','Ñ'=>'n'
                            ])));
                            // Ocultar visual de estatus 3 para tecnologia/compras mostrando el anterior
                            $ocultarGerencia = in_array($opNorm, ['tecnologia','compras']);
                            $displayId = $ultimoEstatusId;
                            $displayNombre = $nombreEstatus;
                            $displayTooltip = $descripcionesEstatus[$ultimoEstatusId] ?? 'Pendiente por gestión.';
                            if ($ocultarGerencia && (int)($ultimoEstatusId ?? 0) === 3) {
                                $histDesc = ($req->estatusHistorial && $req->estatusHistorial->count()) ? $req->estatusHistorial->sortByDesc('created_at') : collect();
                                $lastThree = $histDesc->firstWhere('estatus_id', 3);
                                $prevRegistro = null;
                                if ($lastThree) {
                                    $lastThreeTs = \Carbon\Carbon::parse($lastThree->created_at);
                                    $prevRegistro = $histDesc->first(function($row) use ($lastThreeTs){
                                        return \Carbon\Carbon::parse($row->created_at)->lt($lastThreeTs);
                                    });
                                }
                                if (!$prevRegistro) {
                                    $prevRegistro = ($req->estatusHistorial && $req->estatusHistorial->count())
                                        ? $req->estatusHistorial->where('estatus_id','!=',3)->sortByDesc('created_at')->first()
                                        : null;
                                }
                                $displayId = $prevRegistro->estatus_id ?? 2;
                                if (isset($prevRegistro) && $prevRegistro->estatusRelation) {
                                    $displayNombre = $prevRegistro->estatusRelation->status_name;
                                } else {
                                    $displayNombre = $displayId === 2 ? 'Revisado por compras' : ($displayId === null ? 'Pendiente' : ($descripcionesEstatus[$displayId] ?? 'Pendiente'));
                                }
                                $displayTooltip = $descripcionesEstatus[$displayId] ?? 'Pendiente por gestión.';
                            }
                            $colorEstatus = $calcColor($displayId);
                        @endphp
                        <span class="px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorEstatus }} cursor-help" title="{{ $displayTooltip }}">{{ $displayNombre }}</span>
                    </td>

                    <!-- Acciones -->
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2 items-center">
                            @php
                                $anyMissingProv = DB::table('producto_requisicion')
                                    ->where('id_requisicion', $req->id)
                                    ->whereNull('deleted_at')
                                    ->whereNull('id_productoxproveedor')
                                    ->exists();
                                $isStatus1 = (int)($ultimoEstatusId ?? 0) === 1;
                            @endphp
                            <button onclick="toggleModal('modal-{{ $req->id }}')" class="btn-open-ver bg-blue-600 hover:bg-blue-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Ver requisición" aria-label="Ver requisición">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="{{ route('requisiciones.create') }}?from={{ $req->id }}" class="bg-teal-600 hover:bg-teal-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Solicitar de nuevo" aria-label="Solicitar de nuevo">
                                <i class="fas fa-clone"></i>
                            </a>
                            @if(in_array(($ultimoEstatusId ?? null), [8,12]))
                            <button onclick="toggleModal('modal-recibir-{{ $req->id }}')" class="bg-yellow-600 hover:bg-yellow-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Recibir productos" aria-label="Recibir productos">
                                <i class="fas fa-box"></i>
                            </button>
                            @endif
                            @if($ultimoEstatusId == 11)
                            <a href="{{ route('requisiciones.edit', $req->id) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Editar requisición" aria-label="Editar requisición">
                                <i class="fas fa-edit"></i>
                            </a>
                            @endif
                            @if(!$anyMissingProv && !$isStatus1)
                                <a href="{{ route('requisiciones.pdf', $req->id) }}" class="bg-green-600 hover:bg-green-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Descargar PDF" aria-label="Descargar PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            @endif
                            @if(in_array(($ultimoEstatusId ?? null), [1,2,3,4]))
                            <button onclick="cancelarRequisicion({{ $req->id }})" class="bg-red-600 hover:bg-red-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Cancelar requisición" aria-label="Cancelar requisición">
                                <i class="fas fa-times"></i>
                            </button>
                            @endif
                            @if($ultimoEstatusId == 6)
                            <button onclick="reenviarRequisicion({{ $req->id }})" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded p-2 w-9 h-9 flex items-center justify-center shadow" title="Reenviar requisición" aria-label="Reenviar requisición">
                                <i class="fas fa-paper-plane"></i>
                            </button>
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
        <span id="paginationInfoHist" class="ml-4 text-sm text-gray-600"></span>
    </div>
 
     <!-- ===== Modales fuera de la tabla para evitar desbordes y HTML inválido ===== -->
     @foreach($requisiciones as $req)
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
                         <div><span class="font-medium">Operación:</span> {{ $req->operacion_user ?? '—' }}</div>
                         @php
                             $hist = $req->estatusHistorial;
                             $ultimoActivo = ($hist && $hist->count()) ? ($hist->firstWhere('estatus', 1) ?? $hist->sortByDesc('created_at')->first()) : null;
                             $estatusActualId = $ultimoActivo->estatus_id ?? null;
                             $estatusActualNombre = $ultimoActivo && $ultimoActivo->estatusRelation ? $ultimoActivo->estatusRelation->status_name : 'Pendiente';
                             // Mapa de descripciones para modal
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
                             // Función color para modal
                             $calcColorModal = function($id){
                                 switch($id){
                                     case 1: return 'bg-blue-600';
                                     case 2: case 3: case 4: return 'bg-yellow-500';
                                     case 5: return 'bg-purple-600';
                                     case 6: case 9: return 'bg-red-600';
                                     case 7: case 8: return 'bg-indigo-600';
                                     case 10: return 'bg-green-600';
                                     case 11: return 'bg-orange-500';
                                     default: return 'bg-gray-500';
                                 }
                             };
                             // Ocultar visual de estatus 3 en modal para tecnologia/compras mostrando el anterior
                             $rawOpM = (string) ($req->operacion_user ?? '');
                             $opNormM = strtolower(trim(strtr($rawOpM, [
                                'á'=>'a','à'=>'a','ä'=>'a','â'=>'a','Á'=>'a','À'=>'a','Ä'=>'a','Â'=>'a',
                                'é'=>'e','è'=>'e','ë'=>'e','ê'=>'e','É'=>'e','È'=>'e','Ë'=>'e','Ê'=>'e',
                                'í'=>'i','ì'=>'i','ï'=>'i','î'=>'i','Í'=>'i','Ì'=>'i','Ï'=>'i','Î'=>'i',
                                'ó'=>'o','ò'=>'o','ö'=>'o','ô'=>'o','Ó'=>'o','Ò'=>'o','Ö'=>'o','Ô'=>'o',
                                'ú'=>'u','ù'=>'u','ü'=>'u','û'=>'u','Ú'=>'u','Ù'=>'u','Ü'=>'u','Û'=>'u',
                                'ñ'=>'n','Ñ'=>'n'
                             ])));
                             $ocultarGerenciaModal = in_array($opNormM, ['tecnologia','compras']);
                             $displayActualId = $estatusActualId;
                             $displayActualNombre = $estatusActualNombre;
                             $tooltipModal = $descripcionesEstatusModal[$estatusActualId] ?? 'Pendiente por gestión.';
                             if ($ocultarGerenciaModal && (int)($estatusActualId ?? 0) === 3) {
                                 $histDescM = ($hist && $hist->count()) ? $hist->sortByDesc('created_at') : collect();
                                 $lastThreeM = $histDescM->firstWhere('estatus_id', 3);
                                 $prevRegistro = null;
                                 if ($lastThreeM) {
                                     $lastThreeTsM = \Carbon\Carbon::parse($lastThreeM->created_at);
                                     $prevRegistro = $histDescM->first(function($row) use ($lastThreeTsM){
                                         return \Carbon\Carbon::parse($row->created_at)->lt($lastThreeTsM);
                                     });
                                 }
                                 if (!$prevRegistro) {
                                     $prevRegistro = ($hist && $hist->count()) ? $hist->where('estatus_id','!=',3)->sortByDesc('created_at')->first() : null;
                                 }
                                 $displayActualId = $prevRegistro->estatus_id ?? 2;
                                 if ($prevRegistro && $prevRegistro->estatusRelation) {
                                     $displayActualNombre = $prevRegistro->estatusRelation->status_name;
                                 } else {
                                     $displayActualNombre = $displayActualId === 2 ? 'Revisado por compras' : ($descripcionesEstatusModal[$displayActualId] ?? 'Pendiente');
                                 }
                                 $tooltipModal = $descripcionesEstatusModal[$displayActualId] ?? 'Pendiente por gestión.';
                             }
                             $colorActual = $calcColorModal($displayActualId);
                         @endphp
                         <div>
                             <span class="font-medium">Estatus actual:</span>
                             <span class="ml-2 px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorActual }} cursor-help" title="{{ $tooltipModal }}">{{ $displayActualNombre }}</span>
                         </div>
                         @php
                             // Mostrar motivo sólo si el estatus activo actual es 11 (Ajustes requeridos)
                             $registroComentarioModal = null;
                             if ((int)($estatusActualId ?? 0) === 11) {
                                 if ($req->estatusHistorial && $req->estatusHistorial->count()) {
                                     // obtener el comentario más reciente con estatus 11
                                     $registroComentarioModal = $req->estatusHistorial->where('estatus_id', 11)->sortByDesc('created_at')->first();
                                 }
                             }
                         @endphp
                         @if((int)($estatusActualId ?? 0) === 11 && !empty($registroComentarioModal?->comentario))
                             <div class="col-span-1 md:col-span-2 mt-2 rounded-lg p-3 text-sm bg-amber-50 border border-amber-200 text-amber-800">
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

                <!-- Productos (tabla dentro del modal, sin desbordes) -->
                <section class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">Productos</h3>
                    <div class="border rounded-lg overflow-hidden">
                        <div class="max-h-80 overflow-y-auto">
                            <table class="w-full text-sm bg-white">
                                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-10">
                                    <tr class="border-b">
                                        <th class="p-3 text-left">Producto</th>
                                        <th class="p-3 text-center">Cantidad Total</th>
                                        <th class="p-3 text-left">Distribución por Centro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($req->productos as $prod)
                                    <tr class="border-b">
                                    <td class="p-3 font-medium text-gray-800 align-top">{{ $prod->name_produc }}
                                        </td>
                                        <td class="p-3 text-center align-top">{{ $prod->pivot->pr_amount }}</td>
                                        <td class="p-3 align-top">
                                            <ul class="list-disc list-inside text-sm text-gray-700 space-y-0.5">
                                                @php
                                                $distribucion = DB::table('centro_producto')
                                                ->where('requisicion_id', $req->id)
                                                ->where('producto_id', $prod->id)
                                                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                                ->select('centro.name_centro', 'centro_producto.amount')
                                                ->get();
                                                @endphp
                                                @forelse($distribucion as $centro)
                                                <li>{{ $centro->name_centro }} ({{ $centro->amount }})</li>
                                                @empty
                                                <li>No hay centros asignados</li>
                                                @endforelse
                                            </ul>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Estatus -->
                <section class="mt-6">
                    <a href="{{ route('requisiciones.estatus', $req->id) }}"
                    class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition">
                        Ver Estatus
                    </a>
                    <a href="{{ route('requisiciones.create') }}?from={{ $req->id }}" class="ml-2 bg-teal-600 text-white px-5 py-2 rounded-lg hover:bg-teal-700 transition">
                        Solicitar de nuevo
                    </a>
                </section>
            </div>
        </div>
    </div>

    @php
        $ocpLineas = DB::table('ordencompra_producto as ocp')
            ->join('orden_compras as oc','oc.id','=','ocp.orden_compras_id')
            ->join('productos as p','p.id','=','ocp.producto_id')
            ->leftJoin('proveedores as prov','prov.id','=','ocp.proveedor_id')
            ->whereNull('ocp.deleted_at')
            ->where('ocp.requisicion_id', $req->id)
            ->whereNotNull('ocp.orden_compras_id')
            ->select('ocp.id as ocp_id','oc.order_oc','oc.id as oc_id','p.id as producto_id','p.name_produc','p.unit_produc','prov.prov_name','ocp.total')
            ->orderBy('ocp.id','desc')
            ->get();
    @endphp
    <div id="modal-entregar-{{ $req->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/50" onclick="toggleModal('modal-entregar-{{ $req->id }}')"></div>
        <div class="relative w-full max-w-4xl">
            <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-6 relative">
                <button onclick="toggleModal('modal-entregar-{{ $req->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl" aria-label="Cerrar">&times;</button>
                <h3 class="text-xl font-semibold mb-4">Entregar productos - Requisición #{{ $req->id }}</h3>
                <div class="flex items-center justify-between mb-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="checkbox" id="ent-select-all-{{ $req->id }}" onchange="entTglAll({{ $req->id }}, this)" class="border rounded">
                        Seleccionar todos
                    </label>
                    <span class="text-xs text-gray-500">Estatus resultante: 8 (Material recibido por coordinador)</span>
                </div>
                @if($ocpLineas->count())
                <div class="max-h-[55vh] overflow-y-auto border rounded">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="px-3 py-2 text-center"><input type="checkbox" id="ent-chk-header-{{ $req->id }}" onchange="entTglAll({{ $req->id }}, this)"></th>
                                <th class="px-3 py-2 text-left">Producto</th>
                                <th class="px-3 py-2 text-left">Proveedor</th>
                                <th class="px-3 py-2 text-left">OC</th>
                                <th class="px-3 py-2 text-center">Cantidad OC</th>
                                <th class="px-3 py-2 text-center">Entregar</th>
                            </tr>
                        </thead>
                        <tbody id="ent-tbody-{{ $req->id }}">
                            @foreach($ocpLineas as $l)
                            <tr class="border-t">
                                <td class="px-3 py-2 text-center"><input type="checkbox" class="ent-row-chk" data-ocp-id="{{ $l->ocp_id }}" data-producto-id="{{ $l->producto_id }}"></td>
                                <td class="px-3 py-2">{{ $l->name_produc }}</td>
                                <td class="px-3 py-2">{{ $l->prov_name ?? 'Proveedor' }}</td>
                                <td class="px-3 py-2">{{ $l->order_oc ?? ('OC-'.$l->oc_id) }}</td>
                                <td class="px-3 py-2 text-center">{{ $l->total }}</td>
                                <td class="px-3 py-2 text-center">
                                    <input type="number" min="0" max="{{ $l->total }}" value="{{ $l->total }}" class="w-24 border rounded p-1 text-center ent-cant-input">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-gray-600">No hay líneas de órdenes para esta requisición.</div>
                @endif
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="px-4 py-2 border rounded" onclick="toggleModal('modal-entregar-{{ $req->id }}')">Cancelar</button>
                    <button type="button" class="px-4 py-2 bg-green-600 text-white rounded btn-ent-save" onclick="entGuardarEntrega({{ $req->id }})">Guardar entrega</button>
                </div>
            </div>
        </div>
    </div>
 
    @php
        $hist = $req->estatusHistorial;
        $ultimoActivo = ($hist && $hist->count()) ? ($hist->firstWhere('estatus', 1) ?? $hist->sortByDesc('created_at')->first()) : null;
        $estatusActualId = $ultimoActivo->estatus_id ?? null;
        $usarEntrega = in_array($estatusActualId, [8,12]);
        if ($usarEntrega) {
            // Solo traer entregas pendientes de confirmación (cantidad_recibido IS NULL)
            $recList = DB::table('entrega as e')
                ->join('productos as p','p.id','=','e.producto_id')
                ->select('e.id','p.name_produc','e.cantidad','e.cantidad_recibido')
                ->where('e.requisicion_id', $req->id)
                ->whereNull('e.deleted_at')
                ->whereNull('e.cantidad_recibido')
                ->orderBy('e.id','asc')
                ->get();
        } else {
            // Solo traer recepciones pendientes de confirmación: la tabla 'recepcion' no tiene cantidad_recibida
            // así que consultamos los registros y usamos r.cantidad como referencia (la confirmación guarda cantidad en la tabla correspondiente)
            $recList = DB::table('recepcion as r')
                ->join('orden_compras as oc','oc.id','=','r.orden_compra_id')
                ->join('productos as p','p.id','=','r.producto_id')
                ->select('r.id','p.name_produc','r.cantidad')
                ->where('oc.requisicion_id', $req->id)
                ->whereNull('r.deleted_at')
                ->orderBy('r.id','asc')
                ->get();
        }
    @endphp
    <div id="modal-recibir-{{ $req->id }}" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4" data-tipo="{{ $usarEntrega ? 'entrega' : 'recepcion' }}">
        <div class="absolute inset-0 bg-black/50" onclick="toggleModal('modal-recibir-{{ $req->id }}')"></div>
        <div class="relative w-full max-w-2xl">
            <div class="bg-white rounded-2xl shadow-2xl max-h-[85vh] overflow-y-auto p-6 relative">
                <button onclick="toggleModal('modal-recibir-{{ $req->id }}')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl" aria-label="Cerrar">&times;</button>
                <h3 class="text-xl font-semibold mb-4">Recibir productos - Requisición #{{ $req->id }}</h3>
                @if(($recList ?? collect())->count())
                <table class="w-full text-sm border rounded overflow-hidden bg-white">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2 text-left">Producto</th>
                            <th class="p-2 text-center">Entregado</th>
                            <th class="p-2 text-center">Cantidad recibida</th>
                            {{-- <th class="p-2 text-center">Acción</th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recList as $r)
                        <tr class="border-t" data-item-id="{{ $r->id }}">
                            <td class="p-2">{{ $r->name_produc }}</td>
                            <td class="p-2 text-center">{{ $r->cantidad }}</td>
                            <td class="p-2 text-center">
                                <input type="number" min="0" max="{{ $r->cantidad }}" value="0" class="w-24 border rounded p-1 text-center recx-input">
                            </td>
                            {{-- <td class="p-2 text-center">
                                <button class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm" onclick="confirmarRecepcionItem({{ $r->id }}, this, '{{ $usarEntrega ? 'entrega' : 'recepcion' }}')">Guardar</button>
                            </td> --}}
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" class="px-4 py-2 border rounded" onclick="toggleModal('modal-recibir-{{ $req->id }}')">Cancelar</button>
                    <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded" onclick="guardarRecepcionesMasivo({{ $req->id }})">Guardar todo</button>
                </div>
                @else
                    <div class="text-gray-600">No hay registros para esta requisición.</div>
                @endif
            </div>
        </div>
    </div>
     @endforeach
    @endif
</div>

<script>
    function toggleModal(id){
        const modal = document.getElementById(id);
        if (!modal) return; // evitar errores si no existe
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

    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-"]').forEach(m => {
                if (!m.classList.contains('hidden')) m.classList.add('hidden');
            });
            document.body.style.overflow = '';
        }
    });

    // Filtro búsqueda
    document.getElementById('busqueda').addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaRequisiciones tbody tr').forEach(row => {
            row.dataset.match = row.textContent.toLowerCase().includes(filtro) ? '1' : '0';
        });
        showPage(1);
    });

    // Paginación
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

        // Ocultar todas y mostrar solo las de la página
        const allRows = Array.from(document.querySelectorAll('#tablaRequisiciones tbody tr'));
        allRows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        renderPagination(totalPages);
        const infoEl = document.getElementById('paginationInfoHist');
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

    // Inicializar paginación
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
    });

    // Función para cancelar requisición
    function cancelarRequisicion(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción cancelará la requisición",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, cancelar',
            cancelButtonText: 'No, volver'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar alerta de carga
                Swal.fire({
                    title: 'Procesando',
                    text: 'Cancelando requisición...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                
                // Enviar solicitud al servidor
                fetch(`/requisiciones/${id}/cancelar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Cancelada!',
                            text: 'La requisición ha sido cancelada',
                            confirmButtonColor: '#1e40af'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Ocurrió un error al cancelar la requisición',
                            confirmButtonColor: '#1e40af'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al procesar la solicitud',
                        confirmButtonColor: '#1e40af'
                    });
                });
            }
        });
    }

    // Función para reenviar requisición
    function reenviarRequisicion(id) {
        Swal.fire({
            title: '¿Reenviar requisición?',
            text: "Esta acción cambiará el estatus a 'Requisición creada'",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, reenviar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar alerta de carga
                Swal.fire({
                    title: 'Procesando',
                    text: 'Reenviando requisición...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading()
                    }
                });
                
                // Enviar solicitud al servidor
                fetch(`/requisiciones/${id}/reenviar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Reenviada!',
                            text: 'La requisición ha sido reenviada',
                            confirmButtonColor: '#1e40af'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Ocurrió un error al reenviar la requisición',
                            confirmButtonColor: '#1e40af'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al procesar la solicitud',
                        confirmButtonColor: '#1e40af'
                    });
                });
            }
        });
    }

    // Confirmar recepción de item
    const URL_CONFIRM_ENTREGA = "{{ route('entregas.confirmar') }}";
    const URL_CONFIRM_RECEPCION = "{{ route('recepciones.confirmar') }}";
    // Exponer objeto user de la sesión al JS y derivar una cadena para quien recibe
    const APP_SESSION_USER = {!! json_encode(session('user') ?? null) !!};
    const receptionUser = (APP_SESSION_USER && (APP_SESSION_USER.name || APP_SESSION_USER.email || APP_SESSION_USER.id)) ? (APP_SESSION_USER.name ?? APP_SESSION_USER.email ?? APP_SESSION_USER.id) : '';
    async function confirmarRecepcionItem(id, btn, tipo = 'recepcion'){
        const row = btn.closest('tr');
        const inp = row.querySelector('.recx-input');
        const max = parseInt(inp.max || '0', 10);
        let val = parseInt(inp.value || '0', 10);
        if (isNaN(val) || val < 0) val = 0;
        if (val > max) val = max;
        inp.value = val;

        const ask = await Swal.fire({
            title: 'Confirmar recepción',
            text: `¿Desea registrar ${val} como cantidad recibida?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        });
        if (!ask.isConfirmed) return;
        btn.disabled = true;
        try {
            const url = (tipo === 'entrega') ? URL_CONFIRM_ENTREGA : URL_CONFIRM_RECEPCION;
            const payload = (tipo === 'entrega')
                ? {
                    entrega_id: id,
                    cantidad: val,
                    reception_user: receptionUser,
                    reception_user_id: APP_SESSION_USER?.id ?? null,
                    user_id: APP_SESSION_USER?.id ?? null,
                    user_name: APP_SESSION_USER?.name ?? APP_SESSION_USER?.email ?? null
                }
                : {
                    recepcion_id: id,
                    cantidad: val,
                    reception_user: receptionUser,
                    reception_user_id: APP_SESSION_USER?.id ?? null,
                    user_id: APP_SESSION_USER?.id ?? null,
                    user_name: APP_SESSION_USER?.name ?? APP_SESSION_USER?.email ?? null
                };

            console.debug('confirmarRecepcionItem payload', payload);

            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });
            const data = await resp.json().catch(() => null);
            if (!resp.ok) {
                const msg = (data && (data.message || data.error)) ? (data.message || data.error) : 'Error al confirmar recepción';
                throw new Error(msg);
            }
            Swal.fire({icon:'success', title:'Guardado', text:'Cantidad recibida actualizada.'}).then(() => location.reload());
        } catch(e){
            console.error('confirmarRecepcionItem error', e);
            Swal.fire({icon:'error', title:'Error', text: e.message || 'Error desconocido'});
        } finally {
            btn.disabled = false;
        }
    }

    document.addEventListener('input', function(e){
        if (e.target && e.target.classList && e.target.classList.contains('recx-input')){
            const max = parseInt(e.target.max || '0', 10);
            let v = parseInt(e.target.value || '0', 10);
            if (isNaN(v) || v < 0) v = 0;
            if (v > max) v = max;
            e.target.value = v;
        }
     });

    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1e40af'
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#1e40af'
        });
    @endif

    function entSetAll(reqId, checked){
        document.querySelectorAll(`#ent-tbody-${reqId} .ent-row-chk`).forEach(ch => ch.checked = checked);
    }
    function entTglAll(reqId, el){ entSetAll(reqId, el.checked); }
    async function entGuardarEntrega(reqId){
        const tbody = document.getElementById(`ent-tbody-${reqId}`);
        if (!tbody) return;
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const items = [];
        rows.forEach(tr => {
            const chk = tr.querySelector('.ent-row-chk');
            const inp = tr.querySelector('.ent-cant-input');
            if (!chk || !inp) return;
            if (!chk.checked) return;
            const cant = parseInt(inp.value||'0',10);
            if (cant>0) items.push({ producto_id: Number(chk.dataset.productoId), ocp_id: Number(chk.dataset.ocpId), cantidad: cant, cantidad_recibido: null });
        });
        if (items.length === 0) { Swal.fire({icon:'info', title:'Sin selección', text:'Seleccione al menos un producto con cantidad > 0.'}); return; }
        try {
            const resp = await fetch(`/requisiciones/${reqId}/entregar`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept':'application/json', 'Content-Type':'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ requisicion_id: reqId, items, comentario: null })
              });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'Error al registrar entregas');
            Swal.fire({icon:'success', title:'Éxito', text:'Entregas registradas (estatus 8).'}).then(()=> location.reload());
        } catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
        }
    }

    async function guardarRecepcionesMasivo(reqId){
        const modal = document.getElementById(`modal-recibir-${reqId}`);
        if (!modal) return;
        const tipo = modal.dataset.tipo === 'entrega' ? 'entrega' : 'recepcion';
        const url = (tipo === 'entrega') ? URL_CONFIRM_ENTREGA : URL_CONFIRM_RECEPCION;
        const rows = Array.from(modal.querySelectorAll('tbody tr[data-item-id]'));
        if (rows.length === 0) { Swal.fire({icon:'info', title:'Sin registros', text:'No hay filas para guardar.'}); return; }

        const items = rows.map(tr => {
            const id = parseInt(tr.dataset.itemId, 10);
            const inp = tr.querySelector('.recx-input');
            const max = parseInt(inp?.max || '0', 10);
            let val = parseInt(inp?.value || '0', 10);
            if (isNaN(val) || val < 0) val = 0;
            if (val > max) val = max;
            if (inp) inp.value = val;
            return { id, cantidad: val };
        });

        const total = items.length;
        const confirm = await Swal.fire({
            title: 'Guardar todas las recepciones',
            text: `Se actualizarán ${total} registro(s). ¿Desea continuar?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, guardar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirm.isConfirmed) return;

        Swal.fire({ title: 'Guardando', text: 'Procesando recepciones...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            // Enviar todos los items en una sola petición para que el backend guarde recepción y usuario de sesión
            const payload = {
                tipo,
                items,
                reception_user: receptionUser,
                reception_user_id: APP_SESSION_USER?.id ?? null
            };

            const res = await fetch('/recepciones/confirmar-masivo', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });

            let result = null;
            try { result = await res.json(); } catch(e) { result = null; }

            if (!res.ok) {
                console.error('guardarRecepcionesMasivo error response', result);
                Swal.fire({icon:'error', title:'Error', text: result?.message || 'Error al guardar recepciones'});
            } else {
                Swal.fire({icon:'success', title:'¡Listo!', text:`Se han guardado todas las recepciones.`}).then(() => location.reload());
            }
        } catch(e){
            console.error('guardarRecepcionesMasivo error', e);
            Swal.fire({icon:'error', title:'Error', text:e.message || 'Error desconocido'});
        }
    }
</script>
@endsection

<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const bar = document.getElementById('paginationBar');
    if (bar && !document.getElementById('paginationInfoHist')) {
        const leftBox = bar.querySelector('div.text-sm');
        if (leftBox) {
            const span = document.createElement('span');
            span.id = 'paginationInfoHist';
            span.className = 'ml-4 text-sm text-gray-600';
            leftBox.appendChild(span);
        }
    }
});
</script>
