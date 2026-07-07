@extends('layouts.app')

@section('content')
<x-sidebar />
<div class="container mx-auto px-4 py-6 ml-0 md:ml-64">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Formulario de Salida de Stock</h1>
            
            <div class="mb-4">
                <a href="{{ route('requisiciones.menu') }}" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                    ← Volver al menú
                </a>
            </div>
            
            <form id="formSalidaStock" class="space-y-6">
                <!-- Buscador de productos -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Producto</label>
                    <div class="relative">
                        <input type="text" id="buscadorProducto" 
                            class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Escriba el nombre del producto..." autocomplete="off">
                        <div id="resultadosBusqueda" class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                        </div>
                    </div>
                </div>

                <!-- Productos seleccionados -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Productos a Extraer</label>
                    <div id="listaProductos" class="space-y-3">
                        <p class="text-gray-500 text-sm italic" id="sinProductos">No hay productos seleccionados</p>
                    </div>
                </div>

                <!-- Firma -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Firma</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 bg-gray-50">
                        <canvas id="canvasFirma" class="w-full h-40 bg-white border border-gray-200 rounded cursor-crosshair"></canvas>
                        <div class="flex justify-between mt-2">
                            <button type="button" id="limpiarFirma" class="text-sm text-red-600 hover:text-red-800">
                                Limpiar firma
                            </button>
                            <span class="text-xs text-gray-500">Firme en el recuadro acima</span>
                        </div>
                    </div>
                    <input type="hidden" name="firma" id="firmaData">
                </div>

                <!-- Nombre quien firma -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de quien firma *</label>
                    <input type="text" name="nombre_firma" id="nombreFirma" required
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Ingrese su nombre completo">
                </div>

                <!-- Observaciones -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                    <textarea name="observaciones" id="observaciones" rows="3"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Observaciones adicionales (opcional)"></textarea>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" id="btnGuardar" 
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        Generar PDF de Salida
                    </button>
                    <button type="button" id="btnCancelar" 
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#resultadosBusqueda div:hover {
    background-color: #f3f4f6;
    cursor: pointer;
}
</style>

@push('scripts')
<script>
let productosSeleccionados = [];
let firmaDrawing = false;
let firmaCtx;
let firmaLastX = 0;
let firmaLastY = 0;

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar canvas de firma
    const canvas = document.getElementById('canvasFirma');
    firmaCtx = canvas.getContext('2d');
    
    // Ajustar tamaño del canvas
    function resizeCanvas() {
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
        firmaCtx.fillStyle = '#ffffff';
        firmaCtx.fillRect(0, 0, canvas.width, canvas.height);
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Eventos del canvas
    canvas.addEventListener('mousedown', function(e) {
        firmaDrawing = true;
        const rect = canvas.getBoundingClientRect();
        firmaLastX = e.clientX - rect.left;
        firmaLastY = e.clientY - rect.top;
    });

    canvas.addEventListener('mousemove', function(e) {
        if (!firmaDrawing) return;
        const rect = canvas.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        firmaCtx.strokeStyle = '#000000';
        firmaCtx.lineWidth = 2;
        firmaCtx.lineCap = 'round';
        firmaCtx.beginPath();
        firmaCtx.moveTo(firmaLastX, firmaLastY);
        firmaCtx.lineTo(x, y);
        firmaCtx.stroke();
        
        firmaLastX = x;
        firmaLastY = y;
    });

    canvas.addEventListener('mouseup', function() { firmaDrawing = false; });
    canvas.addEventListener('mouseout', function() { firmaDrawing = false; });

    // Limpiar firma
    document.getElementById('limpiarFirma').addEventListener('click', function() {
        firmaCtx.fillStyle = '#ffffff';
        firmaCtx.fillRect(0, 0, canvas.width, canvas.height);
        document.getElementById('firmaData').value = '';
    });

    // Buscador de productos
    const buscador = document.getElementById('buscadorProducto');
    const resultados = document.getElementById('resultadosBusqueda');
    let searchTimeout;

    buscador.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            resultados.classList.add('hidden');
            return;
        }

        searchTimeout = setTimeout(async function() {
            try {
                resultados.innerHTML = '<div class="p-3 text-gray-500">Buscando...</div>';
                resultados.classList.remove('hidden');
                
                const url = `./salida-stock/buscar?q=${encodeURIComponent(query)}`;
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    if (response.status === 302 || response.redirected) {
                        window.location.href = '/';
                        return;
                    }
                    throw new Error('Error de conexión: ' + response.status);
                }
                
                // Verificar si la respuesta es HTML (redirección al login)
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('text/html')) {
                    window.location.href = '/';
                    return;
                }
                
                const productos = await response.json();
                
                resultados.innerHTML = '';
                
                if (productos.length === 0) {
                    resultados.innerHTML = '<div class="p-3 text-gray-500">No se encontraron productos</div>';
                } else {
                    productos.forEach(function(p) {
                        const div = document.createElement('div');
                        div.className = 'p-3 border-b hover:bg-gray-50 cursor-pointer';
                        div.dataset.id = p.id;
                        div.dataset.nombre = p.name_produc;
                        div.dataset.stock = p.stock_produc;
                        div.dataset.unidad = p.unit_produc;
                        div.dataset.categoria = p.categoria_produc;
                        div.innerHTML = `
                            <div class="font-medium">${p.name_produc}</div>
                            <div class="text-sm text-gray-600">
                                Stock: <span class="font-bold text-green-600">${p.stock_produc}</span> ${p.unit_produc || ''} | 
                                Categoría: ${p.categoria_produc || 'Sin categoría'}
                            </div>
                        `;
                        div.addEventListener('click', function() {
                            agregarProducto({
                                id: this.dataset.id,
                                name_produc: this.dataset.nombre,
                                stock_produc: this.dataset.stock,
                                unit_produc: this.dataset.unidad
                            });
                            buscador.value = '';
                            resultados.classList.add('hidden');
                        });
                        resultados.appendChild(div);
                    });
                }
                
            } catch (error) {
                console.error('Error buscando productos:', error);
                resultados.innerHTML = '<div class="p-3 text-red-500">Error al buscar. Verifique la conexión.</div>';
            }
        }, 300);
    });

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!buscador.contains(e.target) && !resultados.contains(e.target)) {
            resultados.classList.add('hidden');
        }
    });

    // Agregar producto
    function agregarProducto(producto) {
        // Verificar si ya existe
        if (productosSeleccionados.find(p => p.id === producto.id)) {
            alert('Este producto ya está seleccionado');
            return;
        }

        productosSeleccionados.push({
            id: producto.id,
            nombre: producto.name_produc,
            stock: producto.stock_produc,
            unidad: producto.unit_produc || '',
            cantidad: 1
        });

        renderProductos();
    }

    // Renderizar lista de productos
    function renderProductos() {
        const container = document.getElementById('listaProductos');
        const sinProductos = document.getElementById('sinProductos');
        
        if (productosSeleccionados.length === 0) {
            container.innerHTML = '<p class="text-gray-500 text-sm italic" id="sinProductos">No hay productos seleccionados</p>';
            return;
        }

        container.innerHTML = productosSeleccionados.map((p, index) => `
            <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg border">
                <div class="flex-1">
                    <div class="font-medium">${p.nombre}</div>
                    <div class="text-sm text-gray-600">Stock disponible: ${p.stock}</div>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-600">Cantidad:</label>
                    <input type="number" min="1" max="${p.stock}" value="${p.cantidad}" 
                        class="w-20 border rounded px-2 py-1 text-center cantidad-input"
                        data-index="${index}">
                    <button type="button" class="text-red-600 hover:text-red-800 eliminar-producto" data-index="${index}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');

        // Event listeners
        container.querySelectorAll('.cantidad-input').forEach(input => {
            input.addEventListener('change', function() {
                const index = parseInt(this.dataset.index);
                let val = parseInt(this.value);
                if (val < 1) val = 1;
                if (val > productosSeleccionados[index].stock) val = productosSeleccionados[index].stock;
                productosSeleccionados[index].cantidad = val;
            });
        });

        container.querySelectorAll('.eliminar-producto').forEach(btn => {
            btn.addEventListener('click', function() {
                const index = parseInt(this.dataset.index);
                productosSeleccionados.splice(index, 1);
                renderProductos();
            });
        });
    }

    // Enviar formulario
    document.getElementById('formSalidaStock').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (productosSeleccionados.length === 0) {
            alert('Debe seleccionar al menos un producto');
            return;
        }

        const nombreFirma = document.getElementById('nombreFirma').value.trim();
        if (!nombreFirma) {
            alert('Debe ingresar el nombre de quien firma');
            return;
        }

        const canvas = document.getElementById('canvasFirma');
        const firmaData = canvas.toDataURL('image/png');
        
        // Verificar que hay algo escrito en el canvas
        const imgData = firmaCtx.getImageData(0, 0, canvas.width, canvas.height);
        const hasDrawing = imgData.data.some(channel => channel !== 255);
        
        if (!hasDrawing) {
            alert('Debe firmar en el recuadro');
            return;
        }

        const btnGuardar = document.getElementById('btnGuardar');
        btnGuardar.disabled = true;
        btnGuardar.textContent = 'Generando PDF...';

        const data = {
            productos: productosSeleccionados.map(p => ({
                producto_id: p.id,
                cantidad: p.cantidad
            })),
            firma: firmaData,
            nombre_firma: nombreFirma,
            observaciones: document.getElementById('observaciones').value.trim()
        };

        try {
            const response = await fetch('./salida-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Error de conexión con el servidor');
            }

            const result = await response.json();

            if (result.success) {
                // Abrir PDF en nueva ventana
                const pdfWindow = window.open('', '_blank');
                if (pdfWindow) {
                    pdfWindow.document.write(result.data.html);
                    pdfWindow.document.close();
                } else {
                    alert('El PDF se generó pero la ventana fue bloqueda. Permita ventanas emergentes.');
                }
                
                // Limpiar formulario
                productosSeleccionados = [];
                renderProductos();
                firmaCtx.fillStyle = '#ffffff';
                firmaCtx.fillRect(0, 0, canvas.width, canvas.height);
                document.getElementById('nombreFirma').value = '';
                document.getElementById('observaciones').value = '';
                
                alert('Salida de stock registrada correctamente');
            } else {
                alert(result.message || 'Error al registrar la salida');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar la solicitud. Verifique que el servidor esté corriendo.');
        } finally {
            btnGuardar.disabled = false;
            btnGuardar.textContent = 'Generar PDF de Salida';
        }
    });

    // Botón cancelar
    document.getElementById('btnCancelar').addEventListener('click', function() {
        if (confirm('¿Está seguro que desea cancelar?')) {
            window.location.href = '/';
        }
    });
});
</script>
@endpush
@endsection
