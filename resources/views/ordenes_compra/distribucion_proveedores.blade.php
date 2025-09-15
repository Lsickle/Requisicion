@extends('layouts.app') 

@section('title', 'Distribuir Producto entre Proveedores')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-md p-6">
            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-semibold text-gray-800">Distribuir Producto entre Proveedores</h1>
                <a href="{{ route('ordenes_compra.create', ['requisicion_id' => $requisicion->id]) }}"
                    class="px-4 py-2 text-sm rounded-lg border border-gray-300 bg-gray-50 hover:bg-gray-100">
                    Volver a Crear Orden
                </a>
            </div>

            <!-- Mensajes -->
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                {{ session('error') }}
            </div>
            @endif

            <!-- Formulario de distribución -->
            <form id="form-distribucion-proveedores" action="{{ route('ordenes_compra.distribuirProveedores') }}" method="POST">
                @csrf
                <input type="hidden" name="requisicion_id" id="requisicion_id_hidden" value="{{ $requisicion->id ?? '' }}">

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Producto a distribuir *</label>
                    <select id="producto-distribuir" name="producto_id" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400" required>
                        <option value="">Seleccione un producto</option>
                        @foreach($productosDisponibles as $producto)
                        <option value="{{ $producto->id }}" data-cantidad="{{ $producto->pivot->pr_amount ?? 1 }}"
                            data-nombre="{{ $producto->name_produc }}" data-unidad="{{ $producto->unit_produc }}">
                            {{ $producto->name_produc }} ({{ $producto->unit_produc }}) - Cantidad: {{ $producto->pivot->pr_amount ?? 1 }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Cantidad Total Disponible:
                        <span id="cantidad-total-distribuir" class="font-bold">0</span>
                        <span id="unidad-producto"></span>
                    </label>
                </div>

                <!-- Tabla de distribución -->
                <div class="overflow-x-auto mb-6">
                    <table class="w-full border text-sm rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Proveedor *</th>
                                <th class="p-3 text-center">Cantidad *</th>
                                <th class="p-3 text-center">Método de Pago *</th>
                                <th class="p-3 text-center">Plazo de Pago *</th>
                                <th class="p-3 text-center">Observaciones</th>
                                <th class="p-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-distribucion-body"></tbody>
                        <tfoot>
                            <tr>
                                <td class="p-3 font-semibold">Total Distribuido</td>
                                <td class="p-3 text-center">
                                    <span id="total-distribuido" class="font-bold">0</span> /
                                    <span id="cantidad-maxima" class="font-bold">0</span>
                                    <span id="unidad-distribucion"></span>
                                </td>
                                <td colspan="4" class="p-3 text-center">
                                    <button type="button" onclick="agregarFilaProveedor()"
                                        class="px-3 py-1 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700">
                                        + Agregar Proveedor
                                    </button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Mensaje de alerta -->
                <div id="distribucion-alert" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 hidden"></div>

                <!-- Botones -->
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('ordenes_compra.create', ['requisicion_id' => $requisicion->id]) }}"
                        class="px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 hover:bg-gray-100">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Guardar Distribución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let distribucionProveedores = [];
    let contadorProveedores = 0;
    let productoSeleccionado = null;
    
    // Inicializar con una fila de proveedor
    document.addEventListener('DOMContentLoaded', function() {
        // Fallback: asegurar requisicion_id desde la query string si no vino en servidor
        const hid = document.getElementById('requisicion_id_hidden');
        if (!hid.value) {
            const params = new URLSearchParams(window.location.search);
            const rq = params.get('requisicion_id');
            if (rq) hid.value = rq;
        }

        agregarFilaProveedor();
        
        // Configurar evento para cambiar producto
        document.getElementById('producto-distribuir').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const cantidad = selectedOption ? parseInt(selectedOption.dataset.cantidad) || 0 : 0;
            const unidad = selectedOption ? selectedOption.dataset.unidad : '';
            
            productoSeleccionado = {
                id: this.value,
                nombre: selectedOption ? selectedOption.dataset.nombre : '',
                cantidad: cantidad,
                unidad: unidad
            };
            
            document.getElementById('cantidad-total-distribuir').textContent = cantidad;
            document.getElementById('cantidad-maxima').textContent = cantidad;
            document.getElementById('unidad-producto').textContent = unidad;
            document.getElementById('unidad-distribucion').textContent = unidad;
            
            // Reiniciar la tabla de distribución
            document.getElementById('tabla-distribucion-body').innerHTML = '';
            distribucionProveedores = [];
            contadorProveedores = 0;
            document.getElementById('total-distribuido').textContent = '0';
            document.getElementById('distribucion-alert').classList.add('hidden');
            
            // Agregar primera fila automáticamente
            if (cantidad > 0) {
                agregarFilaProveedor();
            }
        });
    });

    function agregarFilaProveedor() {
        const index = contadorProveedores++;
        const html = `
            <tr id="fila-proveedor-${index}">
                <td class="p-3">
                    <select name="distribucion[${index}][proveedor_id]" class="w-full border rounded p-1 proveedor-select" required
                        onchange="actualizarDistribucion(${index})">
                        <option value="">Seleccione proveedor</option>
                        @foreach($proveedores as $proveedor)
                        <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                        @endforeach
                    </select>
                </td>
                <td class="p-3">
                    <input type="number" name="distribucion[${index}][cantidad]" min="0" value="0" 
                           class="w-full border rounded p-1 text-center cantidad-proveedor" required
                           onchange="actualizarDistribucion(${index})"
                           oninput="validarCantidadProveedor(this, ${index})">
                </td>
                <td class="p-3">
                    <select name="distribucion[${index}][methods_oc]" class="w-full border rounded p-1 metodo-pago-proveedor" required
                        onchange="actualizarDistribucion(${index})">
                        <option value="">Seleccione</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Transferencia">Transferencia</option>
                    </select>
                </td>
                <td class="p-3">
                    <select name="distribucion[${index}][plazo_oc]" class="w-full border rounded p-1 plazo-pago-proveedor" required
                        onchange="actualizarDistribucion(${index})">
                        <option value="">Seleccione</option>
                        <option value="Contado">Contado</option>
                        <option value="30 días">30 días</option>
                        <option value="45 días">45 días</option>
                    </select>
                </td>
                <td class="p-3">
                    <input type="text" name="distribucion[${index}][observaciones]" 
                        class="w-full border rounded p-1 observaciones-proveedor" placeholder="Observaciones" 
                        onchange="actualizarDistribucion(${index})">
                </td>
                <td class="p-3 text-center">
                    <button type="button" onclick="eliminarFilaProveedor(${index})"
                        class="px-2 py-1 bg-red-500 text-white rounded text-sm hover:bg-red-600">
                        ✕
                    </button>
                </td>
            </tr>
        `;
        document.getElementById('tabla-distribucion-body').insertAdjacentHTML('beforeend', html);
        distribucionProveedores.push({proveedor_id: null, cantidad: 0, methods_oc: '', plazo_oc: '', observaciones: ''});
    }

    function validarCantidadProveedor(input, index) {
        const cantidad = parseInt(input.value) || 0;
        const cantidadMaxima = parseInt(document.getElementById('cantidad-maxima').textContent) || 0;
        const totalDistribuido = parseInt(document.getElementById('total-distribuido').textContent) || 0;
        
        if (cantidad > cantidadMaxima) {
            Swal.fire({
                icon: 'warning',
                title: 'Cantidad excedida',
                text: `No puede asignar más de ${cantidadMaxima} unidades a un solo proveedor`
            });
            input.value = cantidadMaxima;
            actualizarDistribucion(index);
        }
    }

    function eliminarFilaProveedor(index) {
        document.getElementById(`fila-proveedor-${index}`).remove();
        distribucionProveedores.splice(index, 1);
        actualizarTotalDistribuido();
    }

    function actualizarDistribucion(index) {
        const row = document.getElementById(`fila-proveedor-${index}`);
        if (!row) return;
        const select = row.querySelector('.proveedor-select');
        const input = row.querySelector('.cantidad-proveedor');
        const metodo = row.querySelector('.metodo-pago-proveedor');
        const plazo = row.querySelector('.plazo-pago-proveedor');
        const obs = row.querySelector('.observaciones-proveedor');
        distribucionProveedores[index] = {
            proveedor_id: select.value,
            cantidad: parseInt(input.value) || 0,
            methods_oc: metodo.value,
            plazo_oc: plazo.value,
            observaciones: obs.value
        };
        actualizarTotalDistribuido();
    }

    function actualizarTotalDistribuido() {
        let total = 0;
        distribucionProveedores.forEach(dist => {
            total += dist.cantidad || 0;
        });
        
        document.getElementById('total-distribuido').textContent = total;
        
        const cantidadMaxima = parseInt(document.getElementById('cantidad-maxima').textContent) || 0;
        const alerta = document.getElementById('distribucion-alert');
        
        if (total > cantidadMaxima) {
            alerta.classList.remove('hidden');
            alerta.textContent = `La distribución total (${total}) excede la cantidad disponible (${cantidadMaxima})`;
        } else {
            alerta.classList.add('hidden');
        }
    }

    // Validación antes de enviar el formulario
    document.querySelector('form').addEventListener('submit', function(e) {
        const productoId = document.getElementById('producto-distribuir').value;
        const cantidadMaxima = parseInt(document.getElementById('cantidad-maxima').textContent) || 0;
        const totalDistribuido = parseInt(document.getElementById('total-distribuido').textContent) || 0;
        
        if (!productoId) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Seleccione un producto para distribuir'});
            return;
        }
        
        if (totalDistribuido !== cantidadMaxima) {
            e.preventDefault();
            Swal.fire({
                icon: 'error', 
                title: 'Error', 
                text: `La distribución total (${totalDistribuido}) debe ser igual a la cantidad disponible (${cantidadMaxima})`
            });
            return;
        }
        
        // Filtrar distribuciones válidas
        const distribucionesValidas = distribucionProveedores.filter(dist => 
            dist.proveedor_id && dist.cantidad > 0
        );
        
        if (distribucionesValidas.length === 0) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'Agregue al menos un proveedor con cantidad'});
            return;
        }
        
        // Verificar que no haya proveedores duplicados
        const proveedoresIds = distribucionesValidas.map(dist => dist.proveedor_id);
        const proveedoresUnicos = new Set(proveedoresIds);
        
        if (proveedoresIds.length !== proveedoresUnicos.size) {
            e.preventDefault();
            Swal.fire({icon: 'warning', title: 'Atención', text: 'No puede asignar el mismo proveedor múltiples veces'});
            return;
        }
        
        // Verificar que todos tengan método, plazo y observaciones (opcional)
        for (const dist of distribucionesValidas) {
            if (!dist.methods_oc || !dist.plazo_oc) {
                e.preventDefault();
                Swal.fire({icon: 'warning', title: 'Atención', text: 'Debe seleccionar método y plazo de pago para cada proveedor'});
                return;
            }
        }
    });
</script>
@endsection