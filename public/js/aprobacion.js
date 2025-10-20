(function(){
  function format2(n){ try{ const v=Number(n||0); return new Intl.NumberFormat('es-CO',{minimumFractionDigits:2,maximumFractionDigits:2}).format(v);}catch(_){const v=Number(n||0); return (Math.round(v*100)/100).toFixed(2);} }

  window.toggleModal = function(id){
    try{
      const modal = document.getElementById(id);
      if(!modal) return;
      if(modal.classList.contains('hidden')){ modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden'; }
      else { modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto'; }
    }catch(e){ console.warn('toggleModal error', e); }
  };

  function recomputeTotals(reqId){
    try{
      const totals = Array.from(document.querySelectorAll(`#modal-${reqId} .total-cell`));
      let sum = 0;
      totals.forEach(el => { const raw=(el.textContent||'').trim().replace(/\./g,'').replace(',','.'); const num=Number(raw); if(!isNaN(num)) sum+=num; });
      const span = document.getElementById(`total-general-${reqId}`); if(span) span.textContent = format2(sum);
    }catch(e){ console.warn('recomputeTotals', e); }
  }

  function initProviderSelections(){
    document.querySelectorAll('.prov-select').forEach(sel => {
      const reqId = sel.dataset.req;
      const prodId = sel.dataset.prod;
      const qty = Number(sel.dataset.qty || 0);
      const setFromOption = (opt)=>{
        const price = Number(opt?.dataset?.price || 0); // en COP
        const precioEl = document.getElementById(`precio-${reqId}-${prodId}`);
        const totalEl = document.getElementById(`total-${reqId}-${prodId}`);
        if (precioEl) precioEl.textContent = format2(price);
        if (totalEl) totalEl.textContent = format2(price * qty);
      };

      const updateVisibleSelection = (opt) => {
        const nameEl = document.getElementById(`selprov-name-${reqId}-${prodId}`);
        const priceEl = document.getElementById(`selprov-price-${reqId}-${prodId}`);
        const text = (opt && (opt.text || '') ) ? String(opt.text) : '';
        const name = text.split(' (')[0] || '';
        const price = Number(opt?.dataset?.price || 0);
        if (nameEl) nameEl.textContent = name || (nameEl.textContent || 'No seleccionado');
        if (priceEl) priceEl.textContent = price ? (format2(price) + ' COP') : '';
      };

      const cur = sel.options[sel.selectedIndex] || null;
      if (cur && (cur.value || '').length) {
        setFromOption(cur);
        updateVisibleSelection(cur);
      } else {
        const first = Array.from(sel.options).find(o => (o.value || '').length && typeof o.dataset?.price !== 'undefined');
        if (first) { sel.value = first.value; setFromOption(first); updateVisibleSelection(first); }
      }

      sel.addEventListener('change', function(){
        const opt = this.options[this.selectedIndex] || { dataset: { price: 0 } };
        setFromOption(opt);
        recomputeTotals(reqId);
        updateVisibleSelection(opt);
      });
    });

    document.querySelectorAll('[id^="total-general-"]').forEach(span => { const reqId = span.id.replace('total-general-',''); recomputeTotals(reqId); });
  }

  window.openProviderChoiceModal = function(reqId, prodId, providers, selectedId){
    const modal = document.getElementById('providerChoiceModal');
    const list = document.getElementById('providerChoiceList');
    if (!modal || !list) return;
    list.innerHTML = '';
    providers.forEach(prov => {
      const item = document.createElement('div'); item.className = 'flex justify-between items-center p-2 border-b';
      const left = document.createElement('div');
      const orig = (prov.price_produc != null) ? `${format2(prov.price_produc)} ${prov.moneda || ''}` : '';
      const cop  = (prov.price_cop != null)    ? `${format2(prov.price_cop)} COP` : '';
      left.innerHTML = `<div class="font-medium">${prov.prov_name || 'Proveedor'}</div><div class="text-sm text-gray-500">Precio: ${orig}${orig && cop ? ' — ' : ''}${cop}</div>`;
      const btn = document.createElement('button'); btn.className='select-prov-btn inline-flex items-center justify-center px-3 py-1 rounded-md bg-green-600 hover:bg-green-700 text-white';
      btn.setAttribute('data-id', prov.id); btn.setAttribute('data-pxp-id', prov.pxp_id); btn.setAttribute('data-req', reqId); btn.setAttribute('data-prod', prodId); btn.textContent = 'Seleccionar';
      btn.addEventListener('click', function(e){
        e.stopPropagation();
        const provId = this.getAttribute('data-id');
        const pxpId = this.getAttribute('data-pxp-id');
        const rId = this.getAttribute('data-req');
        const pId = this.getAttribute('data-prod');
        const hiddenSelect = document.querySelector(`.prov-select[data-req="${rId}"][data-prod="${pId}"]`);
        if (hiddenSelect) {
          let opt = Array.from(hiddenSelect.options).find(o => String(o.value) === String(pxpId));
          if (!opt) {
            opt = document.createElement('option');
            opt.value = String(pxpId);
            opt.dataset.provId = String(provId || '');
             opt.dataset.price = String(prov.price_cop ?? 0);
             opt.dataset.priceOriginal = String(prov.price_produc ?? 0);
             opt.dataset.currencyOriginal = String(prov.moneda || 'COP');
             opt.text = prov.prov_name || 'Proveedor';
             hiddenSelect.appendChild(opt);
          }
          hiddenSelect.value = opt.value;
          hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        // actualizar etiquetas visibles
        const nameEl = document.getElementById(`selprov-name-${rId}-${pId}`);
        const priceEl = document.getElementById(`selprov-price-${rId}-${pId}`);
        if (nameEl) nameEl.textContent = prov.prov_name || 'Seleccionado';
        if (priceEl) priceEl.textContent = (prov.price_cop ? format2(prov.price_cop) + ' COP' : '');
        modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto';
      });
      item.appendChild(left); item.appendChild(btn); list.appendChild(item);
    });
    modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden';
  };

  window.closeProviderChoiceModal = function(){ const modal = document.getElementById('providerChoiceModal'); if (!modal) return; modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto'; };
  window.confirmProviderChoice = function(){ closeProviderChoiceModal(); };

  function csrfToken(){ return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (document.getElementById('csrf_token')?.value || ''); }

  function confirmarCambioEstatus(requisicionId, estatusId, comentario){
    const data = { estatus_id: estatusId, comentario: comentario ?? null };
    try{
      const selects = document.querySelectorAll(`#modal-${requisicionId} .prov-select`);
      if (selects.length > 0) {
        const proveedores = Array.from(selects).map(s => {
          const opt = s.options[s.selectedIndex] || {};
          return {
            producto_id: Number(s.dataset.prod),
            pxp_id: Number(s.value || 0),
            proveedor_id: Number(opt.dataset?.provId || 0),
            price: Number(opt.dataset?.price || 0),
            currency: String(opt.dataset?.currencyOriginal || 'COP')
          };
        });
        data.proveedores = proveedores;
      }
    }catch(e){ console.warn('confirmarCambioEstatus gather providers', e); }

    const swal = window.Swal || null;
    if (swal) swal.fire({ title: 'Procesando...', html: 'Enviando solicitud, por favor espere.', allowOutsideClick: false, didOpen: () => swal.showLoading() });
    fetch(`/requisiciones/${requisicionId}/estatus`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(data)
    }).then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
    .then(res => {
      if (swal) {
        if (res.success) swal.fire('Éxito', res.message || 'Estatus actualizado', 'success').then(()=> location.reload());
        else swal.fire('Error', res.message || 'No se pudo actualizar el estatus', 'error');
      } else {
        if (res.success) location.reload(); else alert(res.message || 'No se pudo actualizar el estatus');
      }
    }).catch(err => { if (swal) swal.fire('Error', 'No se pudo actualizar el estatus: ' + err.message, 'error'); else alert('Error: '+err.message); });
  }

  function bindStatusButtons(){
    document.querySelectorAll('.status-btn').forEach(btn => {
      btn.addEventListener('click', function(){
        const requisicionId = this.dataset.id;
        const estatusId = parseInt(this.dataset.estatus);
        const accion = this.dataset.action;
        const requiresProviders = this.dataset.requiresProviders === '1';
        const estatusActual = parseInt(this.dataset.estatusActual || this.dataset.estatus_actual || '0');
        const swal = window.Swal || null;

        if (accion === 'rechazar'){
          if (swal) {
            swal.fire({ title: 'Motivo de rechazo (opcional)', input: 'textarea', inputPlaceholder: 'Escribe el motivo...', showCancelButton: true, confirmButtonText: 'Rechazar', cancelButtonText: 'Cancelar', confirmButtonColor: '#dc2626' })
            .then(r => { if (!r.isConfirmed) return; const comentario = (r.value || '').trim(); if (!comentario) { swal.fire({ title: 'Enviar rechazo sin comentario', text: '¿Deseas continuar sin comentario?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, rechazar' }).then(c => { if (c.isConfirmed) confirmarCambioEstatus(requisicionId, estatusId, null); }); } else confirmarCambioEstatus(requisicionId, estatusId, comentario); });
          } else { const comentario = prompt('Motivo (opcional):'); confirmarCambioEstatus(requisicionId, estatusId, comentario || null); }
          return;
        }

        if (requiresProviders && estatusActual === 1) {
          const selects = Array.from(document.querySelectorAll(`#modal-${requisicionId} .prov-select`));
          const faltantes = selects.filter(s => !s.value);
          if (faltantes.length > 0) { if (swal) swal.fire({ icon: 'warning', title: 'Seleccione proveedores', text: 'Debe seleccionar un proveedor por cada producto antes de aprobar.'}); else alert('Seleccione proveedores por cada producto.'); return; }
        }

        if (swal) swal.fire({ title: `¿Seguro que deseas aprobar la requisición #${requisicionId}?`, icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, aprobar', cancelButtonText: 'Cancelar', confirmButtonColor: '#16a34a' }).then(r => { if (r.isConfirmed) confirmarCambioEstatus(requisicionId, estatusId, null); });
        else if (confirm('¿Aprobar requisición?')) confirmarCambioEstatus(requisicionId, estatusId, null);
      });
    });
  }

  function setupPaginationAndSearch(){
    const input = document.getElementById('busquedaAprob');
    const pageSizeSel = document.getElementById('pageSizeSelectAprob');
    let currentPage = 1; let pageSize = parseInt(pageSizeSel?.value || '10', 10) || 10;
    function getMatched(){ return Array.from(document.querySelectorAll('.aprob-item')).filter(el => (el.dataset.match ?? '1') !== '0'); }
    function render(totalPages){
      const container = document.getElementById('paginationControlsAprob'); if (!container) return; container.innerHTML='';
      const start = Math.max(1, currentPage - 2); const end = Math.min(totalPages, currentPage + 2);
      const btnPrev = document.createElement('button'); btnPrev.textContent='Anterior'; btnPrev.className='px-3 py-1 border rounded text-sm ' + (currentPage===1? 'opacity-50 cursor-not-allowed':'hover:bg-gray-100'); btnPrev.disabled = currentPage===1; btnPrev.onclick = () => showPage(currentPage-1); container.appendChild(btnPrev);
      for(let p=start;p<=end;p++){ const btn=document.createElement('button'); btn.textContent=String(p); btn.className='px-3 py-1 rounded text-sm ' + (p===currentPage? 'bg-blue-600 text-white':'border hover:bg-gray-100'); btn.onclick=()=>showPage(p); container.appendChild(btn); }
      const btnNext = document.createElement('button'); btnNext.textContent='Siguiente'; btnNext.className='px-3 py-1 border rounded text-sm ' + (currentPage===totalPages? 'opacity-50 cursor-not-allowed':'hover:bg-gray-100'); btnNext.disabled = currentPage===totalPages; btnNext.onclick = () => showPage(currentPage+1); container.appendChild(btnNext);
    }
    function showPage(page){ const items=getMatched(); const totalPages=Math.max(1, Math.ceil(items.length / pageSize)); currentPage = Math.min(Math.max(1, page||1), totalPages); const start=(currentPage-1)*pageSize; const end=start+pageSize; document.querySelectorAll('.aprob-item').forEach(el => el.style.display='none'); items.slice(start,end).forEach(el => el.style.display=''); render(totalPages); const info=document.getElementById('paginationInfoAprob'); if(info){ const total=items.length; const showing=Math.min(end, total); info.textContent=`Mostrando ${showing} de ${total}`; } }
    if (pageSizeSel) pageSizeSel.addEventListener('change', e => { pageSize = parseInt(e.target.value,10)||10; showPage(1); });
    if (input) input.addEventListener('keyup', function(){ const filtro=this.value.toLowerCase(); document.querySelectorAll('.aprob-item').forEach(el => { el.dataset.match = el.textContent.toLowerCase().includes(filtro) ? '1' : '0'; }); showPage(1); });
    document.querySelectorAll('.aprob-item').forEach(el => el.dataset.match='1');
    showPage(1);
  }

  document.addEventListener('click', function(e){
    const btn = e.target.closest && e.target.closest('.open-prov-modal-btn');
    if (!btn) return;
    e.preventDefault();
    let providers = [];
    try { providers = JSON.parse(btn.getAttribute('data-providers') || '[]'); } catch(err) { providers = []; }
    const reqId = btn.getAttribute('data-req');
    const prodId = btn.getAttribute('data-prod');
    const selectedId = btn.getAttribute('data-selected') || null;
    window.openProviderChoiceModal(reqId, prodId, providers, selectedId);
  });

  document.addEventListener('DOMContentLoaded', function(){
    try{ initProviderSelections(); }catch(e){ console.warn(e); }
    try{ bindStatusButtons(); }catch(e){ console.warn(e); }
    try{ setupPaginationAndSearch(); }catch(e){ console.warn(e); }
  });
})();