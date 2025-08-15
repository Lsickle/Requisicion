<link href="{{ asset('css/requisiciones.css') }}" rel="stylesheet">
<!-- resources/views/requisiciones/create.blade.php -->
@extends('layouts.app')

@section('title', 'Requisición')

@section('content')
<x-navbar />
<div class="container requisicion-container">
    <h1>Nueva Requisición</h1>

    <div class="card mb-4 requisicion-card">
        <div class="card-header">
            <h5>Información Principal</h5>
        </div>
        <div class="card-body requisicion-form">
            <div class="form-group">
                <label for="tipo">Tipo de Requisición</label>
                <select class="form-control" id="tipo" name="tipo">
                    <option value="principal">Principal</option>
                    <option value="recoincible">Recoincible</option>
                    <option value="no-recoincible">No Recoincible</option>
                </select>
            </div>

            <div class="form-group">
                <label for="detalles">Detalles Adicionales</label>
                <textarea class="form-control" id="detalles" name="detalles" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="justificacion">Justificación</label>
                <textarea class="form-control" id="justificacion" name="justificacion" rows="3"></textarea>
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
                            <th>Proveedor</th>
                            <th>Cantidad</th>
                            <th>Centro de Costos</th>
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
            <button type="button" class="btn btn-success mr-2">
                <i class="fas fa-paper-plane"></i> Enviar Requisición
            </button>
            <button type="button" class="btn btn-info">
                <i class="fas fa-boxes"></i> Enviar Requisición Stock
            </button>
        </div>
    </div>
</div>

<!-- Modal para agregar productos -->
<div class="modal fade modal-requisicion" id="modalAgregarProducto" tabindex="-1" role="dialog" aria-labelledby="modalAgregarProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarProductoLabel">Añadir Productos</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body requisicion-form">
                <form id="formAgregarProducto">
                    <div class="form-group">
                        <label for="categoria">Categoría</label>
                        <select class="form-control" id="categoria" name="categoria">
                            <option value="tecnologia">Tecnología</option>
                            <option value="oficina">Oficina</option>
                            <option value="limpieza">Limpieza</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="producto">Producto</label>
                        <select class="form-control" id="producto" name="producto">
                            <option value="producto1">Producto 1</option>
                            <option value="producto2">Producto 2</option>
                            <option value="producto3">Producto 3</option>
                            <option value="producto4">Producto 4</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cantidad">Cantidad</label>
                        <input type="number" class="form-control" id="cantidad" name="cantidad" min="1">
                    </div>

                    <div class="form-group">
                        <label for="centro_costos">Centro de Costos</label>
                        <select class="form-control" id="centro_costos" name="centro_costos">
                            <option value="operativo_tecnologia">Operativo Tecnología</option>
                            <option value="centro3">Centro 3</option>
                            <option value="centro4">Centro 4</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="proveedor">Proveedor</label>
                        <input type="text" class="form-control" id="proveedor" name="proveedor" value="Proveedor por defecto" readonly>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnAgregarProducto">Agregar Producto</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para previsualizar producto -->
<div class="modal fade modal-requisicion" id="modalPrevisualizar" tabindex="-1" role="dialog" aria-labelledby="modalPrevisualizarLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPrevisualizarLabel">Detalles del Producto</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="previsualizacionContenido">
                <!-- Contenido de la previsualización se cargará aquí -->
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

    // Agregar producto a la tabla
    $('#btnAgregarProducto').click(function() {
        const categoria = $('#categoria').val();
        const producto = $('#producto option:selected').text();
        const cantidad = $('#cantidad').val();
        const centroCostos = $('#centro_costos option:selected').text();
        const proveedor = $('#proveedor').val();

        if (!cantidad || cantidad < 1) {
            alert('Por favor ingrese una cantidad válida');
            return;
        }

        // Agregar a la lista de productos
        productos.push({
            categoria,
            producto,
            cantidad,
            centroCostos,
            proveedor
        });

        // Actualizar la tabla
        actualizarTablaProductos();

        // Cerrar el modal y limpiar el formulario
        $('#modalAgregarProducto').modal('hide');
        $('#formAgregarProducto')[0].reset();

        // Habilitar el botón de previsualizar
        $('#btnPrevisualizar').prop('disabled', false);
    });

    // Actualizar la tabla de productos
    function actualizarTablaProductos() {
        const tbody = $('#tablaProductos tbody');
        tbody.empty();

        productos.forEach((prod, index) => {
            tbody.append(`
                <tr>
                    <td>${prod.producto}</td>
                    <td>${prod.proveedor}</td>
                    <td>${prod.cantidad}</td>
                    <td>${prod.centroCostos}</td>
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

    // Previsualizar producto
    $(document).on('click', '.btnPrevisualizar', function() {
        const index = $(this).data('index');
        const producto = productos[index];

        $('#previsualizacionContenido').html(`
            <table class="table table-bordered">
                <tr>
                    <th>Producto</th>
                    <td>${producto.producto}</td>
                </tr>
                <tr>
                    <th>Proveedor</th>
                    <td>${producto.proveedor}</td>
                </tr>
                <tr>
                    <th>Centro de Costos</th>
                    <td>${producto.centroCostos}</td>
                </tr>
                <tr>
                    <th>Cantidad</th>
                    <td>${producto.cantidad}</td>
                </tr>
            </table>
        `);

        $('#modalPrevisualizar').modal('show');
    });

    // Eliminar producto
    $(document).on('click', '.btnEliminar', function() {
        const index = $(this).data('index');
        productos.splice(index, 1);
        actualizarTablaProductos();

        if (productos.length === 0) {
            $('#btnPrevisualizar').prop('disabled', true);
        }
    });

    // Previsualizar todos los productos
    $('#btnPrevisualizar').click(function() {
        let contenido = '<h4>Resumen de Requisición</h4>';

        productos.forEach(prod => {
            contenido += `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>${prod.producto}</h5>
                        <p><strong>Proveedor:</strong> ${prod.proveedor}</p>
                        <p><strong>Cantidad:</strong> ${prod.cantidad}</p>
                        <p><strong>Centro de Costos:</strong> ${prod.centroCostos}</p>
                    </div>
                </div>
            `;
        });

        $('#previsualizacionContenido').html(contenido);
        $('#modalPrevisualizar').modal('show');
    });
});
</script>
@endsection