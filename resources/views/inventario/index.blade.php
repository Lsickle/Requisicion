@extends('layouts.app')

@section('title', 'Inventario - Bodega')

@php
$permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
$roles = array_map(fn($r) => mb_strtolower($r, 'UTF-8'), Session::get('user_roles', []));
$hasPermission = fn($perm) => in_array(mb_strtolower($perm, 'UTF-8'), $permissions, true);
$isVerTodas = count(array_filter($roles, fn($r) => in_array($r, ['compras', 'admin'], true))) > 0;

$todosLosSubcentros = $isVerTodas 
    ? \App\Models\Subcentro::with('centro')->orderBy('name_subcentro')->get() 
    : (isset($todosLosSubcentros) ? $todosLosSubcentros : collect());
$tieneMultipleSubcentros = isset($todosLosSubcentros) && count($todosLosSubcentros) > 0;
@endphp

<style>
.product-row:hover { background-color: #f9fafb; }
.modal-overlay { background-color: rgba(0,0,0,0.4); }
.subcentro-card { cursor: pointer; transition: all .2s; }
.subcentro-card:hover { transform: translateY(-2px); }

/* Fix: ensure green button shows background even if global CSS missing */
.bg-green-600 { background-color: #16a34a !important; }
.bg-green-700 { background-color: #15803d !important; }
.btn-green { color: #ffffff !important; }
</style>

@section('content')
<x-sidebar />

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-warehouse text-blue-600"></i>
                Inventario de Bodega
            </h1>
            @if($mostrarResumen)
                <p class="text-blue-600 font-semibold mt-1">Vista General - Todas las Bodegas</p>
            @elseif(isset($subcentroActual) && $subcentroActual)
                <p class="text-emerald-600 font-semibold mt-1">{{ $subcentroActual->name_subcentro }}</p>
                <p class="text-gray-500 text-sm">{{ $subcentroActual->centro->name_centro ?? '' }}</p>
            @elseif(isset($bodegaActual) && $bodegaActual)
                <p class="text-emerald-600 font-semibold mt-1">{{ $bodegaActual->name_centro }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if(!$mostrarResumen)
                @if($subcentroActual)
                <a href="{{ route('inventario.exportar', ['subcentro' => $subcentroActual->id]) }}" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
                <a href="{{ route('inventario.plantilla', ['subcentro_id' => $subcentroActual->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-download mr-1"></i> Descargar Plantilla
                </a>
                <button onclick="abrirModalImportar({{ $subcentroActual->id }})" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-upload mr-1"></i> Importar Inventario
                </button>
                @elseif($bodegaActual)
                <a href="{{ route('inventario.exportar', ['bodega' => $bodegaActual->id]) }}" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-file-excel mr-1"></i> Exportar Excel
                </a>
                @endif
            @endif
            <a href="{{ route('inventario.historial') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">
                <i class="fas fa-history mr-1"></i> Historial
            </a>
        </div>
    </div>

    @if($mostrarResumen)
    <!-- VISTA RESUMEN DE BODEGAS PARA ADMINS -->
    <div class="space-y-6">
        @forelse($todasLasBodegas as $bodega)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Header de Bodega -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-building text-2xl"></i>
                        <div>
                            <h2 class="text-lg font-bold">{{ $bodega['nombre'] }}</h2>
                            <p class="text-blue-100 text-sm">Total: {{ $bodega['totalUnidades'] }} unidades en {{ $bodega['totalProductos'] }} productos</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operaciones de la Bodega -->
            <div class="p-6">
                @if(count($bodega['operaciones']) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($bodega['operaciones'] as $operacion)
                    <a href="{{ route('inventario.index', ['subcentro' => $operacion['id']]) }}" 
                       class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 hover:border-blue-400 hover:shadow-md transition-all cursor-pointer group">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1">
                                <h3 class="text-base font-semibold text-gray-800 group-hover:text-blue-600 transition-colors">
                                    {{ $operacion['nombre'] }}
                                </h3>
                            </div>
                            <i class="fas fa-chevron-right text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                        </div>

                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm">Productos:</span>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800">
                                    {{ $operacion['cantidadProductos'] }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 text-sm">Unidades:</span>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800">
                                    {{ $operacion['cantidadUnidades'] }}
                                </span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @else
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                    <p>No hay operaciones en esta bodega</p>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center">
            <i class="fas fa-exclamation-circle text-3xl text-yellow-600 mb-2 block"></i>
            <p class="text-yellow-700 font-medium">No hay bodegas disponibles</p>
        </div>
        @endforelse
    </div>
    @else
    <!-- VISTA DETALLADA DE OPERACIÓN -->
    @if($tieneMultipleSubcentros || $isVerTodas)
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex items-center gap-4">
            <label class="font-medium text-gray-600">Seleccionar Operación:</label>
            <div class="flex items-center gap-2 flex-1">
                <select onchange="if(this.value) window.location.href='{{ route('inventario.index') }}?subcentro='+this.value" class="flex-1 max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-300/40">
                    @if($isVerTodas)
                    <option value="">-- Ver todas las bodegas --</option>
                    @else
                    <option value="">-- Selecciona una operación --</option>
                    @endif
                    @foreach($todosLosSubcentros as $sc)
                    <option value="{{ $sc['id'] }}" @if($subcentroSeleccionado == $sc['id']) selected @endif>
                        {{ $sc['nombre'] }} ({{ $sc['bodega_nombre'] ?? 'Sin bodega' }})
                    </option>
                    @endforeach
                </select>
                @if($isVerTodas && $subcentroSeleccionado)
                <a href="{{ route('inventario.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg text-sm">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
                @endif
            </div>
        </div>
    </div>
    @endif


    @if(!$isVerTodas && !$subcentroActual && !$bodegaActual)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center text-yellow-700 mb-6">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No tienes ningún subcentro asignado. Contacta al administrador.
    </div>
    @else
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 w-full max-w-md">
                <i class="fas fa-search text-gray-400"></i>
                <input type="text" id="search-input" placeholder="Buscar producto por nombre o SKU..." class="w-full border rounded-lg px-3 py-2">
            </div>
            @if($puedeModificar)
            <div class="flex gap-2">
                <button onclick="abrirModal('entrada')" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Agregar
                </button>
                <button onclick="abrirModal('salida')" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-minus mr-1"></i> Retirar
                </button>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-center">SKU</th>
                        <th class="px-4 py-3 text-center">Unidad</th>
                        <th class="px-4 py-3 text-center">Stock</th>
                        @if($puedeModificar)
                        <th class="px-4 py-3 text-center">Acciones</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="inventario-table-body">
                    @forelse($inventario as $item)
                    <tr class="border-b product-row" data-name="{{ strtolower($item->producto->name_produc ?? '') }}" data-sku="{{ strtolower($item->producto->sku ?? '') }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $item->producto->name_produc ?? 'N/A' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $item->producto->sku ?? '-' }}</td>
                        <td class="px-4 py-3 text-center text-gray-500">{{ $item->producto->unit_produc ?? '-' }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-bold {{ $item->cantidad > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500' }}">
                                {{ $item->cantidad }}
                            </span>
                        </td>
                        @if($puedeModificar)
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="abrirModalEditar({{ $item->id }}, {{ $item->cantidad }}, '{{ addslashes($item->producto->name_produc ?? '') }}')" class="text-blue-600 hover:text-blue-800 mr-2" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="eliminarProducto({{ $item->id }}, '{{ addslashes($item->producto->name_produc ?? '') }}')" class="text-red-600 hover:text-red-800" title="Eliminar del inventario">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $puedeModificar ? 5 : 4 }}" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-box-open text-3xl mb-2 block"></i>
                            No hay productos en inventario.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif
</div>

@if($puedeModificar)
<!-- Modal paraEditar -->
<div id="modal-editar" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Editar Stock</h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="form-editar" class="p-6 space-y-4">
            <input type="hidden" id="editar-inventario-id" name="inventario_id" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                <p id="editar-producto-nombre" class="text-gray-800 font-medium"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Cantidad *</label>
                <input type="number" id="editar-cantidad" name="cantidad" min="0" required class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentario (opcional)</label>
                <textarea id="editar-comentario" name="comentario" rows="2" maxlength="500" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModalEditar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">Cancelar</button>
                <button type="submit" id="btn-editar" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg">
                    <i class="fas fa-spinner fa-spin hidden" id="btn-editar-loading"></i>
                    <span id="btn-editar-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-movimiento" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div id="modal-header" class="px-6 py-4 flex items-center justify-between">
            <h3 id="modal-titulo" class="text-lg font-semibold"></h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="form-movimiento" class="p-6 space-y-4">
            <input type="hidden" id="movimiento-tipo" name="tipo" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto *</label>
                <input type="text" id="producto-buscar" placeholder="Buscar producto..." class="w-full border rounded-lg px-3 py-2 mb-1" onkeyup="filtrarProducto(this.value)">
                <select id="producto-select" class="w-full border rounded-lg px-3 py-2 bg-white" size="5" required>
                    <option value="">-- Seleccionar producto --</option>
                    @foreach($productosMap ?? [] as $prod)
                    <option value="{{ $prod['id'] }}" data-nombre="{{ strtolower($prod['name_produc']) }}" data-sku="{{ strtolower($prod['sku']) }}" data-cantidad="{{ $prod['cantidad'] }}">{{ $prod['name_produc'] }} ({{ $prod['sku'] }}) - {{ $prod['unit_produc'] }} [Stock: {{ $prod['cantidad'] }}]</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Escribe para buscar y selecciona de la lista</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" min="1" required class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentario (opcional)</label>
                <textarea id="comentario" name="comentario" rows="2" maxlength="500" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">Cancelar</button>
                <button type="submit" id="btn-submit" class="text-white font-medium py-2 px-6 rounded-lg">
                    <i class="fas fa-spinner fa-spin hidden" id="btn-loading"></i>
                    <span id="btn-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var movimientoTipo = document.getElementById('movimiento-tipo');
var formMovimiento = document.getElementById('form-movimiento');
var productoSelect = document.getElementById('producto-select');
var cantidadInput = document.getElementById('cantidad');
var modalMovimiento = document.getElementById('modal-movimiento');

function filtrarProducto(texto) {
    var textoLower = texto.toLowerCase();
    var select = document.getElementById('producto-select');
    var options = select.options;
    for (var i = 1; i < options.length; i++) {
        var option = options[i];
        var nombre = option.getAttribute('data-nombre') || '';
        var sku = option.getAttribute('data-sku') || '';
        if (textoLower === '' || nombre.indexOf(textoLower) !== -1 || sku.indexOf(textoLower) !== -1) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
}

function abrirModal(tipo) {
    movimientoTipo.value = tipo;
    var isEntrada = tipo === 'entrada';
    document.getElementById('modal-titulo').textContent = isEntrada ? 'Agregar al Inventario' : 'Retirar del Inventario';
    var btnSubmit = document.getElementById('btn-submit');
    if (isEntrada) {
        document.getElementById('modal-header').className = 'bg-green-600 text-white px-6 py-4 flex items-center justify-between';
        btnSubmit.className = 'bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg';
    } else {
        document.getElementById('modal-header').className = 'bg-red-600 text-white px-6 py-4 flex items-center justify-between';
        btnSubmit.className = 'bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-lg';
    }
    
    // Filtrar productos según el tipo de movimiento
    var select = document.getElementById('producto-select');
    var options = select.options;
    for (var i = 1; i < options.length; i++) {
        var option = options[i];
        var cantidad = parseInt(option.getAttribute('data-cantidad') || '0', 10);
        if (isEntrada) {
            // En entrada, mostrar todos los productos
            option.style.display = '';
        } else {
            // En salida, solo mostrar los que tienen stock > 0
            option.style.display = cantidad > 0 ? '' : 'none';
        }
    }
    
    // Seleccionar primera opción visible
    if (!isEntrada) {
        for (var i = 1; i < options.length; i++) {
            if (options[i].style.display !== 'none') {
                select.selectedIndex = i;
                break;
            }
        }
    }
    
    formMovimiento.reset();
    modalMovimiento.classList.remove('hidden');
}

function cerrarModal() {
    modalMovimiento.classList.add('hidden');
    formMovimiento.reset();
}

if (formMovimiento) {
    formMovimiento.addEventListener('submit', async function(e) {
        e.preventDefault();
        document.getElementById('btn-loading').classList.remove('hidden');
        document.getElementById('btn-text').classList.add('opacity-50');
        document.getElementById('btn-submit').disabled = true;

        var error = false;
        if (!productoSelect.value) {
            alert('Selecciona un producto');
            error = true;
        }
        if (!cantidadInput.value || parseInt(cantidadInput.value) < 1) {
            alert('Ingresa una cantidad valida');
            error = true;
        }
        if (error) {
            document.getElementById('btn-loading').classList.add('hidden');
            document.getElementById('btn-text').classList.remove('opacity-50');
            document.getElementById('btn-submit').disabled = false;
            return;
        }

        var tipoMov = movimientoTipo.value;
        var url = tipoMov === 'entrada' ? '/inventario/entrada' : '/inventario/salida';
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        try {
            var res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productoSelect.value,
                    cantidad: cantidadInput.value,
                    comentario: document.getElementById('comentario').value
                })
            });
            console.log('Response:', res.status);
            
            var data = await res.json();
            console.log('Data:', data);
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Correcto', text: data.message }).then(function() {
                    cerrarModal();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        } finally {
            document.getElementById('btn-loading').classList.add('hidden');
            document.getElementById('btn-text').classList.remove('opacity-50');
            document.getElementById('btn-submit').disabled = false;
        }
    });
}

var searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.product-row').forEach(function(row) {
            var match = q === '' || row.dataset.name.includes(q) || row.dataset.sku.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
}

// Función para editar stock
var modalEditar = document.getElementById('modal-editar');
var formEditar = document.getElementById('form-editar');

function abrirModalEditar(inventarioId, cantidad, productoNombre) {
    document.getElementById('editar-inventario-id').value = inventarioId;
    document.getElementById('editar-cantidad').value = cantidad;
    document.getElementById('editar-producto-nombre').textContent = productoNombre;
    document.getElementById('editar-comentario').value = '';
    modalEditar.classList.remove('hidden');
}

function cerrarModalEditar() {
    modalEditar.classList.add('hidden');
    formEditar.reset();
}

if (formEditar) {
    formEditar.addEventListener('submit', async function(e) {
        e.preventDefault();
        document.getElementById('btn-editar-loading').classList.remove('hidden');
        document.getElementById('btn-editar-text').classList.add('opacity-50');
        document.getElementById('btn-editar').disabled = true;

        var url = '/inventario/editar';
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        try {
            var res = await fetch(url, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    inventario_id: document.getElementById('editar-inventario-id').value,
                    cantidad: document.getElementById('editar-cantidad').value,
                    comentario: document.getElementById('editar-comentario').value
                })
            });
            var data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Correcto', text: data.message }).then(function() {
                    cerrarModalEditar();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        } finally {
            document.getElementById('btn-editar-loading').classList.add('hidden');
            document.getElementById('btn-editar-text').classList.remove('opacity-50');
            document.getElementById('btn-editar').disabled = false;
        }
    });
}

// Función para eliminar producto
async function eliminarProducto(inventarioId, productoNombre) {
    var result = await Swal.fire({
        title: '¿Eliminar?',
        text: '¿Seguro que quieres eliminar "' + productoNombre + '" del inventario?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    console.log('Eliminando inventario ID:', inventarioId);
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        var res = await fetch('/inventario/eliminar', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken, 
                'Accept': 'application/json'
            },
            body: JSON.stringify({ inventario_id: inventarioId })
        });
        console.log('Response status:', res.status);
        
        var data = await res.json();
        console.log('Data:', data);
        
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message }).then(function() {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión: ' + err.message });
    }
}

// Modal de Importar Inventario
function abrirModalImportar(subcentroId) {
    var modal = document.getElementById('modal-importar') || crearModalImportar();
    document.getElementById('importar-subcentro-id').value = subcentroId;
    modal.classList.remove('hidden');
}

function cerrarModalImportar() {
    var modal = document.getElementById('modal-importar');
    if (modal) modal.classList.add('hidden');
}

function crearModalImportar() {
    var html = `
    <div id="modal-importar" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold">Importar Inventario</h3>
                <button onclick="cerrarModalImportar()" class="text-white hover:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form id="form-importar" class="p-6 space-y-4">
                <input type="hidden" id="importar-subcentro-id" value="">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Cargar archivo Excel (.xlsx, .xls, .csv)
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Descarga la plantilla primero para conocer el formato correcto
                    </p>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-purple-400 transition-colors" id="drop-zone">
                        <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2 block"></i>
                        <p class="text-sm font-medium text-gray-700 mb-1">Arrastra el archivo aquí</p>
                        <p class="text-xs text-gray-500">o haz clic para seleccionar</p>
                        <input type="file" id="archivo-importar" accept=".xlsx,.xls,.csv" class="hidden" onchange="mostrarNombreArchivo()">
                    </div>
                    <p id="archivo-nombre" class="text-sm text-gray-600 mt-2"></p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="cerrarModalImportar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-importar" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-6 rounded-lg">
                        <i class="fas fa-spinner fa-spin hidden" id="btn-importar-loading"></i>
                        <span id="btn-importar-text">Importar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', html);
    var modal = document.getElementById('modal-importar');
    
    // Configurar drag and drop
    var dropZone = document.getElementById('drop-zone');
    var inputFile = document.getElementById('archivo-importar');
    
    dropZone.addEventListener('click', () => inputFile.click());
    
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.add('border-purple-400', 'bg-purple-50'), false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-purple-400', 'bg-purple-50'), false);
    });
    
    dropZone.addEventListener('drop', (e) => {
        var dt = e.dataTransfer;
        var files = dt.files;
        inputFile.files = files;
        mostrarNombreArchivo();
    }, false);
    
    // Manejar submit del formulario
    document.getElementById('form-importar').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        var archivo = document.getElementById('archivo-importar').files[0];
        var subcentroId = document.getElementById('importar-subcentro-id').value;
        
        if (!archivo) {
            Swal.fire({ icon: 'warning', title: 'Advertencia', text: 'Por favor selecciona un archivo' });
            return;
        }
        
        var formData = new FormData();
        formData.append('archivo', archivo);
        formData.append('subcentro_id', subcentroId);
        
        document.getElementById('btn-importar-loading').classList.remove('hidden');
        document.getElementById('btn-importar-text').classList.add('opacity-50');
        document.getElementById('btn-importar').disabled = true;
        
        try {
            var res = await fetch('{{ route("inventario.importar") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: formData
            });
            
            var data = await res.json();
            
            if (data.success) {
                var mensaje = data.message;
                if (data.data && data.data.errores && data.data.errores.length > 0) {
                    mensaje += '\\n\\nErrores encontrados:';
                    data.data.errores.slice(0, 5).forEach(e => {
                        mensaje += '\\n• Fila ' + e.fila + ': ' + e.error;
                    });
                    if (data.data.errores.length > 5) {
                        mensaje += '\\n... y ' + (data.data.errores.length - 5) + ' errores más';
                    }
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Importación Completada',
                    text: mensaje,
                    willClose: () => {
                        cerrarModalImportar();
                        window.location.reload();
                    }
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión: ' + err.message });
        } finally {
            document.getElementById('btn-importar-loading').classList.add('hidden');
            document.getElementById('btn-importar-text').classList.remove('opacity-50');
            document.getElementById('btn-importar').disabled = false;
        }
    });
    
    return modal;
}

function mostrarNombreArchivo() {
    var archivo = document.getElementById('archivo-importar').files[0];
    var nombreEl = document.getElementById('archivo-nombre');
    if (archivo) {
        nombreEl.textContent = '✓ ' + archivo.name + ' (' + Math.round(archivo.size / 1024) + ' KB)';
        nombreEl.classList.remove('text-gray-600');
        nombreEl.classList.add('text-green-600', 'font-medium');
    }
}
</script>
@endif
@endsection