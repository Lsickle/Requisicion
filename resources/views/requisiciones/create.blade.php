@extends('layouts.app')

@section('title', 'Crear Requisición')
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@section('content')
<x-sidebar/>
<div class="max-w-5xl mx-auto p-6 mt-20">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <h1 class="text-2xl font-bold text-gray-700 mb-6">Crear Requisición</h1>

        @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        @if ($errors->any())
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: `{!! implode('<br>', $errors->all()) !!}`,
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        <form id="requisicionForm" action="{{ route('requisiciones.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Recobrable</label>
                    <select name="Recobrable" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>Recobrable</option>
                        <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No recobrable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-2">Prioridad</label>
                    <select name="prioridad_requisicion" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" required>
                        <option value="">-- Selecciona --</option>
                        <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-2">Justificación</label>
                <textarea name="justify_requisicion" rows="3" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" placeholder="Describe la razón de esta requisición" required>{{ old('justify_requisicion') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-2">Detalles Adicionales</label>
                <textarea name="detail_requisicion" rows="3" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" placeholder="Proporciona detalles adicionales sobre la requisición" required>{{ old('detail_requisicion') }}</textarea>
            </div>

            <hr class="my-4">

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                    <p class="text-sm text-gray-500 mt-1">Agrega los productos necesarios para esta requisición</p>
                </div>
                <button type="button" id="abrirModalBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700">
                    + Añadir Producto
                </button>
            </div>

            <div class="overflow-x-auto mt-4">
                <table id="productosTable" class="w-full border border-gray-200 rounded-lg overflow-hidden">
                    <thead class="bg-gray-100 text-gray-600 text-left">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Cantidad Total</th>
                            <th class="p-3">Unidad</th>
                            <th class="p-3">Distribución por Centros</th>
                            <th class="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr id="emptyState" class="text-center text-gray-500">
                            <td colspan="5" class="p-4">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                                <p>No hay productos agregados</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end pt-4">
                <button type="submit" id="guardarRequisicionBtn" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Guardar Requisición
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 1: Selección de Producto -->
<div id="modalProducto" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white p-6 border-b rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-700">Añadir Producto</h2>
                <button id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
        </div>

        <div class="p-6">
            <!-- Filtro de categoría -->
            <div class="mb-6">
                <label class="block text-gray-600 font-semibold mb-2">Filtrar por Categoría</label>
                <select id="categoriaFilter" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300">
                    <option value="">-- Todas las categorías --</option>
                    @php
                        $categoriasUnicas = $productos->pluck('categoria_produc')->unique()->sort();
                    @endphp
                    @foreach ($categoriasUnicas as $categoria)
                        <option value="{{ $categoria }}">{{ $categoria }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Selección de producto y cantidad -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-4">Información del Producto</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Producto</label>
                        <select id="productoSelect" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300">
                            <option value="">-- Selecciona producto --</option>
                            @foreach ($productos as $p)
                            <option value="{{ $p->id }}" data-nombre="{{ $p->name_produc }}"
                                data-proveedor="{{ $p->proveedor_id ?? '' }}" 
                                data-categoria="{{ $p->categoria_produc }}"
                                data-unidad="{{ $p->unit_produc }}">
                                {{ $p->name_produc }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Cantidad Total</label>
                        <input type="number" id="cantidadTotalInput" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" min="1" placeholder="Ej: 100">
                    </div>
                    <div class="bg-indigo-50 p-3 rounded-lg">
                        <label class="block text-gray-600 font-semibold mb-1 text-sm">Unidad de Medida</label>
                        <p id="unidadMedida" class="text-indigo-700 font-medium text-lg">-</p>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="button" id="iniciarProductoBtn" class="w-full bg-indigo-600 text-white px-4 py-3 rounded-lg hover:bg-indigo-700">
                        Distribuir en Centros
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Distribución por Centros -->
<div id="modalDistribucion" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white p-6 border-b rounded-t-2xl">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-700">Distribuir Producto</h2>
                <button id="cerrarModalDistribucionBtn" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
        </div>

        <div class="p-6">
            <!-- Información del producto seleccionado (versión simplificada) -->
            <div class="bg-blue-50 p-4 rounded-lg mb-4">
                <h3 class="text-lg font-semibold text-blue-800 mb-2">Producto Seleccionado</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <p class="text-sm text-gray-600">Producto:</p>
                        <p id="modalProductoNombre" class="font-semibold text-blue-900 truncate">-</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Cantidad Total:</p>
                        <p id="modalCantidadTotal" class="font-semibold text-blue-900">-</p>
                    </div>
                </div>
                <div class="mt-2 pt-2 border-t border-blue-200">
                    <p class="text-sm text-gray-600 flex justify-between">
                        <span>Restante por distribuir:</span>
                        <span id="modalCantidadRestante" class="font-semibold text-blue-900">-</span>
                    </p>
                </div>
            </div>

            <!-- Distribución por centros -->
            <div id="centrosSection">
                <h4 class="text-lg font-semibold text-gray-700 mb-2">Distribución por Centros de Costo</h4>
                <p class="text-sm text-gray-500 mb-4">Distribuya la cantidad total entre los centros de costo</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Centro</label>
                        <select id="centroSelect" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300">
                            <option value="">-- Selecciona centro --</option>
                            @foreach ($centros as $c)
                            <option value="{{ $c->id }}" data-nombre="{{ $c->name_centro }}">{{ $c->name_centro }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-600 font-semibold mb-2">Cantidad</label>
                        <input type="number" id="cantidadCentroInput" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300" min="1" placeholder="Ej: 50">
                    </div>
                    <div>
                        <button type="button" id="agregarCentroBtn" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Agregar
                        </button>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                    <div class="text-sm font-semibold text-gray-600 flex justify-between">
                        <span>Total asignado:</span>
                        <span><span id="totalAsignado" class="text-blue-700">0</span> de <span id="cantidadDisponible" class="text-blue-700">0</span></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                        <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
                    </div>
                </div>

                <div class="mt-4">
                    <h5 class="font-semibold text-gray-600 mb-2">Centros agregados:</h5>
                    <div id="centrosContainer" class="border rounded-lg overflow-hidden max-h-40 overflow-y-auto">
                        <ul id="centrosList" class="divide-y divide-gray-200"></ul>
                    </div>
                </div>

                <div class="flex justify-end mt-6 pt-4 border-t sticky bottom-0 bg-white pb-2">
                    <button type="button" id="guardarProductoBtn" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                        Guardar Producto
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const $ = s => document.querySelector(s);
    const $$ = s => document.querySelectorAll(s);

    // Modales
    const modalProducto = $('#modalProducto');
    const modalDistribucion = $('#modalDistribucion');
    const abrirBtn = $('#abrirModalBtn');
    const cerrarBtn = $('#cerrarModalBtn');
    const cerrarDistribucionBtn = $('#cerrarModalDistribucionBtn');
    
    abrirBtn.addEventListener('click', () => {
        modalProducto.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
    
    cerrarBtn.addEventListener('click', cerrarModalProducto);
    cerrarDistribucionBtn.addEventListener('click', cerrarModalDistribucion);

    function cerrarModalProducto() {
        modalProducto.classList.add('hidden');
        document.body.style.overflow = 'auto';
        resetModalProducto();
    }
    
    function cerrarModalDistribucion() {
        modalDistribucion.classList.add('hidden');
        resetModalDistribucion();
    }

    // Elementos
    const iniciarProductoBtn = $('#iniciarProductoBtn');
    const agregarCentroBtn = $('#agregarCentroBtn');
    const guardarProductoBtn = $('#guardarProductoBtn');
    const productoSelect = $('#productoSelect');
    const cantidadTotalInput = $('#cantidadTotalInput');
    const centroSelect = $('#centroSelect');
    const cantidadCentroInput = $('#cantidadCentroInput');
    const centrosList = $('#centrosList');
    const productosTable = $('#productosTable tbody');
    const emptyState = $('#emptyState');
    const requisicionForm = $('#requisicionForm');
    const totalAsignadoSpan = $('#totalAsignado');
    const cantidadDisponibleSpan = $('#cantidadDisponible');
    const categoriaFilter = $('#categoriaFilter');
    const unidadMedida = $('#unidadMedida');
    const progressBar = $('#progressBar');
    const guardarRequisicionBtn = $('#guardarRequisicionBtn');
    
    // Elementos del modal de distribución
    const modalProductoNombre = $('#modalProductoNombre');
    const modalCantidadTotal = $('#modalCantidadTotal');
    const modalUnidad = $('#modalUnidad');
    const modalCantidadRestante = $('#modalCantidadRestante');

    let productos = [];
    let productoActual = null;
    let cantidadTotal = 0;
    let cantidadAsignada = 0;

    // Mostrar unidad de medida al seleccionar producto
    productoSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const unidad = selectedOption.getAttribute('data-unidad') || 'No especificada';
        unidadMedida.textContent = unidad;
    });

    // Filtrar productos por categoría
    categoriaFilter.addEventListener('change', () => {
        const categoriaSeleccionada = categoriaFilter.value;
        const opcionesProductos = productoSelect.querySelectorAll('option');
        opcionesProductos.forEach(opcion => {
            if (opcion.value === '') return opcion.style.display = '';
            opcion.style.display = (categoriaSeleccionada === '' || opcion.dataset.categoria === categoriaSeleccionada) ? '' : 'none';
        });
        productoSelect.value = '';
        unidadMedida.textContent = '-';
    });

    function resetModalProducto() {
        productoSelect.value = '';
        cantidadTotalInput.value = '';
        unidadMedida.textContent = '-';
        categoriaFilter.value = '';
    }
    
    function resetModalDistribucion() {
        centroSelect.value = '';
        cantidadCentroInput.value = '';
        centrosList.innerHTML = '';
        productoActual = null;
        cantidadTotal = 0;
        cantidadAsignada = 0;
        actualizarResumen();
    }

    function mostrarError(mensaje) {
        Swal.fire({ icon: 'error', title: 'Error', text: mensaje, confirmButtonText: 'Entendido' });
    }

    function mostrarExito(mensaje) {
        Swal.fire({ icon: 'success', title: 'Éxito', text: mensaje, confirmButtonText: 'OK' });
    }

    function actualizarResumen() {
        totalAsignadoSpan.textContent = cantidadAsignada;
        cantidadDisponibleSpan.textContent = cantidadTotal;
        modalCantidadRestante.textContent = (cantidadTotal - cantidadAsignada) + ' ' + (productoActual ? productoActual.unidad : '');
        
        // Actualizar barra de progreso
        const porcentaje = cantidadTotal > 0 ? (cantidadAsignada / cantidadTotal) * 100 : 0;
        progressBar.style.width = `${porcentaje}%`;
        
        // Cambiar color según el progreso
        if (porcentaje === 100) {
            progressBar.classList.remove('bg-blue-600', 'bg-yellow-500');
            progressBar.classList.add('bg-green-600');
        } else if (porcentaje > 0) {
            progressBar.classList.remove('bg-green-600', 'bg-blue-600');
            progressBar.classList.add('bg-yellow-500');
        } else {
            progressBar.classList.remove('bg-green-600', 'bg-yellow-500');
            progressBar.classList.add('bg-blue-600');
        }
    }

    function actualizarTabla() {
        productosTable.innerHTML = "";
        
        if (productos.length === 0) {
            emptyState.style.display = '';
            return;
        }
        
        emptyState.style.display = 'none';
        
        productos.forEach((prod, i) => {
            let centrosHTML = "";
            prod.centros.forEach((centro, j) => {
                centrosHTML += `
                    <div class="inline-block bg-gray-200 px-3 py-1 rounded-full text-xs mr-2 mb-2">
                        ${centro.nombre} <span class="font-bold">(${centro.cantidad})</span>
                    </div>
                    <input type="hidden" name="productos[${i}][centros][${j}][id]" value="${centro.id}">
                    <input type="hidden" name="productos[${i}][centros][${j}][cantidad]" value="${centro.cantidad}">
                `;
            });
            const tr = document.createElement('tr');
            tr.className = 'border-b hover:bg-gray-50';
            tr.innerHTML = `
                <td class="p-3 font-medium">
                    ${prod.nombre}
                    <input type="hidden" name="productos[${i}][id]" value="${prod.id}">
                    ${prod.proveedorId ? `<input type="hidden" name="productos[${i}][proveedor_id]" value="${prod.proveedorId}">` : ''}
                </td>
                <td class="p-3">
                    ${prod.cantidadTotal}
                    <input type="hidden" name="productos[${i}][requisicion_amount]" value="${prod.cantidadTotal}">
                </td>
                <td class="p-3 font-medium text-indigo-700">${prod.unidad}</td>
                <td class="p-3">${centrosHTML}</td>
                <td class="p-3 text-right">
                    <button type="button" onclick="eliminarProducto(${i})" class="bg-red-100 text-red-600 px-3 py-2 rounded-lg hover:bg-red-200 text-sm">
                        Eliminar
                    </button>
                </td>
            `;
            productosTable.appendChild(tr);
        });
    }

    window.eliminarProducto = function(index) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El producto será eliminado de la requisición",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                productos.splice(index, 1);
                actualizarTabla();
                mostrarExito('Producto eliminado correctamente');
            }
        });
    };

    iniciarProductoBtn.addEventListener('click', () => {
        const productoId = productoSelect.value;
        cantidadTotal = parseInt(cantidadTotalInput.value);
        const unidad = productoSelect.options[productoSelect.selectedIndex].getAttribute('data-unidad') || 'Unidad';
        const nombreProducto = productoSelect.options[productoSelect.selectedIndex].getAttribute('data-nombre');
        
        if (!productoId) return mostrarError('Debes seleccionar un producto.');
        if (!cantidadTotal || cantidadTotal < 1) return mostrarError('La cantidad total debe ser un número válido mayor a 0.');
        if (productos.some(p => p.id === productoId)) return mostrarError('Este producto ya fue agregado.');
        
        productoActual = { 
            id: productoId, 
            nombre: nombreProducto, 
            proveedorId: productoSelect.selectedOptions[0].dataset.proveedor || null, 
            cantidadTotal, 
            unidad: unidad,
            centros: [] 
        };
        
        // Actualizar información en el modal de distribución
        modalProductoNombre.textContent = nombreProducto;
        modalCantidadTotal.textContent = cantidadTotal + ' ' + unidad;
        modalCantidadRestante.textContent = cantidadTotal + ' ' + unidad;
        
        cantidadAsignada = 0;
        centrosList.innerHTML = '';
        cantidadDisponibleSpan.textContent = cantidadTotal;
        actualizarResumen();
        
        // Cambiar al modal de distribución
        modalProducto.classList.add('hidden');
        modalDistribucion.classList.remove('hidden');
    });

    agregarCentroBtn.addEventListener('click', () => {
        if (!productoActual) return;
        const centroId = centroSelect.value;
        const cantidadCentro = parseInt(cantidadCentroInput.value);
        const cantidadRestante = cantidadTotal - cantidadAsignada;
        
        if (!centroId) return mostrarError('Debes seleccionar un centro de costo.');
        if (!cantidadCentro || cantidadCentro < 1) return mostrarError('La cantidad debe ser un número válido mayor a 0.');
        if (cantidadCentro > cantidadRestante) return mostrarError(`Solo puedes asignar hasta ${cantidadRestante} unidades.`);
        
        const centroNombre = centroSelect.options[centroSelect.selectedIndex].getAttribute('data-nombre');
        const idx = productoActual.centros.findIndex(c => c.id === centroId);
        
        if (idx >= 0) {
            productoActual.centros[idx].cantidad += cantidadCentro;
        } else {
            productoActual.centros.push({ 
                id: centroId, 
                nombre: centroNombre, 
                cantidad: cantidadCentro 
            });
        }
        
        cantidadAsignada += cantidadCentro;
        actualizarListaCentros();
        actualizarResumen();
        cantidadCentroInput.value = '';
        centroSelect.focus();
    });

    function actualizarListaCentros() {
        centrosList.innerHTML = '';
        productoActual.centros.forEach((c, index) => {
            const li = document.createElement('li');
            li.className = 'p-3 flex justify-between items-center hover:bg-gray-50';
            li.innerHTML = `
                <div>
                    <span class="font-medium">${c.nombre}</span>
                    <span class="text-gray-500 ml-2">${c.cantidad} ${productoActual.unidad}</span>
                </div>
                <button type="button" class="text-red-500 hover:text-red-700" onclick="eliminarCentro(${index})">
                    ×
                </button>
            `;
            centrosList.appendChild(li);
        });
    }

    window.eliminarCentro = function(index) {
        const cantidadCentro = productoActual.centros[index].cantidad;
        productoActual.centros.splice(index, 1);
        cantidadAsignada -= cantidadCentro;
        actualizarListaCentros();
        actualizarResumen();
    };

    guardarProductoBtn.addEventListener('click', () => {
        if (!productoActual || productoActual.centros.length === 0) {
            return mostrarError('Debes añadir al menos un centro de costo.');
        }
        
        if (cantidadAsignada !== cantidadTotal) {
            return mostrarError(`Debes distribuir toda la cantidad (${cantidadTotal - cantidadAsignada} ${productoActual.unidad} restantes).`);
        }
        
        productos.push(productoActual);
        actualizarTabla();
        mostrarExito('Producto agregado correctamente');
        cerrarModalDistribucion();
        resetModalProducto();
    });

    requisicionForm.addEventListener('submit', function(e) {
        if (productos.length === 0) {
            e.preventDefault();
            mostrarError('Debes agregar al menos un producto a la requisición.');
            return;
        }
        
        // Mostrar alerta de carga
        e.preventDefault();
        Swal.fire({
            title: 'Guardando requisición',
            text: 'Por favor espere...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
                // Enviar el formulario después de mostrar la alerta
                setTimeout(() => {
                    this.submit();
                }, 500);
            }
        });
    });

    // Cerrar modales al hacer clic fuera de ellos
    modalProducto.addEventListener('click', (e) => {
        if (e.target === modalProducto) {
            cerrarModalProducto();
        }
    });
    
    modalDistribucion.addEventListener('click', (e) => {
        if (e.target === modalDistribucion) {
            cerrarModalDistribucion();
        }
    });
});
</script>
@endsection