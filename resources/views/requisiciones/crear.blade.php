@extends('layouts.app')

@section('title', 'Crear Requisición')

@section('content')
    <div class="wrap">
        <div class="card">
            <div class="card-header">
                <h1>Crear Requisición</h1>
            </div>

            <div class="card-body">
                {{-- Alertas con SweetAlert2 --}}
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
                                title: 'Hay errores',
                                html: {!! json_encode(implode('<br>', $errors->all())) !!},
                                confirmButtonText: 'Revisar'
                            });
                        });
                    </script>
                @endif

                <form id="requisicionForm" action="{{ route('requisiciones.store') }}" method="POST" class="form">
                    @csrf

                    <div class="grid-2">
                        <div class="form-group">
                            <label class="label">Recobrable</label>
                            <select name="recobrable" class="input" required>
                                <option value="">-- Selecciona --</option>
                                <option value="Recobrable" {{ old('recobrable') === 'Recobrable' ? 'selected' : '' }}>Recobrable</option>
                                <option value="No recobrable" {{ old('recobrable') === 'No recobrable' ? 'selected' : '' }}>No Recobrable</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="label">Prioridad</label>
                            <select name="prioridad_requisicion" class="input" required>
                                <option value="">-- Selecciona --</option>
                                <option value="baja" {{ old('prioridad_requisicion') === 'baja' ? 'selected' : '' }}>Baja</option>
                                <option value="media" {{ old('prioridad_requisicion') === 'media' ? 'selected' : '' }}>Media</option>
                                <option value="alta" {{ old('prioridad_requisicion') === 'alta' ? 'selected' : '' }}>Alta</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="label">Justificación</label>
                        <textarea name="justify_requisicion" class="input" rows="3" required>{{ old('justify_requisicion') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label class="label">Detalles adicionales</label>
                        <textarea name="justify_requisicion" class="input" rows="3" required>{{ old('justify_requisicion') }}</textarea>
                    </div>

                    <hr class="divider">

                    <h3 class="section-title">Agregar Producto</h3>
                    <div class="grid-3 align-end">
                        <div class="form-group">
                            <label class="label">Producto</label>
                            <select id="productoSelect" class="input">
                                <option value="">-- Selecciona producto --</option>
                                @foreach ($productos as $p)
                                    <option value="{{ $p->id }}"
                                        data-nombre="{{ $p->name_produc }}"
                                        data-proveedor="{{ $p->proveedor_id ?? '' }}">
                                        {{ $p->name_produc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="label">Cantidad Total</label>
                            <input type="number" id="cantidadTotalInput" class="input" min="1" placeholder="Ej: 10">
                        </div>
                        <div class="form-group">
                            <button type="button" id="iniciarProductoBtn" class="btn primary w-100">
                                Iniciar Centros de Costo
                            </button>
                        </div>
                    </div>

                    <div id="centrosSection" class="card soft d-none">
                        <h4 class="section-subtitle">Centros de Costo</h4>
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
                                <input type="number" id="cantidadCentroInput" class="input" min="1" placeholder="Ej: 3">
                            </div>

                            <div class="form-group">
                                <button type="button" id="agregarCentroBtn" class="btn success w-100">Agregar centro</button>
                            </div>
                        </div>

                        <ul id="centrosList" class="list"></ul>

                        <div class="actions">
                            <button type="button" id="guardarProductoBtn" class="btn success">
                                Guardar producto en la requisición
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
                                    <th>Centros</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    {{-- Total de la requisición (oculto) --}}
                    <input type="hidden" name="amount_requisicion" id="amount_requisicion" value="0">

                    <div class="actions end">
                        <button type="submit" class="btn primary lg">Guardar Requisición</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        let productos = [];
        let productoActual = null;
        let cantidadTotalTemp = 0;

        const $ = (sel) => document.querySelector(sel);

        function actualizarTabla() {
            const tbody = $("#productosTable tbody");
            tbody.innerHTML = "";
            let totalCantidad = 0;

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
                        <input type="hidden" name="productos[${i}][proveedor_id]" value="${prod.proveedor_id ?? ''}">
                    </td>
                    <td>${prod.cantidad}</td>
                    <td>${centrosHTML}</td>
                    <td class="text-right">
                        <button type="button" onclick="eliminarProducto(${i})" class="btn danger sm">Eliminar</button>
                    </td>
                `;
                tbody.appendChild(tr);

                totalCantidad += prod.cantidad;
            });

            $("#amount_requisicion").value = totalCantidad;
        }

        function eliminarProducto(index) {
            productos.splice(index, 1);
            actualizarTabla();
        }

        $("#iniciarProductoBtn").addEventListener("click", () => {
            const prodSelect = $("#productoSelect");
            const cantidadTotal = parseInt($("#cantidadTotalInput").value);

            if (!prodSelect.value || !cantidadTotal || cantidadTotal < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Falta información',
                    text: 'Selecciona un producto y una cantidad total válida.'
                });
                return;
            }

            productoActual = {
                id: prodSelect.value,
                nombre: prodSelect.selectedOptions[0].dataset.nombre,
                proveedor_id: prodSelect.selectedOptions[0].dataset.proveedor || '',
                cantidad: 0,
                centros: []
            };

            cantidadTotalTemp = cantidadTotal;
            $("#centrosSection").classList.remove("d-none");
            $("#centrosList").innerHTML = "";
        });

        $("#agregarCentroBtn").addEventListener("click", () => {
            if (!productoActual) return;

            const centroSelect = $("#centroSelect");
            const cantidadCentro = parseInt($("#cantidadCentroInput").value);

            if (!centroSelect.value || !cantidadCentro || cantidadCentro < 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Datos incompletos',
                    text: 'Selecciona un centro y una cantidad válida.'
                });
                return;
            }

            const sumaActual = productoActual.centros.reduce((sum, c) => sum + c.cantidad, 0);
            if (sumaActual + cantidadCentro > cantidadTotalTemp) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cantidad excedida',
                    text: `No puedes superar la cantidad total de ${cantidadTotalTemp}.`
                });
                return;
            }

            const idx = productoActual.centros.findIndex(c => c.id === centroSelect.value);
            if (idx >= 0) {
                const nueva = productoActual.centros[idx].cantidad + cantidadCentro;
                if (nueva > cantidadTotalTemp) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Cantidad excedida',
                        text: `No puedes superar la cantidad total de ${cantidadTotalTemp}.`
                    });
                    return;
                }
                productoActual.centros[idx].cantidad = nueva;
            } else {
                productoActual.centros.push({
                    id: centroSelect.value,
                    nombre: centroSelect.selectedOptions[0].dataset.nombre,
                    cantidad: cantidadCentro
                });
            }

            const list = $("#centrosList");
            list.innerHTML = "";
            productoActual.centros.forEach(c => {
                const li = document.createElement("li");
                li.className = "list-item";
                li.textContent = `${c.nombre} - ${c.cantidad}`;
                list.appendChild(li);
            });

            $("#cantidadCentroInput").value = "";
            centroSelect.value = "";
        });

        $("#guardarProductoBtn").addEventListener("click", () => {
            if (!productoActual || productoActual.centros.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Centros requeridos',
                    text: 'Debes añadir al menos un centro de costo.'
                });
                return;
            }

            productoActual.cantidad = productoActual.centros.reduce((sum, c) => sum + c.cantidad, 0);

            if (productoActual.cantidad !== cantidadTotalTemp) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cantidades no coinciden',
                    text: `La suma por centros (${productoActual.cantidad}) debe ser igual a la cantidad total (${cantidadTotalTemp}).`
                });
                return;
            }

            productos.push(productoActual);
            productoActual = null;
            cantidadTotalTemp = 0;

            $("#centrosSection").classList.add("d-none");
            $("#productoSelect").value = "";
            $("#cantidadTotalInput").value = "";

            actualizarTabla();
        });

        $("#requisicionForm").addEventListener("submit", (e) => {
            if (productos.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin productos',
                    text: 'Agrega al menos un producto antes de guardar.'
                });
                return;
            }
        });
    </script>
@endsection

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/requisiciones.css') }}">
@endsection
