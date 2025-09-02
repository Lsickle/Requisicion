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
                <h2 class="text-xl font-semibold">Productos Registrados</h2>

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
                            <th class="px-4 py-2 text-left">Precio</th>
                            <th class="px-4 py-2 text-left">Estado</th>
                            <th class="px-4 py-2 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($productos as $producto)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $producto->name_produc }}</td>
                            <td class="px-4 py-2">{{ $producto->categoria_produc }}</td>
                            <td class="px-4 py-2">{{ $producto->proveedor->prov_name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $producto->stock_produc }}</td>
                            <td class="px-4 py-2">${{ number_format($producto->price_produc, 2) }}</td>
                            <td class="px-4 py-2">
                                @if($producto->trashed())
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Eliminado</span>
                                @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Activo</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-center">
                                <div class="flex justify-center space-x-2">
                                    @if($producto->trashed())
                                    <form action="{{ route('productos.restore', $producto->id) }}" method="POST"
                                        class="inline" onsubmit="showLoading(event)">
                                        @csrf
                                        @method('POST')
                                        <button type="submit" class="text-green-600 hover:text-green-800"
                                            title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('productos.forceDelete', $producto->id) }}" method="POST"
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
                                        onclick="openEditModal({{ $producto->id }}, '{{ $producto->name_produc }}', '{{ $producto->categoria_produc }}', {{ $producto->proveedor_id }}, {{ $producto->stock_produc }}, {{ $producto->price_produc }}, '{{ $producto->unit_produc }}', `{{ $producto->description_produc }}`)"
                                        class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST"
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
        </div>

        <!-- Sección de Productos Solicitados (oculta inicialmente) -->
        <div id="solicitudes-section" class="p-4 overflow-x-auto hidden">
            @if($solicitudes->isEmpty())
            <p class="text-gray-500">No hay productos solicitados.</p>
            @else
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Solicitado por</th>
                        <th class="px-4 py-2 text-left">Producto</th>
                        <th class="px-4 py-2 text-left">Descripción</th>
                        <th class="px-4 py-2 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($solicitudes as $solicitud)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ $solicitud->name_user }}</td>
                        <td class="px-4 py-2">{{ $solicitud->nombre }}</td>
                        <td class="px-4 py-2">{{ $solicitud->descripcion }}</td>
                        <td class="px-4 py-2 text-center">
                            <div class="flex justify-center space-x-2">
                                @if($solicitud->trashed())
                                <form action="{{ route('nuevo_producto.restore', $solicitud->id) }}" method="POST"
                                    onsubmit="showLoading(event)">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" class="text-green-600 hover:text-green-800" title="Restaurar">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                </form>
                                <form action="{{ route('nuevo_producto.forceDelete', $solicitud->id) }}" method="POST"
                                    onsubmit="return confirmDelete(event)">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800"
                                        title="Eliminar Permanentemente">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <!-- Botón Añadir producto -->
                                <button type="button"
                                    class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                                    title="Añadir producto">
                                    <i class="fas fa-plus"></i> Añadir
                                </button>

                                <!-- Botón Rechazar solicitud -->
                                <form action="{{ route('nuevo_producto.destroy', $solicitud->id) }}" method="POST"
                                    onsubmit="return confirmDelete(event)" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"
                                        title="Rechazar solicitud">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                </form>

                                <!-- Botón Ver solicitud -->
                                <button type="button"
                                    onclick="openSolicitudModal('{{ $solicitud->nombre }}', `{{ $solicitud->descripcion }}`, '{{ $solicitud->name_user }}')"
                                    class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                                    title="Ver solicitud">
                                    <i class="fas fa-eye"></i> Ver
                                </button>

                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
                    <form id="productForm" action="{{ route('productos.store') }}" method="POST"
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <select id="categoria_produc" name="categoria_produc"
                                    class="w-full px-3 py-2 border rounded-md" required>
                                    <option value="">Seleccionar categoría</option>
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
                                <span id="categoria_produc_error" class="text-red-500 text-xs hidden"></span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                <div class="relative">
                                    <div class="flex">
                                        <input type="text" id="proveedor_search" placeholder="Buscar proveedor..."
                                            class="w-full px-3 py-2 border rounded-md rounded-r-none"
                                            oninput="filterProveedores()">
                                        <button type="button" onclick="openModal('proveedor')"
                                            class="bg-blue-500 text-white px-3 rounded-r-md">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <select id="proveedor_id" name="proveedor_id"
                                        class="w-full px-3 py-2 border rounded-md mt-1" required>
                                        <option value="">Seleccionar proveedor</option>
                                        @foreach($proveedores as $proveedor)
                                        <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                                        @endforeach
                                    </select>
                                    <div id="proveedor_results"
                                        class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto hidden">
                                    </div>
                                    <span id="proveedor_id_error" class="text-red-500 text-xs hidden"></span>
                                </div>
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
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida</label>
                                <input type="text" id="unit_produc" name="unit_produc"
                                    class="w-full px-3 py-2 border rounded-md" required>
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

        <!-- Modal para crear proveedor -->
        <div id="proveedorModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Nuevo Proveedor</h2>
                    <button onclick="closeModal('proveedor')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="proveedorForm" action="{{ route('proveedores.store') }}" method="POST"
                        onsubmit="return validateProveedorForm()">
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
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea id="prov_descrip" name="prov_descrip" rows="3"
                                class="w-full px-3 py-2 border rounded-md"></textarea>
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
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
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
                    <button type="button" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                        title="Añadir producto">
                        <i class="fas fa-plus"></i> Añadir
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



            // Funciones para controlar modales
            function openModal(type) {
                if (type === 'producto') {
                    document.getElementById('productModal').classList.remove('hidden');
                    document.getElementById('modalTitle').textContent = 'Nuevo Producto';
                    document.getElementById('productId').value = '';
                    document.getElementById('formMethod').value = 'POST';
                    document.getElementById('productForm').action = "{{ route('productos.store') }}";
                    document.getElementById('productForm').reset();
                    // Limpiar búsqueda de proveedores
                    document.getElementById('proveedor_search').value = '';
                    document.getElementById('proveedor_results').classList.add('hidden');
                    // Limpiar mensajes de error
                    clearErrorMessages();
                } else if (type === 'proveedor') {
                    document.getElementById('proveedorModal').classList.remove('hidden');
                    document.getElementById('proveedorForm').reset();
                    // Limpiar mensajes de error
                    clearProveedorErrorMessages();
                }
            }

        function closeModal(type) {
            if (type === 'producto') {
                document.getElementById('productModal').classList.add('hidden');
            } else if (type === 'proveedor') {
                document.getElementById('proveedorModal').classList.add('hidden');
            }
        }

        function openEditModal(id, nombre, categoria, proveedorId, stock, precio, unidad, descripcion) {
            document.getElementById('modalTitle').textContent = 'Editar Producto';
            document.getElementById('productId').value = id;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('productForm').action = "{{ url('productos') }}/" + id;
            
            document.getElementById('name_produc').value = nombre;
            document.getElementById('categoria_produc').value = categoria;
            document.getElementById('proveedor_id').value = proveedorId;
            document.getElementById('stock_produc').value = stock;
            document.getElementById('price_produc').value = precio;
            document.getElementById('unit_produc').value = unidad;
            document.getElementById('description_produc').value = descripcion;
            
            // Para el proveedor, mostrar el nombre en el campo de búsqueda
            const proveedorSelect = document.getElementById('proveedor_id');
            const selectedOption = proveedorSelect.options[proveedorSelect.selectedIndex];
            document.getElementById('proveedor_search').value = selectedOption.text;
            
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
            } else {
                productosSection.classList.add('hidden');
                solicitudesSection.classList.remove('hidden');

                // Activar tab solicitudes
                tabSolicitudes.classList.add('bg-white', 'shadow', 'text-gray-700');
                tabSolicitudes.classList.remove('text-gray-600');

                // Desactivar tab productos
                tabProductos.classList.remove('bg-white', 'shadow', 'text-gray-700');
                tabProductos.classList.add('text-gray-600');
            }
        }


        // Función para filtrar proveedores
        function filterProveedores() {
            const searchTerm = document.getElementById('proveedor_search').value.toLowerCase();
            const proveedorSelect = document.getElementById('proveedor_id');
            const resultsContainer = document.getElementById('proveedor_results');
            
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
                        document.getElementById('proveedor_search').value = option.text;
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
            const categoria_produc = document.getElementById('categoria_produc');
            const proveedor_id = document.getElementById('proveedor_id');
            const stock_produc = document.getElementById('stock_produc');
            const price_produc = document.getElementById('price_produc');
            const unit_produc = document.getElementById('unit_produc');
            
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
            const searchInput = document.getElementById("searchInput").value.toLowerCase();
            const filterCategoria = document.getElementById("filterCategoria").value.toLowerCase();
            const filterEstado = document.getElementById("filterEstado").value.toLowerCase();
            const table = document.getElementById("productosTable");
            const rows = table.getElementsByTagName("tr");

            for (let i = 1; i < rows.length; i++) { // empieza en 1 para saltar el header
                let cells = rows[i].getElementsByTagName("td");
                if (!cells.length) continue;

                const nombre = cells[1].textContent.toLowerCase();
                const categoria = cells[2].textContent.toLowerCase();
                const estado = cells[6].textContent.toLowerCase();

                const matchesSearch = nombre.includes(searchInput) || categoria.includes(searchInput);
                const matchesCategoria = filterCategoria === "" || categoria === filterCategoria;
                const matchesEstado = filterEstado === "" || estado.includes(filterEstado);

                if (matchesSearch && matchesCategoria && matchesEstado) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
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
        </style>
</body>

</html>