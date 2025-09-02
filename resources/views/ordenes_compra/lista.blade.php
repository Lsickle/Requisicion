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
                                <th class="px-4 py-2 text-left">Monto</th>
                                <th class="px-4 py-2 text-left">Solicitante</th>
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
                                <td class="px-4 py-2">{{ $req->name_user }}</td>
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
                                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
                                    <div class="flex-1 overflow-y-auto p-6 relative">
                                        <button onclick="toggleModal('modal-{{ $req->id }}')" 
                                            class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 font-bold text-2xl">&times;</button>

                                        <h2 class="text-2xl font-bold mb-4">Requisición #{{ $req->id }}</h2>

                                        <div class="grid grid-cols-2 gap-4 mb-4">
                                            <div>
                                                <p><strong>Solicitante:</strong> {{ $req->name_user }}</p>
                                                <p><strong>Email:</strong> {{ $req->email_user }}</p>
                                                <p><strong>Operación:</strong> {{ $req->operacion_user }}</p>
                                            </div>
                                            <div>
                                                <p><strong>Prioridad:</strong> {{ $req->prioridad_requisicion }}</p>
                                                <p><strong>Recobrable:</strong> {{ $req->Recobrable }}</p>
                                                <p><strong>Monto Total:</strong> ${{ number_format($req->amount_requisicion,2) }}</p>
                                            </div>
                                        </div>

                                        <p><strong>Detalle:</strong> {{ $req->detail_requisicion }}</p>
                                        <p><strong>Justificación:</strong> {{ $req->justify_requisicion }}</p>

                                        <h3 class="text-xl font-semibold mt-4">Productos</h3>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full border border-gray-200 mt-2">
                                                <thead class="bg-gray-100">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left">Producto</th>
                                                        <th class="px-4 py-2 text-left">Cantidad</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($req->productos as $prod)
                                                        <tr>
                                                            <td class="px-4 py-2 border">{{ $prod->name_produc }}</td>
                                                            <td class="px-4 py-2 border">{{ $prod->pivot->pr_amount }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <h3 class="text-xl font-semibold mt-4">Historial de Estatus</h3>
                                        <ul class="list-disc pl-5">
                                            @foreach($req->estatusHistorial as $hist)
                                                <li>{{ $hist->estatus->status_name ?? 'Iniciada' }} - {{ $hist->created_at->format('d/m/Y H:i') }}</li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    <!-- Botón Crear Orden de Compra -->
                                    <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                                        <a href="{{ route('ordenes-compra.create-from-requisicion', $req->id) }}" 
                                           class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                                            Crear Orden de Compra
                                        </a>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-500">No hay requisiciones aprobadas para orden de compra</td>
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
                        <p><strong>Solicitante:</strong> {{ $req->name_user }}</p>
                        <div class="mt-3">
                            <button onclick="toggleModal('modal-{{ $req->id }}')" 
                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition">
                                Ver
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
@endsection

@section('scripts')
<script>
function toggleModal(id){
    const modal = document.getElementById(id);
    modal.classList.toggle('hidden');
    modal.classList.toggle('flex');
}
</script>
@endsection