(function(){
  'use strict';

  // Estado global para el modal de asignacion
  window.__uscState = { bodegaActual: null, bodegaNombre: '' };

  function $(sel, root){ return (root||document).querySelector(sel); }
  function $$(sel, root){ return Array.from((root||document).querySelectorAll(sel)); }

  function debounce(fn, wait){
    let t; return function(){ clearTimeout(t); t = setTimeout(()=>fn.apply(this, arguments), wait); };
  }

  function getAllSubcentros(){
    try {
      const el = document.getElementById('all-subcentros-json');
      if (!el) return [];
      const txt = el.textContent || el.innerText || '[]';
      const arr = JSON.parse(txt);
      return Array.isArray(arr) ? arr : [];
    } catch(e){ return []; }
  }

  const CFG = (window.USER_SUBCENTROS_CFG || {});
  const ALL_SUBCENTROS = getAllSubcentros();
  let assignedSet = new Set();
  let userSearch = '';

  async function loadUsers(page = 1, length = 10){
    const container = document.getElementById('usersList');
    if (!container) return;
    const base = (CFG.apiBase || '').replace(/\/$/, '');
    const token = CFG.token || '';

    container.innerHTML = '';
    let loadingEl = document.createElement('div');
    loadingEl.id = 'usersLoading';
    loadingEl.className = 'text-gray-500';
    loadingEl.textContent = 'Cargando usuarios...';
    container.appendChild(loadingEl);

    const start = (Math.max(1, page) - 1) * length;
    const url = base + '/api/usuarios?start=' + encodeURIComponent(start) + '&length=' + encodeURIComponent(length) + '&search[value]=' + encodeURIComponent(userSearch);

    try {
      const headers = { 'Accept': 'application/json' };
      if (token) headers['Authorization'] = 'Bearer ' + token;
      const res = await fetch(url, { headers, credentials: 'include' });
      if (!res.ok) throw new Error('Error al obtener usuarios: ' + res.status);
      const payload = await res.json();
      const users = Array.isArray(payload?.data) ? payload.data : (Array.isArray(payload) ? payload : []);
      const total = payload?.recordsFiltered ?? payload?.recordsTotal ?? null;
      const currentPage = Math.floor((start / length)) + 1;
      const lastPage = total ? Math.ceil(total / length) : null;

      users.forEach(u => renderUserRow(container, u));
      renderPagination(currentPage, lastPage, total);
    } catch(e){
      container.innerHTML = '<div class="text-red-500">No se pudo obtener la lista de usuarios desde el servicio externo. ' + (e.message||'') + '</div>';
    } finally {
      const loading = document.getElementById('usersLoading'); if (loading) loading.style.display = 'none';
    }
  }

  function renderUserRow(container, u){
    const name = u.name ?? u.nombre ?? u.full_name ?? u.email;
    const email = u.email ?? u.correo ?? '';
    const div = document.createElement('div');
    div.className = 'flex items-center justify-between p-2 border rounded';
    const safeName = String(name||'').replace(/'/g, "\\'");
    const safeEmail = String(email||'').replace(/'/g, "\\'");
    div.innerHTML = `<div><strong>${name||''}</strong><div class="text-sm text-gray-500">${email||''}</div></div><div><button class="px-3 py-1 bg-indigo-600 text-white rounded" onclick="openAssignModal('${safeEmail}', '${safeName}')">Asignar</button></div>`;
    container.appendChild(div);
  }

  function renderPagination(currentPage, lastPage, total){
    const pagContainer = document.getElementById('usersPaginationContainer');
    if (!pagContainer) return;
    pagContainer.innerHTML = '';
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

    pag.appendChild(addBtn('Anterior', currentPage <= 1, () => loadUsers(currentPage - 1)));

    const maxButtons = 7;
    let start = Math.max(1, currentPage - Math.floor(maxButtons/2));
    let end = Math.min(lastPage, start + maxButtons - 1);
    if (end - start < maxButtons - 1) start = Math.max(1, end - maxButtons + 1);

    if (start > 1) {
      pag.appendChild(addBtn('1', false, () => loadUsers(1)));
      if (start > 2) { const dots = document.createElement('span'); dots.textContent = '...'; dots.className = 'px-2'; pag.appendChild(dots); }
    }

    for (let p = start; p <= end; p++) {
      const btn = document.createElement('button');
      btn.className = 'px-3 py-1 rounded ' + (p === currentPage ? 'bg-blue-600 text-white' : 'bg-white border');
      btn.textContent = p;
      if (p !== currentPage) btn.addEventListener('click', () => loadUsers(p));
      pag.appendChild(btn);
    }

    if (end < lastPage) {
      if (end < lastPage - 1) { const dots = document.createElement('span'); dots.textContent = '...'; dots.className = 'px-2'; pag.appendChild(dots); }
      pag.appendChild(addBtn(String(lastPage), false, () => loadUsers(lastPage)));
    }

    pag.appendChild(addBtn('Siguiente', currentPage >= lastPage, () => loadUsers(currentPage + 1)));

    if (total !== null) {
      const info = document.createElement('div');
      info.className = 'ml-3 text-sm text-gray-600';
      info.textContent = `Página ${currentPage} de ${lastPage} — Total: ${total}`;
      pag.appendChild(info);
    }

    pagContainer.appendChild(pag);
  }

  function renderAvailableSubcentros(){
    const select = document.getElementById('subcentroSelect');
    if (!select) return;
    const prev = select.value;
    const opts = ['<option value="">-- Selecciona un subcentro --</option>'];
    (ALL_SUBCENTROS||[]).forEach(s => {
      const idNum = Number(s.id);
      if (!assignedSet.has(idNum)) {
        const label = (s.name || '') + (s.centro ? ` (${s.centro})` : '');
        const opt = document.createElement('option');
        opt.value = idNum;
        opt.textContent = label;
        opt.setAttribute('data-centro-id', s.centro_id || '');
        select.appendChild(opt);
      }
    });
    if (prev && !assignedSet.has(Number(prev))) select.value = prev;
  }

  async function openAssignModal(email, name){
    $('#assign_email_user').value = email;
    const titleText = 'Asignar subcentros a ' + (name || email);
    $('#assignUserTitle').textContent = titleText;
    $('#assignUserInfo').innerHTML = email;
    const select = $('#subcentroSelect'); if (select) select.value = '';
    assignedSet = new Set();
    window.__uscState = { bodegaActual: [], bodegaNombre: [] };
    
    try {
      const r = await fetch(`/centros/user_subcentros/list/${encodeURIComponent(email)}`);
      const data = await r.json();
      if (data && Array.isArray(data.assigned)) data.assigned.forEach(id => assignedSet.add(Number(id)));
      window.__uscState.bodegaActual = Array.isArray(data?.bodega_actual) ? data.bodega_actual : (data?.bodega_actual ? [data.bodega_actual] : []);
      window.__uscState.bodegaNombre = Array.isArray(data?.bodega_nombre) ? data.bodega_nombre : (data?.bodega_nombre ? [data.bodega_nombre] : []);
    } catch(e){ console.warn(e); }

    // Mostrar bodegas actuales si existen
    if (window.__uscState.bodegaActual?.length && window.__uscState.bodegaNombre?.length) {
      const bodegaTags = window.__uscState.bodegaNombre.map(b => `<span class="ml-2 px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs">${b}</span>`).join(' ');
      $('#assignUserInfo').innerHTML = email + ' <div class="mt-1">' + bodegaTags + '</div>';
    }

    const addBtn = $('#addSubcentroBtn');
    if (addBtn) {
      addBtn.onclick = function(){
        const sel = $('#subcentroSelect'); if (!sel) return;
        const v = sel.value; if (!v) return;
        
        assignedSet.add(Number(v));
        renderAssignedTable();
        renderAvailableSubcentros();
        sel.value = '';
      };
    }
    renderAssignedTable();
    renderAvailableSubcentros();
    $('#assignModal')?.classList.remove('hidden');
  }

  function renderAssignedTable(){
    const tbody = $('#assignedTbody'); if (!tbody) return;
    tbody.innerHTML = '';
    $$('input[name="subcentro_ids[]"]').forEach(i => i.remove());
    if (!assignedSet || assignedSet.size === 0) {
      tbody.innerHTML = '<tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>';
      renderAvailableSubcentros();
      return;
    }
    const subs = (ALL_SUBCENTROS||[]).filter(s => assignedSet.has(Number(s.id)));
    subs.forEach(s => {
      const tr = document.createElement('tr');
      tr.innerHTML = `<td class="px-2 py-1">${s.name}</td><td class="px-2 py-1">${s.centro}</td><td class="px-2 py-1"><button type="button" class="px-2 py-1 bg-red-500 text-white rounded" onclick="unassignSub(${s.id})">Quitar</button></td>`;
      tbody.appendChild(tr);
      const hidden = document.createElement('input');
      hidden.type = 'hidden'; hidden.name = 'subcentro_ids[]'; hidden.value = s.id;
      $('#assignForm').appendChild(hidden);
    });
    renderAvailableSubcentros();
  }

  function unassignSub(id){
    const sel = $('#subcentroSelect'); if (sel && String(sel.value) === String(id)) sel.value = '';
    assignedSet.delete(Number(id));
    renderAssignedTable();
    renderAvailableSubcentros();
  }

  function bindSearch(){
    const input = $('#usersSearch');
    const clearBtn = $('#usersClearSearch');
    if (input) input.addEventListener('input', debounce(function(e){ userSearch = (e.target.value||'').trim(); loadUsers(1); }, 400));
    if (clearBtn && input) clearBtn.addEventListener('click', function(){ input.value = ''; userSearch = ''; loadUsers(1); });
  }

  function bindLoadCentro(){
    const loadBtn = document.getElementById('loadSubcentrosBtn');
    const centroSelect = document.getElementById('centroSelect');
    if (!loadBtn || !centroSelect) return;
    loadBtn.addEventListener('click', function(e){
      e.preventDefault();
      const cid = centroSelect.value;
      if (!cid) { if (typeof Swal !== 'undefined') Swal.fire({icon:'info', title:'Seleccione centro', text:'Seleccione un centro para cargar sus subcentros.'}); return; }
      const list = (ALL_SUBCENTROS||[]).filter(s => String(s.centro_id) === String(cid));
      if (!list.length) { if (typeof Swal !== 'undefined') Swal.fire({icon:'info', title:'Sin subcentros', text:'El centro seleccionado no tiene subcentros.'}); return; }
      list.forEach(s => assignedSet.add(Number(s.id)));
      renderAssignedTable();
      // reset subcentro select (optional)
      const sel = document.getElementById('subcentroSelect'); if (sel) sel.value = '';
    });
  }

  function bindFormConfirm(){
    const form = $('#assignForm');
    if (!form) return;
    form.addEventListener('submit', function(evt){
      if (form.dataset.confirmed === '1') { delete form.dataset.confirmed; return true; }
      evt.preventDefault();
      if (typeof Swal === 'undefined') { form.submit(); return; }
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
          Swal.fire({ title: 'Guardando...', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
          if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    bindSearch();
    bindLoadCentro();
    bindFormConfirm();
    loadUsers();
  });

  // Expose for inline HTML handlers
  window.openAssignModal = openAssignModal;
  window.unassignSub = unassignSub;

})();
