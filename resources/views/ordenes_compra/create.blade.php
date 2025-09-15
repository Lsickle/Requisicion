@extends('layouts.app')

@section('title', 'Crear Orden de Compra')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-md p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Crear Orden de Compra</h1>
                <a href="{{ route('ordenes_compra.lista') }}"
                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100">
                    Volver
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
            <div class="mb-8 border rounded-lg bg-gray-50 p-6 shadow-sm">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Requisición #{{ $requisicion->id }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Solicitante</h3>
                        <p><strong>Nombre:</strong> {{ $requisicion->name_user }}</p>
                        <p><strong>Email:</strong> {{ $requisicion->email_user }}</p>
                        <p><strong>Operación:</strong> {{ $requisicion->operacion_user }}</p>
                    </div>
                    <div class="p-4 rounded-lg border bg-white">
                        <h3 class="font-medium text-gray-700 mb-2">Información General</h3>
                        <p>
                            <strong>Prioridad:</strong>
                            <span class="px-2 py-1 rounded-full text-xs font-medium
                                {{ $requisicion->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-700' :
                                   ($requisicion->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-700' :
                                   'bg-green-100 text-green-700') }}">
                                {{ ucfirst($requisicion->prioridad_requisicion) }}
                            </span>
                        </p>
                        <p><strong>Recobrable:</strong> {{ $requisicion->Recobrable }}</p>
                    </div>
                </div>

                <div class="mb-4 text-sm text-gray-700">
                    <p><strong>Detalle:</strong> {{ $requisicion->detail_requisicion }}</p>
                    <p><strong>Justificación:</strong> {{ $requisicion->justify_requisicion }}</p>
                </div>

                <!-- Distribución -->
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-700 mb-3">Distribución Original por Centros</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full border border-gray-200 text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left">Producto</th>
                                    <th class="px-4 py-2 text-center">Cantidad Total</th>
                                    <th class="px-4 py-2 text-left">Distribución</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requisicion->productos as $prod)
                                @php
                                $distribucion = DB::table('centro_producto')
                                ->where('requisicion_id', $requisicion->id)
                                ->where('producto_id', $prod->id)
                                ->join('centro', 'centro_producto.centro_id', '=', 'centro.id')
                                ->select('centro.name_centro', 'centro_producto.amount')
                                ->get();
                                @endphp
                                <tr class="border-t">
                                    <td class="px-4 py-3">{{ $prod->name_produc }}</td>
                                    <td class="px-4 py-3 text-center font-medium">{{ $prod->pivot->pr_amount }}</td>
                                    <td class="px-4 py-3">
                                        @if($distribucion->count() > 0)
                                        <div class="space-y-1">
                                            @foreach($distribucion as $centro)
                                            <div class="flex justify-between items-center bg-gray-50 px-3 py-1 rounded">
                                                <span>{{ $centro->name_centro }}</span>
                                                <span
                                                    class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 text-xs font-medium">
                                                    {{ $centro->amount }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                        @else
                                        <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Formulario para Crear Orden -->
            @if($requisicion)
            <div class="border p-6 mb-6 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Nueva Orden de Compra</h2>

                <form id="orden-form" action="{{ route('ordenes_compra.store') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="requisicion_id" value="{{ $requisicion->id }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Proveedor *</label>
                            <select id="proveedor_id" name="proveedor_id"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400" required>
                                <option value="">Seleccione un proveedor</option>
                                @foreach($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Método de Pago</label>
                            <select name="methods_oc"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="Efectivo">Efectivo</option>
                                <option value="Transferencia">Transferencia</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-600 mb-1">Plazo de Pago</label>
                            <select name="plazo_oc"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="Contado">Contado</option>
                                <option value="30 días">30 días</option>
                                <option value="45 días">45 días</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-600 mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="2"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400"></textarea>
                        </div>
                    </div>

                    <!-- Selector de productos -->
                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-600 mb-2">Añadir Producto</label>
                        <div class="flex gap-3">
                            <select id="producto-selector"
                                class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400">
                                <option value="">Seleccione un producto</option>
                                @foreach($productosDisponibles as $producto)
                                <option value="{{ $producto->id }}"
                                    data-cantidad="{{ $producto->pivot->pr_amount ?? 1 }}"
                                    data-nombre="{{ $producto->name_produc }}"
                                    data-unidad="{{ $producto->unit_produc }}"
                                    data-stock="{{ $producto->stock_produc }}"
                                    data-proveedor="{{ $producto->proveedor_id ?? '' }}">
                                    {{ $producto->name_produc }} ({{ $producto->unit_produc }}) - Cantidad: {{
                                    $producto->pivot->pr_amount ?? 1 }}
                                </option>
                                @endforeach
                            </select>
                            <button type="button" onclick="agregarProducto()"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                ➕ Añadir
                            </button>
                            <a href="{{ route('ordenes_compra.distribucionProveedores', ['requisicion_id' => $requisicion->id]) }}"
                                class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                                📊 Distribuir entre Proveedores
                            </a>
                        </div>
                    </div>

                    <!-- Tabla productos -->
                    <div class="overflow-x-auto mt-6">
                        <h3 class="text-lg font-medium text-gray-700 mb-2">Productos en la Orden</h3>
                        <table class="w-full border text-sm rounded-lg overflow-hidden bg-white">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-left">Producto</th>
                                    <th class="p-3 text-center">Cantidad</th>
                                    <th class="p-3 text-center">Unidad</th>
                                    <th class="p-3 text-center">Stock Disponible</th>
                                    <th class="p-3 text-center">Distribución por Centros</th>
                                    <th class="p-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="productos-table"></tbody>
                        </table>
                    </div>

                    <!-- Botón submit -->
                    <div class="flex justify-end">
                        <button type="submit"
                            class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow">
                            Crear Orden de Compra
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabla de órdenes creadas -->
            <div class="border p-6 mt-10 rounded-lg shadow bg-gray-50">
                <h2 class="text-xl font-medium text-gray-700 mb-4">Órdenes de Compra Creadas</h2>
                <table class="w-full border text-sm rounded-lg overflow-hidden bg-white" id="ordenes-table">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Número</th>
                            <th class="p-3">Proveedor</th>
                            <th class="p-3">Productos</th>
                            <th class="p-3">Fecha de creación</th>
                            <th class="p-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $ordenes = \App\Models\OrdenCompra::with('ordencompraProductos.producto', 'ordencompraProductos.proveedor')
                            ->where('requisicion_id', $requisicion->id)
                            ->get();
                        @endphp

                        @foreach($ordenes as $orden)
                        <tr class="border-t">
                            <td class="p-3">{{ $loop->iteration }}</td>
                            <td class="p-3">{{ $orden->order_oc ?? 'N/A' }}</td>
                            <td class="p-3">
                                @php $prov = optional($orden->ordencompraProductos->first())->proveedor; @endphp
                                {{ $prov ? $prov->prov_name : 'Proveedor no disponible' }}
                            </td>
                            <td class="p-3">
                                @foreach($orden->ordencompraProductos as $p)
                                    @if($p->producto)
                                        {{ $p->producto->name_produc }} ({{ $p->total }} {{ $p->producto->unit_produc }})<br>
                                    @else
                                        Producto eliminado ({{ $p->total }})<br>
                                    @endif
                                @endforeach
                            </td>
                            <td class="p-3">{{ $orden->created_at ? $orden->created_at->format('d/m/Y') : 'Sin fecha' }}</td>
                            <td class="p-3 text-center">
                                <form action="{{ route('ordenes_compra.anular', $orden->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="button" onclick="confirmarAnulacion(this)"
                                        class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded text-sm">
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
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg shadow">
                        Descargar ZIP
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
    
    // Preparar la distribución original de la requisición
    let distribucionOriginal = {};
    @if($requisicion)
        @foreach($requisicion->productos as $prod)
            distribucionOriginal[{{ $prod->id }}] = {
                @foreach(DB::table('centro_producto')->where('requisicion_id', $requisicion->id)->where('producto_id', $prod->id)->get() as $dist)
                    {{ $dist->centro_id }}: {{ $dist->amount }},
                @endforeach
            };
        @endforeach
    @endif

    function agregarProducto() {
        let selector = document.getElementById('producto-selector');
        let proveedorSelect = document.getElementById('proveedor_id');

        let productoId = selector.value;
        let selectedOption = selector.options[selector.selectedIndex];
        
        if (!selectedOption) return;
        
        let productoNombre = selectedOption.dataset.nombre;
        let proveedorId = selectedOption.dataset.proveedor;
        let unidad = selectedOption.dataset.unidad || '';
        let cantidadOriginal = selectedOption.dataset.cantidad || 1;
        let stockDisponible = parseInt(selectedOption.dataset.stock ?? '0', 10);

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
                    agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector);
                }
            });
        } else {
            agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector);
        }
    }

    function agregarProductoFinal(productoId, productoNombre, proveedorId, unidad, cantidadOriginal, stockDisponible, selector) {
        let table = document.getElementById('productos-table');
        let proveedorSelect = document.getElementById('proveedor_id');
        
        let row = document.createElement('tr');
        row.id = `producto-${productoId}`;
        
        // Obtener distribución original para este producto
        let distribucionProducto = distribucionOriginal[productoId] || {};
        
        // Generar campos de distribución por centros - SOLO LOS CENTROS QUE ESTÁN EN LA DISTRIBUCIÓN
        let centrosHtml = '';
        
        // Obtener solo los centros que tienen distribución para este producto
        let centrosConDistribucion = [];
        for (let centroId in distribucionProducto) {
            if (distribucionProducto[centroId] > 0) {
                let centro = centros.find(c => c.id == centroId);
                if (centro) {
                    centrosConDistribucion.push(centro);
                }
            }
        }
        
        // Si no hay centros con distribución, usar todos los centros
        if (centrosConDistribucion.length === 0) {
            centrosConDistribucion = centros;
        }
        
        // Generar los campos para los centros con distribución
        centrosConDistribucion.forEach(centro => {
            let cantidadCentro = distribucionProducto[centro.id] || 0;
            centrosHtml += `
                <div class="mb-2">
                    <label class="block text-sm text-gray-600">${centro.name_centro}:</label>
                    <input type="number" name="productos[${productoId}][centros][${centro.id}]" 
                           min="0" value="${cantidadCentro}" class="w-20 border rounded p-1 text-center distribucion-centro"
                           data-producto="${productoId}" onchange="actualizarTotal(${productoId})">
                </div>
            `;
        });
        
        row.innerHTML = `
            <td class="p-3">
                ${productoNombre}
                <input type="hidden" name="productos[${productoId}][id]" value="${productoId}" 
                    data-proveedor="${proveedorId}" data-unidad="${unidad}" data-nombre="${productoNombre}" data-cantidad="${cantidadOriginal}" data-stock="${stockDisponible}">
            </td>
            <td class="p-3 text-center">
                <input type="number" name="productos[${productoId}][cantidad]" min="1" value="${cantidadOriginal}" 
                    class="w-20 border rounded p-1 text-center cantidad-total" 
                    id="cantidad-total-${productoId}" 
                    onchange="distribuirAutomaticamente(${productoId})" required>
            </td>
            <td class="p-3 text-center">${unidad}</td>
            <td class="p-3 text-center" id="stock-disponible-${productoId}">${stockDisponible}</td>
            <td class="p-3">
                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto">
                    ${centrosHtml}
                </div>
            </td>
            <td class="p-3 text-center space-x-2">
                <button type="button" onclick="quitarProducto(${productoId})" 
                    class="bg-red-500 text-white px-3 py-1 rounded-lg mb-1">Quitar</button>
                <button type="button" onclick="mostrarCampoStock(${productoId})" 
                    class="bg-yellow-600 text-white px-3 py-1 rounded-lg mb-1">Quitar de stock</button>
                <div id="stock-campo-${productoId}" class="mt-2 hidden">
                    <label class="block text-sm text-gray-600">Cantidad a retirar de stock:</label>
                    <input type="number" name="productos[${productoId}][quitar_stock]" 
                           min="0" max="${stockDisponible}" value="0" 
                           class="w-24 border rounded p-1 text-center quitar-stock"
                           onchange="actualizarCantidadOrden(${productoId})" 
                           placeholder="Cantidad a retirar">
                </div>
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
        
        // Calcular total inicial
        actualizarTotal(productoId);
    }

    function actualizarTotal(productoId) {
        const inputs = document.querySelectorAll(`input[name^="productos[${productoId}][centros]"]`);
        let total = 0;
        
        inputs.forEach(input => {
            total += parseInt(input.value) || 0;
        });
        
        document.getElementById(`cantidad-total-${productoId}`).value = total;
    }

    function actualizarCantidadOrden(productoId) {
        const quitarStockInput = document.querySelector(`input[name="productos[${productoId}][quitar_stock]"]`);
        const cantidadTotalInput = document.getElementById(`cantidad-total-${productoId}`);
        const stockDisponible = parseInt(document.getElementById(`stock-disponible-${productoId}`).textContent);
        
        const quitarStock = parseInt(quitarStockInput.value) || 0;
        
        if (quitarStock > stockDisponible) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: `No puede retirar más de ${stockDisponible} unidades del stock`,
                confirmButtonText: 'Aceptar'
            });
            quitarStockInput.value = stockDisponible;
            return;
        }
        
        // La cantidad a ordenar es la cantidad original menos lo que se retira del stock
        const cantidadOrden = parseInt(cantidadTotalInput.dataset.original || cantidadTotalInput.value) - quitarStock;
        
        if (cantidadOrden < 0) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'La cantidad a ordenar no puede ser negativa',
                confirmButtonText: 'Aceptar'
            });
            quitarStockInput.value = 0;
            cantidadTotalInput.value = cantidadTotalInput.dataset.original || cantidadTotalInput.value;
            return;
        }
        
        cantidadTotalInput.value = cantidadOrden;
        
        // Actualizar la distribución automáticamente
        distribuirAutomaticamente(productoId);
    }

    function distribuirAutomaticamente(productoId) {
        const total = parseInt(document.getElementById(`cantidad-total-${productoId}`).value) || 0;
        const inputs = document.querySelectorAll(`input[name^="productos[${productoId}][centros]"]`);
        
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
            const cantidad = inputHidden.dataset.cantidad;
            const stock = inputHidden.dataset.stock;
            
            let productoOption = document.createElement('option');
            productoOption.value = productoId;
            productoOption.dataset.proveedor = proveedorId;
            productoOption.dataset.unidad = unidad;
            productoOption.dataset.nombre = nombre;
            productoOption.dataset.cantidad = cantidad;
            productoOption.dataset.stock = stock;
            productoOption.textContent = nombre + ' (' + unidad + ') - Cantidad: ' + cantidad + ' - Stock: ' + stock;
            
            selector.appendChild(productoOption);

            if (selector.options.length > 1) {
                document.getElementById('zip-container').classList.add('hidden');
            }
        }
    }

    function mostrarCampoStock(productoId) {
        const campo = document.getElementById(`stock-campo-${productoId}`);
        if (campo) {
            campo.classList.toggle('hidden');
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
            
            const distribucionInputs = document.querySelectorAll(`input[name^="productos[${productoId}][centros]"]`);
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

    function configurarAutoCargaProveedor() {
        const productoSelector = document.getElementById('producto-selector');
        const proveedorSelect = document.getElementById('proveedor_id');
        
        productoSelector.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.proveedor) {
                proveedorSelect.value = selectedOption.dataset.proveedor;
            }
        });
    }

    // Llamar a la función cuando el documento esté listo
    document.addEventListener('DOMContentLoaded', function() {
        configurarAutoCargaProveedor();
    });
</script>
@endsection