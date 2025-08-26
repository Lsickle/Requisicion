@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-6xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">
        Panel de Aprobación de Requisiciones
    </h1>

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
                @forelse ($requisiciones as $req)
                <tr class="border-b">
                    <td class="px-4 py-2">{{ $req->id }}</td>
                    <td class="px-4 py-2">{{ $req->detail_requisicion }}</td>
                    <td class="px-4 py-2">{{ $req->prioridad_requisicion }}</td>
                    <td class="px-4 py-2">${{ number_format($req->amount_requisicion, 2) }}</td>
                    <td class="px-4 py-2">
                        {{ $req->ultimoEstatus->estatus->status_name ?? 'Pendiente' }}
                    </td>
                    <td class="px-4 py-2">
                        <button class="view-details bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600"
                            data-id="{{ $req->id }}">
                            Ver
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4">No hay requisiciones pendientes</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="detailsModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center">
    <div class="bg-white w-full max-w-2xl p-6 rounded-lg shadow-lg">
        <h2 class="text-xl font-bold mb-4">Detalles de la Requisición</h2>

        <div id="requisicion-details"></div>

        <form id="statusForm" class="mt-4">
            @csrf
            <input type="hidden" name="requisicion_id" id="requisicion_id">

            <label class="block mb-2">Cambiar estatus:</label>
            <select name="estatus_id" id="estatus_id" class="border rounded w-full p-2 mb-4">
                @foreach($estatusOptions as $id => $nombre)
                <option value="{{ $id }}">{{ $nombre }}</option>
                @endforeach
            </select>

            <label class="block mb-2">Comentarios:</label>
            <textarea name="comentarios" id="comentarios" rows="3" class="border rounded w-full p-2"></textarea>

            <div class="flex justify-end mt-4">
                <button type="button" id="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">
                    Cerrar
                </button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {

    function loadStatistics() {
        $.ajax({
            url: '{{ route("requisiciones.estadisticas") }}',
            method: 'GET',
            success: function(response) {
                $('#pendientes-count').text(response['Pendiente'] || 0);
                $('#aprobadas-count').text(response['Aprobado'] || 0);
                $('#rechazadas-count').text(response['Rechazado'] || 0);
                $('#revision-count').text(response['En revisión'] || 0);
            }
        });
    }

    loadStatistics();

    $('.view-details').click(function() {
        var requisicionId = $(this).data('id');
        
        $.ajax({
            url: '{{ route("requisiciones.detalles", ":id") }}'.replace(':id', requisicionId),
            method: 'GET',
            success: function(response) {
                $('#requisicion_id').val(requisicionId);

                let detalles = `
                    <p><strong>Justificación:</strong> ${response.requisicion.justify_requisicion}</p>
                    <p><strong>Detalle:</strong> ${response.requisicion.detail_requisicion}</p>
                    <p><strong>Monto:</strong> $${response.requisicion.amount_requisicion}</p>
                    <h3 class="font-semibold mt-3">Productos</h3>
                    <ul class="list-disc pl-5">
                        ${response.productos.map(p => `<li>${p.cantidad} ${p.unidad} de ${p.nombre}</li>`).join('')}
                    </ul>
                    <h3 class="font-semibold mt-3">Historial</h3>
                    <ul class="list-disc pl-5">
                        ${response.historial.map(h => `<li>${h.estatus} (${h.fecha}) - ${h.comentarios}</li>`).join('')}
                    </ul>
                `;
                $('#requisicion-details').html(detalles);
                $('#detailsModal').removeClass('hidden');
            }
        });
    });

    $('#closeModal').click(function() {
        $('#detailsModal').addClass('hidden');
    });

    $('#statusForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var requisicionId = $('#requisicion_id').val();
        
        $.ajax({
            url: '{{ route("requisiciones.estatus.update", ":id") }}'.replace(':id', requisicionId),
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            success: function(response) {
                alert(response.message);
                $('#detailsModal').addClass('hidden');
                loadStatistics();
                location.reload();
            },
            error: function(xhr) {
                alert(xhr.responseJSON.message || 'Error al actualizar el estatus.');
            }
        });
    });
});
</script>
@endsection