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
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2" data-col="nombre">{{ $producto->name_produc }}</td>
                            <td class="px-4 py-2" data-col="categoria">{{ $producto->categoria_produc }}</td>
                            <td class="px-4 py-2" data-col="proveedor">{{ $producto->proveedor->prov_name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $producto->stock_produc }}</td>
                            <td class="px-4 py-2">{{ $producto->unit_produc }}</td>
                            <td class="px-4 py-2">${{ number_format($producto->price_produc, 2) }}</td>
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
                                    <button
                                        onclick="openEditModal({{ $producto->id }}, '{{ $producto->name_produc }}', '{{ $producto->categoria_produc }}', {{ $producto->proveedor_id }}, {{ $producto->stock_produc }}, {{ $producto->price_produc }}, {{ $producto->iva ?? 0 }}, '{{ $producto->unit_produc }}', `{{ $producto->description_produc }}`)"
                                        class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <i class="fas fa-edit"></i>
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

                            <div class="relative mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                <div class="relative flex">
                                    <!-- Input con búsqueda -->
                                    <input type="text" id="proveedor_input" name="proveedor_name"
                                        placeholder="Escribe o selecciona un proveedor..."
                                        class="w-full px-3 py-2 border rounded-md rounded-r-none" autocomplete="off"
                                        required>

                                    <!-- Botón para crear nuevo proveedor -->
                                    <button type="button" onclick="openModal('proveedor')"
                                        class="bg-blue-500 text-white px-3 rounded-r-md">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>

                                <!-- Dropdown personalizado - Ahora está dentro del contenedor relativo -->
                                <div id="proveedor_dropdown"
                                    class="absolute z-20 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-48 overflow-y-auto hidden">
                                    @foreach($proveedores as $proveedor)
                                    <div class="px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item"
                                        data-id="{{ $proveedor->id }}" data-name="{{ $proveedor->prov_name }}">
                                        {{ $proveedor->prov_name }}
                                    </div>
                                    @endforeach
                                </div>

                                <input type="hidden" id="proveedor_id" name="proveedor_id">
                                <span id="proveedor_id_error" class="text-red-500 text-xs hidden"></span>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                <input type="number" id="stock_produc" name="stock_produc" min="0"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="stock_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                                <input type="number" id="price_produc" name="price_produc" step="0.01" min="0"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                <span id="price_produc_error" class="text-red-500 text-xs hidden"></span>
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
                                        placeholder="Selecciona una unidad..."
                                        class="w-full px-3 py-2 border rounded-md cursor-pointer" autocomplete="off" readonly>
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
                    <form id="proveedorForm" method="POST">
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
                                <input type="number" id="solicitud_stock_produc" name="stock_produc" min="0" value="0"
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
                                        placeholder="Selecciona una unidad..."
                                        class="w-full px-3 py-2 border rounded-md cursor-pointer" autocomplete="off" readonly value="Unidad">
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
        document.getElementById('loadingOverlay').classList.remove('hidden');
        
        // Obtener datos de la solicitud
        fetch(`/productos/solicitud/${solicitudId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error al obtener los datos de la solicitud');
                }
                return response.json();
            })
            .then(data => {
                // Llenar el formulario con los datos de la solicitud
                document.getElementById('solicitudId').value = solicitudId;
                document.getElementById('solicitud_name_produc').value = data.nombre;
                document.getElementById('solicitud_description_produc').value = data.descripcion;
                
                // Mostrar el modal
                document.getElementById('addFromSolicitudModal').classList.remove('hidden');
                
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.add('hidden');
            })
            .catch(error => {
                // Ocultar loading
                document.getElementById('loadingOverlay').classList.add('hidden');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                    confirmButtonColor: '#1e40af'
                });
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
            // Limpiar campos de búsqueda
            document.getElementById('proveedor_input').value = '';
            document.getElementById('categoria_input').value = '';
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
        }
    }

    function openEditModal(id, nombre, categoria, proveedorId, stock, precio, unidad, descripcion) {
        // backward compatible: support signature with iva param
        let ivaParam = 0;
        if (arguments.length === 9) {
            // new signature: id, nombre, categoria, proveedorId, stock, precio, iva, unidad, descripcion
            ivaParam = arguments[6];
            precio = arguments[5];
            unidad = arguments[7];
            descripcion = arguments[8];
        } else if (arguments.length === 8) {
            // old signature
            ivaParam = 0;
        }
        document.getElementById('modalTitle').textContent = 'Editar Producto';
        document.getElementById('productId').value = id;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('productForm').action = '/productos/' + id;
        
        document.getElementById('name_produc').value = nombre;
        document.getElementById('stock_produc').value = stock;
        document.getElementById('price_produc').value = precio;
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

    function toggleSection(section) {
        const productosSection = document.getElementById('productos-section');
        const solicitudesSection = document.getElementById('solicitudes-section');

        const tabProductos = document.getElementById('tab-productos');
        const tabSolicitudes = document.getElementById('tab-solicitudes');

        if (section === 'productos') {
            productosSection.classList.remove('hidden');
            solicitudesSection.classList.add('hidden');

            // Activar tab productos
            tabProductos.classList.add('bg-white', 'shadow', 'text-gray-700');
            tabProductos.classList.remove('text-gray-600');

            // Desactivar tab solicitudes
            tabSolicitudes.classList.remove('bg-white', 'shadow', 'text-gray-700');
            tabSolicitudes.classList.add('text-gray-600');

            // Recalcular paginación de productos al mostrar la pestaña
            if (typeof prodShowPage === 'function') {
                const page = (typeof prodCurrentPage === 'number' && prodCurrentPage > 0) ? prodCurrentPage : 1;
                setTimeout(() => prodShowPage(page), 0);
            }
        } else {
            productosSection.classList.add('hidden');
            solicitudesSection.classList.remove('hidden');

            // Activar tab solicitudes
            tabSolicitudes.classList.add('bg-white', 'shadow', 'text-gray-700');
            tabSolicitudes.classList.remove('text-gray-600');

            // Desactivar tab productos
            tabProductos.classList.remove('bg-white', 'shadow', 'text-gray-700');
            tabProductos.classList.add('text-gray-600');

            // Recalcular paginación de solicitudes al mostrar la pestaña
            if (typeof solShowPage === 'function') {
                const page = (typeof solCurrentPage === 'number' && solCurrentPage > 0) ? solCurrentPage : 1;
                setTimeout(() => solShowPage(page), 0);
            }
        }
    }

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

    // Cerrar resultados al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#proveedor_search') && !e.target.closest('#proveedor_results')) {
            document.getElementById('proveedor_results').classList.add('hidden');
        }
    });

    // Mostrar loading
    function showLoading(event) {
        document.getElementById('loadingOverlay').classList.remove('hidden');
        return true;
    }

    // Confirmar eliminación
    function confirmDelete(event) {
        event.preventDefault();
        const form = event.target;
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1e40af',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                showLoading();
                form.submit();
            }
        });
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
        
        const name_produc = document.getElementById('name_produc');
        const proveedor_id = document.getElementById('proveedor_id');
        const categoria_produc = document.getElementById('categoria_produc');
        const stock_produc = document.getElementById('stock_produc');
        const price_produc = document.getElementById('price_produc');
        const unit_produc = document.getElementById('unit_produc');
        const ivaElem = document.getElementById('iva');
        
        if (!name_produc.value.trim()) {
            document.getElementById('name_produc_error').textContent = 'El nombre del producto es requerido';
            document.getElementById('name_produc_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!categoria_produc.value) {
            document.getElementById('categoria_produc_error').textContent = 'La categoría es requerida';
            document.getElementById('categoria_produc_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!proveedor_id.value) {
            document.getElementById('proveedor_id_error').textContent = 'El proveedor es requerido';
            document.getElementById('proveedor_id_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!stock_produc.value || stock_produc.value < 0) {
            document.getElementById('stock_produc_error').textContent = 'El stock debe ser un número válido mayor o igual a 0';
            document.getElementById('stock_produc_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!price_produc.value || price_produc.value < 0) {
            document.getElementById('price_produc_error').textContent = 'El precio debe ser un número válido mayor o igual a 0';
            document.getElementById('price_produc_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (ivaElem && (isNaN(parseFloat(ivaElem.value)) || parseFloat(ivaElem.value) < 0)) {
            document.getElementById('iva_error').textContent = 'El IVA debe ser un número válido mayor o igual a 0';
            document.getElementById('iva_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!unit_produc.value.trim()) {
            document.getElementById('unit_produc_error').textContent = 'La unidad de medida es requerida';
            document.getElementById('unit_produc_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (isValid) {
            showLoading();
        }
        
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
        
        if (!prov_name.value.trim()) {
            document.getElementById('prov_name_error').textContent = 'El nombre del proveedor es requerido';
            document.getElementById('prov_name_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_nit.value.trim()) {
            document.getElementById('prov_nit_error').textContent = 'El NIT es requerido';
            document.getElementById('prov_nit_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_name_c.value.trim()) {
            document.getElementById('prov_name_c_error').textContent = 'El nombre de contacto es requerido';
            document.getElementById('prov_name_c_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_phone.value.trim()) {
            document.getElementById('prov_phone_error').textContent = 'El teléfono es requerido';
            document.getElementById('prov_phone_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_adress.value.trim()) {
            document.getElementById('prov_adress_error').textContent = 'La dirección es requerida';
            document.getElementById('prov_adress_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_city.value.trim()) {
            document.getElementById('prov_city_error').textContent = 'La ciudad es requerida';
            document.getElementById('prov_city_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (isValid) {
            showLoading();
        }
        
        return isValid;
    }

    // Enviar formularios
    function submitProductForm() {
        if (validateProductForm()) {
            document.getElementById('productForm').submit();
        }
    }

    function submitProveedorForm() {
        if (validateProveedorForm()) {
            document.getElementById('proveedorForm').submit();
        }
    }

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
        
        if (!prov_name.value.trim()) {
            document.getElementById('prov_name_error').textContent = 'El nombre del proveedor es requerido';
            document.getElementById('prov_name_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_nit.value.trim()) {
            document.getElementById('prov_nit_error').textContent = 'El NIT es requerido';
            document.getElementById('prov_nit_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_name_c.value.trim()) {
            document.getElementById('prov_name_c_error').textContent = 'El nombre de contacto es requerido';
            document.getElementById('prov_name_c_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_phone.value.trim()) {
            document.getElementById('prov_phone_error').textContent = 'El teléfono es requerido';
            document.getElementById('prov_phone_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_adress.value.trim()) {
            document.getElementById('prov_adress_error').textContent = 'La dirección es requerida';
            document.getElementById('prov_adress_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_city.value.trim()) {
            document.getElementById('prov_city_error').textContent = 'La ciudad es requerida';
            document.getElementById('prov_city_error').classList.remove('hidden');
            isValid = false;
        }
        
        if (!prov_descrip.value.trim()) {
            document.getElementById('prov_descrip_error').textContent = 'La descripción es requerida';
            document.getElementById('prov_descrip_error').classList.remove('hidden');
            isValid = false;
        }
        
        return isValid;
    }

    // Enviar formulario de proveedor via AJAX
    function submitProveedorForm() {
        if (!validateProveedorForm()) {
            return;
        }

        const formData = new FormData();
        formData.append('prov_name', document.getElementById('prov_name').value);
        formData.append('prov_nit', document.getElementById('prov_nit').value);
        formData.append('prov_name_c', document.getElementById('prov_name_c').value);
        formData.append('prov_phone', document.getElementById('prov_phone').value);
        formData.append('prov_adress', document.getElementById('prov_adress').value);
        formData.append('prov_city', document.getElementById('prov_city').value);
        formData.append('prov_descrip', document.getElementById('prov_descrip').value);
        formData.append('_token', '{{ csrf_token() }}');

        showLoading();

        fetch('{{ route("proveedores.store", [], false) }}', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar SweetAlert de éxito (confirmación)
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: data.message,
                    confirmButtonColor: '#1e40af'
                }).then(() => {
                    // Actualizar select de proveedores en todos los formularios
                    updateProveedoresSelect(data.proveedores);
                    // Cerrar modal
                    closeModal('proveedor');
                });
            } else {
                // Mostrar errores de validación
                if (data.errors) {
                    for (const field in data.errors) {
                        const errorElement = document.getElementById(field + '_error');
                        if (errorElement) {
                            errorElement.textContent = data.errors[field][0];
                            errorElement.classList.remove('hidden');
                        }
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al crear el proveedor',
                        confirmButtonColor: '#1e40af'
                    });
                }
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error al procesar la solicitud',
                confirmButtonColor: '#1e40af'
            });
        })
        .finally(() => {
            document.getElementById('loadingOverlay').classList.add('hidden');
        });
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

        if (!nameEl.value.trim()) { const e = document.getElementById('solicitud_name_produc_error'); e.textContent = 'Requerido'; e.classList.remove('hidden'); ok = false; }
        if (!catHidden.value) { const e = document.getElementById('solicitud_categoria_produc_error'); e.textContent = 'Seleccione una categoría'; e.classList.remove('hidden'); ok = false; }
        if (!provHidden.value) { const e = document.getElementById('solicitud_proveedor_id_error'); e.textContent = 'Seleccione un proveedor'; e.classList.remove('hidden'); ok = false; }

        const stockVal = parseInt(stockEl.value, 10);
        if (isNaN(stockVal) || stockVal < 0) { const e = document.getElementById('solicitud_stock_produc_error'); e.textContent = 'Stock inválido'; e.classList.remove('hidden'); ok = false; }

        const priceVal = parseFloat(priceEl.value);
        if (isNaN(priceVal) || priceVal < 0) { const e = document.getElementById('solicitud_price_produc_error'); e.textContent = 'Precio inválido'; e.classList.remove('hidden'); ok = false; }

        const ivaVal = parseFloat(ivaEl.value);
        if (isNaN(ivaVal) || ivaVal < 0) { const e = document.getElementById('solicitud_iva_error'); e.textContent = 'IVA inválido'; e.classList.remove('hidden'); ok = false; }

        if (!unitHidden.value.trim()) { const e = document.getElementById('solicitud_unit_produc_error'); e.textContent = 'Seleccione una unidad'; e.classList.remove('hidden'); ok = false; }
        if (!descEl.value.trim()) { const e = document.getElementById('solicitud_description_produc_error'); e.textContent = 'Descripción requerida'; e.classList.remove('hidden'); ok = false; }

        return ok;
    }

    function submitAddFromSolicitudForm() {
        if (validateAddFromSolicitudForm()) {
            showLoading();
            try {
                const id = document.getElementById('solicitudId').value;
                const url = '{{ url('/nuevo-producto') }}' + '/' + id + '/notify-added';
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                }).catch(() => { /* no bloquear envío si falla notificación */ });
            } catch (e) { /* ignorar */ }
            document.getElementById('addFromSolicitudForm').submit();
        }
    }

    // Actualizar select de proveedores en todos los formularios
    function updateProveedoresSelect(proveedores) {
        const selectElements = [
            document.getElementById('proveedor_id'),
            document.getElementById('solicitud_proveedor_id')
        ];

        // Actualizar los dropdowns visuales
        const proveedorDropdowns = [
            document.getElementById('proveedor_dropdown'),
            document.getElementById('solicitud_proveedor_dropdown')
        ];

        selectElements.forEach(select => {
            if (select) {
                const currentValue = select.value;

                while (select.options.length > 1) {
                    select.remove(1);
                }

                proveedores.forEach(proveedor => {
                    const option = document.createElement('option');
                    option.value = proveedor.id;
                    option.textContent = proveedor.prov_name;
                    select.appendChild(option);
                });

                if (currentValue && select.querySelector(`option[value="${currentValue}"]`)) {
                    select.value = currentValue;
                }
            }
        });

        // Actualizar los dropdowns visuales
        proveedorDropdowns.forEach(dropdown => {
            if (dropdown) {
                dropdown.innerHTML = '';
                
                proveedores.forEach(proveedor => {
                    const div = document.createElement('div');
                    div.className = 'px-3 py-2 hover:bg-indigo-100 cursor-pointer option-item' + 
                                    (dropdown.id === 'solicitud_proveedor_dropdown' ? '-solicitud' : '');
                    div.setAttribute('data-id', proveedor.id);
                    div.setAttribute('data-name', proveedor.prov_name);
                    div.textContent = proveedor.prov_name;
                    dropdown.appendChild(div);
                });
            }
        });
    }

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
        // Producto - Proveedor
        const provIn = document.getElementById('proveedor_input');
        const provDrop = document.getElementById('proveedor_dropdown');
        const provId = document.getElementById('proveedor_id');
        const provErr = document.getElementById('proveedor_id_error');
        if (provIn && provDrop) {
            const filterProv = () => {
                const q = (provIn.value || '').toLowerCase();
                provDrop.querySelectorAll('.option-item').forEach(opt => {
                    const name = (opt.getAttribute('data-name') || '').toLowerCase();
                    opt.style.display = name.includes(q) ? '' : 'none';
                });
            };
            provIn.addEventListener('focus', () => provDrop.classList.remove('hidden'));
            provIn.addEventListener('input', filterProv);
            provIn.addEventListener('blur', () => setTimeout(() => provDrop.classList.add('hidden'), 150));
            provDrop.addEventListener('click', (e) => {
                const el = e.target.closest('.option-item');
                if (!el) return;
                provIn.value = el.getAttribute('data-name') || '';
                if (provId) provId.value = el.getAttribute('data-id') || '';
                provDrop.classList.add('hidden');
                if (provErr) provErr.classList.add('hidden');
            });
        }

        // Producto - Categoría
        const catIn = document.getElementById('categoria_input');
        const catDrop = document.getElementById('categoria_dropdown');
        const catHidden = document.getElementById('categoria_produc');
        const catErr = document.getElementById('categoria_produc_error');
        if (catIn && catDrop) {
            const filterCat = () => {
                const q = (catIn.value || '').toLowerCase();
                catDrop.querySelectorAll('.option-item-cat').forEach(opt => {
                    const val = (opt.getAttribute('data-value') || '').toLowerCase();
                    opt.style.display = val.includes(q) ? '' : 'none';
                });
            };
            catIn.addEventListener('focus', () => catDrop.classList.remove('hidden'));
            catIn.addEventListener('input', filterCat);
            catIn.addEventListener('blur', () => setTimeout(() => catDrop.classList.add('hidden'), 150));
            catDrop.addEventListener('click', (e) => {
                const el = e.target.closest('.option-item-cat');
                if (!el) return;
                const val = el.getAttribute('data-value') || '';
                catIn.value = val;
                if (catHidden) catHidden.value = val;
                catDrop.classList.add('hidden');
                if (catErr) catErr.classList.add('hidden');
            });
        }

        // Producto - Unidad
        const uIn = document.getElementById('unit_input');
        const uDrop = document.getElementById('unit_dropdown');
        const uHidden = document.getElementById('unit_produc');
        const uErr = document.getElementById('unit_produc_error');
        if (uIn && uDrop) {
            uIn.addEventListener('focus', () => uDrop.classList.remove('hidden'));
            uIn.addEventListener('click', () => uDrop.classList.remove('hidden'));
            uIn.addEventListener('blur', () => setTimeout(() => uDrop.classList.add('hidden'), 150));
            uDrop.addEventListener('click', (e) => {
                const el = e.target.closest('[data-value]');
                if (!el) return;
                const val = el.getAttribute('data-value') || '';
                uIn.value = val;
                if (uHidden) uHidden.value = val;
                uDrop.classList.add('hidden');
                if (uErr) uErr.classList.add('hidden');
            });
        }

        // Solicitud - Proveedor
        const sProvIn = document.getElementById('solicitud_proveedor_input');
        const sProvDrop = document.getElementById('solicitud_proveedor_dropdown');
        const sProvId = document.getElementById('solicitud_proveedor_id');
        const sProvErr = document.getElementById('solicitud_proveedor_id_error');
        if (sProvIn && sProvDrop) {
            const filterSProv = () => {
                const q = (sProvIn.value || '').toLowerCase();
                sProvDrop.querySelectorAll('.option-item-solicitud').forEach(opt => {
                    const name = (opt.getAttribute('data-name') || '').toLowerCase();
                    opt.style.display = name.includes(q) ? '' : 'none';
                });
            };
            sProvIn.addEventListener('focus', () => sProvDrop.classList.remove('hidden'));
            sProvIn.addEventListener('input', filterSProv);
            sProvIn.addEventListener('blur', () => setTimeout(() => sProvDrop.classList.add('hidden'), 150));
            sProvDrop.addEventListener('click', (e) => {
                const el = e.target.closest('.option-item-solicitud');
                if (!el) return;
                sProvIn.value = el.getAttribute('data-name') || '';
                if (sProvId) sProvId.value = el.getAttribute('data-id') || '';
                sProvDrop.classList.add('hidden');
                if (sProvErr) sProvErr.classList.add('hidden');
            });
        }

        // Solicitud - Categoría
        const sCatIn = document.getElementById('solicitud_categoria_input');
        const sCatDrop = document.getElementById('solicitud_categoria_dropdown');
        const sCatHidden = document.getElementById('solicitud_categoria_produc');
        const sCatErr = document.getElementById('solicitud_categoria_produc_error');
        if (sCatIn && sCatDrop) {
            const filterSCat = () => {
                const q = (sCatIn.value || '').toLowerCase();
                sCatDrop.querySelectorAll('.option-item-cat-solicitud').forEach(opt => {
                    const val = (opt.getAttribute('data-value') || '').toLowerCase();
                    opt.style.display = val.includes(q) ? '' : 'none';
                });
            };
            sCatIn.addEventListener('focus', () => sCatDrop.classList.remove('hidden'));
            sCatIn.addEventListener('input', filterSCat);
            sCatIn.addEventListener('blur', () => setTimeout(() => sCatDrop.classList.add('hidden'), 150));
            sCatDrop.addEventListener('click', (e) => {
                const el = e.target.closest('.option-item-cat-solicitud');
                if (!el) return;
                const val = el.getAttribute('data-value') || '';
                sCatIn.value = val;
                if (sCatHidden) sCatHidden.value = val;
                sCatDrop.classList.add('hidden');
                if (sCatErr) sCatErr.classList.add('hidden');
            });
        }

        // Solicitud - Unidad
        const suIn = document.getElementById('solicitud_unit_input');
        const suDrop = document.getElementById('solicitud_unit_dropdown');
        const suHidden = document.getElementById('solicitud_unit_produc');
        const suErr = document.getElementById('solicitud_unit_produc_error');
        if (suIn && suDrop) {
            suIn.addEventListener('focus', () => suDrop.classList.remove('hidden'));
            suIn.addEventListener('click', () => suDrop.classList.remove('hidden'));
            suIn.addEventListener('blur', () => setTimeout(() => suDrop.classList.add('hidden'), 150));
            suDrop.addEventListener('click', (e) => {
                const el = e.target.closest('[data-value]');
                if (!el) return;
                const val = el.getAttribute('data-value') || '';
                suIn.value = val;
                if (suHidden) suHidden.value = val;
                suDrop.classList.add('hidden');
                if (suErr) suErr.classList.add('hidden');
            });
        }
    });
    
    // Cuando se abre el modal de añadir desde solicitud, asegurarse de que el campo IVA esté disponible
    document.addEventListener('DOMContentLoaded', function(){
        const solIva = document.getElementById('solicitud_iva');
        if (solIva) solIva.value = solIva.value || 0;
    });
        </script>

        <style>
            .hidden {
                display: none;
            }

            .loader {
                border-top-color: #1e40af;
                -webkit-animation: spinner 1.5s linear infinite;
                animation: spinner 1.5s linear infinite;
            }

            @-webkit-keyframes spinner {
                0% {
                    -webkit-transform: rotate(0deg);
                }

                100% {
                    -webkit-transform: rotate(360deg);
                }
            }

            @keyframes spinner {
                0% {
                    transform: rotate(0deg);
                }

                100% {
                    transform: rotate(360deg);
                }
            }

            /* Asegurar que el modal de proveedor tenga mayor z-index */
            #proveedorModal {
                z-index: 60;
            }

            #productModal,
            #addFromSolicitudModal,
            #solicitudModal {
                z-index: 50;
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