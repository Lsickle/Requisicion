@extends('layouts.app')

@section('title', 'Crear Requisición')
@section('content')
<x-sidebar />
<div class="max-w-5xl mx-auto p-6 mt-20">
    <div class="bg-white shadow-xl rounded-2xl p-6">
        <div class="flex justify-center items-center gap-8 py-4 mb-8">
            <img src="{{ asset('images/VigiaLogoC.svg') }}" alt="Vigía Plus Logistics" class="h-16 w-auto">
            <h1 class="text-3xl font-bold text-gray-700">Crear Requisición</h1>
        </div>

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

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Recobrable</label>
                    <select name="Recobrable" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>Recobrable
                        </option>
                        <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No
                            recobrable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Prioridad</label>
                    <select name="prioridad_requisicion" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media
                        </option>
                        <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Justificación</label>
                <textarea name="justify_requisicion" rows="3" class="w-full border rounded-lg p-2"
                    required>{{ old('justify_requisicion') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Detalles Adicionales</label>
                <textarea name="detail_requisicion" rows="3" class="w-full border rounded-lg p-2"
                    required>{{ old('detail_requisicion') }}</textarea>
            </div>

            <hr class="my-4">

            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                <button type="button" id="abrirModalBtn"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700">
                    + Añadir Producto
                </button>
            </div>

            <div class="overflow-x-auto">
                <table id="productosTable" class="w-full border border-gray-200 rounded-lg overflow-hidden mt-3">
                    <thead class="bg-gray-100 text-gray-600 text-left">
                        <tr>
                            <th class="p-3">Producto</th>
                            <th class="p-3">Cantidad Total</th>
                            <th class="p-3">Distribución por Centros</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="submit" id="submitBtn"
                    class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Guardar Requisición
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 1: Selección de Producto -->
<div id="modalProducto" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">Seleccionar Producto</h2>
            <button id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <!-- Filtro de categoría -->
        <div class="mb-4 relative">
            <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
            <input type="text" id="categoriaFilter" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona una categoría">
            <div id="categoriasList" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-50 hidden p-1">
                @php
                $categoriasUnicas = $productos->pluck('categoria_produc')->unique()->sort();
                @endphp
                @foreach ($categoriasUnicas as $categoria)
                <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" onclick="seleccionarOpcion(event, this, 'categoriaFilter')">
                    {{ $categoria }}
                </div>
                @endforeach
            </div>
        </div>

        <!-- Selección de producto y cantidad -->
        <div class="grid grid-cols-3 gap-4 items-end">
            <div class="relative">
                <label class="block text-gray-600 font-semibold mb-1">Producto</label>
                <input type="text" id="productoSelect" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona un producto">
                <div id="productosList" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                    @foreach ($productos as $p)
                    <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded whitespace-normal break-words"
                        onclick="seleccionarOpcion(event, this, 'productoSelect')" data-id="{{ $p->id }}"
                        data-nombre="{{ $p->name_produc }}" data-proveedor="{{ $p->proveedor_id ?? '' }}"
                        data-categoria="{{ $p->categoria_produc }}" data-unidad="{{ $p->unit_produc }}">
                        {{ $p->name_produc }} ({{ $p->unit_produc }})
                    </div>
                    @endforeach
                </div>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Cantidad Total</label>
                <input type="number" id="cantidadTotalInput" class="w-full border rounded-lg p-2" min="1"
                    placeholder="Ej: 100">
            </div>
            <div class="flex items-center">
                <span id="unidadMedida" class="text-gray-600 font-semibold">Unidad: -</span>
            </div>
        </div>


        <div class="flex justify-end mt-6">
            <button type="button" id="siguienteModalBtn"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                Siguiente <i class="ml-1 fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Modal 2: Distribución por Centros de Costo -->
<div id="modalDistribucion" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">Distribuir Producto</h2>
            <button id="cerrarModalDistribucionBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <div class="mb-4 p-3 bg-gray-50 rounded-lg">
            <p class="font-semibold" id="productoSeleccionadoNombre"></p>
            <p class="text-sm">Cantidad total: <span id="productoSeleccionadoCantidad" class="font-bold">0</span> <span
                    id="productoSeleccionadoUnidad"></span></p>
        </div>

        <!-- Distribución por centros -->
        <div id="centrosSection" class="mt-4">
            <h4 class="text-lg font-semibold text-gray-700 mb-2">Distribución por Centros de Costo</h4>
            <p class="text-sm text-gray-500 mb-4">Distribuya la cantidad total entre los centros de costo</p>

            <div class="grid grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Centro</label>
                    <div class="relative">
                        <input type="text" id="centroFilter" class="w-full border rounded-lg p-2" placeholder="Escribe o selecciona un centro" autocomplete="off">
                        <input type="hidden" id="centroSelect" name="centroSelectHidden" value="">
                        <div id="centrosDropdown" class="absolute left-0 w-full bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50 hidden p-1">
                            @foreach ($centros as $c)
                            <div class="p-2 hover:bg-indigo-100 cursor-pointer rounded" data-id="{{ $c->id }}" data-nombre="{{ $c->name_centro }}" onclick="seleccionarCentro(event, this)">
                                {{ $c->name_centro }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Cantidad</label>
                    <input type="number" id="cantidadCentroInput" class="w-full border rounded-lg p-2" min="1"
                        placeholder="Ej: 50">
                </div>
                <div>
                    <button type="button" id="agregarCentroBtn"
                        class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Agregar
                    </button>
                </div>
            </div>

            <div class="mt-4 text-sm font-semibold text-gray-600">
                Total asignado: <span id="totalAsignado">0</span> de <span id="cantidadDisponible">0</span> <span
                    id="unidadDisponible"></span>
            </div>

            <ul id="centrosList" class="divide-y divide-gray-200 mt-3 border rounded-lg p-2 max-h-40 overflow-y-auto">
            </ul>

            <div class="flex justify-between mt-6">
                <button type="button" id="volverModalBtn"
                    class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </button>
                <button type="button" id="guardarProductoBtn"
                    class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                    Guardar Producto
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Alertas de carga -->
<div id="cargandoAlert" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl p-6 flex flex-col items-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mb-4"></div>
        <p class="text-gray-700 font-semibold">Procesando, por favor espere...</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const $ = s => document.querySelector(s);
    const $$ = s => document.querySelectorAll(s);

    // Modales
    const modalProducto = $('#modalProducto');
    const modalDistribucion = $('#modalDistribucion');
    const cargandoAlert = $('#cargandoAlert');
    
    // Botones
    const abrirBtn = $('#abrirModalBtn');
    const cerrarBtn = $('#cerrarModalBtn');
    const cerrarDistribucionBtn = $('#cerrarModalDistribucionBtn');
    const siguienteBtn = $('#siguienteModalBtn');
    const volverBtn = $('#volverModalBtn');
    const agregarCentroBtn = $('#agregarCentroBtn');
    const guardarProductoBtn = $('#guardarProductoBtn');
    const submitBtn = $('#submitBtn');
    
    // Elementos de formulario
    const productoSelect = $('#productoSelect');
    const cantidadTotalInput = $('#cantidadTotalInput');
    const centroSelect = $('#centroSelect');
    const cantidadCentroInput = $('#cantidadCentroInput');
    const centrosList = $('#centrosList');
    const productosTable = $('#productosTable tbody');
    const requisicionForm = $('#requisicionForm');
    const totalAsignadoSpan = $('#totalAsignado');
    const cantidadDisponibleSpan = $('#cantidadDisponible');
    const unidadDisponibleSpan = $('#unidadDisponible');
    const categoriaFilter = $('#categoriaFilter');
    const productoSeleccionadoNombre = $('#productoSeleccionadoNombre');
    const productoSeleccionadoCantidad = $('#productoSeleccionadoCantidad');
    const productoSeleccionadoUnidad = $('#productoSeleccionadoUnidad');
    const unidadMedidaSpan = $('#unidadMedida');

    // Elementos adicionales para centros
    const centroFilter = $('#centroFilter');
    const centrosDropdown = $('#centrosDropdown');

    let productos = [];
    let productoActual = null;
    let cantidadTotal = 0;
    let cantidadAsignada = 0;
    let unidadMedida = '';

    // ====== Datos iniciales de Laravel ======
    const categorias = @json($productos->pluck('categoria_produc')->unique()->sort()->values());
    const productosData = [
        @foreach($productos as $p)
        {
            id: {{ json_encode($p->id) }},
            nombre: {!! json_encode($p->name_produc) !!},
            unidad: {!! json_encode($p->unit_produc) !!},
            proveedor: {!! json_encode($p->proveedor_id) !!},
            categoria: {!! json_encode($p->categoria_produc) !!}
        },
        @endforeach
    ];

    // ====== Función para rellenar datalist limitado ======
    function renderDatalist(input, datalist, items, formatFn) {
        const value = input.value.toLowerCase();
        datalist.innerHTML = "";
        let filtered = items.filter(item => formatFn(item).toLowerCase().includes(value));
        filtered = filtered.slice(0, 15);
        filtered.forEach(item => {
            const option = document.createElement("option");
            option.value = formatFn(item);
            datalist.appendChild(option);
        });
    }

    // Nota: usamos dropdowns personalizados; no usar los atributos datalist/renderDatalist aquí.
    // Los listeners para input/focus se gestionan más abajo para los dropdowns personalizados.

    // Mostrar/filtrar opciones (implementación consolidada)
    const categoriasListDiv = document.getElementById('categoriasList');
    const productosListDiv = document.getElementById('productosList');

    // Re-attach option handlers to allow multiple selections and prevent dropdown from disappearing permanently
    function attachOptionHandlers() {
        if (categoriasListDiv) {
            categoriasListDiv.querySelectorAll('div').forEach(div => {
                // remove previous to avoid duplicates
                if (div._handler) div.removeEventListener('mousedown', div._handler);
                const handler = function(e) {
                    e.preventDefault();
                    // use global seleccionarOpcion (pass event so it can stop propagation)
                    window.seleccionarOpcion && window.seleccionarOpcion(e, div, 'categoriaFilter');
                };
                div._handler = handler;
                div.addEventListener('mousedown', handler);
            });
        }
        if (productosListDiv) {
            productosListDiv.querySelectorAll('div').forEach(div => {
                if (div._handlerProd) div.removeEventListener('mousedown', div._handlerProd);
                const handler = function(e) {
                    e.preventDefault();
                    window.seleccionarOpcion && window.seleccionarOpcion(e, div, 'productoSelect');
                };
                div._handlerProd = handler;
                div.addEventListener('mousedown', handler);
            });
        }
    }

    // Attach once on load
    attachOptionHandlers();

    function filtrarDropdown(input, listId) {
        const dropdown = document.getElementById(listId);
        const filtro = (input.value || '').toLowerCase();
        let hayCoincidencias = false;

        dropdown.querySelectorAll('div').forEach(opcion => {
            const txt = (opcion.textContent || '').toLowerCase();
            if (txt.includes(filtro)) {
                opcion.style.display = 'block';
                hayCoincidencias = true;
            } else {
                opcion.style.display = 'none';
            }
        });

        dropdown.style.display = hayCoincidencias ? 'block' : 'none';
    }

    // Mostrar dropdown al enfocar
    categoriaFilter.addEventListener('focus', function() {
        filtrarDropdown(this, 'categoriasList');
    });
    productoSelect.addEventListener('focus', function() {
        filtrarDropdown(this, 'productosList');
        // Si hay categoría escrita, aplicar filtro de categoría también
        filtrarProductosPorCategoria();
    });
    centroFilter.addEventListener('focus', () => {
        centrosDropdown.style.display = 'block';
    });

    // Filtrar productos teniendo en cuenta categoría y texto
    function filtrarProductosPorCategoria() {
        const categoriaSeleccionada = (categoriaFilter.value || '').trim();
        const texto = (productoSelect.value || '').toLowerCase();
        let hay = false;

        productosListDiv.querySelectorAll('div').forEach(item => {
            const cat = (item.getAttribute('data-categoria') || '').toString();
            const txt = (item.textContent || '').toLowerCase();
            const matchesCat = !categoriaSeleccionada || cat === categoriaSeleccionada;
            const matchesText = txt.includes(texto);
            if (matchesCat && matchesText) {
                item.style.display = 'block';
                hay = true;
            } else {
                item.style.display = 'none';
            }
        });

        // Mostrar el dropdown de productos solo si el input de producto tiene el foco
        if (document.activeElement === productoSelect) {
            productosListDiv.style.display = hay ? 'block' : 'none';
        } else {
            productosListDiv.style.display = 'none';
        }
    }

    // Filtrar centros
    if (centroFilter) {
        centroFilter.addEventListener('focus', () => {
            centrosDropdown.style.display = 'block';
        });
        centroFilter.addEventListener('input', function() {
            const filtro = (this.value || '').toLowerCase();
            let any = false;
            centrosDropdown.querySelectorAll('div').forEach(div => {
                const txt = (div.textContent || '').toLowerCase();
                if (txt.includes(filtro)) {
                    div.style.display = 'block'; any = true;
                } else {
                    div.style.display = 'none';
                }
            });
            centrosDropdown.style.display = any ? 'block' : 'none';
        });
        centroFilter.addEventListener('keydown', function(evt) {
            if (evt.key === 'Backspace' || evt.key === 'Delete') {
                setTimeout(() => centroFilter.dispatchEvent(new Event('input')), 0);
            }
        });
    }

    // Seleccionar una opción (actualiza datos para producto)
    // Exponer función global para onclick inline
    window.seleccionarOpcion = function(e, element, inputId) {
        // evitar que el click burste el handler de documento
        if (e && e.preventDefault) e.preventDefault();
        if (e && e.stopPropagation) e.stopPropagation();
        const input = document.getElementById(inputId);
        input.value = element.textContent.trim();
         // Si es producto, guardar metadatos
         if (inputId === 'productoSelect') {
             input.dataset.id = element.getAttribute('data-id') || '';
             input.dataset.nombre = element.getAttribute('data-nombre') || '';
             input.dataset.proveedor = element.getAttribute('data-proveedor') || '';
             input.dataset.categoria = element.getAttribute('data-categoria') || '';
             input.dataset.unidad = element.getAttribute('data-unidad') || '';
             unidadMedidaSpan.textContent = input.dataset.unidad ? 'Unidad: ' + input.dataset.unidad : 'Unidad: -';
         }
         // Si es categoría, aplicar filtro en productos
         if (inputId === 'categoriaFilter') {
             filtrarProductosPorCategoria();
         }
        // ocultar dropdown del input seleccionado (categoria o producto)
        if (element && element.parentElement) element.parentElement.style.display = 'none';
         // mantener foco para permitir borrar/editar inmediatamente
         input.focus();
     }

    // Seleccionar centro
    window.seleccionarCentro = function(e, element) {
        if (e && e.preventDefault) e.preventDefault();
        if (e && e.stopPropagation) e.stopPropagation();
        const id = element.getAttribute('data-id');
        const nombre = element.getAttribute('data-nombre') || element.textContent.trim();
        $('#centroSelect').value = id;
        $('#centroFilter').value = nombre;
        centrosDropdown.style.display = 'none';
        $('#centroFilter').focus();
    }

    // Actualizar filtros en input events
    categoriaFilter.addEventListener('input', function() {
        // asegurar que el dropdown de categorías se muestre al escribir o borrar
        categoriasListDiv.style.display = 'block';
        filtrarDropdown(this, 'categoriasList');
        // actualizar listado oculto de productos, pero no mostrar el dropdown de productos
        filtrarProductosPorCategoria();
    });
    // Mostrar dropdown también al presionar Backspace/Delete para permitir re-aparecer
    categoriaFilter.addEventListener('keydown', function(evt) {
        if (evt.key === 'Backspace' || evt.key === 'Delete') {
            categoriasListDiv.style.display = 'block';
            // pequeño retardo para que el input.value ya refleje el cambio
            setTimeout(() => filtrarDropdown(this, 'categoriasList'), 0);
            filtrarProductosPorCategoria();
        }
    });
    categoriaFilter.addEventListener('keyup', function() {
        if ((this.value || '').trim() === '') {
            categoriasListDiv.style.display = 'block';
            filtrarDropdown(this, 'categoriasList');
            filtrarProductosPorCategoria();
        }
    });
    productoSelect.addEventListener('input', function() {
        productosListDiv.style.display = 'block';
        filtrarProductosPorCategoria();
    });
    productoSelect.addEventListener('keyup', function() {
        if ((this.value || '').trim() === '') {
            productosListDiv.style.display = 'block';
            filtrarProductosPorCategoria();
        }
    });

    // Ocultar al hacer click fuera
    document.addEventListener('click', function(e) {
        // categorías
        if (!categoriasListDiv.contains(e.target) && !categoriaFilter.contains(e.target)) {
            categoriasListDiv.style.display = 'none';
        }
        // productos
        if (!productosListDiv.contains(e.target) && !productoSelect.contains(e.target)) {
            productosListDiv.style.display = 'none';
        }
        // centros
        if (!centrosDropdown.contains(e.target) && !centroFilter.contains(e.target)) {
            centrosDropdown.style.display = 'none';
        }
    });

    // Event listeners para abrir/cerrar modales
    abrirBtn.addEventListener('click', () => {
        modalProducto.classList.remove('hidden');
        resetModalProducto();
        // reattach handlers in case markup refreshed
        attachOptionHandlers();
    });
    
    cerrarBtn.addEventListener('click', () => {
        modalProducto.classList.add('hidden');
        resetModalProducto();
    });
    
    cerrarDistribucionBtn.addEventListener('click', () => {
        modalDistribucion.classList.add('hidden');
        resetModalDistribucion();
    });
    
    siguienteBtn.addEventListener('click', () => {
        const productoTexto = productoSelect.value;
        const prodSeleccionado = productosData.find(p => `${p.nombre} (${p.unidad})` === productoTexto);
        cantidadTotal = parseInt(cantidadTotalInput.value);
        if (!prodSeleccionado) {
            mostrarError('Debes seleccionar un producto.');
            return;
        }
        if (!cantidadTotal || cantidadTotal < 1) {
            mostrarError('Debes ingresar una cantidad válida.');
            return;
        }
        if (productos.some(p => p.id === prodSeleccionado.id)) {
            mostrarError('Este producto ya fue agregado.');
            return;
        }
        // Configurar producto actual
        productoActual = {
            id: prodSeleccionado.id,
            nombre: prodSeleccionado.nombre,
            proveedorId: prodSeleccionado.proveedor || null,
            cantidadTotal,
            unidad: prodSeleccionado.unidad,
            centros: []
        };
        cantidadAsignada = 0;
        unidadMedida = prodSeleccionado.unidad;
        // Actualizar información en modal de distribución
        productoSeleccionadoNombre.textContent = prodSeleccionado.nombre;
        productoSeleccionadoCantidad.textContent = cantidadTotal;
        productoSeleccionadoUnidad.textContent = prodSeleccionado.unidad;
        cantidadDisponibleSpan.textContent = cantidadTotal;
        unidadDisponibleSpan.textContent = prodSeleccionado.unidad;
        totalAsignadoSpan.textContent = cantidadAsignada;
        centrosList.innerHTML = '';
        // Cambiar de modal
        modalProducto.classList.add('hidden');
        modalDistribucion.classList.remove('hidden');
    });
    
    volverBtn.addEventListener('click', () => {
        modalDistribucion.classList.add('hidden');
        modalProducto.classList.remove('hidden');
        resetModalDistribucion();
    });

    function mostrarError(mensaje) {
        Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            text: mensaje, 
            confirmButtonText: 'Entendido' 
        });
    }
    
    function mostrarCarga() {
        cargandoAlert.classList.remove('hidden');
    }
    
    function ocultarCarga() {
        cargandoAlert.classList.add('hidden');
    }

    function resetModalProducto() {
        productoSelect.value = '';
        cantidadTotalInput.value = '';
        categoriaFilter.value = '';
        unidadMedidaSpan.textContent = 'Unidad: -';
        
        // Mostrar todos los productos nuevamente
        const opcionesProductos = productoSelect.querySelectorAll('option');
        opcionesProductos.forEach(opcion => {
            opcion.style.display = '';
        });
    }
    
    function resetModalDistribucion() {
        centroSelect.value = '';
        cantidadCentroInput.value = '';
        productoActual = null;
        cantidadTotal = 0;
        cantidadAsignada = 0;
        unidadMedida = '';
    }

    function actualizarResumen() {
        totalAsignadoSpan.textContent = cantidadAsignada;
    }

    function actualizarTabla() {
        productosTable.innerHTML = "";
        productos.forEach((prod, i) => {
            let centrosHTML = "";
            prod.centros.forEach((centro, j) => {
                centrosHTML += `
                    <span class="inline-block bg-gray-200 px-2 py-1 rounded-full text-xs mr-1 mb-1">
                        ${centro.nombre} <b>(${centro.cantidad} ${prod.unidad})</b>
                    </span>
                    <input type="hidden" name="productos[${i}][centros][${j}][id]" value="${centro.id}">
                    <input type="hidden" name="productos[${i}][centros][${j}][cantidad]" value="${centro.cantidad}">
                `;
            });
            
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="p-3">
                    ${prod.nombre} (${prod.unidad})
                    <input type="hidden" name="productos[${i}][id]" value="${prod.id}">
                    ${prod.proveedorId ? `<input type="hidden" name="productos[${i}][proveedor_id]" value="${prod.proveedorId}">` : ''}
                    <input type="hidden" name="productos[${i}][unidad]" value="${prod.unidad}">
                </td>
                <td class="p-3">
                    ${prod.cantidadTotal} ${prod.unidad}
                    <input type="hidden" name="productos[${i}][requisicion_amount]" value="${prod.cantidadTotal}">
                </td>
                <td class="p-3">${centrosHTML}</td>
                <td class="p-3 text-right">
                    <button type="button" onclick="eliminarProducto(${i})" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">
                        Eliminar
                    </button>
                </td>
            `;
            productosTable.appendChild(tr);
        });
    }

    window.eliminarProducto = function(index) {
        productos.splice(index, 1);
        actualizarTabla();
    };

    agregarCentroBtn.addEventListener('click', () => {
        if (!productoActual) return;
        const centroIdVal = $('#centroSelect').value;
        const centroNombre = $('#centroFilter').value;
        const cantidadCentro = parseInt(cantidadCentroInput.value);
        const cantidadRestante = cantidadTotal - cantidadAsignada;

        if (!centroIdVal) {
            mostrarError('Debes seleccionar un centro de costo.');
            return;
        }
        if (!cantidadCentro || cantidadCentro < 1) {
            mostrarError('Debes ingresar una cantidad válida.');
            return;
        }
        if (cantidadCentro > cantidadRestante) {
            mostrarError(`No puedes asignar más de ${cantidadRestante} ${unidadMedida}.`);
            return;
        }

        const idx = productoActual.centros.findIndex(c => c.id === centroIdVal);
        if (idx >= 0) {
            productoActual.centros[idx].cantidad += cantidadCentro;
        } else {
            productoActual.centros.push({ id: centroIdVal, nombre: centroNombre, cantidad: cantidadCentro });
        }

        cantidadAsignada += cantidadCentro;
        centrosList.innerHTML = '';
        productoActual.centros.forEach(c => {
            const li = document.createElement('li');
            li.className = 'py-2 px-3 flex justify-between items-center';
            li.innerHTML = `
                <span>${c.nombre}</span>
                <span class="font-semibold">${c.cantidad} ${unidadMedida}</span>
            `;
            centrosList.appendChild(li);
        });

        actualizarResumen();
        cantidadCentroInput.value = '';
        $('#centroSelect').value = '';
        $('#centroFilter').value = '';
    });

    guardarProductoBtn.addEventListener('click', () => {
        if (!productoActual || productoActual.centros.length === 0) {
            mostrarError('Debes añadir al menos un centro de costo.');
            return;
        }
        
        if (cantidadAsignada !== cantidadTotal) {
            mostrarError(`Debes distribuir toda la cantidad (${cantidadTotal - cantidadAsignada} ${unidadMedida} restantes).`);
            return;
        }
        
        productos.push(productoActual);
        actualizarTabla();
        
        // Cerrar modal y resetear
        modalDistribucion.classList.add('hidden');
        resetModalDistribucion();
        resetModalProducto();
    });

    requisicionForm.addEventListener('submit', function(e) {
        if (productos.length === 0) {
            e.preventDefault();
            mostrarError('Debes agregar al menos un producto.');
            return;
        }
        
        // Mostrar alerta de carga
        mostrarCarga();
        
        // El formulario se enviará normalmente después de esto
    });
});
</script>
@endsection