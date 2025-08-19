@extends('layouts.app')

@section('title', 'Crear Requisición')

@section('content')
<div class="wrap">
    <div class="card">
        <div class="card-header">
            <h1>Crear Requisición</h1>
        </div>

        <div class="card-body">
            @if (session('success'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: {!! json_encode(session('success')) !!},
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


            <form id="requisicionForm" action="{{ route('requisiciones.store') }}" method="POST" class="form">
                @csrf

                <div class="grid-2">
                    <div class="form-group">
                        <label class="label">Recobrable</label>
                        <select name="Recobrable" class="input" required>
                            <option value="">-- Selecciona --</option>
                            <option value="Recobrable" {{ old('Recobrable')=='Recobrable' ? 'selected' : '' }}>
                                Recobrable</option>
                            <option value="No recobrable" {{ old('Recobrable')=='No recobrable' ? 'selected' : '' }}>No
                                recobrable</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="label">Prioridad</label>
                        <select name="prioridad_requisicion" class="input" required>
                            <option value="">-- Selecciona --</option>
                            <option value="baja" {{ old('prioridad_requisicion')=='baja' ? 'selected' : '' }}>Baja
                            </option>
                            <option value="media" {{ old('prioridad_requisicion')=='media' ? 'selected' : '' }}>Media
                            </option>
                            <option value="alta" {{ old('prioridad_requisicion')=='alta' ? 'selected' : '' }}>Alta
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="label">Justificación</label>
                    <textarea name="justify_requisicion" class="input" rows="3"
                        required>{{ old('justify_requisicion') }}</textarea>
                </div>

                <div class="form-group">
                    <label class="label">Detalles Adicionales</label>
                    <textarea name="detail_requisicion" class="input" rows="3"
                        required>{{ old('detail_requisicion') }}</textarea>
                </div>

                <hr class="divider">

                <h3 class="section-title">Agregar Producto</h3>
                <div class="grid-3 align-end">
                    <div class="form-group">
                        <label class="label">Producto</label>
                        <select id="productoSelect" class="input">
                            <option value="">-- Selecciona producto --</option>
                            @foreach ($productos as $p)
                            <option value="{{ $p->id }}" data-nombre="{{ $p->name_produc }}"
                                data-proveedor="{{ $p->proveedor_id ?? '' }}">
                                {{ $p->name_produc }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="label">Cantidad Total</label>
                        <input type="number" id="cantidadTotalInput" class="input" min="1" placeholder="Ej: 100">
                    </div>
                    <div class="form-group">
                        <button type="button" id="iniciarProductoBtn" class="btn primary w-100">
                            Distribuir en Centros
                        </button>
                    </div>
                </div>

                <div id="centrosSection" class="card soft d-none">
                    <h4 class="section-subtitle">Distribución por Centros de Costo</h4>
                    <div class="notice">
                        <p>Distribuya la cantidad total entre los centros de costo</p>
                    </div>

                    <div class="grid-3 align-end">
                        <div class="form-group">
                            <label class="label">Centro</label>
                            <select id="centroSelect" class="input">
                                <option value="">-- Selecciona centro --</option>
                                @foreach ($centros as $c)
                                <option value="{{ $c->id }}" data-nombre="{{ $c->name_centro }}">
                                    {{ $c->name_centro }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Cantidad</label>
                            <input type="number" id="cantidadCentroInput" class="input" min="1" placeholder="Ej: 50">
                        </div>

                        <div class="form-group">
                            <button type="button" id="agregarCentroBtn" class="btn success w-100">Agregar</button>
                        </div>
                    </div>

                    <div class="summary">
                        <p>Total asignado: <span id="totalAsignado">0</span> de <span id="cantidadDisponible">0</span>
                        </p>
                    </div>

                    <ul id="centrosList" class="list"></ul>

                    <div class="actions">
                        <button type="button" id="guardarProductoBtn" class="btn success">
                            Guardar Distribución
                        </button>
                    </div>
                </div>

                <hr class="divider">

                <h3 class="section-title">Productos agregados</h3>
                <div class="table-wrap">
                    <table id="productosTable" class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad Total</th>
                                <th>Distribución por Centros</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="actions end">
                    <button type="submit" class="btn primary lg">Guardar Requisición</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $ = selector => document.querySelector(selector);
        
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

        let productos = [];
        let productoActual = null;
        let cantidadTotal = 0;
        let cantidadAsignada = 0;

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
                        <span class="chip">${centro.nombre} <b>(${centro.cantidad})</b></span>
                        <input type="hidden" name="productos[${i}][centros][${j}][id]" value="${centro.id}">
                        <input type="hidden" name="productos[${i}][centros][${j}][cantidad]" value="${centro.cantidad}">
                    `;
                });

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>
                        ${prod.nombre}
                        <input type="hidden" name="productos[${i}][id]" value="${prod.id}">
                        ${prod.proveedorId ? `<input type="hidden" name="productos[${i}][proveedor_id]" value="${prod.proveedorId}">` : ''}
                    </td>
                    <td>
                        ${prod.cantidadTotal}
                        <input type="hidden" name="productos[${i}][requisicion_amount]" value="${prod.cantidadTotal}">
                    </td>
                    <td>${centrosHTML}</td>
                    <td class="text-right">
                        <button type="button" onclick="eliminarProducto(${i})" class="btn danger sm">Eliminar</button>
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

            if (!productoId || !cantidadTotal || cantidadTotal < 1) {
                mostrarError('Selecciona un producto y una cantidad total válida.');
                return;
            }

            if (productos.some(p => p.id === productoId)) {
                mostrarError('Este producto ya fue agregado a la requisición.');
                return;
            }

            productoActual = {
                id: productoId,
                nombre: productoSelect.selectedOptions[0].dataset.nombre,
                proveedorId: productoSelect.selectedOptions[0].dataset.proveedor || null,
                cantidadTotal: cantidadTotal,
                centros: []
            };

            cantidadAsignada = 0;
            centrosSection.classList.remove('d-none');
            centrosList.innerHTML = '';
            actualizarResumen();
        });

        agregarCentroBtn.addEventListener('click', () => {
            if (!productoActual) return;

            const centroId = centroSelect.value;
            const cantidadCentro = parseInt(cantidadCentroInput.value);
            const cantidadRestante = cantidadTotal - cantidadAsignada;

            if (!centroId || !cantidadCentro || cantidadCentro < 1) {
                mostrarError('Selecciona un centro y una cantidad válida.');
                return;
            }

            if (cantidadCentro > cantidadRestante) {
                mostrarError(`No puedes asignar más de ${cantidadRestante} unidades (restantes).`);
                return;
            }

            const idx = productoActual.centros.findIndex(c => c.id === centroId);
            if (idx >= 0) {
                productoActual.centros[idx].cantidad += cantidadCentro;
            } else {
                productoActual.centros.push({
                    id: centroId,
                    nombre: centroSelect.selectedOptions[0].dataset.nombre,
                    cantidad: cantidadCentro
                });
            }

            cantidadAsignada += cantidadCentro;
            
            centrosList.innerHTML = '';
            productoActual.centros.forEach(c => {
                const li = document.createElement('li');
                li.className = 'list-item';
                li.textContent = `${c.nombre} - ${c.cantidad} unidades`;
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
                mostrarError(`Debes distribuir toda la cantidad (${cantidadTotal - cantidadAsignada} unidades restantes).`);
                return;
            }

            productos.push(productoActual);
            productoActual = null;
            cantidadTotal = 0;
            cantidadAsignada = 0;

            centrosSection.classList.add('d-none');
            productoSelect.value = '';
            cantidadTotalInput.value = '';
            actualizarTabla();
        });

        requisicionForm.addEventListener('submit', (e) => {
            if (productos.length === 0) {
                e.preventDefault();
                mostrarError('Agrega al menos un producto antes de guardar.');
            }
        });
    });
</script>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/requisiciones.css') }}">
<style>
    .notice {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 4px;
        margin-bottom: 15px;
        border-left: 4px solid #17a2b8;
    }

    .notice p {
        margin: 0;
        color: #6c757d;
        font-size: 0.9em;
    }

    .list-item {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }

    .summary {
        margin: 15px 0;
        font-weight: bold;
    }

    .chip {
        display: inline-block;
        background-color: #e9ecef;
        padding: 3px 8px;
        border-radius: 12px;
        margin-right: 5px;
        margin-bottom: 5px;
    }
</style>
@endsection