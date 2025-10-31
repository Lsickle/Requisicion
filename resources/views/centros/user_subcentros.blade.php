@extends('layouts.app')

@section('title','Asignar Subcentros a Usuarios')

@section('content')
<x-sidebar />
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-4xl mx-auto bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Asignar Subcentros a Usuarios</h2>
        <p class="text-sm text-gray-500 mb-4">Seleccione un usuario desde la lista (traída desde la API) y asigne los subcentros disponibles.</p>

        @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif
        @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        <!-- Buscador -->
        <div class="mb-4 flex items-center gap-2">
            <input type="text" id="usersSearch" placeholder="Buscar por nombre o email..." class="flex-1 px-3 py-2 border rounded" />
            <button id="usersClearSearch" class="px-3 py-2 bg-gray-200 rounded">Limpiar</button>
        </div>

        <div id="usersList" class="space-y-2">
            <p class="text-gray-500">Cargando usuarios...</p>
        </div>
        <div id="usersPaginationContainer" class="mt-3"></div>

        <!-- Modal asignación -->
        <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow p-6 w-11/12 md:w-3/4 max-h-[90vh] overflow-auto">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 id="assignUserTitle" class="text-lg font-semibold">Asignar subcentros</h3>
                        <div id="assignUserInfo" class="text-sm text-gray-600">&nbsp;</div>
                    </div>
                    <button type="button" class="text-gray-500" onclick="closeAssignModal()">Cerrar ✕</button>
                </div>

                <form id="assignForm" method="POST" action="{{ route('centros.user_subcentros.store') }}">
                    @csrf
                    <input type="hidden" name="email_user" id="assign_email_user">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Select de subcentros y botón Agregar -->
                        <div class="border rounded p-3 max-h-64 overflow-auto">
                            <h4 class="text-sm font-medium mb-2">Subcentros disponibles</h4>
                            <div class="flex gap-2 mb-3">
                                <select id="subcentroSelect" class="flex-1 px-2 py-2 border rounded">
                                    <option value="">-- Selecciona un subcentro --</option>
                                    @foreach($subcentros as $s)
                                    <option value="{{ $s->id }}">{{ $s->name_subcentro }} @if($s->centro) ({{ $s->centro->name_centro }})@endif</option>
                                    @endforeach
                                </select>
                                <button type="button" id="addSubcentroBtn" class="px-3 py-2 bg-green-600 text-white rounded">Agregar</button>
                            </div>
                            <div class="text-xs text-gray-500">Selecciona y pulsa Agregar para añadir a la lista de asignados.</div>
                        </div>

                        <!-- Tabla dinámica de asignados -->
                        <div class="border rounded p-3 max-h-64 overflow-auto">
                            <h4 class="text-sm font-medium mb-2">Subcentros asignados</h4>
                            <table class="min-w-full text-sm" id="assignedTable">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500">
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

<script>
    // preparar datos de subcentros en JS
    const ALL_SUBCENTROS = @json($subcentros->map(function($s){ return ['id'=>$s->id,'name'=>$s->name_subcentro,'centro'=> $s->centro?->name_centro ?? '']; }));

    // búsqueda global para la vista
    let userSearch = '';
    let searchDebounceTimer = null;
    let assignedSet = new Set();

    // Cargar usuarios paginados desde VPL_CORE API (formato DataTables: start/length)
    async function loadUsers(page = 1, length = 10) {
        const container = document.getElementById('usersList');
        const base = '{{ rtrim(env("VPL_CORE"), "/") }}';
        const token = '{{ session("api_token") ?? '' }}';

        // Limpiar resultados previos y mostrar indicador de carga
        container.innerHTML = '';
        let loadingEl = document.getElementById('usersLoading');
        if (loadingEl) loadingEl.remove();
        loadingEl = document.createElement('div');
        loadingEl.id = 'usersLoading';
        loadingEl.className = 'text-gray-500';
        loadingEl.textContent = 'Cargando usuarios...';
        container.appendChild(loadingEl);

        // DataTables-style
        const start = (Math.max(1, page) - 1) * length;
        const url = base + '/api/usuarios?start=' + encodeURIComponent(start) + '&length=' + encodeURIComponent(length) + '&search[value]=' + encodeURIComponent(userSearch);

        try {
            const res = await fetch(url, {
                headers: Object.assign({ 'Accept': 'application/json' }, token ? { 'Authorization': 'Bearer ' + token } : {}),
                credentials: 'include'
            });

            if (!res.ok) throw new Error('Error al obtener usuarios: ' + res.status);

            const payload = await res.json();

            // Payload esperado: { draw, recordsTotal, recordsFiltered, data }
            const users = Array.isArray(payload.data) ? payload.data : (Array.isArray(payload) ? payload : []);
            const total = payload.recordsFiltered ?? payload.recordsTotal ?? null;

            const currentPage = Math.floor((start / length)) + 1;
            const lastPage = total ? Math.ceil(total / length) : null;

            // Mostrar sólo los usuarios de la página solicitada (no acumular)
            users.forEach(u => renderUserRow(container, u));

            // Renderizar paginación basado en total/length
            renderPagination(container, currentPage, lastPage, total);

        } catch (e) {
            container.innerHTML = '<div class="text-red-500">No se pudo obtener la lista de usuarios desde el servicio externo. ' + (e.message || '') + '</div>';
        } finally {
            const loading = document.getElementById('usersLoading'); if (loading) loading.style.display = 'none';
        }
    }

    function renderUserRow(container, u) {
        const name = u.name ?? u.nombre ?? u.full_name ?? u.email;
        const email = u.email ?? u.correo ?? '';
        const div = document.createElement('div');
        div.className = 'flex items-center justify-between p-2 border rounded';
        const safeName = (name || '').replace(/'/g, "\\'");
        const safeEmail = (email || '').replace(/'/g, "\\'");
        div.innerHTML = `<div><strong>${name || ''}</strong><div class="text-sm text-gray-500">${email || ''}</div></div><div><button class="px-3 py-1 bg-indigo-600 text-white rounded" onclick="openAssignModal('${safeEmail}', '${safeName}')">Asignar</button></div>`;
        container.appendChild(div);
    }

    function renderPagination(container, currentPage, lastPage, total) {
        const pagContainer = document.getElementById('usersPaginationContainer');
        if (!pagContainer) return;
        // limpiar paginación previa
        pagContainer.innerHTML = '';

        // Si no hay total o lastPage y no se puede paginar, no mostrar controles
        if (!lastPage || lastPage <= 1) return;

        const pag = document.createElement('div');
        pag.id = 'usersPagination';
        pag.className = 'flex items-center gap-2 flex-wrap';

        const addBtn = (text, disabled, cb) => {
            const b = document.createElement('button');
            b.className = 'px-3 py-1 rounded ' + (disabled ? 'bg-gray-200 text-gray-500' : 'bg-white border');
            b.textContent = text;
            if (!disabled && cb) b.addEventListener('click', cb);
            return b;
        };

        // Prev
        pag.appendChild(addBtn('Anterior', currentPage <= 1, () => loadUsers(currentPage - 1)));

        // simple window of pages
        const maxButtons = 7;
        let start = Math.max(1, currentPage - Math.floor(maxButtons/2));
        let end = Math.min(lastPage, start + maxButtons - 1);
        if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);

        if (start > 1) {
            pag.appendChild(addBtn('1', false, () => loadUsers(1)));
            if (start > 2) {
                const dots = document.createElement('span'); dots.textContent = '...'; dots.className = 'px-2'; pag.appendChild(dots);
            }
        }

        for (let p = start; p <= end; p++) {
            const btn = document.createElement('button');
            btn.className = 'px-3 py-1 rounded ' + (p === currentPage ? 'bg-blue-600 text-white' : 'bg-white border');
            btn.textContent = p;
            if (p !== currentPage) btn.addEventListener('click', () => loadUsers(p));
            pag.appendChild(btn);
        }

        if (end < lastPage) {
            if (end < lastPage - 1) {
                const dots = document.createElement('span'); dots.textContent = '...'; dots.className = 'px-2'; pag.appendChild(dots);
            }
            pag.appendChild(addBtn(String(lastPage), false, () => loadUsers(lastPage)));
        }

        // Next
        pag.appendChild(addBtn('Siguiente', currentPage >= lastPage, () => loadUsers(currentPage + 1)));

        if (total !== null) {
            const info = document.createElement('div');
            info.className = 'ml-3 text-sm text-gray-600';
            info.textContent = `Página ${currentPage} de ${lastPage} — Total: ${total}`;
            pag.appendChild(info);
        }

        pagContainer.appendChild(pag);
    }

    async function openAssignModal(email, name) {
        document.getElementById('assign_email_user').value = email;
        document.getElementById('assignUserTitle').textContent = 'Asignar subcentros a ' + (name || email);
        document.getElementById('assignUserInfo').textContent = email;
        // reset select and assigned set
        const select = document.getElementById('subcentroSelect'); if (select) select.value = '';
        assignedSet = new Set();
         // fetch existing assignments
         try {
             const r = await fetch(`/centros/user_subcentros/list/${encodeURIComponent(email)}`);
             const data = await r.json();
             if (data && data.assigned) {
                data.assigned.forEach(id => { assignedSet.add(Number(id)); });
             }
         } catch (e) {
             console.warn(e);
         }
        // attach handler for select add button
        const addBtn = document.getElementById('addSubcentroBtn');
        if (addBtn) {
            addBtn.onclick = function(){
                const sel = document.getElementById('subcentroSelect');
                if (!sel) return;
                const v = sel.value; if (!v) return;
                assignedSet.add(Number(v));
                renderAssignedTable();
            }
        }
        renderAssignedTable();
        document.getElementById('assignModal').classList.remove('hidden');
    }

    function renderAssignedTable(){
        const tbody = document.getElementById('assignedTbody');
        tbody.innerHTML = '';
        // remove previously generated hidden inputs
        document.querySelectorAll('input[name="subcentro_ids[]"]').forEach(i => i.remove());
         if (!assignedSet || assignedSet.size === 0) {
             tbody.innerHTML = '<tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>';
             return;
         }
         const subs = ALL_SUBCENTROS.filter(s => assignedSet.has(Number(s.id)));
         subs.forEach(s =>{
             const tr = document.createElement('tr');
             tr.innerHTML = `<td class="px-2 py-1">${s.name}</td><td class="px-2 py-1">${s.centro}</td><td class="px-2 py-1"><button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="unassignSub(${s.id})">Quitar</button></td>`;
             tbody.appendChild(tr);
             // add hidden input to the form so it will be submitted
            const hidden = document.createElement('input');
            hidden.type = 'hidden'; hidden.name = 'subcentro_ids[]'; hidden.value = s.id;
            document.getElementById('assignForm').appendChild(hidden);
         });
     }

    function unassignSub(id){
        // uncheck checkbox and remove from set
        const sel = document.getElementById('subcentroSelect');
        if (sel && String(sel.value) === String(id)) sel.value = '';
         assignedSet.delete(Number(id));
         renderAssignedTable();
     }

    function closeAssignModal(){ document.getElementById('assignModal').classList.add('hidden'); }

    // Debounce helper
    function debounceSearch(fn, wait) {
        return function(...args) {
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => fn.apply(this, args), wait);
        }
    }

    document.addEventListener('DOMContentLoaded', function(){
        // inicializar carga
        loadUsers();

        const input = document.getElementById('usersSearch');
        const clearBtn = document.getElementById('usersClearSearch');
        if (input) {
            input.addEventListener('input', debounceSearch(function(e){
                userSearch = e.target.value.trim();
                loadUsers(1);
            }, 400));
        }
        if (clearBtn && input) {
            clearBtn.addEventListener('click', function(){ input.value = ''; userSearch = ''; loadUsers(1); });
        }
    });

    // SweetAlert confirm on assignForm submit
    (function(){
        const form = document.getElementById('assignForm');
        if (!form) return;
        form.addEventListener('submit', function(evt){
            // if already confirmed by this handler, allow submit
            if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return true; }
            evt.preventDefault();
            Swal.fire({
                title: '¿Guardar asignaciones?',
                text: 'Se guardará el correo y los subcentros asignados para el usuario.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, guardar',
                cancelButtonText: 'Cancelar'
            }).then(function(res){
                if (res.isConfirmed) {
                    form.dataset.confirmed = '1';
                    Swal.fire({
                        title: 'Guardando...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => Swal.showLoading()
                    });
                    // submit programmatically; handler above will allow the real submit
                    if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
                }
            });
        });
    })();

</script>

@endsection
