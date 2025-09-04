@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<x-sidebar />

<div class="max-w-7xl mx-auto p-6 mt-20 bg-white rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6 text-gray-800">
        Crear Orden de Compra 
        @if(isset($requisicion))
            - Requisición #{{ $requisicion->id }}
        @endif
    </h1>

    <form action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
        @csrf

        @if(isset($requisicion))
            <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">
        @else
            <input type="hidden" name="requisicion_id" value="0">
        @endif
        
        <input type="hidden" name="order_oc" value="{{ $orderNumber }}">

        <!-- Datos generales de la orden -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Número de Orden</label>
                <input type="text" value="{{ $orderNumber }}" class="w-full border rounded-lg p-2 bg-gray-100" readonly>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Fecha *</label>
                <input type="date" name="date_oc" value="{{ date('Y-m-d') }}" class="w-full border rounded-lg p-2" required>
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                <input type="text" name="methods_oc" class="w-full border rounded-lg p-2" placeholder="Ej: Transferencia bancaria">
            </div>
            <div>
                <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                <input type="text" name="plazo_oc" class="w-full border rounded-lg p-2" placeholder="Ej: 30 días">
            </div>
        </div>

        <!-- Observaciones -->
        <div>
            <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3" class="w-full border rounded-lg p-2" placeholder="Observaciones adicionales para la orden de compra"></textarea>
        </div>

        <!-- Proveedor -->
        <div>
            <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
            <select name="proveedor_id" class="w-full border rounded-lg p-2" required>
                <option value="">-- Selecciona un proveedor --</option>
                @if(isset($proveedoresProductos))
                    @foreach($proveedoresProductos as $proveedor)
                    <option value="{{ $proveedor->id }}" {{ old('proveedor_id', $proveedorPreseleccionado) == $proveedor->id ? 'selected' : '' }}>
                        {{ $proveedor->name_proveedor }}
                    </option>
                    @endforeach
                @else
                    @foreach($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">
                        {{ $proveedor->name_proveedor }}
                    </option>
                    @endforeach
                @endif
            </select>
        </div>

        <!-- Tabla de productos -->
        @if(isset($requisicion) && $requisicion->productos->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full border border-gray-300 rounded-lg text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 text-left">Producto</th>
                        <th class="p-3 text-left">Cantidad</th>
                        <th class="p-3 text-left">Precio Unitario *</th>
                        <th class="p-3 text-left">Distribución por Centros</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requisicion->productos as $index => $producto)
                    @php
                    $distribucion = $distribucionCentros[$producto->id] ?? collect([]);
                    @endphp
                    <tr class="border-t">
                        <td class="p-3 border">
                            {{ $producto->name_produc }}
                            <input type="hidden" name="productos[{{ $index }}][id]" value="{{ $producto->id }}">
                        </td>
                        <td class="p-3 border">
                            <input type="number" name="productos[{{ $index }}][cantidad]" 
                                   value="{{ $producto->pivot->pr_amount }}" 
                                   class="w-24 border rounded p-1" required min="1">
                        </td>
                        <td class="p-3 border">
                            <input type="number" step="0.01" name="productos[{{ $index }}][precio]" 
                                   class="w-32 border rounded p-1" required min="0" placeholder="0.00">
                        </td>
                        <td class="p-3 border">
                            @if($distribucion->count() > 0)
                            @foreach($distribucion as $centroIndex => $centro)
                            <div class="mb-2 p-2 bg-gray-50 rounded">
                                <span class="font-medium">{{ $centro->name_centro }}</span>
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold ml-2">
                                    {{ $centro->amount }}
                                </span>
                                <input type="hidden" name="productos[{{ $index }}][centros][{{ $centroIndex }}][id]" value="{{ $centro->centro_id }}">
                                <input type="hidden" name="productos[{{ $index }}][centros][{{ $centroIndex }}][cantidad]" value="{{ $centro->amount }}">
                            </div>
                            @endforeach
                            @else
                            <p class="text-sm text-gray-500">No hay distribución registrada</p>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            <p>No hay productos en la requisición o no se ha seleccionado una requisición.</p>
        </div>
        @endif

        <!-- Botones -->
        <div class="flex justify-end gap-4">
            <a href="{{ route('ordenes_compra.lista') }}" class="bg-gray-600 text-white px-6 py-2 rounded-lg shadow hover:bg-gray-700">
                Cancelar
            </a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                Guardar Orden de Compra
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación básica del formulario
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            let isValid = true;
            const precioInputs = document.querySelectorAll('input[name$="[precio]"]');
            
            precioInputs.forEach(input => {
                if (!input.value || parseFloat(input.value) <= 0) {
                    isValid = false;
                    input.style.borderColor = 'red';
                } else {
                    input.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Por favor, ingrese precios válidos para todos los productos.');
            }
        });
    }
});
</script>
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
    
    // Mostrar unidad de medida y validar stock al seleccionar producto
    productoSelect.addEventListener('change', function() {
        if (this.value) {
            const selectedOption = this.selectedOptions[0];
            unidadMedida = selectedOption.dataset.unidad;
            const stock = parseInt(selectedOption.dataset.stock) || 0;
            
            unidadMedidaSpan.textContent = `Unidad: ${unidadMedida}`;
            
            // Validar si el producto tiene stock
            if (stock > 0) {
                mostrarAlertaStock(stock, unidadMedida);
            }
        } else {
            unidadMedida = '';
            unidadMedidaSpan.textContent = 'Unidad: -';
        }
    });
    
    siguienteBtn.addEventListener('click', () => {
        const productoId = productoSelect.value;
        cantidadTotal = parseInt(cantidadTotalInput.value);
        
        if (!productoId) {
            mostrarError('Debes seleccionar un producto.');
            return;
        }
        
        if (!cantidadTotal || cantidadTotal < 1) {
            mostrarError('Debes ingresar una cantidad válida.');
            return;
        }
        
        if (productos.some(p => p.id === productoId)) {
            mostrarError('Este producto ya fue agregado.');
            return;
        }
        
        // Obtener datos del producto seleccionado
        const selectedOption = productoSelect.selectedOptions[0];
        const unidad = selectedOption.dataset.unidad;
        const stock = parseInt(selectedOption.dataset.stock) || 0;
        
        // Configurar producto actual
        productoActual = { 
            id: productoId, 
            nombre: selectedOption.dataset.nombre, 
            proveedorId: selectedOption.dataset.proveedor || null, 
            cantidadTotal, 
            unidad,
            stock,
            centros: [] 
        };
        
        cantidadAsignada = 0;
        unidadMedida = unidad;
        
        // Actualizar información en modal de distribución
        productoSeleccionadoNombre.textContent = selectedOption.dataset.nombre;
        productoSeleccionadoCantidad.textContent = cantidadTotal;
        productoSeleccionadoUnidad.textContent = unidad;
        cantidadDisponibleSpan.textContent = cantidadTotal;
        unidadDisponibleSpan.textContent = unidad;
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

    // Filtrar productos por categoría
    categoriaFilter.addEventListener('change', () => {
        const categoriaSeleccionada = categoriaFilter.value;
        const opcionesProductos = productoSelect.querySelectorAll('option');
        
        opcionesProductos.forEach(opcion => {
            if (opcion.value === '') return;
            opcion.style.display = (categoriaSeleccionada === '' || opcion.dataset.categoria === categoriaSeleccionada) ? '' : 'none';
        });
        
        productoSelect.value = '';
        unidadMedidaSpan.textContent = 'Unidad: -';
    });

    function mostrarError(mensaje) {
        Swal.fire({ 
            icon: 'error', 
            title: 'Error', 
            text: mensaje, 
            confirmButtonText: 'Entendido' 
        });
    }
    
    function mostrarAlertaStock(stock, unidad) {
        Swal.fire({
            icon: 'info',
            title: 'Producto con stock disponible',
            html: `Este producto tiene <b>${stock} ${unidad}</b> disponibles en inventario.<br><br>¿Desea continuar con la requisición?`,
            showCancelButton: true,
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isDismissed) {
                // Si el usuario cancela, limpiar la selección
                productoSelect.value = '';
                unidadMedidaSpan.textContent = 'Unidad: -';
            }
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
                    <input type="hidden" name="productos[${i}][stock]" value="${prod.stock}">
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