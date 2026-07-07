@extends('layouts.app')

@section('title', 'Recepción de Órdenes de Compra')

@section('content')
<div class="flex pt-20">
    <style>
        .recepcion-scope {
            color: #111827;
        }
        .recepcion-scope .main-card {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
        }
        .recepcion-scope .section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
        }
        .recepcion-scope h1,
        .recepcion-scope h2,
        .recepcion-scope h3 {
            letter-spacing: 0.5px;
        }
        .recepcion-scope table thead {
            background: #eef2ff;
            color: #1e3a8a;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .recepcion-scope table thead th {
            font-weight: 600;
        }
        .recepcion-scope table tbody tr:nth-child(odd) {
            background: #ffffff;
        }
        .recepcion-scope table tbody tr:nth-child(even) {
            background: #f1f5f9;
        }
        .recepcion-scope table tbody tr:hover {
            background: #e0e7ff;
        }
        .btn-base {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            border-radius: 0.6rem;
            transition: 0.25s;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
        }
        .btn-base:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
        .btn-primary {
            background: #2563eb;
            color: #fff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }
        .btn-secondary {
            background: #6366f1;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #4f46e5;
        }
        .btn-danger {
            background: #dc2626;
            color: #fff;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .thin-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #94a3b8 #e2e8f0;
        }
        .thin-scrollbar::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .thin-scrollbar::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 8px;
        }
        .thin-scrollbar::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 8px;
        }
        .thin-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }
        
        /* Canvas para firma */
        #signatureCanvas {
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #fff;
            cursor: crosshair;
            touch-action: none;
            display: block;
            width: 100% !important;
            max-width: 100%;
            margin: 0 auto;
        }
        
        .canvas-container {
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            background: #fff;
            padding: 0;
            margin: 0;
            width: 100%;
            height: 250px;
            position: relative;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            background: #fff;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .spinner {
            display: none;
            width: 24px;
            height: 24px;
            border: 3px solid #f3f4f6;
            border-top-color: #2563eb;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
    
    <x-sidebar />

    <div class="flex-1 px-4 md:px-8 pb-10 recepcion-scope">
        <div class="max-w-6xl mx-auto main-card p-6 flex flex-col min-h-[80vh]">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center shadow-inner">
                        <i class="fas fa-inbox text-xl"></i>
                    </div>
                    <h1 class="text-2xl font-extrabold text-gray-800 tracking-tight">Recepción de Órdenes de Compra</h1>
                </div>
            </div>

            <!-- Búsqueda de OC -->
            <div class="section-card p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Buscar Orden de Compra</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group md:col-span-2">
                        <label>Número de Orden (Order OC)</label>
                        <input type="text" id="orderOcInput" placeholder="Ej: OC-001" class="form-group-input">
                    </div>
                    <div class="flex items-end">
                        <button id="buscarBtn" type="button" class="btn-base btn-primary px-6 py-3 w-full">
                            <i class="fas fa-search mr-2"></i>Buscar
                        </button>
                    </div>
                </div>
                <div id="errorMensaje" class="mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded hidden"></div>
            </div>

            <!-- Formulario de Recepción (oculto hasta buscar) -->
            <form id="recepcionForm" class="hidden">
                @csrf
                <input type="hidden" id="ordenCompraId" name="orden_compra_id">

                <!-- Datos de la Orden -->
                <div class="section-card p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Información de la Orden</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Número de Orden</label>
                            <input type="text" id="orderOcDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Número de Requisición</label>
                            <input type="text" id="requisicionDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Fecha de Creación</label>
                            <input type="text" id="fechaDisplay" readonly>
                        </div>
                        <div class="form-group">
                            <label>Solicitante</label>
                            <input type="text" id="solicitanteDisplay" readonly>
                        </div>
                    </div>
                </div>

                <!-- Productos a Recibir -->
                <div class="section-card p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Productos a Recibir</h2>
                    <div class="overflow-x-auto thin-scrollbar">
                        <table class="w-full border border-gray-200 rounded-lg overflow-hidden bg-white">
                            <thead class="bg-indigo-50 text-indigo-900">
                                <tr>
                                    <th class="p-3 text-left">Producto</th>
                                    <th class="p-3 text-center">Unidad</th>
                                    <th class="p-3 text-center">Cantidad Solicitada</th>
                                    <th class="p-3 text-center">Cantidad Recibida</th>
                                </tr>
                            </thead>
                            <tbody id="productosTable">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Datos del Solicitante -->
                <div class="section-card p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Datos del Solicitante</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-group">
                            <label>Nombre del Solicitante *</label>
                            <input type="text" id="solicitanteNombre" name="solicitante_nombre" placeholder="Nombre completo" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" id="solicitanteEmail" readonly>
                        </div>
                    </div>
                </div>

                <!-- Firma Digital -->
                <div class="section-card p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-700 mb-4">Firma Digital del Solicitante</h2>
                    <p class="text-sm text-gray-600 mb-4">Firme en el recuadro a continuación para autorizar la recepción</p>
                    <div class="canvas-container">
                        <canvas id="signatureCanvas"></canvas>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" id="limpiarFirmaBtn" class="btn-base bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2">
                            <i class="fas fa-undo mr-2"></i>Limpiar Firma
                        </button>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <button type="button" id="cancelarBtn" class="btn-base bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2">
                        Cancelar
                    </button>
                    <button type="submit" id="guardarBtn" class="btn-base btn-primary px-6 py-2">
                        <div class="spinner"></div>
                        <span>Guardar y Generar PDF</span>
                    </button>
                </div>
            </form>

            <!-- Información de Ayuda -->
            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                    <div>
                        <h4 class="font-medium text-blue-800">Instrucciones</h4>
                        <ul class="text-sm text-blue-700 mt-1 list-disc list-inside space-y-1">
                            <li>Ingrese el número de orden (Order OC) en el campo de búsqueda</li>
                            <li>Verifique que todos los productos hayan llegado correctamente</li>
                            <li>Ingrese las cantidades recibidas para cada producto</li>
                            <li>Firme digitalmente en el recuadro de firma</li>
                            <li>Haga clic en "Guardar y Generar PDF" para completar la recepción</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.5/dist/signature_pad.umd.min.js"></script>
<script>
    let signaturePad;
    let productosData = [];

    // Inicializar canvas de firma
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('signatureCanvas');
        const container = canvas.parentElement;

        // Función para redimensionar el canvas
        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const width = container.offsetWidth;
            const height = 250;

            canvas.width = width * ratio;
            canvas.height = height * ratio;
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
            
            // Escalar el contexto para hacer la firma más limpia
            canvas.getContext('2d').scale(ratio, ratio);

            // Reinicializar SignaturePad después de redimensionar
            signaturePad = new SignaturePad(canvas, {
                minWidth: 0.5,
                maxWidth: 2.5,
                throttle: 16,
            });
        }

        // Redimensionar al cargar
        resizeCanvas();

        // Redimensionar cuando cambie el tamaño de la ventana
        window.addEventListener('resize', resizeCanvas);

        // Eventos
        document.getElementById('buscarBtn').addEventListener('click', buscarOc);
        document.getElementById('limpiarFirmaBtn').addEventListener('click', limpiarFirma);
        document.getElementById('recepcionForm').addEventListener('submit', guardarRecepcion);
        document.getElementById('cancelarBtn').addEventListener('click', cancelarRecepcion);
        document.getElementById('orderOcInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') buscarOc();
        });
    });

    async function buscarOc() {
        const orderOc = document.getElementById('orderOcInput').value.trim();
        const errorDiv = document.getElementById('errorMensaje');

        if (!orderOc) {
            mostrarError('Ingrese un número de orden');
            return;
        }

        try {
            errorDiv.classList.add('hidden');
            const response = await fetch('{{ route("recepciones.buscarOc") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({ order_oc: orderOc })
            });

            const data = await response.json();

            if (!response.ok) {
                mostrarError(data.message || 'Error al buscar la orden');
                return;
            }

            // Llenar formulario con datos
            document.getElementById('ordenCompraId').value = data.id;
            document.getElementById('orderOcDisplay').value = data.order_oc;
            document.getElementById('requisicionDisplay').value = '#' + data.requisicion_numero;
            document.getElementById('fechaDisplay').value = data.fecha_creacion;
            document.getElementById('solicitanteDisplay').value = data.solicitante_nombre;
            document.getElementById('solicitanteEmail').value = data.solicitante_email;
            document.getElementById('solicitanteNombre').value = data.solicitante_nombre;

            // Llenar tabla de productos
            productosData = data.productos;
            llenarTablaProductos();

            // Mostrar formulario y limpiar firma
            document.getElementById('recepcionForm').classList.remove('hidden');
            
            // Esperar a que el DOM se actualice y luego redimensionar el canvas
            setTimeout(() => {
                const canvas = document.getElementById('signatureCanvas');
                const container = canvas.parentElement;
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                const width = container.offsetWidth;
                const height = 250;

                canvas.width = width * ratio;
                canvas.height = height * ratio;
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                
                canvas.getContext('2d').scale(ratio, ratio);
                
                signaturePad.clear();
                window.scrollTo(0, document.getElementById('recepcionForm').offsetTop - 100);
            }, 50);

        } catch (error) {
            mostrarError('Error de conexión: ' + error.message);
        }
    }

    function llenarTablaProductos() {
        const tbody = document.getElementById('productosTable');
        tbody.innerHTML = '';

        productosData.forEach((producto, index) => {
            const tr = document.createElement('tr');
            tr.className = 'border-t';
            tr.innerHTML = `
                <td class="p-3">${producto.nombre_producto}</td>
                <td class="p-3 text-center">${producto.unidad}</td>
                <td class="p-3 text-center font-semibold">${producto.cantidad_solicitada}</td>
                <td class="p-3 text-center">
                    <input type="number" 
                        name="productos[${index}][producto_id]" 
                        value="${producto.producto_id}" 
                        style="display:none;">
                    <input type="hidden"
                        name="productos[${index}][cantidad_solicitada]"
                        value="${producto.cantidad_solicitada}">
                    <input type="number" 
                        min="0" 
                        max="${producto.cantidad_solicitada}"
                        name="productos[${index}][cantidad_recibida]"
                        class="cantidad-recibida w-20 border rounded p-2 text-center"
                        value="0"
                        data-index="${index}">
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    function limpiarFirma() {
        signaturePad.clear();
    }

    function mostrarError(mensaje) {
        const errorDiv = document.getElementById('errorMensaje');
        errorDiv.textContent = mensaje;
        errorDiv.classList.remove('hidden');
    }

    function cancelarRecepcion() {
        document.getElementById('recepcionForm').classList.add('hidden');
        document.getElementById('orderOcInput').value = '';
        document.getElementById('errorMensaje').classList.add('hidden');
        signaturePad.clear();
        productosData = [];
    }

    async function guardarRecepcion(e) {
        e.preventDefault();

        // Validar firma
        if (signaturePad.isEmpty()) {
            mostrarError('Debe firmar para completar la recepción');
            return;
        }

        // Obtener datos
        const ordenCompraId = document.getElementById('ordenCompraId').value;
        const solicitanteNombre = document.getElementById('solicitanteNombre').value;
        const firmaDigital = signaturePad.toDataURL('image/png');

        // Recopilar productos
        const productos = [];
        document.querySelectorAll('.cantidad-recibida').forEach((input, index) => {
            productos.push({
                producto_id: productosData[index].producto_id,
                cantidad_solicitada: productosData[index].cantidad_solicitada,
                cantidad_recibida: parseInt(input.value) || 0,
            });
        });

        // Mostrar cargando
        const spinner = document.querySelector('.spinner');
        spinner.style.display = 'block';
        const guardarBtn = document.getElementById('guardarBtn');
        guardarBtn.disabled = true;

        try {
            const response = await fetch('{{ route("recepciones.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: JSON.stringify({
                    orden_compra_id: ordenCompraId,
                    productos: productos,
                    firma_digital: firmaDigital,
                    solicitante_nombre: solicitanteNombre,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                mostrarError(data.message || 'Error al guardar recepción');
                spinner.style.display = 'none';
                guardarBtn.disabled = false;
                return;
            }

            // Éxito - generar y descargar PDF
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Recepción guardada!',
                    text: 'Se está generando el documento PDF...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            }

            // Esperar y descargar PDF
            setTimeout(() => {
                window.location.href = `{{ url('/recepciones') }}/${ordenCompraId}/pdf`;
            }, 1500);

        } catch (error) {
            mostrarError('Error de conexión: ' + error.message);
            spinner.style.display = 'none';
            guardarBtn.disabled = false;
        }
    }
</script>
@endsection
