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
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto p-6 mt-10 bg-white rounded-lg shadow-md">
        <h1 class="text-2xl font-bold mb-6">Gestor de Productos</h1>

        <!-- Botones de acción principales -->
        <div class="flex justify-between mb-6">
            <div class="flex space-x-2">
                <button onclick="toggleSection('productos')"
                    class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Productos Registrados
                </button>
                <button onclick="toggleSection('solicitudes')"
                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                    Productos Solicitados
                </button>
            </div>
            <button onclick="openModal('producto')"
                class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                + Nuevo Producto
            </button>
        </div>

        <!-- Sección de Productos Registrados -->
        <div id="productos-section" class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b">
                <h2 class="text-xl font-semibold">Productos Registrados</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Nombre</th>
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
                            <td class="px-4 py-2">{{ $producto->id }}</td>
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
                                    <form action="{{ route('productos.restore', $producto->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-green-600 hover:text-green-800" title="Restaurar">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('productos.forceDelete', $producto->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('¿Eliminar permanentemente este producto?')"
                                            title="Eliminar Permanentemente">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button onclick="openEditModal({{ $producto->id }}, '{{ $producto->name_produc }}', '{{ $producto->categoria_produc }}', {{ $producto->proveedor_id }}, {{ $producto->stock_produc }}, {{ $producto->price_produc }}, '{{ $producto->unit_produc }}', `{{ $producto->description_produc }}`)" 
                                        class="text-blue-600 hover:text-blue-800" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('productos.destroy', $producto->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800"
                                            onclick="return confirm('¿Eliminar este producto?')" title="Eliminar">
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

        <!-- Modal para crear/editar producto -->
        <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold" id="modalTitle">Nuevo Producto</h2>
                    <button onclick="closeModal('producto')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="productForm" action="{{ route('productos.store') }}" method="POST">
                        @csrf
                        <input type="hidden" id="formMethod" name="_method" value="POST">
                        <input type="hidden" id="productId" name="id" value="">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Producto</label>
                                <input type="text" id="name_produc" name="name_produc" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <input type="text" id="categoria_produc" name="categoria_produc" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Proveedor</label>
                                <div class="flex">
                                    <select id="proveedor_id" name="proveedor_id" class="w-full px-3 py-2 border rounded-md rounded-r-none" required>
                                        <option value="">Seleccionar proveedor</option>
                                        @foreach($proveedores as $proveedor)
                                            <option value="{{ $proveedor->id }}">{{ $proveedor->prov_name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" onclick="openModal('proveedor')" class="bg-blue-500 text-white px-3 rounded-r-md">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Stock</label>
                                <input type="number" id="stock_produc" name="stock_produc" min="0" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Precio</label>
                                <input type="number" id="price_produc" name="price_produc" step="0.01" min="0" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Unidad de Medida</label>
                                <input type="text" id="unit_produc" name="unit_produc" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea id="description_produc" name="description_produc" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>
                    </form>
                </div>
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button onclick="closeModal('producto')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="document.getElementById('productForm').submit()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal para crear proveedor -->
        <div id="proveedorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-3/4 lg:w-1/2 max-h-screen overflow-y-auto">
                <div class="p-4 border-b flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Nuevo Proveedor</h2>
                    <button onclick="closeModal('proveedor')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <form id="proveedorForm" action="{{ route('proveedores.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Proveedor</label>
                                <input type="text" id="prov_name" name="prov_name" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">NIT</label>
                                <input type="text" id="prov_nit" name="prov_nit" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Contacto</label>
                                <input type="text" id="prov_name_c" name="prov_name_c" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                <input type="text" id="prov_phone" name="prov_phone" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                <input type="text" id="prov_adress" name="prov_adress" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                                <input type="text" id="prov_city" name="prov_city" class="w-full px-3 py-2 border rounded-md" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea id="prov_descrip" name="prov_descrip" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>
                    </form>
                </div>
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button onclick="closeModal('proveedor')" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                        Cancelar
                    </button>
                    <button onclick="document.getElementById('proveedorForm').submit()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Funciones para controlar modales
        function openModal(type) {
            if (type === 'producto') {
                document.getElementById('productModal').classList.remove('hidden');
                document.getElementById('modalTitle').textContent = 'Nuevo Producto';
                document.getElementById('productId').value = '';
                document.getElementById('formMethod').value = 'POST';
                document.getElementById('productForm').action = "{{ route('productos.store') }}";
                document.getElementById('productForm').reset();
            } else if (type === 'proveedor') {
                document.getElementById('proveedorModal').classList.remove('hidden');
                document.getElementById('proveedorForm').reset();
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
            
            document.getElementById('productModal').classList.remove('hidden');
        }

        function toggleSection(section) {
            if (section === 'productos') {
                document.getElementById('productos-section').classList.remove('hidden');
                document.getElementById('solicitudes-section').classList.add('hidden');
            } else {
                document.getElementById('productos-section').classList.add('hidden');
                document.getElementById('solicitudes-section').classList.remove('hidden');
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
    </script>

    <style>
        .hidden {
            display: none;
        }
    </style>
</body>
</html>