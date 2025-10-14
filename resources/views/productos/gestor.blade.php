<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor de Productos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-gray-100 pt-16">
    <x-sidebar />
    <div class="max-w-7xl mx-auto mt-4 bg-white">
        <!-- Header -->
        <div class="bg-gray-100 border border-solid border-gray-300 px-6 py-3 flex justify-between items-center">
            <h1 class="text-xl font-semibold text-gray-800">Gestor de Productos</h1>
        </div>

        <!-- Tabs -->
        <div class="flex space-x-1 bg-gray-200 px-2 pt-2">
            <button id="tab-productos" onclick="toggleSection('productos')"
                class="px-4 py-2 bg-white rounded-t-lg shadow text-gray-700 font-medium">
                Productos Registrados
            </button>
            <button id="tab-solicitudes" onclick="toggleSection('solicitudes')"
                class="px-4 py-2 text-gray-600 hover:text-gray-800 font-medium">
                Productos Solicitados
            </button>
        </div>

        <!-- Sección de Productos Registrados -->
        <div id="productos-section" class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <!-- Barra de búsqueda y filtros -->
                <div class="flex flex-col md:flex-row gap-2 md:items-center">
                    <button onclick="openModal('producto')"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        + Añadir Producto
                    </button>

                    <input type="text" id="searchInput" placeholder="Buscar producto..."
                        class="px-3 py-2 border rounded-md w-full md:w-64" onkeyup="filterTable()">

                    <select id="filterCategoria" onchange="filterTable()"
                        class="px-3 py-2 border rounded-md w-full md:w-48">
                        <option value="">Todas las Categorías</option>
                        <option value="Tecnología">Tecnología</option>
                        <option value="Contabilidad">Contabilidad</option>
                        <option value="Talento Humano">Talento Humano</option>
                        <option value="Compras">Compras</option>
                        <option value="Calidad">Calidad</option>
                        <option value="HSEQ">HSEQ</option>
                        <option value="Comercial">Comercial</option>
                        <option value="Operaciones">Operaciones</option>
                        <option value="Financiera">Financiera</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                        <option value="Otros">Otros</option>
                    </select>

                    <!-- Filtro de Proveedor -->
                    <select id="filterProveedor" onchange="filterTable()"
                        class="px-3 py-2 border rounded-md w-full md:w-60">
                        <option value="">Todos los Proveedores</option>
                        @foreach($proveedores as $prov)
                            <option value="{{ $prov->prov_name }}">{{ $prov->prov_name }}</option>
                        @endforeach
                    </select>

                    <select id="filterEstado" onchange="filterTable()"
                        class="px-3 py-2 border rounded-md w-full md:w-40">
                        <option value="">Todos</option>
                        <option value="Activo">Activo</option>
                        <option value="Eliminado">Eliminado</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="productosTable" class="w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Producto</th>
                            <th class="px-4 py-2 text-left">Categoría</th>
                            <th class="px-4 py-2 text-left">Proveedor</th>
                            <th class="px-4 py-2 text-left">Stock</th>
                            <th class="px-4 py-2 text-left">Unidad</th>
                            <th class="px-4 py-2 text-left">Precio</th>
                            <th class="px-4 py-2 text-left">IVA</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                            <th class="px-4 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                        @php
                            // Obtener proveedores asociados y precios desde la tabla productoxproveedor
                            $provList = \Illuminate\Support\Facades\DB::table('productoxproveedor as pxp')
                                ->join('proveedores as prov','prov.id','=','pxp.proveedor_id')
                                ->where('pxp.producto_id', $producto->id)
                                ->select('prov.id as prov_id','prov.prov_name','pxp.price_produc','pxp.moneda')
                                ->orderBy('pxp.id','asc')
                                ->get();
                            $firstProv = $provList->first();
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2" data-col="nombre">{{ $producto->name_produc }}</td>
                            <td class="px-4 py-2" data-col="categoria">{{ $producto->categoria_produc }}</td>
                            <td class="px-4 py-2" data-col="proveedor">
                                @if($provList && $provList->count())
                                    <ul class="text-sm">
                                        @foreach($provList as $pv)
                                            <li>{{ $pv->prov_name }} <small class="text-gray-500">(${{ number_format($pv->price_produc,2) }} {{ $pv->moneda ?? '' }})</small></li>
                                        @endforeach
                                    </ul>
                                @else
                                    N/A
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ number_format($producto->stock_produc, 0) }}</td>
                            <td class="px-4 py-2">{{ $producto->unit_produc }}</td>
                            <td class="px-4 py-2">@if($firstProv) ${{ number_format($firstProv->price_produc, 2) }} @else - @endif</td>
                            <td class="px-4 py-2">{{ isset($producto->iva) ? number_format($producto->iva, 2).'%' : '-' }}</td>
                            <td class="px-4 py-2" data-col="estado">
                                @if($producto->trashed())
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Eliminado</span>
                                @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Activo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div class="flex justify-center space-x-2">
                                    @if($producto->trashed())
                                    <form action="{{ route('productos.restore', [$producto->id], false) }}" method="POST"
                                        class="inline" onsubmit="showLoading(event)">
                                        @csrf
                                        @method('POST')
                                        <button type="submit" class="text-green-600 hover:text-green-800"
                                            title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('productos.forceDelete', [$producto->id], false) }}" method="POST"
                                        class="inline" onsubmit="return confirmDelete(event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800"
                                            title="Eliminar Permanentemente">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    @php
                                        // Valores por defecto para el modal: usar el primer proveedor si existe
                                        $editProvId = $firstProv->prov_id ?? 'null';
                                        $editPrice = $firstProv->price_produc ?? 0;
                                    @endphp
                                    <button
                                        data-providers='@json($provList)'
                                        onclick="openEditModal(this, {{ $producto->id }}, '{{ addslashes($producto->name_produc) }}', '{{ addslashes($producto->categoria_produc) }}', {{ $producto->stock_produc }}, {{ $editPrice }}, {{ $producto->iva ?? 0 }}, '{{ addslashes($producto->unit_produc) }}', `{{ addslashes($producto->description_produc) }}`)"
                                        class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <!-- Botón para gestionar proveedores (abre modal separado) -->
                                    <button
                                        data-providers='@json($provList)'
                                        data-product-name="{{ addslashes($producto->name_produc) }}"
                                        onclick="openManageProvidersModal(this, {{ $producto->id }})"
                                        class="text-yellow-600 hover:text-yellow-800" title="Gestionar Proveedores">
                                        <i class="fas fa-boxes"></i>
                                    </button>
                                    <form action="{{ route('productos.destroy', [$producto->id], false) }}" method="POST"
                                        class="inline" onsubmit="return confirmDelete(event)">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- Paginación Productos -->
            <div class="flex items-center justify-between mt-4 px-4" id="prodPaginationBar">
                <div class="text-sm text-gray-600">
                    Mostrar
                    <select id="prodPageSizeSelect" class="border rounded px-2 py-1">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    por página
                </div>
                <div class="flex flex-wrap gap-1" id="prodPaginationControls"></div>
            </div>
        </div>

        <!-- Sección de Productos Solicitados (oculta inicialmente) -->
        <div id="solicitudes-section" class="p-4 overflow-x-auto hidden">
            @if($solicitudes->isEmpty())
            <p class="text-gray-500">No hay productos solicitados.</p>
            @else
            <table id="solicitudesTable" class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Solicitado por</th>
                        <th class="px-4 py-2 text-left">Producto</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($solicitudes as $solicitud)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $solicitud->created_at ? $solicitud->created_at->format('d/m/Y') : '' }}</td>
                        <td class="px-4 py-2">{{ $solicitud->name_user }}</td>
                        <td class="px-4 py-2">{{ $solicitud->nombre }}</td>
                        <td class="px-4 py-2">{{ $solicitud->descripcion }}</td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex justify-center space-x-2">
                                @if($solicitud->trashed())
                                <form action="{{ route('nuevo_producto.restore', [$solicitud->id], false) }}" method="POST"
                                    onsubmit="showLoading(event)">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Restaurar">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                                <form action="{{ route('nuevo_producto.forceDelete', [$solicitud->id], false) }}" method="POST"
                                    onsubmit="return confirmDelete(event)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"
                                        title="Eliminar Permanentemente">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <!-- Botón Añadir producto (icono minimalista) -->
                                <button type="button" onclick="openAddFromSolicitudModal({{ $solicitud->id }})"
                                    class="text-green-600 hover:text-green-800" title="Añadir producto">
                                    <i class="fas fa-plus"></i>
                                </button>

                                <!-- Botón Rechazar solicitud (icono minimalista) -->
                                <form action="{{ route('nuevo_producto.destroy', [$solicitud->id], false) }}" method="POST"
                                    onsubmit="return confirmDelete(event)" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" title="Rechazar solicitud">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>

                                <!-- Botón Ver solicitud (icono minimalista) -->
                                <button type="button"
                                    onclick="openSolicitudModal('{{ $solicitud->nombre }}', `{{ $solicitud->descripcion }}`, '{{ $solicitud->name_user }}')"
                                    class="text-blue-600 hover:text-blue-800" title="Ver solicitud">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <!-- Paginación Solicitudes -->
            <div class="flex items-center justify-between mt-4 px-4" id="solPaginationBar">
                <div class="text-sm text-gray-600">
                    Mostrar
                    <select id="solPageSizeSelect" class="border rounded px-2 py-1">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                    por página
                </div>
                <div class="flex flex-wrap gap-1" id="solPaginationControls"></div>
            </div>
            @endif
        </div>



        <!-- Modal para crear/editar producto -->
        <div id="productModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold" id="modalTitle">Nuevo Producto</h2>
                    <button onclick="closeModal('producto')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="productForm" action="{{ route('productos.store', [], false) }}" method="POST"
                        onsubmit="return validateProductForm()">
                        @csrf
                        <input type="hidden" id="formMethod" name="_method" value="POST">
                        <input type="hidden" id="productId" name="id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                                <input type="text" id="name_produc" name="name_produc"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="name_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Campo de Categoría con dropdown de búsqueda -->
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <div class="relative">
                                    <!-- Input con búsqueda -->
                                    <input type="text" id="categoria_input" name="categoria_input"
                                        placeholder="Escribe o selecciona una categoría..."
                                        class="w-full px-3 py-2 border rounded-md" autocomplete="off" required>

                                    <!-- Dropdown personalizado -->
                                    <div id="categoria_dropdown"
                                        class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Tecnología">
                                            Tecnología
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Contabilidad">
                                            Contabilidad
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Talento Humano">
                                            Talento Humano
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Compras">
                                            Compras
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Calidad">
                                            Calidad
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="HSEQ">
                                            HSEQ
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Comercial">
                                            Comercial
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Operaciones">
                                            Operaciones
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Financiera">
                                            Financiera
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Mantenimiento">
                                            Mantenimiento
                                        </div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat"
                                            data-value="Otros">
                                            Otros
                                        </div>
                                    </div>
                                </div>

                                <!-- Campo oculto para almacenar el valor real -->
                                <input type="hidden" id="categoria_produc" name="categoria_produc">
                                <span id="categoria_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                <input type="number" id="stock_produc" name="stock_produc" min="0" step="1"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="stock_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IVA (%)</label>
                                <input type="number" id="iva" name="iva" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md" value="0">
                                <span id="iva_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida</label>
                                <div class="relative">
                                    <input type="text" id="unit_input" name="unit_input"
                                        placeholder="Selecciona o escribe una unidad..."
                                        class="w-full px-3 py-2 border rounded-md cursor-pointer" autocomplete="off">
                                    <div id="unit_dropdown"
                                        class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Unidad">Unidad</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Pieza">Pieza</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Docena">Docena</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Caja">Caja</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Paquete">Paquete</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Rollo">Rollo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Juego">Juego</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Litro">Litro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Mililitro">Mililitro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Kilogramo">Kilogramo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Gramo">Gramo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Metro">Metro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Centímetro">Centímetro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Galón">Galón</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat" data-value="Otro">Otro</div>
                                    </div>
                                </div>
                                <input type="hidden" id="unit_produc" name="unit_produc">
                                <span id="unit_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea id="description_produc" name="description_produc" rows="3"
                                class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>
                    </form>
                </div>
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button onclick="closeModal('producto')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="submitProductForm()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para crear proveedor (aumentado z-index para que aparezca encima) -->
        <div id="proveedorModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-60">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Nuevo Proveedor</h2>
                    <button onclick="closeModal('proveedor')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="proveedorForm" method="POST" action="{{ route('proveedores.store', [], false) }}">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Proveedor
                                    *</label>
                                <input type="text" id="prov_name" name="prov_name"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_name_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NIT *</label>
                                <input type="text" id="prov_nit" name="prov_nit"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_nit_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Contacto *</label>
                                <input type="text" id="prov_name_c" name="prov_name_c"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_name_c_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                                <input type="text" id="prov_phone" name="prov_phone"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_phone_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección *</label>
                                <input type="text" id="prov_adress" name="prov_adress"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_adress_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad *</label>
                                <input type="text" id="prov_city" name="prov_city"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="prov_city_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                            <textarea id="prov_descrip" name="prov_descrip" rows="3"
                                class="w-full px-3 py-2 border rounded-md" required></textarea>
                            <span id="prov_descrip_error" class="text-red-500 text-xs hidden"></span>
                        </div>
                    </form>
                </div>
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button onclick="closeModal('proveedor')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="submitProveedorForm()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Ver Solicitud -->
        <div id="solicitudModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-30">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-2/3 lg:w-1/3">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Detalle de la Solicitud</h2>
                    <button onclick="closeSolicitudModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4 space-y-3">
                    <p><strong>Solicitado por:</strong> <span id="solicitudUsuario"></span></p>
                    <p><strong>Nombre:</strong> <span id="solicitudNombre"></span></p>
                    <p><strong>Descripción:</strong> <span id="solicitudDescripcion"></span></p>
                </div>
                <div class="p-4 border-t flex justify-end">
                </div>
            </div>
        </div>

        <!-- Modal Añadir desde Solicitud -->
        <div id="addFromSolicitudModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Añadir Producto desde Solicitud</h2>
                    <button onclick="closeModal('addFromSolicitud')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="addFromSolicitudForm" action="{{ route('productos.store', [], false) }}" method="POST"
                        onsubmit="return validateAddFromSolicitudForm()">
                        @csrf
                        <input type="hidden" id="solicitudId" name="solicitud_id" value="">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <!-- Nombre -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto
                                    *</label>
                                <input type="text" id="solicitud_name_produc" name="name_produc"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="solicitud_name_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Categoría con dropdown estilo modal de producto -->
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                                <div class="relative">
                                    <input type="text" id="solicitud_categoria_input" name="categoria_input"
                                        placeholder="Escribe o selecciona una categoría..."
                                        class="w-full px-3 py-2 border rounded-md" autocomplete="off" required>

                                    <div id="solicitud_categoria_dropdown"
                                        class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Tecnología">Tecnología</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Contabilidad">Contabilidad</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Talento Humano">Talento Humano</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Compras">Compras</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Calidad">Calidad</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="HSEQ">HSEQ</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Comercial">Comercial</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Operaciones">Operaciones</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Financiera">Financiera</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Mantenimiento">Mantenimiento</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud"
                                            data-value="Otros">Otros</div>
                                    </div>
                                </div>

                                <input type="hidden" id="solicitud_categoria_produc" name="categoria_produc">
                                <span id="solicitud_categoria_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Proveedor con dropdown estilo modal de producto -->
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor *</label>
                                <div class="relative flex">
                                    <!-- Input con búsqueda -->
                                    <input type="text" id="solicitud_proveedor_input" name="proveedor_name"
                                        placeholder="Escribe o selecciona un proveedor..."
                                        class="w-full px-3 py-2 border rounded-md rounded-r-none" autocomplete="off"
                                        required>

                                    <!-- Botón para crear nuevo proveedor -->
                                    <button type="button" onclick="openModal('proveedor')"
                                        class="bg-blue-500 text-white px-3 rounded-r-md">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <!-- Dropdown personalizado -->
                                <div id="solicitud_proveedor_dropdown"
                                    class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                    @foreach($proveedores as $proveedor)
                                    <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-solicitud"
                                        data-id="{{ $proveedor->id }}" data-name="{{ $proveedor->prov_name }}">
                                        {{ $proveedor->prov_name }}
                                    </div>
                                    @endforeach
                                </div>

                                <input type="hidden" id="solicitud_proveedor_id" name="proveedor_id">
                                <span id="solicitud_proveedor_id_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Stock -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock *</label>
                                <input type="number" id="solicitud_stock_produc" name="stock_produc" min="0" step="1" value="0"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="solicitud_stock_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Precio -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio *</label>
                                <input type="number" id="solicitud_price_produc" name="price_produc" step="0.01" min="0"
                                    value="0" class="w-full px-3 py-2 border rounded-md" required>
                                <span id="solicitud_price_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">IVA (%)</label>
                                <input type="number" id="solicitud_iva" name="iva" step="0.01" min="0" value="0" class="w-full px-3 py-2 border rounded-md">
                                <span id="solicitud_iva_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <!-- Unidad de medida -->
                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida *</label>
                                <div class="relative">
                                    <input type="text" id="solicitud_unit_input" name="unit_input"
                                        placeholder="Selecciona o escribe una unidad..."
                                        class="w-full px-3 py-2 border rounded-md cursor-pointer" autocomplete="off" value="Unidad">
                                    <div id="solicitud_unit_dropdown"
                                        class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Unidad">Unidad</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Pieza">Pieza</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Docena">Docena</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Caja">Caja</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Paquete">Paquete</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Rollo">Rollo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Juego">Juego</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Litro">Litro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Mililitro">Mililitro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Kilogramo">Kilogramo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Gramo">Gramo</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Metro">Metro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Centímetro">Centímetro</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Galón">Galón</div>
                                        <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item-cat-solicitud" data-value="Otro">Otro</div>
                                    </div>
                                </div>
                                <input type="hidden" id="solicitud_unit_produc" name="unit_produc" value="Unidad">
                                <span id="solicitud_unit_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción *</label>
                            <textarea id="solicitud_description_produc" name="description_produc" rows="3"
                                class="w-full px-3 py-2 border rounded-md" required></textarea>
                            <span id="solicitud_description_produc_error" class="text-red-500 text-xs hidden"></span>
                        </div>
                    </form>
                </div>

                <!-- Footer botones -->
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button onclick="closeModal('addFromSolicitud')"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="submitAddFromSolicitudForm()"
                        class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Guardar Producto
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para gestionar proveedores por producto (separado) -->
        <div id="manageProvidersModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-2/3 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Proveedores del Producto</h2>
                    <button onclick="closeModal('manageProviders')" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-4">
                    <div class="mb-4">
                        <h3 id="manageProductName" class="text-lg font-medium"></h3>
                        <p id="manageProductCategory" class="text-sm text-gray-500"></p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Añadir proveedor</label>
                        <div class="flex gap-2 items-center mb-2">
                            <div class="relative flex-1">
                                <input type="text" id="manage_prov_input" placeholder="Selecciona un proveedor..." class="w-full px-3 py-2 border rounded-md" autocomplete="off">
                                <!-- Dropdown movido al final del body para evitar recorte -->
                            </div>
                            <!-- Botón para abrir modal de crear proveedor desde gestionar proveedores -->
                            <button type="button" onclick="openModal('proveedor')" title="Nuevo proveedor" class="bg-blue-500 text-white px-3 py-2 rounded hover:bg-blue-600">
                                <i class="fas fa-plus"></i>
                            </button>
                            <input type="number" id="manage_prov_price" placeholder="Precio" step="0.01" min="0" class="w-32 px-3 py-2 border rounded-md">
                            <select id="manage_prov_moneda" class="w-40 px-3 py-2 border rounded-md">
                                <option value="Pesos Colombianos">Pesos Colombianos</option>
                                <option value="Dólar">Dólar</option>
                                <option value="Euro">Euro</option>
                                <option value="Otro">Otro</option>
                            </select>
                            <button type="button" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700" onclick="addManageProviderRow()">Agregar</button>
                        </div>

                        <div class="overflow-auto max-h-48 border rounded">
                            <table class="w-full text-sm" id="manageProvidersTable">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Proveedor</th>
                                        <th class="px-3 py-2 text-left">Precio</th>
                                        <th class="px-3 py-2 text-left">Moneda</th>
                                        <th class="px-3 py-2 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div id="manageProvidersInputs"></div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button class="px-4 py-2 bg-gray-300 text-gray-700 rounded" onclick="closeModal('manageProviders')">Cerrar</button>
                        <button class="px-4 py-2 bg-indigo-600 text-white rounded" onclick="submitManageProviders()">Guardar proveedores</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loadingOverlay"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center">
        <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-12 w-12 mb-4"></div>
        <h2 class="text-center text-gray-700 text-xl font-semibold">Procesando...</h2>
        <p class="text-center text-gray-500">Por favor espere.</p>
    </div>
</div>

<script>
    // Evitar redeclaraciones de manageProviderIndex: declarar una vez en ámbito global
    var manageProviderIndex = typeof manageProviderIndex !== 'undefined' ? manageProviderIndex : 0;

    // Abrir modal de solicitud
    function openSolicitudModal(nombre, descripcion, usuario) {
        document.getElementById('solicitudNombre').textContent = nombre;
        document.getElementById('solicitudDescripcion').textContent = descripcion;
        document.getElementById('solicitudUsuario').textContent = usuario;
        document.getElementById('solicitudModal').classList.remove('hidden');
    }

    // Cerrar modal de solicitud
    function closeSolicitudModal() {
        document.getElementById('solicitudModal').classList.add('hidden');
    }

    // Abrir modal para añadir desde solicitud
    function openAddFromSolicitudModal(solicitudId) {
        // Mostrar loading
        const overlay = document.getElementById('loadingOverlay'); if (overlay) overlay.classList.remove('hidden');

        fetch(`/productos/solicitud/${solicitudId}`)
            .then(response => {
                if (!response.ok) throw new Error('Error al obtener los datos de la solicitud');
                return response.json();
            })
            .then(data => {
                try {
                    // Abrir modal de producto (resetea el formulario)
                    openModal('producto');

                    // Asegurar que el formulario de producto tiene un input hidden solicitud_id
                    const form = document.getElementById('productForm');
                    if (form) {
                        let hid = form.querySelector('input[name="solicitud_id"]');
                        if (!hid) {
                            hid = document.createElement('input'); hid.type = 'hidden'; hid.name = 'solicitud_id'; hid.id = 'product_solicitud_id';
                            form.appendChild(hid);
                        }
                        hid.value = solicitudId;
                    }

                    // Mapear datos de la solicitud en los mismos campos que el modal de producto
                    if (data.nombre) document.getElementById('name_produc').value = data.nombre;
                    if (typeof data.descripcion !== 'undefined') document.getElementById('description_produc').value = data.descripcion || '';
                    const stockEl = document.getElementById('stock_produc'); if (stockEl) stockEl.value = sanitizeToInt(data.stock_produc ?? data.stock ?? 0);
                    const ivaEl = document.getElementById('iva'); if (ivaEl) ivaEl.value = (typeof data.iva !== 'undefined') ? data.iva : (ivaEl.value || 0);

                    // Categoría y unidad (asegurar también los campos ocultos)
                    const catIn = document.getElementById('categoria_input'); const catHidden = document.getElementById('categoria_produc');
                    if (catIn) catIn.value = data.categoria ?? (data.categoria_produc || '');
                    if (catHidden) catHidden.value = data.categoria ?? (data.categoria_produc || '');

                    const unitIn = document.getElementById('unit_input'); const unitHidden = document.getElementById('unit_produc');
                    if (unitIn) unitIn.value = data.unit_input ?? (data.unit_produc || 'Unidad');
                    if (unitHidden) unitHidden.value = data.unit_input ?? (data.unit_produc || 'Unidad');

                    // Ocultar overlay
                    if (overlay) overlay.classList.add('hidden');
                } catch (e) {
                    if (overlay) overlay.classList.add('hidden');
                    console.warn(e);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error al abrir el modal de producto' , confirmButtonColor: '#1e40af'});
                }
            })
            .catch(err => {
                if (overlay) overlay.classList.add('hidden');
                Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Error al obtener la solicitud', confirmButtonColor: '#1e40af'});
            });
    }

    // Funciones para controlar modales
    function openModal(type) {
        if (type === 'producto') {
            document.getElementById('productModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = 'Nuevo Producto';
            document.getElementById('productId').value = '';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('productForm').action = "{{ route('productos.store', [], false) }}";
            document.getElementById('productForm').reset();
            // Asegurar stock entero por defecto
            const defaultStockEl = document.getElementById('stock_produc'); if (defaultStockEl) defaultStockEl.value = 0;
            // Limpiar campos de búsqueda
            const provInput = document.getElementById('proveedor_input'); if(provInput) provInput.value = '';
            const catInput = document.getElementById('categoria_input'); if(catInput) catInput.value = '';
            const uIn = document.getElementById('unit_input');
            const uHidden = document.getElementById('unit_produc');
            if (uIn) uIn.value = '';
            if (uHidden) uHidden.value = '';
            const provHidden = document.getElementById('proveedor_id');
            const catHidden = document.getElementById('categoria_produc');
            if (provHidden) provHidden.value = '';
            if (catHidden) catHidden.value = '';
            // Limpiar mensajes de error
            clearErrorMessages();
        } else if (type === 'proveedor') {
            document.getElementById('proveedorModal').classList.remove('hidden');
            document.getElementById('proveedorForm').reset();
            // Limpiar mensajes de error
            clearProveedorErrorMessages();
        } else if (type === 'addFromSolicitud') {
            document.getElementById('addFromSolicitudModal').classList.remove('hidden');
        }
    }

    function closeModal(type) {
        if (type === 'producto') {
            document.getElementById('productModal').classList.add('hidden');
        } else if (type === 'proveedor') {
            document.getElementById('proveedorModal').classList.add('hidden');
        } else if (type === 'addFromSolicitud') {
            document.getElementById('addFromSolicitudModal').classList.add('hidden');
        } else if (type === 'manageProviders') {
            document.getElementById('manageProvidersModal').classList.add('hidden');
        }
    }

    function openEditModal(id, nombre, categoria, proveedorId, stock, precio, unidad, descripcion) {
        // backward compatible: support signature with iva param
        let ivaParam = 0;
        // Si se llama con el elemento como primer argumento, el orden esperado es:
        // (elem, id, nombre, categoria, stock, precio, iva, unidad, descripcion)
        if (typeof id === 'object' && id.dataset) {
            const elem = id;
            id = arguments[1];
            nombre = arguments[2];
            categoria = arguments[3];
            // aquí stock viene en la posición 4
            stock = arguments[4];
            precio = arguments[5];
            ivaParam = arguments[6] ?? 0;
            unidad = arguments[7] ?? '';
            descripcion = arguments[8] ?? '';

            // populate providers from data attribute if present
            try {
                const provData = elem.getAttribute('data-providers');
                const providers = provData ? JSON.parse(provData) : [];
                populateProvidersTable(providers);
            } catch (e) { console.warn('No providers data', e); }
        } else {
            // llamado sin elemento, asumir signature id,nombre,categoria,proveedorId,stock,precio,unidad,descripcion
            ivaParam = 0;
        }
        document.getElementById('modalTitle').textContent = 'Editar Producto';
        document.getElementById('productId').value = id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('productForm').action = '/productos/' + id;
        
        document.getElementById('name_produc').value = nombre;
        const stockElem = document.getElementById('stock_produc'); if (stockElem) stockElem.value = sanitizeToInt(stock);
        document.getElementById('iva').value = ivaParam;
        const uIn = document.getElementById('unit_input');
        const uHidden = document.getElementById('unit_produc');
        if (uIn) uIn.value = unidad;
        if (uHidden) uHidden.value = unidad;
        document.getElementById('description_produc').value = descripcion;
        
        // Para el proveedor, buscar el nombre correspondiente al ID
        const proveedorOption = document.querySelector(`.option-item[data-id="${proveedorId}"]`);
        if (proveedorOption) {
            document.getElementById('proveedor_input').value = proveedorOption.getAttribute('data-name');
            document.getElementById('proveedor_id').value = proveedorId;
        }
        
        // Para la categoría, establecer el valor en ambos campos
        document.getElementById('categoria_input').value = categoria;
        document.getElementById('categoria_produc').value = categoria;
        
        document.getElementById('productModal').classList.remove('hidden');
        
        // Limpiar mensajes de error
        clearErrorMessages();
    }

    // Gestión de proveedores dentro del modal de producto eliminada: usar modal "Gestionar Proveedores".
    // Función para filtrar proveedores en el modal de añadir desde solicitud
    function filterSolicitudProveedores() {
        const searchTerm = document.getElementById('solicitud_proveedor_search').value.toLowerCase();
        const proveedorSelect = document.getElementById('solicitud_proveedor_id');
        const resultsContainer = document.getElementById('solicitud_proveedor_results');
        
        // Limpiar resultados anteriores
        resultsContainer.innerHTML = '';
        
        if (searchTerm === '') {
            resultsContainer.classList.add('hidden');
            return;
        }
        
        let hasResults = false;
        
        // Buscar coincidencias
        for (let i = 0; i < proveedorSelect.options.length; i++) {
            const option = proveedorSelect.options[i];
            if (option.text.toLowerCase().includes(searchTerm)) {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
                div.textContent = option.text;
                div.onclick = function() {
                    proveedorSelect.value = option.value;
                    document.getElementById('solicitud_proveedor_search').value = option.text;
                    resultsContainer.classList.add('hidden');
                };
                resultsContainer.appendChild(div);
                hasResults = true;
            }
        }
        
        if (hasResults) {
            resultsContainer.classList.remove('hidden');
        } else {
            resultsContainer.classList.add('hidden');
        }
    }

    // Gestión de proveedores por producto (modal separado)
    manageProviderIndex = 0;
    function openManageProvidersModal(elem, productId) {
        const provData = elem.getAttribute('data-providers');
        const productName = elem.getAttribute('data-product-name') || '';
        document.getElementById('manageProductName').textContent = productName;
        document.getElementById('manageProductCategory').textContent = '';
        document.getElementById('manageProvidersTable').querySelector('tbody').innerHTML = '';
        document.getElementById('manageProvidersInputs').innerHTML = '';
        manageProviderIndex = 0;
        if (provData) {
            try {
                const provs = JSON.parse(provData);
                populateManageProvidersTable(provs);
            } catch (e) { console.warn(e); }
        }
        // show modal and store product id
        document.getElementById('manageProvidersModal').dataset.productId = productId;
        document.getElementById('manageProvidersModal').classList.remove('hidden');
    }

    function addManageProviderRow() {
        const nameInput = document.getElementById('manage_prov_input');
        const priceInput = document.getElementById('manage_prov_price');
        const monedaSelect = document.getElementById('manage_prov_moneda');
        const dropdown = document.getElementById('manage_prov_dropdown');
        const tbody = document.querySelector('#manageProvidersTable tbody');
        const provName = nameInput.value.trim();
        const price = parseFloat(priceInput.value);
        const moneda = monedaSelect ? monedaSelect.value : '';
        if (!provName) { Swal.fire({icon:'info', title:'Proveedor', text:'Seleccione un proveedor'}); return; }
        if (isNaN(price) || price < 0) { Swal.fire({icon:'info', title:'Precio', text:'Ingrese un precio válido'}); return; }
        // Buscar opción por nombre (case-insensitive)
        const option = Array.from(dropdown.querySelectorAll('.manage-option-item')).find(o => (o.getAttribute('data-name') || o.textContent || '').toLowerCase() === provName.toLowerCase());
        const providerId = option ? option.getAttribute('data-id') : null;
        if (!providerId) { Swal.fire({icon:'error', title:'Proveedor', text:'Proveedor no válido'}); return; }
        if (document.querySelector(`#manageProvidersTable tbody tr[data-prov-id="${providerId}"]`)) { Swal.fire({icon:'info', title:'Duplicado', text:'El proveedor ya fue agregado'}); return; }
        const tr = document.createElement('tr');
        tr.setAttribute('data-prov-id', providerId);
        tr.innerHTML = `<td class="px-3 py-2">${provName}</td><td class="px-3 py-2">$${price.toFixed(2)}</td><td class=\"px-3 py-2\">${moneda}</td><td class="px-3 py-2 text-center"><button type="button" class="text-red-600" onclick="removeManageProviderRow(${manageProviderIndex})"><i class=\"fas fa-trash\"></i></button></td>`;
        tbody.appendChild(tr);
        const inputsDiv = document.getElementById('manageProvidersInputs');
        const wrapper = document.createElement('div'); wrapper.id = 'manage_provider_row_' + manageProviderIndex;
        wrapper.setAttribute('data-prov-id', providerId);
        wrapper.innerHTML = `<input type="hidden" name="providers[${manageProviderIndex}][provider_id]" value="${providerId}"><input type="hidden" name="providers[${manageProviderIndex}][price]" value="${price}"><input type="hidden" name="providers[${manageProviderIndex}][moneda]" value="${moneda}">`;
        inputsDiv.appendChild(wrapper);
        manageProviderIndex++;
        nameInput.value = ''; priceInput.value = '';
    }

    function removeManageProviderRow(idx) {
        const wrapper = document.getElementById('manage_provider_row_' + idx);
        let provId = null;
        if (wrapper) provId = wrapper.getAttribute('data-prov-id') || wrapper.querySelector('input')?.value;
        if (wrapper) wrapper.remove();
        if (provId) {
            const tr = document.querySelector(`#manageProvidersTable tbody tr[data-prov-id="${provId}"]`);
            if (tr) tr.remove();
        } else {
            // fallback: remove any row at that index
            const tbody = document.querySelector('#manageProvidersTable tbody'); const rows = Array.from(tbody.querySelectorAll('tr'));
            if (rows[idx]) rows[idx].remove();
        }
    }

    function populateManageProvidersTable(providers) {
        const tbody = document.querySelector('#manageProvidersTable tbody'); tbody.innerHTML = '';
        const inputsDiv = document.getElementById('manageProvidersInputs'); inputsDiv.innerHTML = '';
        manageProviderIndex = 0;
        providers.forEach(p => {
            const name = p.prov_name || p.prov_name || '';
            const price = parseFloat(p.price_produc || p.price || 0);
            const moneda = p.moneda || p.currency || '';
            const provId = p.prov_id || p.id || p.proveedor_id;
            const tr = document.createElement('tr');
            tr.setAttribute('data-prov-id', provId);
            tr.innerHTML = `<td class="px-3 py-2">${name}</td><td class="px-3 py-2">$${price.toFixed(2)}</td><td class=\"px-3 py-2\">${moneda}</td><td class="px-3 py-2 text-center"><button type="button" class="text-red-600" onclick="removeManageProviderRow(${manageProviderIndex})"><i class=\"fas fa-trash\"></i></button></td>`;
            tbody.appendChild(tr);
            const wrapper = document.createElement('div'); wrapper.id = 'manage_provider_row_' + manageProviderIndex;
            wrapper.setAttribute('data-prov-id', provId);
            wrapper.innerHTML = `<input type="hidden" name="providers[${manageProviderIndex}][provider_id]" value="${provId}"><input type="hidden" name="providers[${manageProviderIndex}][price]" value="${price}"><input type="hidden" name="providers[${manageProviderIndex}][moneda]" value="${moneda}">`;
            inputsDiv.appendChild(wrapper);
            manageProviderIndex++;
        });
    }

    function submitManageProviders() {
        const modal = document.getElementById('manageProvidersModal');
        const productId = modal.dataset.productId;
        const inputs = Array.from(document.querySelectorAll('#manageProvidersInputs input'));
        const providers = [];
        for (let i = 0; i < inputs.length; i += 3) {
            const pid = inputs[i].value; const price = inputs[i+1].value; const moneda = inputs[i+2].value;
            providers.push({provider_id: pid, price: price, moneda: moneda});
        }

        // Si no se agregaron proveedores, avisar y no enviar
        if (!providers.length) {
            Swal.fire({
                icon: 'info',
                title: 'Sin proveedores',
                text: 'Agregue al menos un proveedor antes de guardar.',
                confirmButtonColor: '#1e40af'
            });
            return;
        }

        fetch(`/productos/${productId}/providers`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ providers })
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                Swal.fire({icon:'success', title:'Guardado', text: data.message || 'Proveedores guardados'}).then(()=> location.reload());
            } else {
                Swal.fire({icon:'error', title:'Error', text: data.message || 'No se pudo guardar'});
            }
        }).catch(e => {
            Swal.fire({icon:'error', title:'Error', text:'Error al guardar proveedores'});
        });
    }

    // Script adicional para manejo seguro de creación de proveedores y notificaciones
    function submitProveedorForm() {
        if (!validateProveedorForm()) return;
        const form = document.getElementById('proveedorForm');
        const url = form.action;
        const fd = new FormData(form);
        document.getElementById('loadingOverlay').classList.remove('hidden');

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: fd
        }).then(async (res) => {
            document.getElementById('loadingOverlay').classList.add('hidden');
            let data = null;
            try { data = await res.json(); } catch (e) { data = null; }

            // Si hubo redirección, recargar
            if (res.redirected) {
                Swal.fire({icon:'success', title:'Proveedor creado', text: 'Proveedor guardado correctamente'}).then(() => location.reload());
                return;
            }

            // Si la respuesta es JSON con errores de validación
            if (!res.ok) {
                // Construir mensaje legible
                let htmlMessage = '';
                if (data && data.errors && typeof data.errors === 'object') {
                    htmlMessage = '<ul style="text-align:left;margin:0;padding-left:18px;">';
                    Object.keys(data.errors).forEach(key => {
                        const arr = data.errors[key] || [];
                        arr.forEach(msg => { htmlMessage += `<li>${msg}</li>`; });
                    });
                    htmlMessage += '</ul>';
                } else if (data && data.message) {
                    htmlMessage = `<div style="text-align:left;">${data.message}</div>`;
                } else {
                    htmlMessage = 'No se pudo crear el proveedor';
                }

                Swal.fire({icon:'error', title:'Error', html: htmlMessage});
                return;
            }

            // OK response
            if (data && (data.success || data.provider)) {
                const prov = data.provider || data;
                try { addProveedorToUI(prov); } catch (e) { console.warn(e); }
                form.reset();
                document.getElementById('proveedorModal').classList.add('hidden');
                Swal.fire({icon:'success', title:'Proveedor creado', text: data.message || 'Proveedor guardado correctamente'}).then(() => location.reload());
            } else {
                // Fallback: mostrar mensaje si viene en data
                const msg = (data && data.message) ? data.message : 'Proveedor creado';
                Swal.fire({icon:'success', title:'Proveedor creado', text: msg}).then(() => location.reload());
            }
        }).catch(err => {
            document.getElementById('loadingOverlay').classList.add('hidden');
            console.error(err);
            Swal.fire({icon:'error', title:'Error', text: 'Error al guardar proveedor'});
        });
    }

    // Mostrar overlay de carga y permitir que el formulario continúe con el submit
    function showLoading(event) {
        try {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.classList.remove('hidden');
        } catch (e) { console.warn('showLoading:', e); }
        // No llamar event.preventDefault() aquí: permitimos que el envío continúe
        return true;
    }

    // Confirmación genérica para eliminar (usa SweetAlert2). Previene el submit y envía si confirma.
    function confirmDelete(event) {
        // localizar el formulario asociado
        let form = null;
        try {
            if (event && event.target) {
                form = (event.target.tagName === 'FORM') ? event.target : event.target.closest('form');
            }
        } catch (e) { form = null; }

        // Si el formulario fue marcado para saltar la confirmación (envío programático), permitirlo
        if (form && form.dataset && form.dataset.skipConfirm === '1') {
            // limpiar la marca y permitir que el envío continúe
            delete form.dataset.skipConfirm;
            return true;
        }

        try { event.preventDefault(); } catch (e) { /* ignore */ }
         Swal.fire({
             title: '¿Está seguro?',
             text: 'Se notificará al solicitante.',
             icon: 'warning',
             showCancelButton: true,
             confirmButtonColor: '#dc2626',
             cancelButtonColor: '#6b7280',
             confirmButtonText: 'Sí, eliminar',
             cancelButtonText: 'Cancelar'
         }).then((result) => {
             if (result.isConfirmed) {
                 try { const overlay = document.getElementById('loadingOverlay'); if (overlay) overlay.classList.remove('hidden'); } catch(e){}
                 if (form) {
                    // marcar para que el onsubmit no vuelva a pedir confirmación
                    try { form.dataset.skipConfirm = '1'; } catch (e) {}
                    // usar requestSubmit si está disponible para respetar onsubmit
                    if (typeof form.requestSubmit === 'function') form.requestSubmit();
                    else form.submit();
                 }
             }
         });
         return false;
     }

    // Limpiar mensajes de error
    function clearErrorMessages() {
        const errorElements = document.querySelectorAll('[id$="_error"]');
        errorElements.forEach(element => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    function clearProveedorErrorMessages() {
        const errorElements = document.querySelectorAll('[id$="_error"]');
        errorElements.forEach(element => {
            element.classList.add('hidden');
            element.textContent = '';
        });
    }

    // Validar formulario de producto
    function validateProductForm() {
        clearErrorMessages();
        let isValid = true;

        const nameElem = document.getElementById('name_produc');
        const catElem = document.getElementById('categoria_produc');
        const stockElem = document.getElementById('stock_produc');
        const ivaElem = document.getElementById('iva');
        const unitElem = document.getElementById('unit_produc');

        if (!nameElem || !nameElem.value.trim()) { const e = document.getElementById('name_produc_error'); if (e) { e.textContent = 'El nombre del producto es requerido'; e.classList.remove('hidden'); } isValid = false; }

        if (!catElem || !catElem.value) { const e = document.getElementById('categoria_produc_error'); if (e) { e.textContent = 'La categoría es requerida'; e.classList.remove('hidden'); } isValid = false; }

        if (!stockElem) { const e = document.getElementById('stock_produc_error'); if (e) { e.textContent = 'El stock es requerido'; e.classList.remove('hidden'); } isValid = false; }
        else {
            const stockVal = parseInt(stockElem.value, 10);
            if (isNaN(stockVal) || stockVal < 0) { const e = document.getElementById('stock_produc_error'); if (e) { e.textContent = 'El stock debe ser un entero mayor o igual a 0'; e.classList.remove('hidden'); } isValid = false; }
            else stockElem.value = stockVal;
        }

        if (ivaElem && (isNaN(parseFloat(ivaElem.value)) || parseFloat(ivaElem.value) < 0)) { const e = document.getElementById('iva_error'); if (e) { e.textContent = 'El IVA debe ser un número válido mayor o igual a 0'; e.classList.remove('hidden'); } isValid = false; }

        if (!unitElem || !unitElem.value.trim()) { const e = document.getElementById('unit_produc_error'); if (e) { e.textContent = 'La unidad de medida es requerida'; e.classList.remove('hidden'); } isValid = false; }

        if (isValid) showLoading();
        return isValid;
    }

    // Validar formulario de proveedor
    function validateProveedorForm() {
        clearProveedorErrorMessages();
        let isValid = true;
        const prov_name = document.getElementById('prov_name');
        const prov_nit = document.getElementById('prov_nit');
        const prov_name_c = document.getElementById('prov_name_c');
        const prov_phone = document.getElementById('prov_phone');
        const prov_adress = document.getElementById('prov_adress');
        const prov_city = document.getElementById('prov_city');
        const prov_descrip = document.getElementById('prov_descrip');

        if (!prov_name || !prov_name.value.trim()) { const e = document.getElementById('prov_name_error'); if (e) { e.textContent = 'El nombre del proveedor es requerido'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_nit || !prov_nit.value.trim()) { const e = document.getElementById('prov_nit_error'); if (e) { e.textContent = 'El NIT es requerido'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_name_c || !prov_name_c.value.trim()) { const e = document.getElementById('prov_name_c_error'); if (e) { e.textContent = 'El nombre de contacto es requerido'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_phone || !prov_phone.value.trim()) { const e = document.getElementById('prov_phone_error'); if (e) { e.textContent = 'El teléfono es requerido'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_adress || !prov_adress.value.trim()) { const e = document.getElementById('prov_adress_error'); if (e) { e.textContent = 'La dirección es requerida'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_city || !prov_city.value.trim()) { const e = document.getElementById('prov_city_error'); if (e) { e.textContent = 'La ciudad es requerida'; e.classList.remove('hidden'); } isValid = false; }
        if (!prov_descrip || !prov_descrip.value.trim()) { const e = document.getElementById('prov_descrip_error'); if (e) { e.textContent = 'La descripción es requerida'; e.classList.remove('hidden'); } isValid = false; }
        return isValid;
    }

    // Enviar formularios
    function submitProductForm() {
        if (validateProductForm()) {
            document.getElementById('productForm').submit();
        }
    }

    // submitProveedorForm está implementada previamente usando fetch() para manejar respuestas JSON y mostrar SweetAlert

    // Función para abrir modal de proveedor desde cualquier formulario
    function openProveedorModal() {
        document.getElementById('proveedorModal').classList.remove('hidden');
        document.getElementById('proveedorForm').reset();
        clearProveedorErrorMessages();
    }

    // Función para cerrar modal de proveedor
    function closeProveedorModal() {
        document.getElementById('proveedorModal').classList.add('hidden');
    }

    // NUEVO: Validación modal "Añadir desde Solicitud"
    function validateAddFromSolicitudForm() {
        // ocultar errores previos
        const errIds = [
            'solicitud_name_produc_error', 'solicitud_categoria_produc_error', 'solicitud_proveedor_id_error',
            'solicitud_stock_produc_error', 'solicitud_price_produc_error', 'solicitud_iva_error',
            'solicitud_unit_produc_error', 'solicitud_description_produc_error'
        ];
        errIds.forEach(id => { const el = document.getElementById(id); if (el) { el.classList.add('hidden'); el.textContent = ''; } });

        let ok = true;
        const nameEl = document.getElementById('solicitud_name_produc');
        const catHidden = document.getElementById('solicitud_categoria_produc');
        const provHidden = document.getElementById('solicitud_proveedor_id');
        const stockEl = document.getElementById('solicitud_stock_produc');
        const priceEl = document.getElementById('solicitud_price_produc');
        const ivaEl = document.getElementById('solicitud_iva');
        const unitHidden = document.getElementById('solicitud_unit_produc');
        const descEl = document.getElementById('solicitud_description_produc');

        if (!nameEl || !nameEl.value.trim()) { const e = document.getElementById('solicitud_name_produc_error'); if (e) { e.textContent = 'Requerido'; e.classList.remove('hidden'); } ok = false; }
        if (!catHidden || !catHidden.value) { const e = document.getElementById('solicitud_categoria_produc_error'); if (e) { e.textContent = 'Seleccione una categoría'; e.classList.remove('hidden'); } ok = false; }
        // El proveedor no es obligatorio al añadir desde solicitud; omitir validación

        if (!stockEl) { const e = document.getElementById('solicitud_stock_produc_error'); if (e) { e.textContent = 'Stock requerido'; e.classList.remove('hidden'); } ok = false; }
        else {
            const stockVal = parseInt(stockEl.value, 10);
            if (isNaN(stockVal) || stockVal < 0) { const e = document.getElementById('solicitud_stock_produc_error'); if (e) { e.textContent = 'Stock inválido'; e.classList.remove('hidden'); } ok = false; }
            else stockEl.value = stockVal;
        }

        if (!priceEl) { const e = document.getElementById('solicitud_price_produc_error'); if (e) { e.textContent = 'Precio requerido'; e.classList.remove('hidden'); } ok = false; }
        else { const priceVal = parseFloat(priceEl.value); if (isNaN(priceVal) || priceVal < 0) { const e = document.getElementById('solicitud_price_produc_error'); if (e) { e.textContent = 'Precio inválido'; e.classList.remove('hidden'); } ok = false; } }

        if (ivaEl && (isNaN(parseFloat(ivaEl.value)) || parseFloat(ivaEl.value) < 0)) { const e = document.getElementById('solicitud_iva_error'); if (e) { e.textContent = 'IVA inválido'; e.classList.remove('hidden'); } ok = false; }

        if (!unitHidden || !unitHidden.value.trim()) { const e = document.getElementById('solicitud_unit_produc_error'); if (e) { e.textContent = 'Seleccione una unidad'; e.classList.remove('hidden'); } ok = false; }
        if (!descEl || !descEl.value.trim()) { const e = document.getElementById('solicitud_description_produc_error'); if (e) { e.textContent = 'Descripción requerida'; e.classList.remove('hidden'); } ok = false; }

        return ok;
    }

    // Actualizar select de proveedores en todos los formularios
    function updateProveedoresSelect(proveedores) {
        // update solicitud select
        const solicitudSelect = document.getElementById('solicitud_proveedor_id');
        if (solicitudSelect) {
            const currentValue = solicitudSelect.value;
            while (solicitudSelect.options.length > 0) { solicitudSelect.remove(0); }
            proveedores.forEach(p => { const o = document.createElement('option'); o.value = p.id; o.textContent = p.prov_name; solicitudSelect.appendChild(o); });
            if (currentValue && solicitudSelect.querySelector(`option[value="${currentValue}"]`)) solicitudSelect.value = currentValue;
        }

        // actualizar dropdowns visuales: solicitud y manage
        const solicitudDropdown = document.getElementById('solicitud_proveedor_dropdown');
        const manageDropdown = document.getElementById('manage_prov_dropdown');
        [solicitudDropdown, manageDropdown].forEach(dropdown => {
            if (!dropdown) return;
            dropdown.innerHTML = '';
            proveedores.forEach(proveedor => {
                const div = document.createElement('div');
                div.className = 'px-3 py-2 hover:bg-indigo-100 cursor-pointer';
                div.setAttribute('data-id', proveedor.id);
                div.setAttribute('data-name', proveedor.prov_name);
                div.textContent = proveedor.prov_name;
                dropdown.appendChild(div);
            });
        });
     }

    // Función para alternar secciones (Productos / Solicitudes)
    function toggleSection(section) {
        const prodBtn = document.getElementById('tab-productos');
        const solBtn = document.getElementById('tab-solicitudes');
        const prodSec = document.getElementById('productos-section');
        const solSec = document.getElementById('solicitudes-section');
        if (!prodBtn || !solBtn || !prodSec || !solSec) return;

        if (section === 'productos') {
            prodSec.classList.remove('hidden');
            solSec.classList.add('hidden');
            prodBtn.classList.add('bg-white');
            solBtn.classList.remove('bg-white');
        } else if (section === 'solicitudes') {
            prodSec.classList.add('hidden');
            solSec.classList.remove('hidden');
            prodBtn.classList.remove('bg-white');
            solBtn.classList.add('bg-white');
        }

        // Reset pagination/views
        try { if (typeof prodShowPage === 'function') prodShowPage(1); } catch(e) {}
        try { if (typeof solShowPage === 'function') solShowPage(1); } catch(e) {}
    }

    // Asegurar que la pestaña de Productos esté activa al cargar
    document.addEventListener('DOMContentLoaded', function(){
        const prodBtn = document.getElementById('tab-productos');
        const solBtn = document.getElementById('tab-solicitudes');
        const prodSec = document.getElementById('productos-section');
        const solSec = document.getElementById('solicitudes-section');
        if (!prodBtn || !solBtn || !prodSec || !solSec) return;
        // si la vista de solicitudes está visible por alguna razón, mantenerla oculta
        if (!prodBtn.classList.contains('bg-white') && !solBtn.classList.contains('bg-white')) {
            prodBtn.classList.add('bg-white');
        }
        // asegurar sección por defecto
        prodSec.classList.remove('hidden');
        solSec.classList.add('hidden');
    });

    // Mostrar mensajes de éxito/error con SweetAlert
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: '¡Éxito!',
            text: '{{ session('success') }}',
            confirmButtonColor: '#1e40af'
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
            confirmButtonColor: '#1e40af'
        });
    @endif

    @if($errors->any())
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `@foreach ($errors->all() as $error){{ $error }}<br>@endforeach`,
            confirmButtonColor: '#1e40af'
        });
    @endif

    function filterTable() {
        const searchInput = document.getElementById('searchInput').value.toLowerCase().trim();
        const filterCategoria = document.getElementById('filterCategoria').value;
        const filterProveedor = document.getElementById('filterProveedor').value;
        const filterEstado = document.getElementById('filterEstado').value;
        const rows = document.querySelectorAll('#productosTable tbody tr');

        rows.forEach(row => {
            const nombre = (row.querySelector('td[data-col="nombre"]')?.textContent || '').toLowerCase();
            const categoria = (row.querySelector('td[data-col="categoria"]')?.textContent || '').trim();
            const proveedor = (row.querySelector('td[data-col="proveedor"]')?.textContent || '').trim();
            const estadoEl = row.querySelector('td[data-col="estado"] span');
            const estado = estadoEl ? estadoEl.textContent.trim() : '';

            const matchesSearch = !searchInput || nombre.includes(searchInput);
            const matchesCategoria = !filterCategoria || categoria === filterCategoria;
            const matchesProveedor = !filterProveedor || proveedor === filterProveedor;
            const matchesEstado = !filterEstado || estado === filterEstado;

            row.dataset.match = (matchesSearch && matchesCategoria && matchesProveedor && matchesEstado) ? '1' : '0';
        });

        // Reiniciar a la primera página tras filtrar
        prodShowPage(1);
    }

    // ===== Paginación Productos =====
    let prodCurrentPage = 1;
    let prodPageSize = 10;

    function getProdMatchedRows() {
        return Array.from(document.querySelectorAll('#productosTable tbody tr'))
            .filter(r => (r.dataset.match ?? '1') !== '0');
    }

    function prodShowPage(page = 1) {
        const rows = getProdMatchedRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / prodPageSize));
        prodCurrentPage = Math.min(Math.max(1, page), totalPages);
        const start = (prodCurrentPage - 1) * prodPageSize;
        const end = start + prodPageSize;

        const allRows = Array.from(document.querySelectorAll('#productosTable tbody tr'));
        allRows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        renderProdPagination(totalPages);
    }

    function renderProdPagination(totalPages) {
        const container = document.getElementById('prodPaginationControls');
        if (!container) return;
        container.innerHTML = '';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (prodCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = prodCurrentPage === 1;
        btnPrev.onclick = () => prodShowPage(prodCurrentPage - 1);
        container.appendChild(btnPrev);

        const start = Math.max(1, prodCurrentPage - 2);
        const end = Math.min(totalPages, prodCurrentPage + 2);
        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1 rounded text-sm ' + (p === prodCurrentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
            btn.onclick = () => prodShowPage(p);
            container.appendChild(btn);
        }

        const btnNext = document.createElement('button');
        btnNext.textContent = 'Siguiente';
        btnNext.className = 'px-3 py-1 border rounded text-sm ' + (prodCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnNext.disabled = prodCurrentPage === totalPages;
        btnNext.onclick = () => prodShowPage(prodCurrentPage + 1);
        container.appendChild(btnNext);
    }

    // ===== Paginación Solicitudes =====
    let solCurrentPage =  1;
    let solPageSize = 10;

    function solGetAllRows(){
        return Array.from(document.querySelectorAll('#solicitudesTable tbody tr'));
    }



    function solShowPage(page = 1){
        const rows = solGetAllRows();
        const totalPages = Math.max(1, Math.ceil(rows.length / solPageSize));
        solCurrentPage = Math.min(Math.max(1, page), totalPages);
        const start = (solCurrentPage - 1) * solPageSize;
        const end = start + solPageSize;

        rows.forEach(r => r.style.display = 'none');
        rows.slice(start, end).forEach(r => r.style.display = '');

        renderSolPagination(totalPages);
    }

    function renderSolPagination(totalPages){
        const container = document.getElementById('solPaginationControls');
        if (!container) return;
        container.innerHTML = '';

        const btnPrev = document.createElement('button');
        btnPrev.textContent = 'Anterior';
        btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (solCurrentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnPrev.disabled = solCurrentPage === 1;
        btnPrev.onclick = () => solShowPage(solCurrentPage - 1);
        container.appendChild(btnPrev);

        const start = Math.max(1, solCurrentPage - 2);
        const end = Math.min(totalPages, solCurrentPage + 2);
        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.textContent = p;
            btn.className = 'px-3 py-1 rounded text-sm ' + (p === solCurrentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
            btn.onclick = () => solShowPage(p);
            container.appendChild(btn);
        }

        const btnNext = document.createElement('button');
        btnNext.textContent = 'Siguiente';
        btnNext.className = 'px-3 py-1 border rounded text-sm ' + (solCurrentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
        btnNext.disabled = solCurrentPage === totalPages;
        btnNext.onclick = () => solShowPage(solCurrentPage + 1);
        container.appendChild(btnNext);
    }

    // Inicializar paginación
    document.addEventListener('DOMContentLoaded', function(){
        // Productos: marcar todo como coincidente y configurar selector
        document.querySelectorAll('#productosTable tbody tr').forEach(r => r.dataset.match = '1');
        const prodSel = document.getElementById('prodPageSizeSelect');
        if (prodSel) {
            prodPageSize = parseInt(prodSel.value, 10) || 10;
            prodSel.addEventListener('change', (e) => {
                prodPageSize = parseInt(e.target.value, 10) || 10;
                prodShowPage(1);
            });
        }
        prodShowPage(1);

        // Solicitudes: configurar selector si existe
        const solSel = document.getElementById('solPageSizeSelect');
        if (solSel) {
            solPageSize = parseInt(solSel.value, 10) || 10;
            solSel.addEventListener('change', (e) => {
                solPageSize = parseInt(e.target.value, 10) || 10;
                solShowPage(1);
            });
            solShowPage(1);
        }
    });
    // ===== Inicializar selects personalizados (Producto y Solicitud) =====
  document.addEventListener('DOMContentLoaded', function(){
        // Manage providers dropdown: move element to body and use fixed positioning so it's not clipped by modal
        const mProvIn = document.getElementById('manage_prov_input');
        let mProvDrop = document.getElementById('manage_prov_dropdown');
        if (!mProvIn) return;

        // ensure dropdown exists and is attached to body
        if (!mProvDrop) {
            mProvDrop = document.createElement('div');
            mProvDrop.id = 'manage_prov_dropdown';
            mProvDrop.className = 'fixed z-50 bg-white border border-gray-300 rounded-md shadow-lg';
            document.body.appendChild(mProvDrop);
        } else if (mProvDrop.parentElement !== document.body) {
            document.body.appendChild(mProvDrop);
        }

        // Si el dropdown está vacío en el DOM, poblarlo con la lista de proveedores del servidor
        if (!mProvDrop.innerHTML || !mProvDrop.innerHTML.trim()) {
            mProvDrop.innerHTML = `
                <div style="padding:6px 0;">
                @foreach($proveedores as $proveedor)
                    <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer manage-option-item" data-id="{{ $proveedor->id }}" data-name="{{ $proveedor->prov_name }}">{{ $proveedor->prov_name }}</div>
                @endforeach
                </div>
            `;
        }

        mProvDrop.style.position = 'fixed';
        mProvDrop.style.display = 'none';
        mProvDrop.style.zIndex = 9999;
        mProvDrop.style.maxHeight = '220px';
        mProvDrop.style.overflow = 'auto';

        const filterMProv = () => {
            const q = (mProvIn.value || '').toLowerCase();
            Array.from(mProvDrop.querySelectorAll('.manage-option-item')).forEach(opt => {
                const name = (opt.getAttribute('data-name') || opt.textContent || '').toLowerCase();
                opt.style.display = name.includes(q) ? '' : 'none';
            });
        };

        const updatePosition = () => {
            const rect = mProvIn.getBoundingClientRect();
            // position relative to viewport
            mProvDrop.style.left = rect.left + 'px';
            mProvDrop.style.top = rect.bottom + 'px';
            mProvDrop.style.width = rect.width + 'px';
        };

        const show = () => { filterMProv(); updatePosition(); mProvDrop.style.display = 'block'; mProvDrop.classList.remove('hidden'); };
        const hide = () => { mProvDrop.style.display = 'none'; mProvDrop.classList.add('hidden'); };

        mProvIn.addEventListener('focus', show);
        mProvIn.addEventListener('input', show);
        mProvIn.addEventListener('click', show);

        // Support keyboard navigation: Escape hides
        mProvIn.addEventListener('keydown', (e) => { if (e.key === 'Escape') hide(); });

        mProvDrop.addEventListener('click', (e) => {
            const el = e.target.closest('.manage-option-item');
            if (!el) return;
            mProvIn.value = el.getAttribute('data-name') || el.textContent.trim();
            // trigger input event so any listeners update
            mProvIn.dispatchEvent(new Event('input', { bubbles: true }));
            hide();
        });

        // Hide on outside click
        document.addEventListener('click', function(e){ if (!mProvIn.contains(e.target) && !mProvDrop.contains(e.target)) hide(); });
        // Hide on window resize
        window.addEventListener('resize', hide);
        // Hide on scrolls that originate outside the input/dropdown (ignore scrolls inside the dropdown)
        document.addEventListener('scroll', function(e){
            const t = e.target;
            if (!mProvIn.contains(t) && !mProvDrop.contains(t)) hide();
        }, true);

        // Prevent wheel events inside the dropdown from bubbling up and closing it
        mProvDrop.addEventListener('wheel', function(e){ e.stopPropagation(); }, { passive: true });
    });
    
    // Cuando se abre el modal de añadir desde solicitud, asegurarse de que el campo IVA esté disponible
    document.addEventListener('DOMContentLoaded', function(){
        const solIva = document.getElementById('solicitud_iva');
        if (solIva) solIva.value = solIva.value || 0;
    });

    // Helper: convertir cualquier representación numérica (p. ej. "782,47" o " 782.47 ") a entero
    function sanitizeToInt(val) {
        let s = (val === null || val === undefined) ? '' : String(val);
        // normalizar coma decimal y eliminar caracteres no numéricos excepto punto y signo
        s = s.replace(/\s+/g, '').replace(/,/g, '.').replace(/[^0-9.\-]/g, '');
        const n = parseFloat(s);
        if (isNaN(n)) return 0;
        return Math.trunc(n);
    }

    // Inicializar dropdowns filtrables que permiten seleccionar o escribir un valor libre
    (function(){
        function safeOpen(id){
            try{ document.getElementById(id).classList.remove('hidden'); }catch(e){ console.warn('No se pudo abrir modal', id, e); }
        }
        // Añadir handlers para botones inline que usan openModal('proveedor') / 'producto' / 'addFromSolicitud'
        document.querySelectorAll('button[onclick]').forEach(btn=>{
            const oc = btn.getAttribute('onclick') || '';
            if(oc.includes("openModal('proveedor')")){
                btn.addEventListener('click', function(e){ e.preventDefault(); // limpiar form y abrir
                    const form = document.getElementById('proveedorForm'); if(form) form.reset(); safeOpen('proveedorModal');
                });
            }
            if(oc.includes("openModal('producto')")){
                btn.addEventListener('click', function(e){ e.preventDefault(); const form = document.getElementById('productForm'); if(form){ form.reset(); document.getElementById('formMethod').value='POST'; } safeOpen('productModal'); });
            }
            if(oc.includes("openModal('addFromSolicitud')")){
                btn.addEventListener('click', function(e){ e.preventDefault(); safeOpen('addFromSolicitudModal'); });
            }
        });
    })();

    // Conectar dropdowns simples (categoría y unidad) con sus inputs y campos ocultos
    document.addEventListener('DOMContentLoaded', function(){
        function wireDropdownSimple(inputId, dropdownId, hiddenId) {
            const input = document.getElementById(inputId);
            const dropdown = document.getElementById(dropdownId);
            const hidden = hiddenId ? document.getElementById(hiddenId) : null;
            if (!input || !dropdown) return;

            const optionSelector = '.option-item-cat, .option-item-cat-solicitud, .option-item';
            const options = Array.from(dropdown.querySelectorAll(optionSelector));

            const show = () => dropdown.classList.remove('hidden');
            const hide = () => dropdown.classList.add('hidden');

            input.addEventListener('focus', show);
            input.addEventListener('click', show);

            input.addEventListener('input', function(){
                const q = (input.value || '').toLowerCase();
                options.forEach(opt => {
                    const txt = (opt.getAttribute('data-value') || opt.getAttribute('data-name') || opt.textContent || '').toLowerCase();
                    opt.style.display = txt.includes(q) ? '' : 'none';
                });
                show();
            });

            input.addEventListener('keydown', function(e){ if (e.key === 'Escape') hide(); });

            dropdown.addEventListener('click', function(e){
                const opt = e.target.closest(optionSelector);
                if (!opt) return;
                const val = opt.getAttribute('data-value') || opt.getAttribute('data-name') || opt.textContent.trim();
                input.value = opt.getAttribute('data-name') || val;
                if (hidden) hidden.value = val;
                hide();
                // trigger input event for any listeners
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });

            document.addEventListener('click', function(e){
                if (!input.contains(e.target) && !dropdown.contains(e.target)) hide();
            });
        }

        wireDropdownSimple('categoria_input', 'categoria_dropdown', 'categoria_produc');
        wireDropdownSimple('unit_input', 'unit_dropdown', 'unit_produc');
    });
</script>

<style>
    /* Override: asegurar que el modal de proveedor esté por encima de otros modales */
    #proveedorModal {
        z-index: 99999 !important;
    }

    /* Forzar SweetAlert por encima de modales */
    .swal2-container {
        z-index: 100100 !important;
    }
    .swal2-popup, .swal2-modal {
        z-index: 100101 !important;
    }

    /* Tabs estilo Chrome */
    button[id^="tab-"] {
        border-top-left-radius: 0.5rem;
        border-top-right-radius: 0.5rem;
        transition: all 0.2s;
    }

    button[id^="tab-"]:hover {
        background-color: #f9f9f9;
    }

    button[id^="tab-"].bg-white {
        border-bottom: 3px solid #2563eb;
        /* azul Tailwind */
    }

    #proveedor_dropdown {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .option-item {
        transition: background-color 0.2s ease;
    }

    .option-item:not(:last-child) {
        border-bottom: 1px solid #f3f4f6;
    }

    .option-item:hover {
        background-color: #e0e7ff !important;
    }

    #proveedor_dropdown,
    #categoria_dropdown {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0,  0, 0, 0.06);
    }

    .option-item,
    .option-item-cat {
        transition: background-color 0.2s ease;
        cursor: pointer;
    }

    .option-item:not(:last-child),
    .option-item-cat:not(:last-child) {
        border-bottom: 1px solid #f3f4f6;
    }

    .option-item:hover,
    .option-item-cat:hover {
        background-color: #e0e7ff !important;
    }
</style>

</body>
</html>
