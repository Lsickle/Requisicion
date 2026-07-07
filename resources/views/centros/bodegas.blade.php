@extends('layouts.app')

@section('title', 'Gestión de Bodegas')

@section('content')
<x-sidebar />
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white/95 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-white/20 text-white flex items-center justify-center">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div>
                            <h2 class="text-white text-2xl font-bold">Gestión de Bodegas</h2>
                            <p class="text-emerald-100 mt-1">Administra bodegas y sus operaciones (subcentros)</p>
                        </div>
                    </div>
                    <button onclick="openBodegaModal()" class="inline-flex items-center px-4 py-2.5 bg-white text-emerald-700 rounded-lg hover:bg-emerald-50 shadow-md transition-colors font-medium text-sm">
                        <i class="fas fa-plus mr-2"></i>Nueva Bodega
                    </button>
                </div>
            </div>

            @if(session('success'))
            <div class="mx-6 mt-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded-lg">
                <i class="fas fa-check mr-2"></i>{{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="mx-6 mt-4 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg">
                <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
            </div>
            @endif

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="bodegas-grid">
                    @forelse($centros as $centro)
                    <div class="bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-lg hover:border-emerald-300 transition-all duration-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-gradient-to-r from-slate-50 to-emerald-50/30">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <div class="h-12 w-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-warehouse text-xl"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $centro->name_centro }}</h3>
                                        <p class="text-xs text-gray-500">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-medium">
                                                <i class="fas fa-layer-group mr-1"></i>{{ $centro->subcentros->count() }} operaciones
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button onclick="openOperacionModal({{ $centro->id }}, '{{ addslashes($centro->name_centro) }}')" class="p-2 bg-teal-100 text-teal-700 rounded-lg hover:bg-teal-200 transition-colors" title="Agregar operación">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button onclick="openBodegaModal({{ $centro->id }}, '{{ addslashes($centro->name_centro) }}')" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <h4 class="text-sm font-semibold text-gray-600 mb-3 flex items-center gap-2">
                                <i class="fas fa-list text-emerald-500"></i>Operaciones asignadas
                            </h4>
                            <div class="space-y-2" id="ops-{{ $centro->id }}">
                                @forelse($centro->subcentros as $sub)
                                <div class="flex items-center justify-between p-2 bg-slate-50 rounded-lg border border-slate-200">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-angle-right text-emerald-500 text-xs"></i>
                                        <span class="text-sm font-medium text-gray-700">{{ $sub->name_subcentro }}</span>
                                    </div>
                                    <button onclick="openOperacionModal({{ $centro->id }}, '{{ addslashes($centro->name_centro) }}', {{ $sub->id }}, '{{ addslashes($sub->name_subcentro) }}')" class="p-1.5 text-gray-400 hover:text-yellow-600 hover:bg-yellow-50 rounded transition-colors" title="Editar">
                                        <i class="fas fa-pen text-xs"></i>
                                    </button>
                                </div>
                                @empty
                                <div class="text-sm text-gray-400 text-center py-4">
                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                    Sin operaciones
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-warehouse text-6xl text-gray-300 mb-4 block"></i>
                        <p class="text-gray-500">No hay bodegas creadas</p>
                        <button onclick="openBodegaModal()" class="mt-4 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                            <i class="fas fa-plus mr-2"></i>Crear primera bodega
                        </button>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Bodega -->
<div id="modal-bodega" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex items-center justify-between">
            <h3 id="bodega-modal-titulo" class="text-white font-bold text-lg">Nueva Bodega</h3>
            <button onclick="closeBodegaModal()" class="text-white/80 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form action="{{ route('centros.bodegas.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="bodega-id" name="id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Bodega *</label>
                <input type="text" id="bodega-nombre" name="name_centro" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeBodegaModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Operación -->
<div id="modal-operacion" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 id="operacion-modal-titulo" class="text-white font-bold text-lg">Nueva Operación</h3>
                <p id="operacion-bodega-nombre" class="text-teal-100 text-xs"></p>
            </div>
            <button onclick="closeOperacionModal()" class="text-white/80 hover:text-white"><i class="fas fa-times text-xl"></i></button>
        </div>
        <form action="{{ route('centros.bodegas.subcentro.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" id="operacion-bodega-id" name="bodega_id">
            <input type="hidden" id="operacion-id" name="id">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de la Operación *</label>
                <input type="text" id="operacion-nombre" name="name_subcentro" required class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeOperacionModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openBodegaModal(id, nombre) {
    document.getElementById('bodega-id').value = id || '';
    document.getElementById('bodega-nombre').value = nombre || '';
    document.getElementById('bodega-modal-titulo').textContent = id ? 'Editar Bodega' : 'Nueva Bodega';
    document.getElementById('modal-bodega').classList.remove('hidden');
}

function closeBodegaModal() {
    document.getElementById('modal-bodega').classList.add('hidden');
}

function openOperacionModal(bodegaId, bodegaNombre, operacionId, operacionNombre) {
    document.getElementById('operacion-bodega-id').value = bodegaId;
    document.getElementById('operacion-id').value = operacionId || '';
    document.getElementById('operacion-nombre').value = operacionNombre || '';
    document.getElementById('operacion-bodega-nombre').textContent = bodegaNombre || '';
    document.getElementById('operacion-modal-titulo').textContent = operacionId ? 'Editar Operación' : 'Nueva Operación';
    document.getElementById('modal-operacion').classList.remove('hidden');
}

function closeOperacionModal() {
    document.getElementById('modal-operacion').classList.add('hidden');
}

// Cerrar modales con Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeBodegaModal();
        closeOperacionModal();
    }
});
</script>
@endsection