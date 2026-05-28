<?php $__env->startSection('title','Asignar Subcentros a Usuarios'); ?>

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
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-4xl mx-auto bg-white/95 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                    <i class="fas fa-user-cog"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-extrabold">Asignar Subcentros a Usuarios</h2>
                    <p class="text-sm text-gray-500">Seleccione un usuario desde la lista (traída desde la API) y asigne los subcentros disponibles.</p>
                </div>
            </div>
        </div>

        <?php if(session('success')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: '<?php echo e(session('success')); ?>',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        <?php endif; ?>
        <?php if(session('error')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo e(session('error')); ?>',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        <?php endif; ?>

        <!-- Buscador -->
        <div class="mb-4 flex items-center gap-2">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 w-9"><i class="fas fa-search"></i></span>
                <input type="text" id="usersSearch" placeholder="Buscar por nombre o email..." class="pl-12 pr-3 py-2.5 border border-indigo-300 rounded-xl w-full shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300/40" />
            </div>
            <button id="usersClearSearch" class="px-3 py-2 border rounded-xl bg-white hover:bg-gray-50 shadow-sm">Limpiar</button>
        </div>

        <div id="usersList" class="space-y-2">
            <p class="text-gray-500">Cargando usuarios...</p>
        </div>
        <div id="usersPaginationContainer" class="mt-3"></div>

        <!-- Modal asignación -->
        <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 p-6 w-11/12 md:w-3/4 max-h-[90vh] overflow-auto thin-scrollbar">
                <div class="flex items-start justify-between mb-4 sticky top-0 bg-white pt-2 pb-3 z-10 border-b">
                    <div>
                        <h3 id="assignUserTitle" class="text-lg font-semibold">Asignar subcentros</h3>
                        <div id="assignUserInfo" class="text-sm text-gray-600">&nbsp;</div>
                    </div>
                    <button type="button" class="text-gray-500" onclick="closeAssignModal()">Cerrar ✕</button>
                </div>

                <form id="assignForm" method="POST" action="<?php echo e(route('centros.user_subcentros.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="email_user" id="assign_email_user">
                    <!-- JSON con subcentros asignados (se llenará antes del submit) -->
                    <input type="hidden" name="subcentros_json" id="subcentros_json" value="">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Select de subcentros y botón Agregar -->
                        <div class="border rounded-xl p-3 max-h-64 overflow-auto bg-gray-50 thin-scrollbar">
                            <h4 class="text-sm font-medium mb-2">Subcentros disponibles</h4>
                            <div class="flex gap-2 mb-3">
                                <!-- Selector de Centros -->
                                <?php
                                    // Construir lista de centros únicos (compatible con PHP 7.x)
                                    $centrosUnicos = collect();
                                    if (!empty($subcentros)) {
                                        foreach ($subcentros as $s) {
                                            if (isset($s->centro) && $s->centro) {
                                                $centrosUnicos->push($s->centro);
                                            }
                                        }
                                    }
                                    $centrosUnicos = $centrosUnicos->unique('id')->values();
                                ?>
                                <select id="centroSelect" class="flex-1 px-2 py-2 border rounded">
                                    <option value="">-- Selecciona un centro --</option>
                                    <?php $__currentLoopData = $centrosUnicos; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($c->id); ?>"><?php echo e($c->name_centro); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <button type="button" id="loadSubcentrosBtn" class="px-3 py-2 bg-indigo-600 text-white rounded shadow-sm hover:bg-indigo-700">Cargar</button>

                            </div>
                            <div class="flex gap-2 mb-3">
                                 <select id="subcentroSelect" class="flex-1 px-2 py-2 border rounded">
                                     <option value="">-- Selecciona un subcentro --</option>
                                     <!-- Se llenará dinámicamente con el centro seleccionado -->
                                 </select>
                                 <button type="button" id="addSubcentroBtn" class="px-3 py-2 bg-green-600 text-white rounded shadow-sm hover:bg-green-700">Agregar</button>
                             </div>
                             <div class="text-xs text-gray-500">Selecciona y pulsa Agregar para añadir a la lista de asignados.</div>
                         </div>

                        <!-- Tabla dinámica de asignados -->
                        <div class="border rounded-xl p-3 max-h-64 overflow-auto thin-scrollbar">
                            <h4 class="text-sm font-medium mb-2">Subcentros asignados</h4>
                            <table class="min-w-full text-sm table-auto" id="assignedTable">
                                <thead class="bg-indigo-50 sticky top-0 z-10">
                                    <tr class="text-left text-xs text-gray-600">
                                        <th class="px-2 py-1">Subcentro</th>
                                        <th class="px-2 py-1">Centro</th>
                                        <th class="px-2 py-1">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedTbody">
                                    <tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="px-3 py-2 bg-gray-300 rounded" onclick="closeAssignModal()">Cancelar</button>
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<?php
    // Preparar arrays simples para JSON (compatible con PHP 7.x)
    $allSubcentros = [];
    if (!empty($subcentros)) {
        foreach ($subcentros as $s) {
            $centroName = '';
            $centroId = null;
            if (isset($s->centro) && $s->centro) {
                $centroName = isset($s->centro->name_centro) ? $s->centro->name_centro : '';
                $centroId = isset($s->centro->id) ? $s->centro->id : null;
            }
            // Asegurar que solo entren verdaderos subcentros con nombre
            if (!empty($s->name_subcentro)) {
                $allSubcentros[] = ['id' => $s->id, 'name' => $s->name_subcentro, 'centro' => $centroName, 'centro_id' => $centroId];
            }
        }
    }

    $allCentros = [];
    if (!empty($centrosUnicos)) {
        foreach ($centrosUnicos as $c) {
            $allCentros[] = ['id' => $c->id, 'name' => isset($c->name_centro) ? $c->name_centro : ''];
        }
    }
?>

<script id="all-subcentros-json" type="application/json"><?php echo json_encode($allSubcentros, 15, 512) ?></script>
<script id="all-centros-json" type="application/json"><?php echo json_encode($allCentros, 15, 512) ?></script>
<script>
    window.USER_SUBCENTROS_CFG = {
        apiBase: '<?php echo e(rtrim(env('VPL_CORE'), '/')); ?>',
        token: '<?php echo e(session('api_token') ?? ''); ?>'
    };
    // El JS externo expone openAssignModal y unassignSub
</script>
<script src="<?php echo e(asset('js/user_subcentros.js')); ?>"></script>

<!-- Función mínima para cerrar modal y limpiar datos creados por el JS externo -->
<script>
    function closeAssignModal(){
        try {
            const modal = document.getElementById('assignModal'); if (modal) modal.classList.add('hidden');
            const tbody = document.getElementById('assignedTbody'); if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>';
            // eliminar inputs ocultos creados por user_subcentros.js (name="subcentro_ids[]")
            document.querySelectorAll('input[name="subcentro_ids[]"]').forEach(n => n.remove());
            const emailInput = document.getElementById('assign_email_user'); if (emailInput) emailInput.value = '';
        } catch (e) { console.warn('closeAssignModal', e); }
    }

    // Poblar subcentros filtrados por centro seleccionado
    document.addEventListener('DOMContentLoaded', function(){
        const subcentrosDataEl = document.getElementById('all-subcentros-json');
        let subcentros = [];
        try { subcentros = JSON.parse(subcentrosDataEl.textContent || '[]'); } catch(e) { subcentros = []; }
        const centroSelect = document.getElementById('centroSelect');
        const subcentroSelect = document.getElementById('subcentroSelect');
        const loadBtn = document.getElementById('loadSubcentrosBtn');

        function renderSubcentros(centroId){
            const cid = String(centroId || '');
            const list = subcentros.filter(sc => String(sc.centro_id || '') === cid);
            subcentroSelect.innerHTML = '<option value="">-- Selecciona un subcentro --</option>';
            if (list.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = 'No hay subcentros para este centro';
                opt.disabled = true; opt.selected = true;
                subcentroSelect.appendChild(opt);
                return;
            }
            list.forEach(sc => {
                const opt = document.createElement('option');
                opt.value = sc.id;
                opt.textContent = sc.name + (sc.centro ? ' ('+sc.centro+')' : '');
                opt.setAttribute('data-centro-id', sc.centro_id || '');
                subcentroSelect.appendChild(opt);
            });
        }

        if (loadBtn) {
            loadBtn.addEventListener('click', function(){
                const val = centroSelect ? centroSelect.value : '';
                renderSubcentros(val);
            });
        }
        if (centroSelect) {
            centroSelect.addEventListener('change', function(){
                // Opcional: cargar automáticamente al cambiar el centro
                renderSubcentros(this.value);
            });
        }
    });
</script>

<?php $__env->stopSection(); ?>

<style>
/* Zebra y hover para tabla asignados */
#assignedTable tbody tr:nth-child(even){ background-color:#f8fafc; }
#assignedTable tbody tr:hover{ background-color: rgba(99,102,241,0.08); }
/* (revertido) select scroll styling removed */
/* Scrollbar fino reutilizable */
.thin-scrollbar { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
.thin-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
.thin-scrollbar::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 8px; }
.thin-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 8px; }
.thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
</style>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Requisicion\resources\views/centros/user_subcentros.blade.php ENDPATH**/ ?>