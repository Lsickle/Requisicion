@extends('layouts.app')

@section('title', 'Transferencia de Inventario')

@php
$permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
$roles = array_map(fn($r) => mb_strtolower($r, 'UTF-8'), Session::get('user_roles', []));
$hasPermission = fn($perm) => in_array(mb_strtolower($perm, 'UTF-8'), $permissions, true);
$isVerTodas = count(array_filter($roles, fn($r) => in_array($r, ['compras', 'admin'], true))) > 0;
$userName = session('user.name') ?? session('user_email') ?? '';
@endphp

<style>
.modal-overlay { background-color: rgba(0,0,0,0.5); }
.firmaCanvas { border: 2px dashed #ccc; border-radius: 8px; cursor: crosshair; }
.firmaCanvas:active { border-color: #3b82f6; }
</style>

@section('content')
<x-sidebar />

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-exchange-alt text-purple-600"></i>
                Transferencia de Elementos
            </h1>
            <p class="text-gray-500 text-sm mt-1">Prestamo de elementos entre bodegas</p>
        </div>
    </div>

    <button onclick="abrirModal()" class="mb-4 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg">
        <i class="fas fa-plus mr-2"></i>Nueva Transferencia
    </button>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="px-4 py-3 text-left">Fecha</th>
                    <th class="px-4 py-3 text-left">Origen</th>
                    <th class="px-4 py-3 text-left">Destino</th>
                    <th class="px-4 py-3 text-left">Producto</th>
                    <th class="px-4 py-3 text-center">Cantidad</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transferencias as $t)
                <tr class="border-b">
                    <td class="px-4 py-3">{{ $t->created_at->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $t->bodegaOrigen->name_centro ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $t->bodegaDestino->name_centro ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $t->producto->name_produc ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center font-bold">{{ $t->cantidad }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-bold 
                            @if($t->estado == 'aprobado') bg-green-100 text-green-800
                            @elseif($t->estado == 'pendiente') bg-yellow-100 text-yellow-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ $t->estado }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="verPdf({{ $t->id }})" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-file-pdf text-xl"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                        No hay transferencias registradas.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if($transferencias->hasPages())
        <div class="px-4 py-3 border-t">
            {!! $transferencias->links() !!}
        </div>
        @endif
    </div>
</div>

<div id="modal-transferencia" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="bg-purple-600 text-white px-6 py-4 flex items-center justify-between sticky top-0">
            <h3 class="text-lg font-bold">Nueva Transferencia</h3>
            <button onclick="cerrarModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form id="form-transferencia" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="border rounded-lg p-4">
                    <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Datos de quien transfiere (Origen)</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Bodega origen *</label>
                            <select id="bodega_origen" class="w-full border rounded px-3 py-2" onchange="cargarInventarioOrigen()">
                                <option value="">-- Seleccionar bodega --</option>
                                @foreach($centros as $c)
                                <option value="{{ $c->id }}">{{ $c->name_centro }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Producto *</label>
                            <input type="text" id="producto_buscar" placeholder="Buscar producto..." class="w-full border rounded px-3 py-2 mb-1" onkeyup="filtrarProducto(this.value)">
                            <select id="producto_origen" class="w-full border rounded px-3 py-2 bg-white" size="4">
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Cantidad *</label>
                            <input type="number" id="cantidad" min="1" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Nombre completo *</label>
                            <input type="text" id="nombre_origen" value="{{ $userName }}" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Cedula *</label>
                            <input type="text" id="cedula_origen" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Firma *</label>
                            <canvas id="firma_origen" class="firmaCanvas w-full h-24" width="300" height="96"></canvas>
                            <button type="button" onclick="limpiarFirma('firma_origen')" class="text-xs text-gray-500 underline mt-1">Limpiar</button>
                        </div>
                    </div>
                </div>

                <div class="border rounded-lg p-4">
                    <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Datos de quien recibe (Destino)</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Bodega destino *</label>
                            <select id="bodega_destino" class="w-full border rounded px-3 py-2" onchange="cargarInventarioDestino()">
                                <option value="">-- Seleccionar bodega --</option>
                                @foreach($centros as $c)
                                <option value="{{ $c->id }}">{{ $c->name_centro }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Producto a recibir *</label>
                            <select id="producto_destino" class="w-full border rounded px-3 py-2 bg-white">
                                <option value="">-- Seleccionar --</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Nombre completo *</label>
                            <input type="text" id="nombre_destino" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Cedula *</label>
                            <input type="text" id="cedula_destino" class="w-full border rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Firma *</label>
                            <canvas id="firma_destino" class="firmaCanvas w-full h-24" width="300" height="96"></canvas>
                            <button type="button" onclick="limpiarFirma('firma_destino')" class="text-xs text-gray-500 underline mt-1">Limpiar</button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-600">Observaciones</label>
                <textarea id="observaciones" rows="2" class="w-full border rounded px-3 py-2"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg">Cancelar</button>
                <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-6 rounded-lg">
                    <i class="fas fa-spinner fa-spin hidden" id="btn-loading"></i>
                    <span>Transferir</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-pdf" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="bg-gray-800 text-white px-6 py-4 flex items-center justify-between sticky top-0">
            <h3 class="text-lg font-bold">Documento de Transferencia</h3>
            <button onclick="cerrarPdf()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="pdf-content" class="p-6">
        </div>
    </div>
</div>

<script>
var productosData = @json($productos->map(fn($p) => ['id' => $p->id, 'nombre' => $p->name_produc, 'sku' => $p->sku]));

function abrirModal() {
    document.getElementById('modal-transferencia').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-transferencia').classList.add('hidden');
    document.getElementById('form-transferencia').reset();
    limpiarFirma('firma_origen');
    limpiarFirma('firma_destino');
}

function filtrarProducto(texto) {
    var textoLower = texto.toLowerCase();
    var select = document.getElementById('producto_origen');
    select.innerHTML = '<option value="">-- Seleccionar --</option>';
    productosData.forEach(function(p) {
        if (textoLower === '' || p.nombre.toLowerCase().indexOf(textoLower) !== -1 || p.sku.toLowerCase().indexOf(textoLower) !== -1) {
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = p.nombre + ' (' + p.sku + ')';
            select.appendChild(opt);
        }
    });
}

function cargarInventarioOrigen() {
    var bodegaId = document.getElementById('bodega_origen').value;
    if (!bodegaId) return;
    
    fetch('/inventario/transferencia/inventario?bodega_id=' + bodegaId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var select = document.getElementById('producto_origen');
            select.innerHTML = '<option value="">-- Seleccionar --</option>';
            var selectDest = document.getElementById('producto_destino');
            selectDest.innerHTML = '<option value="">-- Seleccionar --</option>';
            data.forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.producto_id;
                opt.textContent = p.producto_nombre + ' (' + p.producto_sku + ') - Stock: ' + p.cantidad;
                opt.dataset.cantidad = p.cantidad;
                select.appendChild(opt);
                
                var optDest = opt.cloneNode(true);
                selectDest.appendChild(optDest);
            });
        });
}

function cargarInventarioDestino() {
    var bodegaId = document.getElementById('bodega_destino').value;
    if (!bodegaId) return;
    
    fetch('/inventario/transferencia/inventario?bodega_id=' + bodegaId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var select = document.getElementById('producto_destino');
            select.innerHTML = '<option value="">-- Seleccionar --</option>';
            data.forEach(function(p) {
                var opt = document.createElement('option');
                opt.value = p.producto_id;
                opt.textContent = p.producto_nombre + ' (' + p.producto_sku + ') - Stock: ' + p.cantidad;
                select.appendChild(opt);
            });
        });
}

// Firma canvas
(function() {
    ['firma_origen', 'firma_destino'].forEach(function(id) {
        var canvas = document.getElementById(id);
        var ctx = canvas.getContext('2d');
        var drawing = false;
        
        canvas.addEventListener('mousedown', function(e) {
            drawing = true;
            ctx.beginPath();
        });
        
        canvas.addEventListener('mousemove', function(e) {
            if (!drawing) return;
            var rect = canvas.getBoundingClientRect();
            ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
        
        canvas.addEventListener('mouseup', function() { drawing = false; });
        canvas.addEventListener('mouseout', function() { drawing = false; });
    });
})();

function limpiarFirma(id) {
    var canvas = document.getElementById(id);
    var ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

function getFirmaData(id) {
    var canvas = document.getElementById(id);
    return canvas.toDataURL();
}

document.getElementById('form-transferencia').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    var data = {
        bodega_origen_id: document.getElementById('bodega_origen').value,
        bodega_destino_id: document.getElementById('bodega_destino').value,
        producto_id: document.getElementById('producto_origen').value,
        cantidad: document.getElementById('cantidad').value,
        nombre_origen: document.getElementById('nombre_origen').value,
        cedula_origen: document.getElementById('cedula_origen').value,
        firma_origen: getFirmaData('firma_origen'),
        nombre_destino: document.getElementById('nombre_destino').value,
        cedula_destino: document.getElementById('cedula_destino').value,
        firma_destino: getFirmaData('firma_destino'),
        observaciones: document.getElementById('observaciones').value,
    };
    
    if (!data.bodega_origen_id || !data.bodega_destino_id || !data.producto_id || !data.cantidad) {
        alert('Completa todos los campos requeridos');
        return;
    }
    
    document.getElementById('btn-loading').classList.remove('hidden');
    
    try {
        var res = await fetch('/inventario/transferencia', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        });
        var contentType = res.headers.get('content-type');
        if (!res.ok || (contentType && contentType.includes('text/html'))) {
            window.location.href = '/';
            return;
        }
        var result = await res.json();
        
        if (res.ok) {
            Swal.fire({ icon: 'success', title: 'Correcto', text: result.message }).then(function() {
                cerrarModal();
                window.location.reload();
            });
        } else {
            alert(result.message || 'Error al.transferir');
        }
    } catch (err) {
        alert('Error de conexion');
    }
    
    document.getElementById('btn-loading').classList.add('hidden');
});

function verPdf(id) {
    fetch('/inventario/transferencia/pdf/' + id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var html = '<div class="border-2 border-gray-800 p-6 max-w-2xl mx-auto">' +
                '<h2 class="text-center text-xl font-bold mb-4">DOCUMENTO DE TRANSFERENCIA</h2>' +
                '<p class="text-right text-sm mb-4">Fecha: ' + data.fecha + '</p>' +
                '<div class="grid grid-cols-2 gap-4 mb-4">' +
                '<div class="border p-3"><h3 class="font-bold text-sm border-b mb-2">TRANSFIERE</h3><p><strong>Bodega:</strong> ' + data.bodega_origen + '</p></div>' +
                '<div class="border p-3"><h3 class="font-bold text-sm border-b mb-2">RECIBE</h3><p><strong>Bodega:</strong> ' + data.bodega_destino + '</p></div>' +
                '</div>' +
                '<div class="border p-3 mb-4"><p><strong>Producto:</strong> ' + data.producto + '</p><p><strong>SKU:</strong> ' + data.producto_sku + '</p><p><strong>Cantidad:</strong> ' + data.cantidad + '</p></div>' +
                '<div class="grid grid-cols-2 gap-4 mb-4">' +
                '<div class="border p-3"><p class="font-bold text-sm">' + data.nombre_origen + '</p><p>CC: ' + data.cedula_origen + '</p><img src="' + data.firma_origen + '" class="h-16 mt-2"></div>' +
                '<div class="border p-3"><p class="font-bold text-sm">' + data.nombre_destino + '</p><p>CC: ' + data.cedula_destino + '</p><img src="' + data.firma_destino + '" class="h-16 mt-2"></div>' +
                '</div>' +
                '<p class="text-sm"><strong>Observaciones:</strong> ' + (data.observaciones || 'Ninguna') + '</p>' +
                '</div>';
            
            document.getElementById('pdf-content').innerHTML = html;
            document.getElementById('modal-pdf').classList.remove('hidden');
        });
}

function cerrarPdf() {
    document.getElementById('modal-pdf').classList.add('hidden');
}
</script>
@endsection