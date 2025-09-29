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
        <div class="mb-4">
            <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
            <input list="categoriasList" id="categoriaFilter" class="w-full border rounded-lg p-2"
                placeholder="Escribe o selecciona una categoría">
            <datalist id="categoriasList">
                @php
                $categoriasUnicas = $productos->pluck('categoria_produc')->unique()->sort();
                @endphp
                @foreach ($categoriasUnicas->take(5) as $categoria)
                <option value="{{ $categoria }}"></option>
                @endforeach
            </datalist>
        </div>

        <!-- Selección de producto y cantidad -->
        <div class="grid grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Producto</label>
                <input list="productosList" id="productoSelect" class="w-full border rounded-lg p-2"
                    placeholder="Escribe o selecciona un producto">
                <datalist id="productosList">
                    @foreach ($productos->take(5) as $p)
                    <option value="{{ $p->name_produc }} ({{ $p->unit_produc }})" data-id="{{ $p->id }}"
                        data-nombre="{{ $p->name_produc }}" data-proveedor="{{ $p->proveedor_id ?? '' }}"
                        data-categoria="{{ $p->categoria_produc }}" data-unidad="{{ $p->unit_produc }}">
                    </option>
                    @endforeach
                </datalist>
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
                    <select id="centroSelect" class="w-full border rounded-lg p-2">
                        <option value="">-- Selecciona centro --</option>
                        @foreach ($centros as $c)
                        <option value="{{ $c->id }}" data-nombre="{{ $c->name_centro }}">{{ $c->name_centro }}</option>
                        @endforeach
                    </select>
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

    // ====== Categorías ======
    categoriaFilter.setAttribute("list", "categoriasList");
    categoriaFilter.addEventListener("input", () => {
        renderDatalist(categoriaFilter, categoriasList, categorias, c => c);
        // Al cambiar la categoría, filtrar productos
        filtrarProductosPorCategoria();
    });

    // ====== Productos ======
    productoSelect.setAttribute("list", "productosList");
    productoSelect.addEventListener("input", () => {
        filtrarProductosPorCategoria();
        mostrarUnidadSeleccionada();
    });

    function filtrarProductosPorCategoria() {
        const categoriaSeleccionada = categoriaFilter.value;
        let filtrados = productosData;
        if (categoriaSeleccionada && categorias.includes(categoriaSeleccionada)) {
            filtrados = filtrados.filter(p => p.categoria === categoriaSeleccionada);
        }
        renderDatalist(productoSelect, productosList, filtrados, p => `${p.nombre} (${p.unidad})`);
    }

    function mostrarUnidadSeleccionada() {
        const texto = productoSelect.value;
        const prod = productosData.find(p => `${p.nombre} (${p.unidad})` === texto);
        if (prod) {
            unidadMedidaSpan.textContent = 'Unidad: ' + prod.unidad;
        } else {
            unidadMedidaSpan.textContent = 'Unidad: -';
        }
    }

    // Inicializar datalist al abrir modal
    abrirBtn.addEventListener('click', () => {
        renderDatalist(categoriaFilter, categoriasList, categorias, c => c);
        filtrarProductosPorCategoria();
        mostrarUnidadSeleccionada();
    });

    // Event listeners para abrir/cerrar modales
    abrirBtn.addEventListener('click', () => {
        modalProducto.classList.remove('hidden');
        resetModalProducto();
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
        
        const centroId = centroSelect.value;
        const cantidadCentro = parseInt(cantidadCentroInput.value);
        const cantidadRestante = cantidadTotal - cantidadAsignada;
        
        if (!centroId) {
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
        
        // Verificar si el centro ya existe
        const idx = productoActual.centros.findIndex(c => c.id === centroId);
        
        if (idx >= 0) {
            // Si el centro ya existe, sumar la cantidad
            productoActual.centros[idx].cantidad += cantidadCentro;
        } else {
            // Si es un centro nuevo, agregarlo
            productoActual.centros.push({ 
                id: centroId, 
                nombre: centroSelect.selectedOptions[0].dataset.nombre, 
                cantidad: cantidadCentro 
            });
        }
        
        cantidadAsignada += cantidadCentro;
        
        // Actualizar la lista de centros
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
        centroSelect.value = '';
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