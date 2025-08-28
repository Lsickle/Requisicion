@extends('layouts.app')

@section('title', 'Historial de Requisiciones')

@section('content')
<x-sidebar />

<div class="max-w-6xl mx-auto p-6 mt-20 bg-gray-100 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">Historial de Requisiciones</h1>

    <!-- 🔍 Barra de búsqueda -->
    <div class="mb-4 flex justify-between items-center">
        <input type="text" id="busqueda" placeholder="Buscar requisición..."
            class="border px-3 py-2 rounded w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300">
    </div>

    @if($requisiciones->isEmpty())
    <p class="text-gray-500">No has realizado ninguna requisición aún.</p>
    @else
    <div class="overflow-x-auto">
        <table id="tablaRequisiciones" class="w-full border border-gray-200 rounded-lg bg-white">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr class="hidden md:table-row">
                    <th class="p-3">Fecha</th>
                    <th class="p-3">Prioridad</th>
                    <th class="p-3">Recobrable</th>
                    <th class="p-3">Productos</th>
                    <th class="p-3">Estatus</th>
                    <th class="p-3">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requisiciones as $req)
                <tr
                    class="border-t md:table-row flex flex-col md:flex-row mb-4 md:mb-0 p-3 md:p-0 bg-white rounded-lg md:rounded-none shadow md:shadow-none">

                    <!-- Fecha -->
                    <td class="p-3 w-full md:w-auto flex justify-between md:block">
                        <div class="md:hidden flex justify-between w-full">
                            <span class="font-medium">Fecha:</span>
                            <span>{{ $req->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="hidden md:block">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                    </td>

                    <td class="p-3 hidden md:table-cell capitalize">
                        <span class="px-2 py-1 rounded text-white 
                            @if($req->prioridad_requisicion=='alta') bg-red-600
                            @elseif($req->prioridad_requisicion=='media') bg-yellow-500
                            @else bg-green-600 @endif">
                            {{ $req->prioridad_requisicion }}
                        </span>
                    </td>

                    <td class="p-3 hidden md:table-cell">{{ $req->Recobrable }}</td>

                    <td class="p-3 hidden md:table-cell">
                        @foreach($req->productos as $prod)
                        <div class="mb-1">{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})</div>
                        @endforeach
                    </td>

                    <td class="p-3 hidden md:table-cell">
                        @php
                        $nombreEstatus = 'Pendiente';
                        $colorEstatus = 'bg-gray-500';

                        // Verificación más robusta para evitar el error
                        if (isset($req->ultimoEstatus) && 
                            is_object($req->ultimoEstatus) && 
                            isset($req->ultimoEstatus->estatus) &&
                            is_object($req->ultimoEstatus->estatus)) {
                            
                            $nombreEstatus = $req->ultimoEstatus->estatus->status_name;
                            
                        } elseif (isset($req->estatusHistorial) && 
                                  $req->estatusHistorial->count() > 0 &&
                                  isset($req->estatusHistorial->first()->estatus) &&
                                  is_object($req->estatusHistorial->first()->estatus)) {
                            
                            $ultimoHistorial = $req->estatusHistorial->first();
                            $nombreEstatus = $ultimoHistorial->estatus->status_name ?? 'Pendiente';
                        }

                        // Definir colores según el nombre del estatus
                        if (str_contains($nombreEstatus, 'Aprobación')) {
                            $colorEstatus = 'bg-yellow-500';
                        } elseif ($nombreEstatus === 'Completado') {
                            $colorEstatus = 'bg-green-600';
                        } elseif (in_array($nombreEstatus, ['Rechazado', 'Cancelado'])) {
                            $colorEstatus = 'bg-red-600';
                        } elseif ($nombreEstatus === 'Iniciada') {
                            $colorEstatus = 'bg-blue-600';
                        } elseif ($nombreEstatus === 'Pendiente') {
                            $colorEstatus = 'bg-gray-500';
                        } else {
                            $colorEstatus = 'bg-gray-600';
                        }
                        @endphp

                        <span class="px-2 py-1 rounded text-white {{ $colorEstatus }}">
                            {{ $nombreEstatus }}
                        </span>
                    </td>

                    <!-- Acciones -->
                    <td class="p-3 w-full flex justify-end gap-2">
                        <button onclick="toggleModal('modal-{{ $req->id }}')"
                            class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm">
                            Ver
                        </button>
                        
                        <!-- Botón para editar si está en estatus "Corregir" -->
                        @php
                            $ultimoEstatusId = $req->ultimoEstatus->estatus_id ?? null;
                        @endphp
                        @if($ultimoEstatusId == 11) <!-- 11 = Corregir -->
                        <a href="{{ route('requisiciones.edit', $req->id) }}"
                            class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 text-sm">
                            Editar
                        </a>
                        @endif
                        
                        <a href="{{ route('requisiciones.pdf', $req->id) }}"
                            class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">
                            PDF
                        </a>
                    </td>
                </tr>

                <!-- Modal -->
                <div id="modal-{{ $req->id }}"
                    class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
                    <div
                        class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto p-6 relative">

                        <!-- Cerrar -->
                        <button onclick="toggleModal('modal-{{ $req->id }}')"
                            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 font-bold text-2xl">&times;</button>

                        <h2 class="text-2xl font-bold mb-4">Requisición #{{ $req->id }}</h2>

                        <!-- Información General -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div><strong>Solicitante:</strong> {{ $req->name_user ?? $req->user->name ?? 'Desconocido'
                                }}</div>
                            <div><strong>Fecha:</strong> {{ $req->created_at->format('d/m/Y H:i') }}</div>
                            <div><strong>Prioridad:</strong> {{ ucfirst($req->prioridad_requisicion) }}</div>
                            <div><strong>Recobrable:</strong> {{ $req->Recobrable }}</div>
                        </div>

                        <!-- Botón Ver Estatus -->
                        <div class="mb-4">
                            <a href="{{ route('requisiciones.estatus', $req->id) }}"
                                class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                                Ver Estatus
                            </a>
                        </div>

                        <!-- Detalle y Justificación -->
                        <div class="mb-4">
                            <strong>Detalle:</strong>
                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $req->detail_requisicion }}</p>
                        </div>
                        <div class="mb-4">
                            <strong>Justificación:</strong>
                            <p class="mt-1 p-2 bg-gray-50 rounded">{{ $req->justify_requisicion }}</p>
                        </div>

                        <!-- Productos y Centros -->
                        <h3 class="text-xl font-semibold mb-2">Productos</h3>
                        <ul class="space-y-3">
                            @foreach($req->productos as $prod)
                            <li class="border p-3 rounded-lg shadow-sm bg-gray-50">
                                <div class="font-medium mb-1">{{ $prod->name_produc }} ({{ $prod->pivot->pr_amount }})
                                </div>
                                <div><strong>Centros:</strong></div>
                                <ul class="ml-4 list-disc">
                                    @if(isset($prod->distribucion_centros) && $prod->distribucion_centros->count() > 0)
                                    @foreach($prod->distribucion_centros as $centro)
                                    <li>{{ $centro->name_centro }} ({{ $centro->amount }})</li>
                                    @endforeach
                                    @else
                                    <li>No hay centros asignados</li>
                                    @endif
                                </ul>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<script>
    function toggleModal(id){
        const modal = document.getElementById(id);
        modal.classList.toggle('hidden');
        modal.classList.toggle('flex');
    }

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