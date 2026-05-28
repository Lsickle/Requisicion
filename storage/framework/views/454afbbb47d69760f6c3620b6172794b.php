<?php $__env->startSection('title', 'Inventario - Bodega'); ?>

<?php
$permissions = array_map(fn($p) => mb_strtolower($p, 'UTF-8'), Session::get('user_permissions', []));
$roles = array_map(fn($r) => mb_strtolower($r, 'UTF-8'), Session::get('user_roles', []));
$hasPermission = fn($perm) => in_array(mb_strtolower($perm, 'UTF-8'), $permissions, true);
$isVerTodas = count(array_filter($roles, fn($r) => in_array($r, ['compras', 'admin'], true))) > 0;

$todosLosSubcentros = $isVerTodas 
    ? \App\Models\Subcentro::with('centro')->orderBy('name_subcentro')->get() 
    : (isset($todosLosSubcentros) ? $todosLosSubcentros : collect());
$tieneMultipleSubcentros = isset($todosLosSubcentros) && count($todosLosSubcentros) > 0;
?>

<style>
.product-row:hover { background-color: #f9fafb; }
.modal-overlay { background-color: rgba(0,0,0,0.4); }
.subcentro-card { cursor: pointer; transition: all .2s; }
.subcentro-card:hover { transform: translateY(-2px); }
</style>

<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal2880b66d47486b4bfeaf519598a469d6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2880b66d47486b4bfeaf519598a469d6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $attributes = $__attributesOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__attributesOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2880b66d47486b4bfeaf519598a469d6)): ?>
<?php $component = $__componentOriginal2880b66d47486b4bfeaf519598a469d6; ?>
<?php unset($__componentOriginal2880b66d47486b4bfeaf519598a469d6); ?>
<?php endif; ?>

<div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-warehouse text-blue-600"></i>
                Inventario de Bodega
            </h1>
            <?php if(isset($subcentroActual) && $subcentroActual): ?>
                <p class="text-emerald-600 font-semibold mt-1"><?php echo e($subcentroActual->name_subcentro); ?></p>
                <p class="text-gray-500 text-sm"><?php echo e($subcentroActual->centro->name_centro ?? ''); ?></p>
            <?php elseif(isset($bodegaActual) && $bodegaActual): ?>
                <p class="text-emerald-600 font-semibold mt-1"><?php echo e($bodegaActual->name_centro); ?></p>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-3">
            <?php if($subcentroActual): ?>
            <a href="<?php echo e(route('inventario.exportar', ['subcentro' => $subcentroActual->id])); ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                <i class="fas fa-file-excel mr-1"></i> Exportar Excel
            </a>
            <?php elseif($bodegaActual): ?>
            <a href="<?php echo e(route('inventario.exportar', ['bodega' => $bodegaActual->id])); ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                <i class="fas fa-file-excel mr-1"></i> Exportar Excel
            </a>
            <?php endif; ?>
            <a href="<?php echo e(route('inventario.historial')); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">
                <i class="fas fa-history mr-1"></i> Historial
            </a>
        </div>
    </div>

    <?php if($tieneMultipleSubcentros || $isVerTodas): ?>
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex items-center gap-4">
            <label class="font-medium text-gray-600">Seleccionar Operación:</label>
            <select onchange="if(this.value) window.location.href='<?php echo e(route('inventario.index')); ?>?subcentro='+this.value" class="flex-1 max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:border-blue-500 focus:ring focus:ring-blue-300/40">
                <?php if(!$subcentroSeleccionado && !$isVerTodas): ?>
                <option value="">-- Selecciona una operación --</option>
                <?php endif; ?>
                <?php $__currentLoopData = $todosLosSubcentros; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($sc['id']); ?>" <?php if($subcentroSeleccionado == $sc['id']): ?> selected <?php endif; ?>>
                    <?php echo e($sc['nombre']); ?> (<?php echo e($sc['bodega_nombre'] ?? 'Sin bodega'); ?>)
                </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$isVerTodas && !$subcentroActual && !$bodegaActual): ?>
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center text-yellow-700 mb-6">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No tienes ningún subcentro asignado. Contacta al administrador.
    </div>
    <?php else: ?>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2 w-full max-w-md">
                <i class="fas fa-search text-gray-400"></i>
                <input type="text" id="search-input" placeholder="Buscar producto por nombre o SKU..." class="w-full border rounded-lg px-3 py-2">
            </div>
            <?php if($puedeModificar): ?>
            <div class="flex gap-2">
                <button onclick="abrirModal('entrada')" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-plus mr-1"></i> Agregar
                </button>
                <button onclick="abrirModal('salida')" class="bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg">
                    <i class="fas fa-minus mr-1"></i> Retirar
                </button>
            </div>
            <?php endif; ?>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-center">SKU</th>
                        <th class="px-4 py-3 text-center">Unidad</th>
                        <th class="px-4 py-3 text-center">Stock</th>
                        <?php if($puedeModificar): ?>
                        <th class="px-4 py-3 text-center">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody id="inventario-table-body">
                    <?php $__empty_1 = true; $__currentLoopData = $inventario; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="border-b product-row" data-name="<?php echo e(strtolower($item->producto->name_produc ?? '')); ?>" data-sku="<?php echo e(strtolower($item->producto->sku ?? '')); ?>">
                        <td class="px-4 py-3 font-medium text-gray-800"><?php echo e($item->producto->name_produc ?? 'N/A'); ?></td>
                        <td class="px-4 py-3 text-center text-gray-500"><?php echo e($item->producto->sku ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center text-gray-500"><?php echo e($item->producto->unit_produc ?? '-'); ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-bold <?php echo e($item->cantidad > 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'); ?>">
                                <?php echo e($item->cantidad); ?>

                            </span>
                        </td>
                        <?php if($puedeModificar): ?>
                        <td class="px-4 py-3 text-center">
                            <button type="button" onclick="abrirModalEditar(<?php echo e($item->id); ?>, <?php echo e($item->cantidad); ?>, '<?php echo e(addslashes($item->producto->name_produc ?? '')); ?>')" class="text-blue-600 hover:text-blue-800 mr-2" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="eliminarProducto(<?php echo e($item->id); ?>, '<?php echo e(addslashes($item->producto->name_produc ?? '')); ?>')" class="text-red-600 hover:text-red-800" title="Eliminar del inventario">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e($puedeModificar ? 5 : 4); ?>" class="px-4 py-8 text-center text-gray-400">
                            <i class="fas fa-box-open text-3xl mb-2 block"></i>
                            No hay productos en inventario.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if($puedeModificar): ?>
<!-- Modal paraEditar -->
<div id="modal-editar" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-blue-600 text-white px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-semibold">Editar Stock</h3>
            <button onclick="cerrarModalEditar()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="form-editar" class="p-6 space-y-4">
            <input type="hidden" id="editar-inventario-id" name="inventario_id" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto</label>
                <p id="editar-producto-nombre" class="text-gray-800 font-medium"></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva Cantidad *</label>
                <input type="number" id="editar-cantidad" name="cantidad" min="0" required class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentario (opcional)</label>
                <textarea id="editar-comentario" name="comentario" rows="2" maxlength="500" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModalEditar()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">Cancelar</button>
                <button type="submit" id="btn-editar" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg">
                    <i class="fas fa-spinner fa-spin hidden" id="btn-editar-loading"></i>
                    <span id="btn-editar-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="modal-movimiento" class="fixed inset-0 z-50 hidden modal-overlay flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div id="modal-header" class="px-6 py-4 flex items-center justify-between">
            <h3 id="modal-titulo" class="text-lg font-semibold"></h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="form-movimiento" class="p-6 space-y-4">
            <input type="hidden" id="movimiento-tipo" name="tipo" value="">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Producto *</label>
                <input type="text" id="producto-buscar" placeholder="Buscar producto..." class="w-full border rounded-lg px-3 py-2 mb-1" onkeyup="filtrarProducto(this.value)">
                <select id="producto-select" class="w-full border rounded-lg px-3 py-2 bg-white" size="5" required>
                    <option value="">-- Seleccionar producto --</option>
                    <?php $__currentLoopData = $productosMap ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($prod['id']); ?>" data-nombre="<?php echo e(strtolower($prod['name_produc'])); ?>" data-sku="<?php echo e(strtolower($prod['sku'])); ?>" data-cantidad="<?php echo e($prod['cantidad']); ?>"><?php echo e($prod['name_produc']); ?> (<?php echo e($prod['sku']); ?>) - <?php echo e($prod['unit_produc']); ?> [Stock: <?php echo e($prod['cantidad']); ?>]</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">Escribe para buscar y selecciona de la lista</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                <input type="number" id="cantidad" name="cantidad" min="1" required class="w-full border rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Comentario (opcional)</label>
                <textarea id="comentario" name="comentario" rows="2" maxlength="500" class="w-full border rounded-lg px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="cerrarModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-lg">Cancelar</button>
                <button type="submit" id="btn-submit" class="text-white font-medium py-2 px-6 rounded-lg">
                    <i class="fas fa-spinner fa-spin hidden" id="btn-loading"></i>
                    <span id="btn-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
var movimientoTipo = document.getElementById('movimiento-tipo');
var formMovimiento = document.getElementById('form-movimiento');
var productoSelect = document.getElementById('producto-select');
var cantidadInput = document.getElementById('cantidad');
var modalMovimiento = document.getElementById('modal-movimiento');

function filtrarProducto(texto) {
    var textoLower = texto.toLowerCase();
    var select = document.getElementById('producto-select');
    var options = select.options;
    for (var i = 1; i < options.length; i++) {
        var option = options[i];
        var nombre = option.getAttribute('data-nombre') || '';
        var sku = option.getAttribute('data-sku') || '';
        if (textoLower === '' || nombre.indexOf(textoLower) !== -1 || sku.indexOf(textoLower) !== -1) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    }
}

function abrirModal(tipo) {
    movimientoTipo.value = tipo;
    var isEntrada = tipo === 'entrada';
    document.getElementById('modal-titulo').textContent = isEntrada ? 'Agregar al Inventario' : 'Retirar del Inventario';
    var btnSubmit = document.getElementById('btn-submit');
    if (isEntrada) {
        document.getElementById('modal-header').className = 'bg-green-600 text-white px-6 py-4 flex items-center justify-between';
        btnSubmit.className = 'bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg';
    } else {
        document.getElementById('modal-header').className = 'bg-red-600 text-white px-6 py-4 flex items-center justify-between';
        btnSubmit.className = 'bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-6 rounded-lg';
    }
    
    // Filtrar productos según el tipo de movimiento
    var select = document.getElementById('producto-select');
    var options = select.options;
    for (var i = 1; i < options.length; i++) {
        var option = options[i];
        var cantidad = parseInt(option.getAttribute('data-cantidad') || '0', 10);
        if (isEntrada) {
            // En entrada, mostrar todos los productos
            option.style.display = '';
        } else {
            // En salida, solo mostrar los que tienen stock > 0
            option.style.display = cantidad > 0 ? '' : 'none';
        }
    }
    
    // Seleccionar primera opción visible
    if (!isEntrada) {
        for (var i = 1; i < options.length; i++) {
            if (options[i].style.display !== 'none') {
                select.selectedIndex = i;
                break;
            }
        }
    }
    
    formMovimiento.reset();
    modalMovimiento.classList.remove('hidden');
}

function cerrarModal() {
    modalMovimiento.classList.add('hidden');
    formMovimiento.reset();
}

if (formMovimiento) {
    formMovimiento.addEventListener('submit', async function(e) {
        e.preventDefault();
        document.getElementById('btn-loading').classList.remove('hidden');
        document.getElementById('btn-text').classList.add('opacity-50');
        document.getElementById('btn-submit').disabled = true;

        var error = false;
        if (!productoSelect.value) {
            alert('Selecciona un producto');
            error = true;
        }
        if (!cantidadInput.value || parseInt(cantidadInput.value) < 1) {
            alert('Ingresa una cantidad valida');
            error = true;
        }
        if (error) {
            document.getElementById('btn-loading').classList.add('hidden');
            document.getElementById('btn-text').classList.remove('opacity-50');
            document.getElementById('btn-submit').disabled = false;
            return;
        }

        var tipoMov = movimientoTipo.value;
        var url = tipoMov === 'entrada' ? '/inventario/entrada' : '/inventario/salida';
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        try {
            var res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productoSelect.value,
                    cantidad: cantidadInput.value,
                    comentario: document.getElementById('comentario').value
                })
            });
            console.log('Response:', res.status);
            
            var data = await res.json();
            console.log('Data:', data);
            
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Correcto', text: data.message }).then(function() {
                    cerrarModal();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        } finally {
            document.getElementById('btn-loading').classList.add('hidden');
            document.getElementById('btn-text').classList.remove('opacity-50');
            document.getElementById('btn-submit').disabled = false;
        }
    });
}

var searchInput = document.getElementById('search-input');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.product-row').forEach(function(row) {
            var match = q === '' || row.dataset.name.includes(q) || row.dataset.sku.includes(q);
            row.style.display = match ? '' : 'none';
        });
    });
}

// Función para editar stock
var modalEditar = document.getElementById('modal-editar');
var formEditar = document.getElementById('form-editar');

function abrirModalEditar(inventarioId, cantidad, productoNombre) {
    document.getElementById('editar-inventario-id').value = inventarioId;
    document.getElementById('editar-cantidad').value = cantidad;
    document.getElementById('editar-producto-nombre').textContent = productoNombre;
    document.getElementById('editar-comentario').value = '';
    modalEditar.classList.remove('hidden');
}

function cerrarModalEditar() {
    modalEditar.classList.add('hidden');
    formEditar.reset();
}

if (formEditar) {
    formEditar.addEventListener('submit', async function(e) {
        e.preventDefault();
        document.getElementById('btn-editar-loading').classList.remove('hidden');
        document.getElementById('btn-editar-text').classList.add('opacity-50');
        document.getElementById('btn-editar').disabled = true;

        var url = '/inventario/editar';
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        try {
            var res = await fetch(url, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: { 
                    'Content-Type': 'application/json', 
                    'X-CSRF-TOKEN': csrfToken, 
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    inventario_id: document.getElementById('editar-inventario-id').value,
                    cantidad: document.getElementById('editar-cantidad').value,
                    comentario: document.getElementById('editar-comentario').value
                })
            });
            var data = await res.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Correcto', text: data.message }).then(function() {
                    cerrarModalEditar();
                });
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message });
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión' });
        } finally {
            document.getElementById('btn-editar-loading').classList.add('hidden');
            document.getElementById('btn-editar-text').classList.remove('opacity-50');
            document.getElementById('btn-editar').disabled = false;
        }
    });
}

// Función para eliminar producto
async function eliminarProducto(inventarioId, productoNombre) {
    var result = await Swal.fire({
        title: '¿Eliminar?',
        text: '¿Seguro que quieres eliminar "' + productoNombre + '" del inventario?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    console.log('Eliminando inventario ID:', inventarioId);
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    try {
        var res = await fetch('/inventario/eliminar', {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: { 
                'Content-Type': 'application/json', 
                'X-CSRF-TOKEN': csrfToken, 
                'Accept': 'application/json'
            },
            body: JSON.stringify({ inventario_id: inventarioId })
        });
        console.log('Response status:', res.status);
        
        var data = await res.json();
        console.log('Data:', data);
        
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Eliminado', text: data.message }).then(function() {
                window.location.reload();
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión: ' + err.message });
    }
}
</script>
<?php endif; ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/inventario/index.blade.php ENDPATH**/ ?>