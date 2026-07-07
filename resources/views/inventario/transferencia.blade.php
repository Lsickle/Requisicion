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
.firma-container {
    border: 3px solid #cbd5e1;
    border-radius: 12px;
    padding: 8px;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}
.firma-container:hover { 
    border-color: #0ea5e9; 
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.12), 0 0 8px rgba(14,165,233,0.3); 
}
.firma-container.active { 
    border-color: #06b6d4; 
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.12), 0 0 12px rgba(6,182,212,0.4); 
}
.firmaCanvas {
    display: block;
    background: white;
    cursor: crosshair;
    border-radius: 8px;
    width: 100%;
    height: auto;
    touch-action: none;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.firma-controls {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.firma-btn {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    font-weight: 500;
}
.btn-limpiar {
    background-color: #fee2e2;
    color: #991b1b;
}
.btn-limpiar:hover { background-color: #fecaca; }
.btn-descargar {
    background-color: #dbeafe;
    color: #1e40af;
}
.btn-descargar:hover { background-color: #bfdbfe; }
.firma-hint {
    font-size: 12px;
    color: #64748b;
    text-align: center;
    padding: 8px;
    background-color: #f1f5f9;
    border-radius: 6px;
    margin-bottom: 8px;
}
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
                            <label class="block text-sm font-medium text-gray-600">Operación origen *</label>
                            <select id="bodega_origen" class="w-full border rounded px-3 py-2" onchange="cargarInventarioOrigen()">
                                <option value="">-- Seleccionar operación --</option>
                                @foreach($subcentros as $sc)
                                <option value="{{ $sc->id }}" data-bodega="{{ $sc->centro->name_centro ?? 'N/A' }}">{{ $sc->name_subcentro }} ({{ $sc->centro->name_centro ?? 'N/A' }})</option>
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
                            <label class="block text-sm font-medium text-gray-600 mb-2">Firma del Origen *</label>
                            <div class="firma-hint"><i class="fas fa-pen-fancy mr-1"></i> Escribe tu firma en el área blanca</div>
                            <div class="firma-container" id="container-firma_origen">
                                <canvas id="firma_origen" class="firmaCanvas" width="280" height="140"></canvas>
                            </div>
                            <div class="firma-controls">
                                <button type="button" onclick="limpiarFirma('firma_origen')" class="firma-btn btn-limpiar">
                                    <i class="fas fa-eraser mr-1"></i> Limpiar
                                </button>
                                <button type="button" onclick="descargarFirma('firma_origen')" class="firma-btn btn-descargar">
                                    <i class="fas fa-download mr-1"></i> Descargar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded-lg p-4">
                    <h4 class="font-bold text-gray-700 mb-3 border-b pb-2">Datos de quien recibe (Destino)</h4>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-600">Operación destino *</label>
                            <select id="bodega_destino" class="w-full border rounded px-3 py-2" onchange="cargarInventarioDestino()">
                                <option value="">-- Seleccionar operación --</option>
                                @foreach($subcentros as $sc)
                                <option value="{{ $sc->id }}" data-bodega="{{ $sc->centro->name_centro ?? 'N/A' }}">{{ $sc->name_subcentro }} ({{ $sc->centro->name_centro ?? 'N/A' }})</option>
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
                            <label class="block text-sm font-medium text-gray-600 mb-2">Firma del Destino *</label>
                            <div class="firma-hint"><i class="fas fa-pen-fancy mr-1"></i> Escribe tu firma en el área blanca</div>
                            <div class="firma-container" id="container-firma_destino">
                                <canvas id="firma_destino" class="firmaCanvas" width="280" height="140"></canvas>
                            </div>
                            <div class="firma-controls">
                                <button type="button" onclick="limpiarFirma('firma_destino')" class="firma-btn btn-limpiar">
                                    <i class="fas fa-eraser mr-1"></i> Limpiar
                                </button>
                                <button type="button" onclick="descargarFirma('firma_destino')" class="firma-btn btn-descargar">
                                    <i class="fas fa-download mr-1"></i> Descargar
                                </button>
                            </div>
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
    // Asegurar que los canvases de firma ocupen todo el contenedor tras mostrarse
    try { adjustFirmaCanvases(); setTimeout(adjustFirmaCanvases, 120); } catch(e) {}
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
    var subcentroId = document.getElementById('bodega_origen').value;
    if (!subcentroId) return;
    
    fetch('/inventario/transferencia/inventario?subcentro_id=' + subcentroId)
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
    var subcentroId = document.getElementById('bodega_destino').value;
    if (!subcentroId) return;
    
    fetch('/inventario/transferencia/inventario?subcentro_id=' + subcentroId)
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

// Firma canvas mejorado: DPR-safe, pointer events y limpieza correcta
(function() {
    ['firma_origen', 'firma_destino'].forEach(function(id) {
        var canvas = document.getElementById(id);
        var container = document.getElementById('container-' + id);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var drawing = false;

        function resizeCanvas() {
            // Prefer container's inner size so canvas fills the box
            var cssWidth = (container && container.clientWidth) || canvas.getBoundingClientRect().width || canvas.getAttribute('width') || 280;
            var cssHeight = (container && container.clientHeight) || canvas.getBoundingClientRect().height || canvas.getAttribute('height') || 140;
            var dpr = window.devicePixelRatio || 1;

            // set the internal pixel size (device pixels)
            canvas.width = Math.max(1, Math.round(cssWidth * dpr));
            canvas.height = Math.max(1, Math.round(cssHeight * dpr));

            // keep CSS size explicit so layout doesn't depend on attribute
            canvas.style.width = '100%';
            canvas.style.height = cssHeight + 'px';

            // Reset transform and clear the full pixel buffer
            ctx.setTransform(1,0,0,1,0,0);
            ctx.clearRect(0,0,canvas.width,canvas.height);

            // Fill background white at pixel resolution
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0,0,canvas.width,canvas.height);

            // Scale drawing so we can use CSS pixels as coordinates
            ctx.setTransform(dpr,0,0,dpr,0,0);

            // Stroke settings in CSS pixels
            ctx.lineWidth = 1.2;
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.strokeStyle = '#1f2937';
        }

        // Initialize and keep in sync on resize (debounced)
        resizeCanvas();
        var resizeTimer = null;
        window.addEventListener('resize', function(){ clearTimeout(resizeTimer); resizeTimer = setTimeout(resizeCanvas, 120); });

        function getCoords(e) {
            var rect = canvas.getBoundingClientRect();
            var clientX = e.clientX || (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
            var clientY = e.clientY || (e.touches && e.touches[0] ? e.touches[0].clientY : 0);
            return { x: clientX - rect.left, y: clientY - rect.top };
        }

        canvas.addEventListener('pointerdown', function(e) {
            e.preventDefault();
            if (e.pointerType === 'mouse' && e.button !== 0) return; // only left click
            canvas.setPointerCapture && canvas.setPointerCapture(e.pointerId);
            drawing = true;
            container && container.classList.add('active');
            var c = getCoords(e);
            ctx.beginPath();
            ctx.moveTo(c.x, c.y);
        });

        canvas.addEventListener('pointermove', function(e) {
            if (!drawing) return;
            var c = getCoords(e);
            ctx.lineTo(c.x, c.y);
            ctx.stroke();
        });

        function stopDrawing(e) {
            drawing = false;
            try { canvas.releasePointerCapture && canvas.releasePointerCapture(e.pointerId); } catch (err) {}
            container && container.classList.remove('active');
        }

        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);
        canvas.addEventListener('pointerleave', function(e) { if (drawing) stopDrawing(e); });
    });
})();

// Función pública para ajustar el tamaño de los canvases de firma (llamar al mostrar modal)
function adjustFirmaCanvases(){
    ['firma_origen','firma_destino'].forEach(function(id){
        var canvas = document.getElementById(id);
        var container = document.getElementById('container-' + id);
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        var cssWidth = (container && container.clientWidth) || canvas.getBoundingClientRect().width || canvas.getAttribute('width') || 280;
        var cssHeight = (container && container.clientHeight) || canvas.getBoundingClientRect().height || canvas.getAttribute('height') || 140;
        var dpr = window.devicePixelRatio || 1;
        // Make CSS width 100% so layout stretches the canvas to fill container
        canvas.style.width = '100%';
        canvas.style.height = cssHeight + 'px';
        // internal pixel buffer should match CSS size * DPR
        canvas.width = Math.max(1, Math.round(cssWidth * dpr));
        canvas.height = Math.max(1, Math.round(cssHeight * dpr));
        ctx.setTransform(1,0,0,1,0,0);
        ctx.clearRect(0,0,canvas.width,canvas.height);
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0,0,canvas.width,canvas.height);
        ctx.setTransform(dpr,0,0,dpr,0,0);
        ctx.lineWidth = 1.2; ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle='#1f2937';
    });
}

function limpiarFirma(id) {
    var canvas = document.getElementById(id);
    if (!canvas) return;
    var ctx = canvas.getContext('2d');
    // Clear full pixel buffer and restore transform
    ctx.setTransform(1,0,0,1,0,0);
    ctx.clearRect(0,0,canvas.width,canvas.height);
    // fill white background at pixel resolution
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0,0,canvas.width,canvas.height);
    // reapply scale for CSS-coordinates
    var dpr = window.devicePixelRatio || 1;
    ctx.setTransform(dpr,0,0,dpr,0,0);
}

function descargarFirma(id) {
    var canvas = document.getElementById(id);
    if (!canvas) return;
    // use CSS size for export to keep file reasonable
    var rect = canvas.getBoundingClientRect();
    var tmp = document.createElement('canvas');
    tmp.width = Math.max(1, Math.round(rect.width));
    tmp.height = Math.max(1, Math.round(rect.height));
    var tctx = tmp.getContext('2d');
    // white background
    tctx.fillStyle = '#ffffff'; tctx.fillRect(0,0,tmp.width,tmp.height);
    // draw source canvas into tmp (browser will scale appropriately)
    try {
        tctx.drawImage(canvas, 0, 0, tmp.width, tmp.height);
    } catch(e) {
        // fallback: try drawing using a cloned image
        var data = canvas.toDataURL();
        var img = new Image();
        img.onload = function(){ tctx.drawImage(img,0,0,tmp.width,tmp.height); var link = document.createElement('a'); link.href = tmp.toDataURL('image/png'); link.download = 'firma_' + id + '_' + new Date().getTime() + '.png'; link.click(); };
        img.src = data;
        return;
    }
    var link = document.createElement('a');
    link.href = tmp.toDataURL('image/png');
    link.download = 'firma_' + id + '_' + new Date().getTime() + '.png';
    link.click();
}

function getFirmaData(id) {
    var canvas = document.getElementById(id);
    if (!canvas) return '';
    // return a reasonably sized PNG using CSS dimensions
    var rect = canvas.getBoundingClientRect();
    var tmp = document.createElement('canvas');
    tmp.width = Math.max(1, Math.round(rect.width));
    tmp.height = Math.max(1, Math.round(rect.height));
    var tctx = tmp.getContext('2d');
    tctx.fillStyle = '#ffffff'; tctx.fillRect(0,0,tmp.width,tmp.height);
    try { tctx.drawImage(canvas, 0, 0, tmp.width, tmp.height); } catch(e) { /* ignore */ }
    return tmp.toDataURL();
}

document.getElementById('form-transferencia').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    var data = {
        subcentro_origen_id: document.getElementById('bodega_origen').value,
        subcentro_destino_id: document.getElementById('bodega_destino').value,
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
    
    if (!data.subcentro_origen_id || !data.subcentro_destino_id || !data.producto_id || !data.cantidad) {
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
        
        // Si la respuesta es HTML (error de sesión), mostrar alerta sin redirigir al home
        if (contentType && contentType.includes('text/html')) {
            alert('Error: Tu sesión ha expirado. Por favor recarga la página.');
            document.getElementById('btn-loading').classList.add('hidden');
            return;
        }
        
        var result = await res.json();
        
        if (res.ok) {
            // Éxito: cerrar modal y mostrar mensaje sin recargar toda la página
            Swal.fire({ icon: 'success', title: 'Correcto', text: result.message }).then(function() {
                cerrarModal();
                // Recargar solo la tabla de transferencias sin perder sesión
                location.href = '/inventario/transferencia';
            });
        } else {
            // Error en la transferencia (422, 400, etc)
            alert(result.message || 'Error al transferir');
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