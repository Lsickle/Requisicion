@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-6xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">Panel de Aprobación de Requisiciones</h1>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="font-semibold">Pendientes</h3>
            <p id="pendientes-count" class="text-2xl font-bold text-yellow-600">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="font-semibold">Aprobadas</h3>
            <p id="aprobadas-count" class="text-2xl font-bold text-green-600">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="font-semibold">Rechazadas</h3>
            <p id="rechazadas-count" class="text-2xl font-bold text-red-600">0</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="font-semibold">En revisión</h3>
            <p id="revision-count" class="text-2xl font-bold text-blue-600">0</p>
        </div>
    </div>

    <!-- Tabla de requisiciones -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full table-auto border-collapse">
            <thead class="bg-gray-200 text-gray-700">
                <tr>
                    <th class="px-4 py-2">#</th>
                    <th class="px-4 py-2">Detalle</th>
                    <th class="px-4 py-2">Prioridad</th>
                    <th class="px-4 py-2">Monto</th>
                    <th class="px-4 py-2">Estatus</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisiciones as $req)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $req->id }}</td>
                    <td class="px-4 py-2">{{ $req->detail_requisicion }}</td>
                    <td class="px-4 py-2">{{ $req->prioridad_requisicion }}</td>
                    <td class="px-4 py-2">${{ number_format($req->amount_requisicion,2) }}</td>
                    <td class="px-4 py-2">{{ $req->ultimoEstatus->estatus->status_name ?? 'Pendiente' }}</td>
                    <td class="px-4 py-2">
                        <button onclick="toggleModal('modal-{{ $req->id }}')" 
                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                            Ver
                        </button>
                    </td>
                </tr>

                <!-- Modal -->
                <div id="modal-{{ $req->id }}" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">
                        <button onclick="toggleModal('modal-{{ $req->id }}')" 
                            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 font-bold text-2xl">&times;</button>

                        <h2 class="text-2xl font-bold mb-4">Requisición #{{ $req->id }}</h2>

                        <p><strong>Detalle:</strong> {{ $req->detail_requisicion }}</p>
                        <p><strong>Justificación:</strong> {{ $req->justify_requisicion }}</p>
                        <p><strong>Monto:</strong> ${{ number_format($req->amount_requisicion,2) }}</p>

                        <h3 class="text-xl font-semibold mt-4">Productos</h3>
                        <ul class="list-disc pl-5">
                            @foreach($req->productos as $prod)
                                <li>{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})</li>
                            @endforeach
                        </ul>

                        <h3 class="text-xl font-semibold mt-4">Historial</h3>
                        <ul class="list-disc pl-5">
                            @foreach($req->estatusHistorial as $hist)
                                <li>{{ $hist->estatus->status_name ?? 'Iniciada' }} - {{ $hist->created_at->format('d/m/Y H:i') }} - {{ $hist->estatus }}</li>
                            @endforeach
                        </ul>

                        <!-- Botones Aprobar / Rechazar -->
                        <div class="flex justify-end gap-2 mt-6">
                            @php
                                $estatusAprobar = $estatusOptions->keys()->first(); // ID según rol
                                $estatusRechazar = 4; // ID genérico para "rechazado"
                            @endphp
                            <button class="status-btn bg-green-600 text-white px-4 py-2 rounded" 
                                data-id="{{ $req->id }}" data-estatus="{{ $estatusAprobar }}">
                                Aprobar
                            </button>
                            <button class="status-btn bg-red-600 text-white px-4 py-2 rounded" 
                                data-id="{{ $req->id }}" data-estatus="{{ $estatusRechazar }}">
                                Rechazar
                            </button>
                        </div>

                    </div>
                </div>

                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">No hay requisiciones pendientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleModal(id){
    const modal = document.getElementById(id);
    modal.classList.toggle('hidden');
    modal.classList.toggle('flex');
}

$(document).ready(function(){
    // Estadísticas
    $.ajax({
        url: '{{ route("requisiciones.estadisticas") }}',
        method: 'GET',
        success: function(resp){
            $('#pendientes-count').text(resp['Pendiente'] || 0);
            $('#aprobadas-count').text(resp['Aprobado'] || 0);
            $('#rechazadas-count').text(resp['Rechazado'] || 0);
            $('#revision-count').text(resp['En revisión'] || 0);
        }
    });

    // Aprobar / Rechazar
    $(document).on('click', '.status-btn', function(){
        const requisicionId = $(this).data('id');
        const estatusId = $(this).data('estatus');

        $.ajax({
            url: '{{ route("requisiciones.estatus.update", ":id") }}'.replace(':id', requisicionId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                estatus_id: estatusId,
                comentarios: ''
            },
            success: function(resp){
                alert(resp.message || 'Estatus actualizado');
                toggleModal('modal-' + requisicionId);
                location.reload();
            },
            error: function(xhr){
                alert(xhr.responseJSON?.message || 'Error al actualizar estatus');
            }
        });
    });
});
</script>
@endsection
