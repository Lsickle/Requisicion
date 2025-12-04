@extends('layouts.app')

@section('title', 'Gestor de Centros')

@section('content')
<x-sidebar/>
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white/95 rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-white/20 text-white flex items-center justify-center">
                        <i class="fas fa-sitemap"></i>
                    </div>
                    <div>
                        <h2 class="text-white text-2xl font-bold">Gestión de Centros y Subcentros</h2>
                        <p class="text-blue-100 mt-1">Crea, edita y organiza centros y sus subcentros.</p>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div></div>
                    <div>
                        <button type="button" class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 shadow-sm" onclick="openAddSubModal(null)">
                            <i class="fas fa-plus"></i> Añadir subcentro
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start mb-6">
                    <div class="flex justify-end md:justify-end">
                        <div class="text-sm text-gray-500">Total centros: <span class="font-semibold text-gray-700">{{ $centros->count() }}</span></div>
                    </div>
                </div>

                <div class="grid gap-6" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));">
                    @foreach($centros as $centro)
                    <div class="bg-white border rounded-xl shadow-sm hover:shadow-md hover:border-slate-300 transition overflow-hidden flex flex-col w-full h-full min-h-40">
                        <div class="p-4 flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $centro->name_centro }}</h3>
                                <p class="text-sm text-gray-500 mt-1">Subcentros: <span class="font-medium text-gray-700">{{ $centro->subcentros->count() }}</span></p>

                                {{-- Edición de centros deshabilitada desde aquí --}}
                                <div class="mt-3 text-sm text-gray-700 font-medium">{{ $centro->name_centro }}</div>
                             </div>
 
                             <div class="text-right flex flex-col items-end gap-2">
                                {{-- Ver subcentros --}}
                                <button type="button" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 shadow-sm" onclick="toggleSubs(event,'subs-{{ $centro->id }}')" aria-expanded="false">
                                    <i class="fas fa-chevron-down"></i> Ver
                                </button>
                             </div>
                         </div>

                        <div class="border-t">
                            <div class="p-4">
                                <div class="mt-3 flex-shrink-0">
                                    @php
                                        // Normalizador simple para comparar nombres insensible a mayúsculas y acentos
                                        $normalizeName = function($t){
                                            $t = mb_strtolower(trim((string)$t), 'UTF-8');
                                            $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','ñ'=>'n','Ñ'=>'n','ü'=>'u','Ü'=>'u']);
                                            $t = preg_replace('/[^a-z0-9\s\-]/u','', $t);
                                            $t = preg_replace('/\s+/',' ',$t);
                                            return $t;
                                        };
                                        // Incluir subcentros soft-deleted y desduplicar por nombre normalizado,
                                        // preferir registros no eliminados al hacer sort
                                        $allSubcentros = \App\Models\Subcentro::withTrashed()->orderBy('name_subcentro')->get();
                                        $allSubcentros = $allSubcentros->sortBy(function($s){ return $s->deleted_at ? 1 : 0; })->unique(function($s) use ($normalizeName){ return $normalizeName($s->name_subcentro); })->values();
                                    @endphp
                                    <form action="{{ route('centros.subcentros.store', $centro->id) }}" method="POST" class="add-sub-form flex flex-col sm:flex-row gap-3 items-center" data-centro-id="{{ $centro->id }}">
                                        @csrf
                                        @php
                                            // excluir subcentros que ya están asociados a este centro por NOMBRE (normalizado)
                                            $existingNames = $centro->subcentros->pluck('name_subcentro')->map(function($n) use ($normalizeName){ return $normalizeName($n); })->toArray();
                                            $subs_for_input = $allSubcentros->filter(function($it) use ($existingNames, $normalizeName){
                                                return !in_array($normalizeName($it->name_subcentro), $existingNames);
                                            })->map(function($it){ return ['id'=>$it->id,'name'=>$it->name_subcentro]; })->values();
                                        @endphp
                                        <div class="w-full sm:flex-1 relative">
                                            <input type="search" class="w-full px-3 py-2.5 border border-indigo-300 rounded-xl search-select shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-300/40" placeholder="Buscar subcentro..." autocomplete="off" data-subs='@json($subs_for_input)'>
                                            <input type="hidden" name="existing_id" class="existing-id-input" value="">
                                            <div class="search-box hidden absolute z-40 left-0 right-0 mt-1 bg-white border rounded-xl shadow-lg ring-1 ring-slate-200 max-h-48 overflow-auto thin-scrollbar"></div>
                                        </div>
                                        <div class="w-full sm:w-auto">
                                            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2 justify-center shadow-sm">
                                                <i class="fas fa-plus"></i> <span>Agregar</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                 <div id="subs-{{ $centro->id }}" class="mt-4 hidden max-h-56 overflow-auto pr-2 thin-scrollbar">
                                     <table class="min-w-full text-sm bg-white table-auto">
                                         <thead class="sticky top-0 z-10 bg-indigo-50 text-indigo-900">
                                             <tr class="text-left text-xs font-semibold uppercase tracking-wide">
                                                 <th class="px-2 py-2">Subcentro</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                         @forelse($centro->subcentros as $sub)
                                             <tr class="odd:bg-white even:bg-slate-50 hover:bg-indigo-50/40 transition">
                                                 <td class="px-2 py-2 text-gray-800 flex items-center justify-between">
                                                     <span>{{ $sub->name_subcentro }}</span>
                                                     <form action="{{ route('centros.subcentros.destroy', $sub->id) }}" method="POST" class="inline-block" data-confirm="Quitar asociación de este subcentro?">
                                                         @csrf
                                                         @method('DELETE')
                                                         <button type="submit" class="px-2 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 shadow-sm" title="Quitar asociación">
                                                             <i class="fas fa-unlink"></i>
                                                         </button>
                                                     </form>
                                                 </td>
                                             </tr>
                                         @empty
                                             <tr><td class="text-sm text-gray-500 italic px-2 py-2">No hay subcentros registrados.</td></tr>
                                         @endforelse
                                         </tbody>
                                     </table>
                                 </div>
                             </div>
                         </div>
                     </div>
                     @endforeach
                 </div>

             </div>
         </div>
     </div>
 </div>

@php
    // preparar array simple de subcentros para uso en el modal (incluye soft-deleted)
    // usar la misma normalización y desduplicado por nombre
    $normalizeName = function($t){
        $t = mb_strtolower(trim((string)$t), 'UTF-8');
        $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','ñ'=>'n','Ñ'=>'n','ü'=>'u','Ü'=>'u']);
        $t = preg_replace('/[^a-z0-9\s\-]/u','', $t);
        $t = preg_replace('/\s+/',' ',$t);
        return $t;
    };
    $allSubcentrosList = \App\Models\Subcentro::withTrashed()->orderBy('name_subcentro')->get();
    $allSubcentrosList = $allSubcentrosList->sortBy(function($s){ return $s->deleted_at ? 1 : 0; })->unique(function($s) use ($normalizeName){ return $normalizeName($s->name_subcentro); })->values();
    $allSubcentrosArr = [];
    foreach ($allSubcentrosList as $s) { $allSubcentrosArr[] = ['id' => $s->id, 'name' => $s->name_subcentro]; }
@endphp

<!-- Modal reutilizable para añadir subcentro -->
<div id="addSubModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 w-full max-w-2xl max-h-[80vh] overflow-auto mx-auto">
        <div class="p-4 border-b flex items-center justify-between sticky top-0 bg-white z-10">
            <h3 class="text-lg font-semibold">Agregar subcentro</h3>
            <button type="button" class="text-gray-600" onclick="closeAddSubModal()">✕</button>
        </div>
        <div class="p-4">
            <input type="hidden" id="modalCentroId" value="">
            <div class="mb-3 grid grid-cols-1 md:grid-cols-2 gap-4 items-start">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Centro de costo</label>
                    <select id="modalCentroSelect" class="w-full px-3 py-2 border rounded">
                        <option value="">-- Selecciona un centro --</option>
                        @foreach($centros as $cc)
                            <option value="{{ $cc->id }}">{{ $cc->name_centro }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">O crear uno nuevo</label>
                    <input type="text" id="modalNewName" class="w-full px-3 py-2 border rounded" placeholder="Nombre del nuevo subcentro (opcional)">
                    <p class="text-sm text-gray-500 mt-2">Nota: el campo crea un nuevo subcentro y lo asigna al centro seleccionado.</p>
                </div>
            </div>
            <div class="mb-4 flex justify-end gap-2">
                <button type="button" class="px-3 py-2 bg-gray-300 rounded" onclick="closeAddSubModal()">Cancelar</button>
                <button type="button" class="px-3 py-2 bg-blue-600 text-white rounded" onclick="submitAddSubModal()">Guardar</button>
            </div>
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Subcentros existentes</label>
                <div id="modalSubList" class="border rounded max-h-48 overflow-auto p-2 thin-scrollbar">
                    <div class="text-sm text-gray-600 mb-2">Lista de subcentros existentes (solo lectura). Para asociar un subcentro existente usa el buscador en la tarjeta del centro correspondiente.</div>
                    <table class="min-w-full text-sm">
                        <thead class="bg-indigo-50 sticky top-0 z-10">
                            <tr class="text-left text-xs text-gray-600">
                                <th class="px-2 py-1">Subcentro</th>
                                <th class="px-2 py-1">Centro</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($allSubcentrosList as $s)
                            <tr class="odd:bg-white even:bg-gray-50">
                                <td class="px-2 py-1">{{ $s->name_subcentro }}</td>
                                <td class="px-2 py-1">{{ optional($s->centro)->name_centro ?? '—' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSubs(e, id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('hidden');
        // rotate icon on the clicked button
        const btn = e && e.currentTarget ? e.currentTarget : null;
        if (btn) {
            const icon = btn.querySelector('i');
            if (icon) icon.classList.toggle('fa-rotate-180');
            const expanded = !el.classList.contains('hidden');
            btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
    }

    // manejar envío: aceptar hidden existing_id (establecido al seleccionar de la lista)
    document.addEventListener('DOMContentLoaded', function(){
        // init search-select inputs
        document.querySelectorAll('.search-select').forEach(function(inp){
            const subs = JSON.parse(inp.getAttribute('data-subs') || '[]');
            const box = inp.closest('.relative').querySelector('.search-box');
            let hideTimeout = null;

            function render(list){
                box.innerHTML = '';
                const frag = document.createDocumentFragment();
                list.slice(0,50).forEach(function(s){
                    const d = document.createElement('div'); d.className='px-3 py-2 cursor-pointer hover:bg-gray-100'; d.textContent = s.name;
                    d.dataset.id = s.id;
                    d.addEventListener('mousedown', function(e){ e.preventDefault(); inp.value = s.name; const hid = inp.closest('form').querySelector('.existing-id-input'); if (hid) hid.value = s.id; box.classList.add('hidden'); });
                    frag.appendChild(d);
                });
                if (!list.length) { box.classList.add('hidden'); } else { box.appendChild(frag); box.classList.remove('hidden'); }
            }

            inp.addEventListener('input', function(){
                const v = (this.value || '').toLowerCase();
                // clear existing id when typing
                const hid = inp.closest('form').querySelector('.existing-id-input'); if (hid) hid.value = '';
                if (!v) { box.classList.add('hidden'); return; }
                const filtered = subs.filter(s => s.name.toLowerCase().includes(v));
                render(filtered.slice(0,50));
            });

            inp.addEventListener('focus', function(){
                const v = (this.value || '').toLowerCase();
                const filtered = v ? subs.filter(s => s.name.toLowerCase().includes(v)) : subs;
                render(filtered.slice(0,50));
            });

            inp.addEventListener('blur', function(){ hideTimeout = setTimeout(()=> box.classList.add('hidden'), 150); });
            box.addEventListener('mousedown', function(e){ if (hideTimeout) clearTimeout(hideTimeout); });
        });

        // submit handler: require existing_id (hidden) or show message
        document.querySelectorAll('.add-sub-form').forEach(function(form){
            form.addEventListener('submit', function(e){
                e.preventDefault();
                const hid = form.querySelector('.existing-id-input');
                const val = hid ? hid.value : '';
                if (!val) return Swal.fire({icon:'info', title:'Seleccione un subcentro', text:'Selecciona un subcentro existente desde la lista o usa el modal para crear uno nuevo.'});
                form.submit();
            });
        });
    });

    function openAddSubModal(centroId){
        document.getElementById('modalCentroId').value = centroId || '';
        const centroSelect = document.getElementById('modalCentroSelect');
        if (centroSelect) centroSelect.value = centroId || '';
        document.getElementById('modalNewName').value = '';
        document.getElementById('addSubModal').classList.remove('hidden');
    }

    function closeAddSubModal(){ document.getElementById('addSubModal').classList.add('hidden'); }

    function submitAddSubModal(){
        let centroId = document.getElementById('modalCentroId').value;
        const centroSelect = document.getElementById('modalCentroSelect');
        if (!centroId && centroSelect) centroId = centroSelect.value;
        const newName = (document.getElementById('modalNewName').value || '').trim();
        // en este modal solo permitimos crear nuevos subcentros; para asociar existentes usa el buscador en la tarjeta
        if (!newName) return Swal.fire({icon:'info', title:'Escribe el nombre del subcentro', text:'En este modal puedes crear un nuevo subcentro y asignarlo al centro seleccionado. Para asociar subcentros ya existentes, utiliza el buscador en la tarjeta del centro.'});
        Swal.fire({ title: 'Crear subcentro', text: '¿Crear "' + newName + '" para este centro?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, crear' }).then(function(r){ if (r.isConfirmed) doSubmit(centroId, { name_subcentro: newName }); });
    }

    function doSubmit(centroId, payload){
        if (!centroId) return Swal.fire({icon:'error', title:'Centro no seleccionado', text:'Selecciona un centro antes de guardar.'});
        const url = BASE_CENTROS_URL + '/' + centroId + '/subcentros';
         const form = document.createElement('form'); form.method='POST'; form.action = url; document.body.appendChild(form);
         const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='_token'; csrf.value='{{ csrf_token() }}'; form.appendChild(csrf);
         if (payload.name_subcentro) { const i = document.createElement('input'); i.type='hidden'; i.name='name_subcentro'; i.value = payload.name_subcentro; form.appendChild(i); }
         if (payload.existing_id) { const e = document.createElement('input'); e.type='hidden'; e.name='existing_id'; e.value = payload.existing_id; form.appendChild(e); }
         form.submit();
     }

    // SweetAlert confirmation for any form with data-confirm attribute (e.g., desvincular subcentros)
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('form[data-confirm]').forEach(function(form){
            form.addEventListener('submit', function(evt){
                // allow if already confirmed
                if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return true; }
                evt.preventDefault();
                const msg = form.getAttribute('data-confirm') || '¿Desea continuar?';
                Swal.fire({
                    title: msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, quitar',
                    cancelButtonText: 'Cancelar'
                }).then(function(res){
                    if (res.isConfirmed) {
                        form.dataset.confirmed = '1';
                        // disable submit buttons to prevent double submissions
                        const submits = form.querySelectorAll('button[type=submit], input[type=submit]');
                        submits.forEach(b=>{ b.disabled = true; b.classList.add('opacity-70','cursor-not-allowed'); });
                        if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
                    }
                });
            });
        });
    });

    function openAddSubModal(centroId){
        document.getElementById('modalCentroId').value = centroId || '';
        const centroSelect = document.getElementById('modalCentroSelect');
        if (centroSelect) centroSelect.value = centroId || '';
        document.getElementById('modalNewName').value = '';
        document.getElementById('addSubModal').classList.remove('hidden');
    }

    function closeAddSubModal(){ document.getElementById('addSubModal').classList.add('hidden'); }

    function submitAddSubModal(){
        let centroId = document.getElementById('modalCentroId').value;
        const centroSelect = document.getElementById('modalCentroSelect');
        if (!centroId && centroSelect) centroId = centroSelect.value;
        const newName = (document.getElementById('modalNewName').value || '').trim();
        // en este modal solo permitimos crear nuevos subcentros; para asociar existentes usa el buscador en la tarjeta
        if (!newName) return Swal.fire({icon:'info', title:'Escribe el nombre del subcentro', text:'En este modal puedes crear un nuevo subcentro y asignarlo al centro seleccionado. Para asociar subcentros ya existentes, utiliza el buscador en la tarjeta del centro.'});
        Swal.fire({ title: 'Crear subcentro', text: '¿Crear "' + newName + '" para este centro?', icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, crear' }).then(function(r){ if (r.isConfirmed) doSubmit(centroId, { name_subcentro: newName }); });
    }

    function doSubmit(centroId, payload){
        if (!centroId) return Swal.fire({icon:'error', title:'Centro no seleccionado', text:'Selecciona un centro antes de guardar.'});
        const url = BASE_CENTROS_URL + '/' + centroId + '/subcentros';
         const form = document.createElement('form'); form.method='POST'; form.action = url; document.body.appendChild(form);
         const csrf = document.createElement('input'); csrf.type='hidden'; csrf.name='_token'; csrf.value='{{ csrf_token() }}'; form.appendChild(csrf);
         if (payload.name_subcentro) { const i = document.createElement('input'); i.type='hidden'; i.name='name_subcentro'; i.value = payload.name_subcentro; form.appendChild(i); }
         if (payload.existing_id) { const e = document.createElement('input'); e.type='hidden'; e.name='existing_id'; e.value = payload.existing_id; form.appendChild(e); }
         form.submit();
     }

    // Sugerencias por input (máx 5 visibles, scroll si hay más)
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.suggestion-input').forEach(function(inp){
            const suggestions = JSON.parse(inp.getAttribute('data-suggestions') || '[]');
            const centroId = inp.id.replace('input-sub-','');
            const box = document.getElementById('suggest-box-' + centroId);
            let timeoutHide = null;

            function render(list){
                box.innerHTML = '';
                const fragment = document.createDocumentFragment();
                list.slice(0, 20).forEach(function(item, idx){ // crear todos pero mostrar max via CSS
                    const div = document.createElement('div'); div.className = 'suggest-item'; div.textContent = item;
                    div.addEventListener('mousedown', function(e){ e.preventDefault(); inp.value = item; box.classList.add('hidden'); });
                    fragment.appendChild(div);
                });
                box.appendChild(fragment);
                if (list.length === 0) box.classList.add('hidden'); else box.classList.remove('hidden');
            }

            inp.addEventListener('input', function(){
                const v = (this.value || '').toLowerCase();
                if (!v) { render([]); return; }
                const filtered = suggestions.filter(s => s.toLowerCase().includes(v));
                render(filtered.slice(0, 50)); // construir y dejar que el box muestre max 5 por height
            });

            inp.addEventListener('focus', function(){
                const v = (this.value || '').toLowerCase();
                const filtered = v ? suggestions.filter(s => s.toLowerCase().includes(v)) : suggestions;
                render(filtered.slice(0, 50));
            });

            inp.addEventListener('blur', function(){
                timeoutHide = setTimeout(()=> box.classList.add('hidden'), 150);
            });

            box.addEventListener('mousedown', function(e){
                if (timeoutHide) clearTimeout(timeoutHide);
            });
        });
    });

    const CSRF_TOKEN = '{{ csrf_token() }}';
    async function editSubPrompt(subId, currentName, updateUrl){
        const { value: newName } = await Swal.fire({
            title: 'Editar subcentro',
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            inputValidator: (value) => { if (!value || !value.trim()) return 'El nombre no puede quedar vacío'; }
        });
        if (!newName) return;
        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        try {
            const body = new URLSearchParams();
            body.append('_method','PUT');
            body.append('_token', CSRF_TOKEN);
            body.append('name_subcentro', newName);
            const res = await fetch(updateUrl, { method: 'POST', headers: {'X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN': CSRF_TOKEN}, body });
            if (!res.ok) throw new Error('Error al guardar');
            // actualizar texto en la fila
            const el = document.getElementById('sub-name-' + subId);
            if (el) el.textContent = newName;
            Swal.fire({ icon: 'success', title: 'Actualizado' });
        } catch (err) {
            console.error(err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo actualizar el subcentro' });
        }
    }
</script>
 
<style>
     /* Small tweaks for nicer cards */
     .fa-rotate-180 { transform: rotate(180deg); transition: transform 0.2s; }
    /* Scrollbar for subcentros list */
    .max-h-56::-webkit-scrollbar { width: 8px; }
    .max-h-56::-webkit-scrollbar-track { background: transparent; }
    .max-h-56::-webkit-scrollbar-thumb { background: rgba(59,130,246,0.35); border-radius: 8px; }
    .max-h-56 { scrollbar-width: thin; scrollbar-color: rgba(59,130,246,0.35) transparent; }
    .suggest-box { max-height: 10rem; } /* aprox 5 items */
    .suggest-item { padding: .5rem .75rem; cursor: pointer; }
    .suggest-item:hover { background: #f1f5f9; }
    /* Ajustes visuales extras para mejor apariencia */
    .min-h-40 { min-height: 10rem; }
    /* Asegurar que el botón 'Ver' no empuje el título en pantallas pequeñas */
    @media (max-width: 640px) {
        .p-4 .text-right { align-self: flex-start; }
    }
    /* Scrollbar fino reutilizable */
    .thin-scrollbar { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
    .thin-scrollbar::-webkit-scrollbar { width: 8px; height: 8px; }
    .thin-scrollbar::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 8px; }
    .thin-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 8px; }
    .thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
 </style>

@if(session('success') || session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function(){
        @if(session('success'))
            Swal.fire({ icon: 'success', title: 'Listo', text: {!! json_encode(session('success')) !!} });
        @endif
        @if(session('error'))
            Swal.fire({ icon: 'error', title: 'Error', text: {!! json_encode(session('error')) !!} });
        @endif
    });
</script>
@endif
