@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    Crear Orden de Compra
                </h1>
                <a href="{{ route('ordenes_compra.lista') }}" 
                   class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                    ← Volver
                </a>
            </div>

            <!-- Mensaje éxito -->
            @if(session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'Aceptar'
                });
            </script>
            @endif

            <!-- ================= Datos de la Requisición ================= -->
            @if($requisicion)
            <div class="mb-8 border rounded-lg bg-white p-6 relative shadow-sm">
                <div class="absolute top-4 right-4">
                    @if($requisicion->ordenCompra?->id)
                    <a href="{{ route('ordenes_compra.edit', $requisicion->ordenCompra->id) }}"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center">
                        <i class="fas fa-edit mr-2"></i> Editar Orden de Compra
                    </a>
                    @endif
                </div>

                <h2 class="text-xl font-semibold text-gray-700 mb-4">Requisición #{{ $requisicion->id }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <h3 class="font-semibold text-gray-700 mb-2">Solicitante</h3>
                        <p><strong>Nombre:</strong> {{ $requisicion->name_user }}</p>
                        <p><strong>Email:</strong> {{ $requisicion->email_user }}</p>
                        <p><strong>Operación:</strong> {{ $requisicion->operacion_user }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg border">
                        <h3 class="font-semibold text-gray-700 mb-2">Información General</h3>
                        <p><strong>Prioridad:</strong>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold
                                {{ $requisicion->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' :
                                   ($requisicion->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' :
                                   'bg-green-100 text-green-800') }}">
                                {{ ucfirst($requisicion->prioridad_requisicion) }}
                            </span>
                        </p>
                        <p><strong>Recobrable:</strong> {{ $requisicion->Recobrable }}</p>
                    </div>
                </div>

                <div class="mb-4">
                    <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
                    <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                </div>
            </div>
            @endif

            <!-- Formulario para Crear Orden -->
            @if($requisicion)
            <div class="border p-6 mb-6 rounded-lg shadow bg-white">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">
                    Nueva Orden de Compra
                </h2>

                <form id="orden-form" action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-600 font-semibold mb-1">Proveedor *</label>
                            <select id="proveedor_id" name="proveedor_id" class="w-full border rounded-lg p-2" required>
                                <option value="">Seleccione un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-600 font-semibold mb-1">Método de Pago</label>
                            <select name="methods_oc" class="w-full border rounded-lg p-2">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-gray-600 font-semibold mb-1">Plazo de Pago</label>
                            <select name="plazo_oc" class="w-full border rounded-lg p-2">
                                <option value="Contado">Contado</option>
                                <option value="30 días">30 días</option>
                                <option value="45 días">45 días</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-600 font-semibold mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="2" class="w-full border rounded-lg p-2"></textarea>
                        </div>
                    </div>

                    <!-- Selector de productos -->
                    <div class="mt-6">
                        <label class="block text-gray-600 font-semibold mb-1">Añadir Producto</label>
                        <div class="flex gap-3">
                            <select id="producto-selector" class="w-full border rounded-lg p-2">
                                <option value="">Seleccione un producto</option>
                                @foreach($productosDisponibles as $producto)
                                <option value="{{ $producto->id }}" data-proveedor="{{ $producto->proveedor_id ?? '' }}"
                                    data-unidad="{{ $producto->unit_produc }}" data-nombre="{{ $producto->name_produc }}">
                                    {{ $producto->name_produc }} ({{ $producto->unit_produc }})
                                </option>
                                @endforeach
                            </select>
                            <button type="button" onclick="agregarProducto()"
                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                                ➕ Añadir
                            </button>
                        </div>
                    </div>

                    <!-- Tabla productos -->
                    <div class="overflow-x-auto mt-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-2">Productos en la Orden</h3>
                        <table class="w-full border text-sm rounded-lg overflow-hidden">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-left">Producto</th>
                                    <th class="p-3 text-center">Cantidad</th>
                                    <th class="p-3 text-center">Unidad</th>
                                    <th class="p-3 text-center">Distribución por Centros</th>
                                    <th class="p-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productos-table"></tbody>
                        </table>
                    </div>

                    <!-- Botón submit -->
                    <div class="flex justify-end gap-4 mt-6">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg shadow">
                            Crear Orden de Compra
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de órdenes creadas -->
            <div class="border p-6 mt-10 rounded-lg shadow bg-white">
                <h2 class="text-xl font-semibold text-gray-700 mb-4">Órdenes de Compra Creadas</h2>
                <table class="w-full border text-sm rounded-lg overflow-hidden" id="ordenes-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Número</th>
                            <th class="p-3">Proveedor</th>
                            <th class="p-3">Productos</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $productosOrdenes = \App\Models\OrdenCompraProducto::whereHas('ordenCompra', function($query) use ($requisicion) {
                            $query->where('requisicion_id', $requisicion->id);
                        })
                        ->with(['ordenCompra.proveedor', 'producto'])
                        ->get()
                        ->groupBy('orden_compra_id');
                        @endphp

                        @foreach($productosOrdenes as $ordenId => $productos)
                        @php
                        $orden = $productos->first()->ordenCompra;
                        @endphp
                        <tr>
                            <td class="p-3">{{ $loop->iteration }}</td>
                            <td class="p-3">{{ $orden->order_oc }}</td>
                            <td class="p-3">
                                {{ $orden->proveedor ? $orden->proveedor->prov_name : 'Proveedor no disponible' }}
                            </td>
                            <td class="p-3">
                                @foreach($productos as $productoOrden)
                                    @if($productoOrden->producto)
                                        {{ $productoOrden->producto->name_produc }}
                                        ({{ $productoOrden->cantidad }} {{ $productoOrden->producto->unit_produc }})<br>
                                    @else
                                        Producto eliminado ({{ $productoOrden->cantidad }})<br>
                                    @endif
                                @endforeach
                            </td>
                            <td class="p-3 text-center">
                                <form action="{{ route('ordenes_compra.anular', $orden->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" onclick="confirmarAnulacion(this)"
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                        Anular
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <!-- Botón descargar ZIP -->
                <div class="mt-6 text-right {{ count($productosDisponibles) > 0 ? 'hidden' : '' }}" id="zip-container">
                    <a href="{{ route('ordenes_compra.downloadZip', $requisicion->id) }}"
                       class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
                        Descargar ZIP de Órdenes
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    let productosAgregados = [];
    let centros = @json($centros);
    
    function agregarProducto() {
        let selector = document.getElementById('producto-selector');
        let proveedorSelect = document.getElementById('proveedor_id');

        let productoId = selector.value;
        let productoNombre = selector.options[selector.selectedIndex]?.dataset.nombre;
        let proveedorId = selector.options[selector.selectedIndex]?.dataset.proveedor;
        let unidad = selector.options[selector.selectedIndex]?.dataset.unidad || '';

        if (!productoId) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto'});
            return;
        }

        if (productosAgregados.includes(productoId)) {
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Este producto ya fue agregado'});
            return;
        }

        let proveedorActual = proveedorSelect.value;
        if (proveedorActual && proveedorId && proveedorActual != proveedorId) {
            Swal.fire({
                icon: 'warning', 
                title: 'Diferente proveedor', 
                text: 'Este producto pertenece a otro proveedor. ¿Desea cambiar el proveedor seleccionado?',
                showCancelButton: true,
                confirmButtonText: 'Sí, cambiar',
                cancelButtonText: 'No, mantener'
            }).then((result) => {
                if (result.isConfirmed) {
                    proveedorSelect.value = proveedorId;
                    agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, selector);
                }
            });
        } else {
            agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, selector);
        }
    }

    function agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, selector) {
        let table = document.getElementById('productos-table');
        let proveedorSelect = document.getElementById('proveedor_id');
        
        let row = document.createElement('tr');
        row.id = `producto-${productoId}`;
        
        // Generar campos de distribución por centros
        let centrosHtml = '';
        centros.forEach(centro => {
            centrosHtml += `
                <div class="mb-2">
                    <label class="block text-sm text-gray-600">${centro.name_centro}:</label>
                    <input type="number" name="productos[${productoId}][centros][${centro.id}]" 
                           min="0" value="0" class="w-20 border rounded p-1 text-center distribucion-centro"
                           data-producto="${productoId}" onchange="actualizarTotal(${productoId})">
                </div>
            `;
        });
        
        row.innerHTML = `
            <td class="p-3">
                ${productoNombre}
                <input type="hidden" name="productos[${productoId}][id]" value="${productoId}" 
                    data-proveedor="${proveedorId}" data-unidad="${unidad}" data-nombre="${productoNombre}">
            </td>
            <td class="p-3 text-center">
                <input type="number" name="productos[${productoId}][cantidad]" min="1" value="1" 
                    class="w-20 border rounded p-1 text-center cantidad-total" 
                    id="cantidad-total-${productoId}" 
                    onchange="distribuirAutomaticamente(${productoId})" required>
            </td>
            <td class="p-3 text-center">${unidad}</td>
            <td class="p-3">
                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                    ${centrosHtml}
                </div>
            </td>
            <td class="p-3 text-center space-x-2">
                <button type="button" onclick="quitarProducto(${productoId})" 
                    class="bg-red-500 text-white px-3 py-1 rounded-lg">Quitar</button>
            </td>
        `;
        table.appendChild(row);

        if (proveedorId && !proveedorSelect.value) {
            proveedorSelect.value = proveedorId;
        }

        productosAgregados.push(productoId);
        
        for (let i = 0; i < selector.options.length; i++) {
            if (selector.options[i].value == productoId) {
                selector.remove(i);
                break;
            }
        }
        selector.value = "";

        if (selector.options.length === 1) {
            document.getElementById('zip-container').classList.remove('hidden');
        }
    }

    function actualizarTotal(productoId) {
        const inputs = document.querySelectorAll(`input[name="productos[${productoId}][centros][]"]`);
        let total = 0;
        
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        document.getElementById(`cantidad-total-${productoId}`).value = total;
    }

    function distribuirAutomaticamente(productoId) {
        const total = parseInt(document.getElementById(`cantidad-total-${productoId}`).value) || 0;
        const inputs = document.querySelectorAll(`input[name="productos[${productoId}][centros][]"]`);
        
        if (inputs.length > 0 && total > 0) {
            const cantidadPorCentro = Math.floor(total / inputs.length);
            const resto = total % inputs.length;
            
            inputs.forEach((input, index) => {
                input.value = index < resto ? cantidadPorCentro + 1 : cantidadPorCentro;
            });
        }
    }

    function quitarProducto(productoId) {
        let row = document.getElementById(`producto-${productoId}`);
        let selector = document.getElementById('producto-selector');
        
        if (row) {
            row.remove();
            productosAgregados = productosAgregados.filter(id => id != productoId);
            
            const inputHidden = row.querySelector('input[type="hidden"]');
            const proveedorId = inputHidden.dataset.proveedor;
            const unidad = inputHidden.dataset.unidad;
            const nombre = inputHidden.dataset.nombre;
            
            let productoOption = document.createElement('option');
            productoOption.value = productoId;
            productoOption.dataset.proveedor = proveedorId;
            productoOption.dataset.unidad = unidad;
            productoOption.dataset.nombre = nombre;
            productoOption.textContent = nombre + ' (' + unidad + ')';
            
            selector.appendChild(productoOption);

            if (selector.options.length > 1) {
                document.getElementById('zip-container').classList.add('hidden');
            }
        }
    }

    function confirmarAnulacion(button) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción anulará la orden de compra.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                button.closest('form').submit();
            }
        });
    }

    document.getElementById('orden-form').addEventListener('submit', function(e) {
        if (productosAgregados.length === 0) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe añadir al menos un producto.'});
            return;
        }

        // Validar que la distribución coincida con las cantidades
        let errores = [];
        productosAgregados.forEach(productoId => {
            const totalInput = document.getElementById(`cantidad-total-${productoId}`);
            const total = parseInt(totalInput.value) || 0;
            
            const distribucionInputs = document.querySelectorAll(`input[name="productos[${productoId}][centros][]"]`);
            let distribucionTotal = 0;
            
            distribucionInputs.forEach(input => {
                distribucionTotal += parseInt(input.value) || 0;
            });
            
            if (total !== distribucionTotal) {
                errores.push(`La distribución del producto ${productoId} no coincide con la cantidad total`);
            }
        });
        
        if (errores.length > 0) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                html: errores.join('<br>')
            });
        }
    });
</script>
@endsection