@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6">

            <!-- Encabezado con botón volver -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Panel de Aprobación de Requisiciones</h1>
                <a href="{{ route('requisiciones.menu') }}" 
                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow transition">
                    ← Volver
                </a>
            </div>

            <!-- Tabla en escritorio -->
            <div class="bg-white rounded-lg shadow overflow-x-auto hidden md:block">
                <table class="min-w-full table-auto border-collapse">
                    <thead class="bg-gray-200 text-gray-700 text-sm uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left">#</th>
                            <th class="px-4 py-2 text-left">Detalle</th>
                            <th class="px-4 py-2 text-left">Prioridad</th>
                            <th class="px-4 py-2 text-left">Monto</th>
                            <th class="px-4 py-2 text-left">Estatus</th>
                            <th class="px-4 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requisiciones as $req)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $req->id }}</td>
                            <td class="px-4 py-2">{{ $req->detail_requisicion }}</td>
                            <td class="px-4 py-2">{{ $req->prioridad_requisicion }}</td>
                            <td class="px-4 py-2 font-semibold">${{ number_format($req->amount_requisicion,2) }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs font-medium
                                    {{ ($req->ultimoEstatus->estatus->status_name ?? 'Pendiente') === 'Aprobado' ? 'bg-green-100 text-green-700' :
                                       (($req->ultimoEstatus->estatus->status_name ?? 'Pendiente') === 'Rechazado' ? 'bg-red-100 text-red-700' :
                                       'bg-yellow-100 text-yellow-700') }}">
                                    {{ $req->ultimoEstatus->estatus->status_name ?? 'Pendiente' }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button onclick="toggleModal('modal-{{ $req->id }}')" 
                                    class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">
                                    Ver
                                </button>
                            </td>
                        </tr>

                        <!-- Modal -->
                        <div id="modal-{{ $req->id }}" 
                             class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
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
                                        <li>{{ $hist->estatus->status_name ?? 'Iniciada' }} - {{ $hist->created_at->format('d/m/Y H:i') }}</li>
                                    @endforeach
                                </ul>

                                <!-- Botones Aprobar / Rechazar -->
                                <div class="flex justify-end gap-2 mt-6">
                                    @php
                                        $estatusAprobar = $estatusOptions->keys()->first();
                                        $estatusRechazar = 9;
                                    @endphp
                                    <button class="status-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition" 
                                        data-id="{{ $req->id }}" data-estatus="{{ $estatusAprobar }}" data-action="aprobar">
                                        Aprobar
                                    </button>
                                    <button class="status-btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition" 
                                        data-id="{{ $req->id }}" data-estatus="{{ $estatusRechazar }}" data-action="rechazar">
                                        Rechazar
                                    </button>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-gray-500">No hay requisiciones pendientes</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Vista móvil como tarjetas -->
            <div class="md:hidden space-y-4">
                @forelse($requisiciones as $req)
                <div class="bg-white rounded-lg shadow p-4">
                    <h2 class="font-bold text-lg mb-2">#{{ $req->id }} - {{ $req->detail_requisicion }}</h2>
                    <p><strong>Prioridad:</strong> {{ $req->prioridad_requisicion }}</p>
                    <p><strong>Monto:</strong> ${{ number_format($req->amount_requisicion,2) }}</p>
                    <p><strong>Estatus:</strong> {{ $req->ultimoEstatus->estatus->status_name ?? 'Pendiente' }}</p>
                    <div class="mt-3">
                        <button onclick="toggleModal('modal-{{ $req->id }}')" 
                            class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">
                            Ver
                        </button>
                    </div>
                </div>
                @empty
                <p class="text-center text-gray-500">No hay requisiciones pendientes</p>
                @endforelse
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- SweetAlert2 -->
@section('scripts')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleModal(id){
    const modal = document.getElementById(id);
    modal.classList.toggle('hidden');
    modal.classList.toggle('flex');
}

// Función para aprobar/rechazar
document.querySelectorAll('.status-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const requisicionId = this.dataset.id;
        const estatusId = parseInt(this.dataset.estatus);
        const accion = this.dataset.action;

        console.log('Botón clickeado:', {requisicionId, estatusId, accion});

        if (accion === "rechazar") {
            // Verificar si es área de compras (requiere comentario)
            @if($role === 'Area de compras')
                console.log('Usuario es Area de compras - solicitando comentario');
                // Pedir comentario obligatorio solo para área de compras
                Swal.fire({
                    title: "Motivo de rechazo",
                    input: "textarea",
                    inputPlaceholder: "Escribe el motivo...",
                    inputAttributes: { 'aria-label': 'Motivo de rechazo' },
                    showCancelButton: true,
                    confirmButtonText: "Rechazar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#dc2626",
                    cancelButtonColor: "#6b7280",
                    inputValidator: (value) => {
                        if (!value || value.trim() === '') return "Debes escribir un motivo de rechazo";
                    }
                }).then((result) => {
                    console.log('Resultado de SweetAlert:', result);
                    if (result.isConfirmed) {
                        console.log('Comentario ingresado:', result.value);
                        confirmarCambioEstatus(requisicionId, estatusId, result.value);
                    }
                });
            @else
                console.log('Usuario es Gerencia/Financiero - confirmación simple');
                // Para Gerencia y Gerente financiero: solo confirmación
                Swal.fire({
                    title: `¿Seguro que deseas rechazar la requisición #${requisicionId}?`,
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Sí, rechazar",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#dc2626",
                    cancelButtonColor: "#6b7280"
                }).then((result) => {
                    if (result.isConfirmed) {
                        confirmarCambioEstatus(requisicionId, estatusId, null);
                    }
                });
            @endif
        } else {
            // aprobar
            console.log('Acción de aprobar');
            Swal.fire({
                title: `¿Seguro que deseas ${accion} la requisición #${requisicionId}?`,
                icon: "success",
                showCancelButton: true,
                confirmButtonText: `Sí, ${accion}`,
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
    // Crear objeto con los datos a enviar
    if (!comentario) {
        const input = document.getElementById(`comentario-${requisicionId}`);
        if (input) {
            comentario = input.value.trim();
        }
    }

    const data = {
        estatus_id: estatusId,
        comentario: comentario
    };

    console.log('Enviando datos al servidor:', JSON.stringify(data, null, 2));

    fetch('{{ route("requisiciones.estatus.update", ":id") }}'.replace(':id', requisicionId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Respuesta del servidor - status:', response.status);
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        console.log('Respuesta del servidor - data:', data);
        if (data.success) {
            Swal.fire("Éxito", data.message || "Estatus actualizado", "success")
                .then(() => location.reload());
        } else {
            Swal.fire("Error", data.message || "No se pudo actualizar el estatus", "error");
        }
    })
    .catch(error => {
        console.error('Error en la petición:', error);
        Swal.fire("Error", "No se pudo actualizar el estatus: " + error.message, "error");
    });
}

</script>
@endsection