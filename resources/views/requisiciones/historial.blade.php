@extends('layouts.app')

@section('title', 'Historial de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Historial de Requisiciones</h1>

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

                    <!-- Fecha -->
                    <td class="p-3">{{ $req->created_at->format('d/m/Y H:i') }}</td>

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
                        $nombreEstatus = 'Pendiente';
                        $colorEstatus = 'bg-gray-500';
                        $ultimoEstatusId = null;

                        if ($req->estatusHistorial && $req->estatusHistorial->count() > 0) {
                        $ultimoEstatus = $req->estatusHistorial->sortByDesc('created_at')->first();
                        $ultimoEstatusId = $ultimoEstatus->estatus_id;

                        switch($ultimoEstatusId) {
                        case 1: $nombreEstatus = 'Iniciada'; $colorEstatus = 'bg-blue-600'; break;
                        case 2: case 3: case 4: $nombreEstatus = 'En revisión'; $colorEstatus = 'bg-yellow-500'; break;
                        case 5: $nombreEstatus = 'Orden generada'; $colorEstatus = 'bg-purple-600'; break;
                        case 6: case 9: $nombreEstatus = 'Cancelada/Rechazada'; $colorEstatus = 'bg-red-600'; break;
                        case 7: case 8: $nombreEstatus = 'Recibido'; $colorEstatus = 'bg-indigo-600'; break;
                        case 10: $nombreEstatus = 'Completado'; $colorEstatus = 'bg-green-600'; break;
                        case 11: $nombreEstatus = 'Corregir'; $colorEstatus = 'bg-orange-500'; break;
                        }
                        }
                        @endphp
                        <span class="px-3 py-1 text-xs font-semibold rounded-full text-white {{ $colorEstatus }}">
                            {{ $nombreEstatus }}
                        </span>
                    </td>

                    <!-- Acciones -->
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2">
                            <button onclick="toggleModal('modal-{{ $req->id }}')"
                                class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition flex items-center gap-1">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            @if($ultimoEstatusId == 11)
                            <a href="{{ route('requisiciones.edit', $req->id) }}"
                                class="bg-yellow-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-yellow-700 transition">
                                Editar
                            </a>
                            @endif
                            <a href="{{ route('requisiciones.pdf', $req->id) }}"
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
                        <div><span class="font-medium">Fecha:</span> {{ $req->created_at->format('d/m/Y H:i') }}</div>
                        <div><span class="font-medium">Prioridad:</span> {{ ucfirst($req->prioridad_requisicion) }}
                        </div>
                        <div><span class="font-medium">Recobrable:</span> {{ $req->Recobrable }}</div>
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
        let filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaRequisiciones tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(filtro) ? '' : 'none';
        });
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
</script>
@endsection