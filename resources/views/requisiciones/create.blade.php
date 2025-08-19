@extends('layouts.app')

@section('title', 'Crear Requisición')
<script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@section('content')
<div class="max-w-5xl mx-auto p-6">
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
                }).then(() => {
                    window.location.href = "{{ route('index') }}";
                });
            });
        </script>
        @endif

        @if ($errors->any()))
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
                        <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>Recobrable</option>
                        <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No recobrable</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-600 font-semibold mb-1">Prioridad</label>
                    <select name="prioridad_requisicion" class="w-full border rounded-lg p-2" required>
                        <option value="">-- Selecciona --</option>
                        <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja</option>
                        <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media</option>
                        <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Justificación</label>
                <textarea name="justify_requisicion" rows="3" class="w-full border rounded-lg p-2" required>{{ old('justify_requisicion') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-600 font-semibold mb-1">Detalles Adicionales</label>
                <textarea name="detail_requisicion" rows="3" class="w-full border rounded-lg p-2" required>{{ old('detail_requisicion') }}</textarea>
            </div>

            <hr class="my-4">

            <div class="flex justify-between items-center">
                <h3 class="text-xl font-bold text-gray-700">Productos agregados</h3>
                <button type="button" id="abrirModalBtn" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700">
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
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg shadow hover:bg-green-700">
                    Guardar Requisición
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal -->
<div id="modalProducto" class="fixed inset-0 flex hidden items-center justify-center bg-black bg-opacity-50 z-50">
    <div class="bg-white rounded-2xl shadow-xl max-w-3xl w-full p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-700">Añadir Producto</h2>
            <button id="cerrarModalBtn" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>

        <!-- Filtro de categoría -->
        <div class="mb-4">
            <label class="block text-gray-600 font-semibold mb-1">Filtrar por Categoría</label>
            <select id="categoriaFilter" class="w-full border rounded-lg p-2">
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
        <div class="grid grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Producto</label>
                <select id="productoSelect" class="w-full border rounded-lg p-2">
                    <option value="">-- Selecciona producto --</option>
                    @foreach ($productos as $p)
                    <option value="{{ $p->id }}" data-nombre="{{ $p->name_produc }}"
                        data-proveedor="{{ $p->proveedor_id ?? '' }}" data-categoria="{{ $p->categoria_produc }}">
                        {{ $p->name_produc }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Cantidad Total</label>
                <input type="number" id="cantidadTotalInput" class="w-full border rounded-lg p-2" min="1" placeholder="Ej: 100">
            </div>
            <div>
                <button type="button" id="iniciarProductoBtn" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                    Distribuir en Centros
                </button>
            </div>
        </div>

        <!-- Distribución por centros -->
        <div id="centrosSection" class="hidden mt-6">
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
                    <input type="number" id="cantidadCentroInput" class="w-full border rounded-lg p-2" min="1" placeholder="Ej: 50">
                </div>
                <div>
                    <button type="button" id="agregarCentroBtn" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        Agregar
                    </button>
                </div>
            </div>

            <div class="mt-4 text-sm font-semibold text-gray-600">
                Total asignado: <span id="totalAsignado">0</span> de <span id="cantidadDisponible">0</span>
            </div>

            <ul id="centrosList" class="divide-y divide-gray-200 mt-3"></ul>

            <div class="flex justify-end mt-4">
                <button type="button" id="guardarProductoBtn" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700">
                    Guardar Producto
                </button>
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

    // Modal
    const modal = $('#modalProducto');
    const abrirBtn = $('#abrirModalBtn');
    const cerrarBtn = $('#cerrarModalBtn');
    abrirBtn.addEventListener('click', () => modal.classList.remove('hidden'));
    cerrarBtn.addEventListener('click', () => modal.classList.add('hidden'));

    // Elementos
    const iniciarProductoBtn = $('#iniciarProductoBtn');
    const agregarCentroBtn = $('#agregarCentroBtn');
    const guardarProductoBtn = $('#guardarProductoBtn');
    const productoSelect = $('#productoSelect');
    const cantidadTotalInput = $('#cantidadTotalInput');
    const centroSelect = $('#centroSelect');
    const cantidadCentroInput = $('#cantidadCentroInput');
    const centrosSection = $('#centrosSection');
    const centrosList = $('#centrosList');
    const productosTable = $('#productosTable tbody');
    const requisicionForm = $('#requisicionForm');
    const totalAsignadoSpan = $('#totalAsignado');
    const cantidadDisponibleSpan = $('#cantidadDisponible');
    const categoriaFilter = $('#categoriaFilter');

    let productos = [];
    let productoActual = null;
    let cantidadTotal = 0;
    let cantidadAsignada = 0;

    // Filtrar productos por categoría
    categoriaFilter.addEventListener('change', () => {
        const categoriaSeleccionada = categoriaFilter.value;
        const opcionesProductos = productoSelect.querySelectorAll('option');
        opcionesProductos.forEach(opcion => {
            if (opcion.value === '') return opcion.style.display = '';
            opcion.style.display = (categoriaSeleccionada === '' || opcion.dataset.categoria === categoriaSeleccionada) ? '' : 'none';
        });
        productoSelect.value = '';
    });

    function mostrarError(mensaje) {
        Swal.fire({ icon: 'error', title: 'Error', text: mensaje, confirmButtonText: 'Entendido' });
    }

    function actualizarResumen() {
        totalAsignadoSpan.textContent = cantidadAsignada;
        cantidadDisponibleSpan.textContent = cantidadTotal;
    }

    function actualizarTabla() {
        productosTable.innerHTML = "";
        productos.forEach((prod, i) => {
            let centrosHTML = "";
            prod.centros.forEach((centro, j) => {
                centrosHTML += `
                    <span class="inline-block bg-gray-200 px-2 py-1 rounded-full text-xs mr-1 mb-1">
                        ${centro.nombre} <b>(${centro.cantidad})</b>
                    </span>
                    <input type="hidden" name="productos[${i}][centros][${j}][id]" value="${centro.id}">
                    <input type="hidden" name="productos[${i}][centros][${j}][cantidad]" value="${centro.cantidad}">
                `;
            });
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="p-3">
                    ${prod.nombre}
                    <input type="hidden" name="productos[${i}][id]" value="${prod.id}">
                    ${prod.proveedorId ? `<input type="hidden" name="productos[${i}][proveedor_id]" value="${prod.proveedorId}">` : ''}
                </td>
                <td class="p-3">
                    ${prod.cantidadTotal}
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

    iniciarProductoBtn.addEventListener('click', () => {
        const productoId = productoSelect.value;
        cantidadTotal = parseInt(cantidadTotalInput.value);
        if (!productoId || !cantidadTotal || cantidadTotal < 1) return mostrarError('Selecciona un producto y una cantidad válida.');
        if (productos.some(p => p.id === productoId)) return mostrarError('Este producto ya fue agregado.');
        productoActual = { id: productoId, nombre: productoSelect.selectedOptions[0].dataset.nombre, proveedorId: productoSelect.selectedOptions[0].dataset.proveedor || null, cantidadTotal, centros: [] };
        cantidadAsignada = 0;
        centrosSection.classList.remove('hidden');
        centrosList.innerHTML = '';
        actualizarResumen();
    });

    agregarCentroBtn.addEventListener('click', () => {
        if (!productoActual) return;
        const centroId = centroSelect.value;
        const cantidadCentro = parseInt(cantidadCentroInput.value);
        const cantidadRestante = cantidadTotal - cantidadAsignada;
        if (!centroId || !cantidadCentro || cantidadCentro < 1) return mostrarError('Selecciona un centro y una cantidad válida.');
        if (cantidadCentro > cantidadRestante) return mostrarError(`No puedes asignar más de ${cantidadRestante} unidades.`);
        const idx = productoActual.centros.findIndex(c => c.id === centroId);
        if (idx >= 0) productoActual.centros[idx].cantidad += cantidadCentro;
        else productoActual.centros.push({ id: centroId, nombre: centroSelect.selectedOptions[0].dataset.nombre, cantidad: cantidadCentro });
        cantidadAsignada += cantidadCentro;
        centrosList.innerHTML = '';
        productoActual.centros.forEach(c => {
            const li = document.createElement('li');
            li.className = 'py-1 text-sm';
            li.textContent = `${c.nombre} - ${c.cantidad} unidades`;
            centrosList.appendChild(li);
        });
        actualizarResumen();
        cantidadCentroInput.value = '';
        centroSelect.value = '';
    });

    guardarProductoBtn.addEventListener('click', () => {
        if (!productoActual || productoActual.centros.length === 0) return mostrarError('Debes añadir al menos un centro.');
        if (cantidadAsignada !== cantidadTotal) return mostrarError(`Debes distribuir toda la cantidad (${cantidadTotal - cantidadAsignada} restantes).`);
        productos.push(productoActual);
        productoActual = null; cantidadTotal = 0; cantidadAsignada = 0;
        centrosSection.classList.add('hidden');
        productoSelect.value = ''; cantidadTotalInput.value = '';
        actualizarTabla();
        modal.classList.add('hidden'); // cerrar modal al guardar
    });

    requisicionForm.addEventListener('submit', e => {
        if (productos.length === 0) {
            e.preventDefault();
            mostrarError('Agrega al menos un producto.');
        }
    });
});
</script>
@endsection