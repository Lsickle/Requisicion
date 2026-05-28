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
                <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="inline-flex items-center px-4 py-2.5 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 shadow-md transition-colors font-medium text-sm" onclick="window.location.href='{{ route('centros.bodegas.index') }}'">
                            <i class="fas fa-warehouse mr-2"></i>Bodegas
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md transition-colors font-medium text-sm" onclick="openCentroModal(null)">
                            <i class="fas fa-building mr-2"></i>Añadir centro
                        </button>
                        <button type="button" class="inline-flex items-center px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md transition-colors font-medium text-sm" onclick="openAddSubModal(null)">
                            <i class="fas fa-layer-group mr-2"></i>Añadir subcentro
                        </button>
                    </div>
                    <div class="inline-flex items-center px-4 py-2 bg-slate-100 rounded-lg">
                        <i class="fas fa-archive text-slate-500 mr-2"></i>
                        <span class="text-sm text-gray-600">Total centros:</span>
                        <span class="ml-2 font-bold text-gray-800">{{ $centros->count() }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach($centros as $centro)
<div class="bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-lg hover:border-indigo-300 transition-all duration-200 overflow-hidden flex flex-col w-full h-full">
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="text-lg font-bold text-gray-900 truncate">{{ $centro->name_centro }}</h3>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                                    <i class="fas fa-layer-group mr-1"></i>{{ $centro->subcentros->count() }} subcentros
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button type="button" class="p-2 text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors" title="Editar centro" onclick="openCentroModal({{ $centro->id }}, '{{ addslashes($centro->name_centro) }}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('centros.destroy', $centro->id) }}" method="POST" onsubmit="return confirm('Eliminar centro y sus subcentros?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar centro">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="px-3 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-medium" onclick="toggleSubs(event,'subs-{{ $centro->id }}')" aria-expanded="false">
                                        <i class="fas fa-chevron-down mr-1"></i><span>Ver</span>
                                    </button>
                                </div>
                            </div>
                        </div>

<div class="p-5">
                            <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                                    <i class="fas fa-plus-circle text-green-600"></i>Agregar subcentro
                                </h4>
                                    @php
                                        $normalizeName = function($t){
                                            $t = mb_strtolower(trim((string)$t), 'UTF-8');
                                            $t = strtr($t, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ú'=>'u','ñ'=>'n','Ñ'=>'n','ü'=>'u','Ü'=>'u']);
                                            $t = preg_replace('/[^a-z0-9\s\-]/u','', $t);
                                            $t = preg_replace('/\s+/',' ',$t);
                                            return $t;
                                        };
                                        $allSubcentros = \App\Models\Subcentro::withTrashed()->orderBy('name_subcentro')->get();
                                        $allSubcentros = $allSubcentros->sortBy(function($s){ return $s->deleted_at ? 1 : 0; })->unique(function($s) use ($normalizeName){ return $normalizeName($s->name_subcentro); })->values();
                                    @endphp
                                    <form action="{{ route('centros.subcentros.store', $centro->id) }}" method="POST" class="add-sub-form" data-centro-id="{{ $centro->id }}">
                                        @csrf
                                        @php
                                            $existingNames = $centro->subcentros->pluck('name_subcentro')->map(function($n) use ($normalizeName){ return $normalizeName($n); })->toArray();
                                            $subs_for_input = $allSubcentros->filter(function($it) use ($existingNames, $normalizeName){
                                                return !in_array($normalizeName($it->name_subcentro), $existingNames);
                                            })->map(function($it){ return ['id'=>$it->id,'name'=>$it->name_subcentro]; })->values();
                                        @endphp
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Unidad</label>
                                                <select name="unidad" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg bg-white shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition" aria-label="Unidad">
                                                    <option value="">Seleccionar</option>
                                                    <option value="UND">UND</option>
                                                    <option value="KG">KG</option>
                                                    <option value="GLN">GLN</option>
                                                    <option value="MT">MT</option>
                                                    <option value="PQT">PQT</option>
                                                    <option value="CJ">CJ</option>
                                                </select>
                                            </div>
                                            <div class="sm:col-span-2 relative">
                                                <label class="block text-xs font-medium text-gray-500 mb-1">Subcentro</label>
                                                <input type="search" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition text-sm" placeholder="Buscar subcentro..." autocomplete="off" data-subs='@json($subs_for_input)'>
                                                <input type="hidden" name="existing_id" class="existing-id-input" value="">
                                                <div class="search-box hidden absolute z-40 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-xl max-h-48 overflow-auto thin-scrollbar"></div>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex justify-end">
                                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2 shadow-sm transition-colors text-sm font-medium">
                                                <i class="fas fa-plus"></i>Agregar
                                            </button>
                                        </div>
                                    </form>
                            </div>

                                  <div id="subs-{{ $centro->id }}" class="mt-4 hidden rounded-xl border border-slate-200 overflow-hidden">
                                      <div class="bg-slate-100 px-4 py-2 border-b border-slate-200 flex items-center justify-between">
                                          <span class="text-sm font-semibold text-gray-700">Lista de subcentros</span>
                                          <span class="text-xs text-gray-500">{{ $centro->subcentros->count() }} registros</span>
                                      </div>
                                      <div class="max-h-64 overflow-auto thin-scrollbar">
                                          <table class="min-w-full text-sm">
                                              <thead class="sticky top-0 bg-slate-50 text-slate-700 shadow-sm">
                                                  <tr class="text-left text-xs font-semibold uppercase tracking-wide">
                                                      <th class="px-4 py-3">Subcentro</th>
                                                      <th class="px-4 py-3 text-right">Acción</th>
                                                  </tr>
                                              </thead>
                                              <tbody class="divide-y divide-slate-100">
                                          @forelse($centro->subcentros as $sub)
                                              <tr class="hover:bg-slate-50 transition-colors">
                                                  <td class="px-4 py-3 text-gray-800">
                                                      <div class="flex items-center gap-2">
                                                          <i class="fas fa-folder text-gray-400"></i>
                                                          <span>{{ $sub->name_subcentro }}</span>
                                                      </div>
                                                  </td>
                                                  <td class="px-4 py-3 text-right">
                                                      <form action="{{ route('centros.subcentros.destroy', $sub->id) }}" method="POST" class="inline-block" data-confirm="Quitar asociación de este subcentro?">
                                                          @csrf
                                                          @method('DELETE')
                                                          <button type="submit" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Quitar asociación">
                                                              <i class="fas fa-unlink"></i>
                                                          </button>
                                                      </form>
                                                  </td>
                                              </tr>
                                          @empty
                                              <tr><td colspan="2" class="px-4 py-6 text-center text-gray-500 italic">No hay subcentros registrados.</td></tr>
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
<!-- Modal para crear/editar Centro -->
<div id="addCentroModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50 px-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 ring-1 ring-slate-100 w-full max-w-lg max-h-[80vh] overflow-auto mx-auto">
        <div class="p-4 border-b flex items-center justify-between sticky top-0 bg-white z-10">
            <h3 class="text-lg font-semibold">Centro de costo</h3>
            <button type="button" class="text-gray-600" onclick="closeCentroModal()">✕</button>
        </div>
        <div class="p-4">
            <form id="centroForm" action="{{ route('centros.store') }}" method="POST">
                @csrf
                <input type="hidden" id="centroIdInput" name="centro_id" value="">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del centro</label>
                    <input type="text" id="centroNameInput" name="name_centro" class="w-full px-3 py-2 border rounded" required>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="px-3 py-2 bg-gray-300 rounded" onclick="closeCentroModal()">Cancelar</button>
                    <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
    const BASE_CENTROS_URL = '{{ url('/centros') }}';

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

        // sincronizar selects de subcentros (si existen) con el hidden existing_id y el input
        document.querySelectorAll('.subs-select').forEach(function(sel){
            sel.addEventListener('change', function(){
                const val = this.value || '';
                const form = this.closest('form');
                const hid = form && form.querySelector('.existing-id-input');
                const search = form && form.querySelector('.search-select');
                if (hid) hid.value = val;
                if (search) search.value = this.selectedOptions[0] ? this.selectedOptions[0].textContent : '';
                const box = form && form.querySelector('.search-box'); if (box) box.classList.add('hidden');
            });
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

    // Centro modal handlers
    function openCentroModal(id, name){
        const modal = document.getElementById('addCentroModal');
        const form = document.getElementById('centroForm');
        const idInput = document.getElementById('centroIdInput');
        const nameInput = document.getElementById('centroNameInput');
        if (!modal || !form) return;
        if (id) {
            // editar
            idInput.value = id;
            nameInput.value = name || '';
            form.action = BASE_CENTROS_URL + '/' + id;
            // add method input for PUT if not exists
            if (!form.querySelector('input[name="_method"]')) {
                const m = document.createElement('input'); m.type='hidden'; m.name='_method'; m.value='PUT'; form.appendChild(m);
            }
        } else {
            // crear
            idInput.value = '';
            nameInput.value = '';
            form.action = '{{ route('centros.store') }}';
            const m = form.querySelector('input[name="_method"]'); if (m) m.remove();
        }
        modal.classList.remove('hidden');
        nameInput.focus();
    }

    function closeCentroModal(){ const modal = document.getElementById('addCentroModal'); if (modal) modal.classList.add('hidden'); }

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
