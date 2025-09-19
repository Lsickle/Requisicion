@extends('layouts.app')

@section('title', 'Requisiciones Aprobadas para Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Requisiciones Aprobadas para Orden de Compra</h1>
            </div>

            @php
                $permitidos = [4,5,7,8,12];
                $requisicionesFiltradas = ($requisiciones ?? collect())->filter(function($r) use ($permitidos) {
                    $hist = $r->estatusHistorial ?? collect();
                    $ultimo = $hist->sortByDesc('created_at')->first();
                    $ultimoId = $ultimo->estatus_id ?? null;
                    return in_array($ultimoId, $permitidos, true);
                });
            @endphp

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
                                    @if($isComplete && $estatusActivo !== 10)
                                    <button type="button" class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition ml-2" onclick="completarReq({{ $req->id }})">
                                        Completar requisición
                                    </button>
                                    @endif
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
                <div class="md:hidden space-y-4">
                    @forelse($requisicionesFiltradas as $req)
                    <div class="bg-white rounded-lg shadow p-4">
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

            <!-- Botón volver fijo abajo -->
            <div class="mt-6 text-center">
                <a href="{{ route('requisiciones.menu') }}"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg shadow transition">
                    ← Volver
                </a>
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
                        // Obtener la distribución por centros para este producto y requisición
                        $distribucion = DB::table('centro_producto')
                            ->where('requisicion_id', $req->id)
                            ->where('producto_id', $prod->id)
                            ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                            ->select('centro.name_centro', 'centro_producto.amount')
                            ->get();
                        @endphp

                        <tr>
                            <td class="px-4 py-3 border">{{ $prod->name_produc }}</td>
                            <td class="px-4 py-3 border text-center font-semibold">{{ $prod->pivot->pr_amount }}</td>
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
        </div>
    </div>
</div>
@endforeach

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
        if (event.target.classList.contains('fixed')) {
            event.target.classList.add('hidden');
            event.target.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
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
</script>
@endsection