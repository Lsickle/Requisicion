<link href="{{ asset('css/requisiciones.css') }}" rel="stylesheet">
<!-- resources/views/requisiciones/create.blade.php -->
@extends('layouts.app')

@section('title', 'Requisición')

@section('content')
<x-navbar />
<div class="container requisicion-container">
    <h1>Nueva Requisición</h1>
    <form id="requisicionForm" method="POST" action="{{ route('requisiciones.store') }}">
        @csrf

        <div class="card mb-4 requisicion-card">
            <div class="card-header">
                <h5>Información Principal</h5>
            </div>
            <div class="card-body requisicion-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="date_requisicion">Fecha de Requisición</label>
                            <input type="date" class="form-control" id="date_requisicion" name="date_requisicion" 
                                   value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="recobrable">Tipo de Requisición</label>
                            <select class="form-control" id="recobrable" name="recobrable" required>
                                <option value="Recobrable">Recobrable</option>
                                <option value="No recobrable">No Recobrable</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="prioridad_requisicion">Prioridad</label>
                            <select class="form-control" id="prioridad_requisicion" name="prioridad_requisicion" required>
                                <option value="baja">Baja</option>
                                <option value="media">Media</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="justify_requisicion">Justificación</label>
                            <textarea class="form-control" id="justify_requisicion" name="justify_requisicion" 
                                      rows="3" required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="detail_requisicion">Detalles Adicionales</label>
                            <textarea class="form-control" id="detail_requisicion" name="detail_requisicion" 
                                      rows="3"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 requisicion-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Productos</h5>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarProducto">
                    <i class="fas fa-plus"></i> Añadir Productos
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="tablaProductos">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad Total</th>
                                <th>Centros de Costo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Los productos se agregarán aquí dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="requisicion-footer d-flex justify-content-between">
            <button type="button" class="btn btn-secondary" id="btnPrevisualizar" disabled>
                <i class="fas fa-eye"></i> Previsualizar
            </button>
            <div>
                <button type="submit" class="btn btn-success mr-2">
                    <i class="fas fa-paper-plane"></i> Enviar Requisición
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal para agregar productos -->
<div class="modal fade modal-requisicion" id="modalAgregarProducto" tabindex="-1" role="dialog" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarProductoLabel">Añadir Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body requisicion-form">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="producto_id">Producto</label>
                            <select class="form-control" id="producto_id" name="producto_id" required>
                                <option value="">Seleccione un producto</option>
                                @foreach($productos as $producto)
                                    <option value="{{ $producto->id }}" 
                                            data-stock="{{ $producto->stock_produc }}"
                                            data-nombre="{{ $producto->name_produc }}">
                                        {{ $producto->name_produc }} ({{ $producto->categoria_produc }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="pr_amount">Cantidad Total</label>
                            <input type="number" class="form-control" id="pr_amount" name="pr_amount" min="1" required>
                            <small id="stockDisponible" class="form-text text-muted"></small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-12">
                        <h5>Distribución por Centros de Costo</h5>
                        <div id="centrosContainer">
                            <div class="row centro-row mb-2">
                                <div class="col-md-6">
                                    <select class="form-control centro-select" name="centros[0][id]" required>
                                        <option value="">Seleccione un centro</option>
                                        @foreach($centros as $centro)
                                            <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <input type="number" class="form-control centro-cantidad" name="centros[0][cantidad]" min="1" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm btn-remove-centro" disabled>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm mt-2" id="btnAddCentro">
                            <i class="fas fa-plus"></i> Añadir otro centro
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAgregarProducto">Agregar a Requisición</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualizar -->
<div class="modal fade modal-requisicion" id="modalPrevisualizar" tabindex="-1" role="dialog" aria-labelledby="modalPrevisualizarLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPrevisualizarLabel">Previsualización de Requisición</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previsualizacionContenido">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let productos = [];
    let totalProductos = 0;
    let centroCounter = 1;

    // Mostrar stock disponible al seleccionar producto
    $('#producto_id').change(function() {
        const selectedOption = $(this).find('option:selected');
        const stock = selectedOption.data('stock');
        const nombreProducto = selectedOption.data('nombre');
        
        $('#stockDisponible').text(`Stock disponible: ${stock}`);
        $('#pr_amount').attr('max', stock).val(1);
        
        // Actualizar la cantidad máxima en todos los inputs de centros
        $('.centro-cantidad').attr('max', stock);
    });

    // Añadir otro centro de costo
    $('#btnAddCentro').click(function() {
        const newRow = `
            <div class="row centro-row mb-2" data-index="${centroCounter}">
                <div class="col-md-6">
                    <select class="form-control centro-select" name="centros[${centroCounter}][id]" required>
                        <option value="">Seleccione un centro</option>
                        @foreach($centros as $centro)
                            <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control centro-cantidad" name="centros[${centroCounter}][cantidad]" min="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-centro">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        $('#centrosContainer').append(newRow);
        centroCounter++;
        
        // Habilitar botones de eliminar si hay más de una fila
        if ($('.centro-row').length > 1) {
            $('.btn-remove-centro').prop('disabled', false);
        }
    });

    // Eliminar fila de centro de costo
    $(document).on('click', '.btn-remove-centro', function() {
        if ($('.centro-row').length > 1) {
            $(this).closest('.centro-row').remove();
            
            // Deshabilitar botones de eliminar si solo queda una fila
            if ($('.centro-row').length === 1) {
                $('.btn-remove-centro').prop('disabled', true);
            }
        }
    });

    // Actualizar máximo en centros cuando cambia cantidad total
    $('#pr_amount').on('input', function() {
        const max = parseInt($(this).val()) || 0;
        $('.centro-cantidad').attr('max', max);
    });

    // Validar suma de cantidades por centro
    $(document).on('input', '.centro-cantidad', function() {
        const total = parseInt($('#pr_amount').val()) || 0;
        let sum = 0;
        
        $('.centro-cantidad').each(function() {
            sum += parseInt($(this).val()) || 0;
        });
        
        if (sum > total) {
            alert('La suma de las cantidades por centro no puede superar la cantidad total');
            $(this).val('');
        }
    });

    // Agregar producto a la tabla
    $('#btnAgregarProducto').click(function() {
        const productoId = $('#producto_id').val();
        const productoText = $('#producto_id option:selected').text();
        const cantidadTotal = parseInt($('#pr_amount').val());
        const stockDisponible = parseInt($('#producto_id option:selected').data('stock'));

        if (!productoId) {
            alert('Seleccione un producto');
            return;
        }

        if (!cantidadTotal || cantidadTotal < 1) {
            alert('Ingrese una cantidad válida');
            return;
        }

        if (cantidadTotal > stockDisponible) {
            alert(`La cantidad supera el stock disponible (${stockDisponible})`);
            return;
        }

        // Validar centros de costo
        const centros = [];
        let centrosValid = true;
        let sumCentros = 0;

        $('.centro-row').each(function(index) {
            const centroId = $(this).find('.centro-select').val();
            const centroText = $(this).find('.centro-select option:selected').text();
            const cantidad = parseInt($(this).find('.centro-cantidad').val()) || 0;

            if (!centroId) {
                alert('Seleccione un centro de costo para todas las filas');
                centrosValid = false;
                return false;
            }

            if (cantidad < 1) {
                alert('Ingrese una cantidad válida para todos los centros');
                centrosValid = false;
                return false;
            }

            sumCentros += cantidad;
            centros.push({
                id: centroId,
                cantidad: cantidad,
                centro_text: centroText
            });
        });

        if (!centrosValid) return;

        if (sumCentros !== cantidadTotal) {
            alert('La suma de las cantidades por centro debe ser igual a la cantidad total');
            return;
        }

        // Verificar si el producto ya existe en la lista
        const productoExistenteIndex = productos.findIndex(p => p.id === productoId);

        if (productoExistenteIndex !== -1) {
            // Actualizar producto existente
            productos[productoExistenteIndex].cantidad = cantidadTotal;
            productos[productoExistenteIndex].centros = centros;
        } else {
            // Agregar nuevo producto
            productos.push({
                id: productoId,
                producto_text: productoText,
                cantidad: cantidadTotal,
                centros: centros
            });
        }

        // Actualizar total de productos
        totalProductos = productos.reduce((sum, p) => sum + p.cantidad, 0);
        $('#amount_requisicion').val(totalProductos);

        // Actualizar la tabla
        actualizarTablaProductos();

        // Cerrar el modal y limpiar el formulario
        $('#modalAgregarProducto').modal('hide');
        $('#producto_id').val('');
        $('#pr_amount').val('');
        $('#stockDisponible').text('');
        $('#centrosContainer').html(`
            <div class="row centro-row mb-2">
                <div class="col-md-6">
                    <select class="form-control centro-select" name="centros[0][id]" required>
                        <option value="">Seleccione un centro</option>
                        @foreach($centros as $centro)
                            <option value="{{ $centro->id }}">{{ $centro->name_centro }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control centro-cantidad" name="centros[0][cantidad]" min="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm btn-remove-centro" disabled>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `);
        centroCounter = 1;

        // Habilitar el botón de previsualizar
        $('#btnPrevisualizar').prop('disabled', false);
    });

    // Actualizar la tabla de productos
    function actualizarTablaProductos() {
        const tbody = $('#tablaProductos tbody');
        tbody.empty();

        productos.forEach((prod, index) => {
            // Mostrar resumen de centros
            const centrosText = prod.centros.map(c => `${c.centro_text} (${c.cantidad})`).join(', ');
            
            tbody.append(`
                <tr>
                    <td>${prod.producto_text}</td>
                    <td>${prod.cantidad}</td>
                    <td>${centrosText}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-action btnPrevisualizar" data-index="${index}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-danger btn-action btnEliminar" data-index="${index}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // Previsualizar producto específico
    $(document).on('click', '.btnPrevisualizar', function() {
        const index = $(this).data('index');
        const producto = productos[index];

        let centrosHtml = '<ul>';
        producto.centros.forEach(c => {
            centrosHtml += `<li>${c.centro_text}: ${c.cantidad}</li>`;
        });
        centrosHtml += '</ul>';

        $('#previsualizacionContenido').html(`
            <div class="card">
                <div class="card-body">
                    <h5>${producto.producto_text}</h5>
                    <p><strong>Cantidad Total:</strong> ${producto.cantidad}</p>
                    <p><strong>Distribución por Centros:</strong></p>
                    ${centrosHtml}
                </div>
            </div>
        `);

        $('#modalPrevisualizar').modal('show');
    });

    // Eliminar producto
    $(document).on('click', '.btnEliminar', function() {
        const index = $(this).data('index');
        const cantidadEliminar = productos[index].cantidad;
        
        productos.splice(index, 1);
        totalProductos -= cantidadEliminar;
        $('#amount_requisicion').val(totalProductos);
        
        actualizarTablaProductos();

        if (productos.length === 0) {
            $('#btnPrevisualizar').prop('disabled', true);
        }
    });

    // Previsualizar toda la requisición
    $('#btnPrevisualizar').click(function() {
        let contenido = '<h4>Resumen de Requisición</h4>';
        contenido += `<p><strong>Fecha:</strong> ${$('#date_requisicion').val()}</p>`;
        contenido += `<p><strong>Tipo:</strong> ${$('#recobrable option:selected').text()}</p>`;
        contenido += `<p><strong>Prioridad:</strong> ${$('#prioridad_requisicion option:selected').text()}</p>`;
        contenido += `<p><strong>Total Productos:</strong> ${totalProductos}</p>`;
        contenido += `<p><strong>Justificación:</strong> ${$('#justify_requisicion').val()}</p>`;

        productos.forEach(prod => {
            let centrosHtml = '<ul>';
            prod.centros.forEach(c => {
                centrosHtml += `<li>${c.centro_text}: ${c.cantidad}</li>`;
            });
            centrosHtml += '</ul>';

            contenido += `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>${prod.producto_text}</h5>
                        <p><strong>Cantidad Total:</strong> ${prod.cantidad}</p>
                        <p><strong>Distribución por Centros:</strong></p>
                        ${centrosHtml}
                    </div>
                </div>
            `;
        });

        $('#previsualizacionContenido').html(contenido);
        $('#modalPrevisualizar').modal('show');
    });

    // Enviar formulario
    $('#requisicionForm').submit(function(e) {
        e.preventDefault();
        
        if (productos.length === 0) {
            alert('Debe agregar al menos un producto');
            return;
        }

        // Preparar los datos en el formato que espera el controlador
        const formData = {
            prioridad_requisicion: $('#prioridad_requisicion').val(),
            recobrable: $('#recobrable').val(),
            detail_requisicion: $('#detail_requisicion').val(),
            justify_requisicion: $('#justify_requisicion').val(),
            date_requisicion: $('#date_requisicion').val(),
            amount_requisicion: totalProductos,
            productos: productos.map(prod => ({
                id: prod.id,
                cantidad: prod.cantidad,
                proveedor_id: $('#producto_id option[value="' + prod.id + '"]').data('proveedor'),
                centros: prod.centros.map(c => ({
                    id: c.id,
                    cantidad: c.cantidad
                }))
            }))
        };

        // Crear inputs ocultos con los datos
        $.each(formData, function(key, value) {
            if (key === 'productos') {
                $.each(value, function(index, producto) {
                    $(`<input type="hidden" name="productos[${index}][id]" value="${producto.id}">`).appendTo('#requisicionForm');
                    $(`<input type="hidden" name="productos[${index}][cantidad]" value="${producto.cantidad}">`).appendTo('#requisicionForm');
                    $(`<input type="hidden" name="productos[${index}][proveedor_id]" value="${producto.proveedor_id}">`).appendTo('#requisicionForm');
                    
                    $.each(producto.centros, function(centroIndex, centro) {
                        $(`<input type="hidden" name="productos[${index}][centros][${centroIndex}][id]" value="${centro.id}">`).appendTo('#requisicionForm');
                        $(`<input type="hidden" name="productos[${index}][centros][${centroIndex}][cantidad]" value="${centro.cantidad}">`).appendTo('#requisicionForm');
                    });
                });
            } else {
                $(`<input type="hidden" name="${key}" value="${value}">`).appendTo('#requisicionForm');
            }
        });

        this.submit();
    });
});
</script>
@endsection