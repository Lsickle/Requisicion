(function(){
    // Short-circuit if DOM not ready; we will attach at the end
    const q = selector => document.querySelector(selector);
    const qAll = selector => Array.from(document.querySelectorAll(selector));

    // Elements
    const abrirModalBtn = q('#abrirModalBtn');
    const modalProducto = q('#modalProducto');
    const cerrarModalBtn = q('#cerrarModalBtn');
    const productoSelect = q('#productoSelect');
    const cantidadTotalInput = q('#cantidadTotalInput');
    const unidadMedida = q('#unidadMedida');
    const siguienteModalBtn = q('#siguienteModalBtn');

    const modalDistribucion = q('#modalDistribucion');
    const cerrarModalDistribucionBtn = q('#cerrarModalDistribucionBtn');
    const productoSeleccionadoNombre = q('#productoSeleccionadoNombre');
    const productoSeleccionadoCantidad = q('#productoSeleccionadoCantidad');
    const productoSeleccionadoUnidad = q('#productoSeleccionadoUnidad');
    const centroSelect = q('#centroSelect');
    const cantidadCentroInput = q('#cantidadCentroInput');
    const agregarCentroBtn = q('#agregarCentroBtn');
    const centrosList = q('#centrosList');
    const totalAsignadoSpan = q('#totalAsignado');
    const cantidadDisponibleSpan = q('#cantidadDisponible');
    const unidadDisponibleSpan = q('#unidadDisponible');
    const volverModalBtn = q('#volverModalBtn');
    const guardarProductoBtn = q('#guardarProductoBtn');

    const productosTableBody = q('#productosTable tbody');

    // State
    const initialProducts = window.__REQ_EDIT_DATA || [];
    let editingProduct = null; // {id,nombre,cantidadTotal,unidad,centros:[]}

    // Utility
    function openModal(modal){ if(!modal) return; modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden'; }
    function closeModal(modal){ if(!modal) return; modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow=''; }

    function resetProductoModal(){ productoSelect.value=''; cantidadTotalInput.value=''; unidadMedida.textContent = 'Unidad: -'; }

    function renderProductosTable(){
        productosTableBody.innerHTML = '';
        initialProducts.forEach((p, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'border-t';
            tr.innerHTML = `
                <td class="p-3 align-top">${escapeHtml(p.nombre)}</td>
                <td class="p-3 align-top">${p.cantidadTotal}</td>
                <td class="p-3 align-top">${renderCentrosBrief(p.centros)}</td>
                <td class="p-3 align-top text-right">
                    <button data-idx="${idx}" class="editarProducto bg-yellow-500 text-white px-3 py-1 rounded mr-2">Editar</button>
                    <button data-idx="${idx}" class="eliminarProducto bg-red-500 text-white px-3 py-1 rounded">Eliminar</button>
                </td>
            `;
            productosTableBody.appendChild(tr);
        });
    }

    function renderCentrosBrief(centros){
        if(!centros || centros.length===0) return '<em>No hay distribución</em>';
        return '<ul class="list-disc pl-5">' + centros.map(c=>`<li>${escapeHtml(c.nombre)} (${c.cantidad})</li>`).join('') + '</ul>';
    }

    function escapeHtml(s){ if(s === null || s === undefined) return ''; return String(s).replace(/[&<>"'`]/g, function(ch){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#96;'}[ch]; }); }

    // Select product in modal: update unidad display
    productoSelect?.addEventListener('change', function(){
        const opt = productoSelect.selectedOptions[0];
        if(!opt) { unidadMedida.textContent = 'Unidad: -'; return; }
        unidadMedida.textContent = 'Unidad: ' + (opt.dataset.unidad || '-');
    });

    abrirModalBtn?.addEventListener('click', function(){
        resetProductoModal();
        openModal(modalProducto);
    });
    cerrarModalBtn?.addEventListener('click', function(){ closeModal(modalProducto); });

    siguienteModalBtn?.addEventListener('click', function(){
        const pid = productoSelect.value; const cantidad = parseInt(cantidadTotalInput.value || '0',10);
        if(!pid || cantidad <= 0){ Swal.fire({icon:'error', title:'Error', text:'Selecciona producto y cantidad válida.'}); return; }
        const opt = productoSelect.selectedOptions[0];
        editingProduct = { id: parseInt(pid,10), nombre: opt.dataset.nombre || opt.textContent, unidad: opt.dataset.unidad || '', cantidadTotal: cantidad, centros: [] };
        productoSeleccionadoNombre.textContent = editingProduct.nombre;
        productoSeleccionadoCantidad.textContent = editingProduct.cantidadTotal;
        productoSeleccionadoUnidad.textContent = editingProduct.unidad;
        cantidadDisponibleSpan.textContent = editingProduct.cantidadTotal;
        unidadDisponibleSpan.textContent = editingProduct.unidad;
        totalAsignadoSpan.textContent = '0';
        centrosList.innerHTML = '';
        cantidadCentroInput.value='';

        closeModal(modalProducto); openModal(modalDistribucion);
    });

    cerrarModalDistribucionBtn?.addEventListener('click', function(){ closeModal(modalDistribucion); });
    volverModalBtn?.addEventListener('click', function(){ closeModal(modalDistribucion); openModal(modalProducto); });

    agregarCentroBtn?.addEventListener('click', function(){
        const centroId = parseInt(centroSelect.value || '0',10);
        const centroNombre = centroSelect.selectedOptions[0]?.dataset?.nombre || centroSelect.selectedOptions[0]?.textContent || '';
        const cantidad = parseInt(cantidadCentroInput.value || '0',10);
        if(!centroId || cantidad <= 0){ Swal.fire({icon:'error', title:'Error', text:'Selecciona centro y cantidad válida.'}); return; }
        const totalAsignado = editingProduct.centros.reduce((s,c)=>s+c.cantidad,0) + cantidad;
        if(totalAsignado > editingProduct.cantidadTotal){ Swal.fire({icon:'error', title:'Error', text:'La suma excede la cantidad total disponible.'}); return; }
        editingProduct.centros.push({ id: centroId, nombre: centroNombre, cantidad: cantidad });
        renderCentrosList();
        cantidadCentroInput.value='';
    });

    function renderCentrosList(){
        centrosList.innerHTML = '';
        let suma = 0;
        editingProduct.centros.forEach((c, i)=>{
            suma += c.cantidad;
            const li = document.createElement('li');
            li.className = 'flex items-center justify-between py-1';
            li.innerHTML = `<div>${escapeHtml(c.nombre)} (${c.cantidad})</div><div><button data-i="${i}" class="quitarCentro text-sm text-red-600">Quitar</button></div>`;
            centrosList.appendChild(li);
        });
        totalAsignadoSpan.textContent = suma;
        if(suma === editingProduct.cantidadTotal){ totalAsignadoSpan.classList.add('text-green-600'); } else { totalAsignadoSpan.classList.remove('text-green-600'); }
    }

    centrosList?.addEventListener('click', function(e){ const btn = e.target.closest('.quitarCentro'); if(!btn) return; const idx = parseInt(btn.dataset.i,10); if(isNaN(idx)) return; editingProduct.centros.splice(idx,1); renderCentrosList(); });

    guardarProductoBtn?.addEventListener('click', function(){
        if(!editingProduct) return;
        const suma = editingProduct.centros.reduce((s,c)=>s+c.cantidad,0);
        if(suma !== editingProduct.cantidadTotal){ Swal.fire({icon:'error', title:'Error', text:`La suma por centros (${suma}) debe ser igual a la cantidad total (${editingProduct.cantidadTotal}).`}); return; }
        // añadir al listado
        initialProducts.push({ id: editingProduct.id, nombre: editingProduct.nombre, cantidadTotal: editingProduct.cantidadTotal, unidad: editingProduct.unidad, centros: editingProduct.centros.slice() });
        renderProductosTable();
        closeModal(modalDistribucion);
        editingProduct = null;
    });

    // Delegated edit/delete handlers
    productosTableBody?.addEventListener('click', function(e){
        const btnEdit = e.target.closest('.editarProducto');
        const btnDel = e.target.closest('.eliminarProducto');
        if(btnEdit){ const idx = parseInt(btnEdit.dataset.idx,10); if(isNaN(idx)) return; const p = initialProducts[idx]; // load into modal for edit
            editingProduct = { id: p.id, nombre: p.nombre, cantidadTotal: p.cantidadTotal, unidad: p.unidad, centros: JSON.parse(JSON.stringify(p.centros||[])) };
            productoSeleccionadoNombre.textContent = editingProduct.nombre;
            productoSeleccionadoCantidad.textContent = editingProduct.cantidadTotal;
            productoSeleccionadoUnidad.textContent = editingProduct.unidad;
            cantidadDisponibleSpan.textContent = editingProduct.cantidadTotal;
            unidadDisponibleSpan.textContent = editingProduct.unidad;
            totalAsignadoSpan.textContent = editingProduct.centros.reduce((s,c)=>s+c.cantidad,0);
            renderCentrosList(); closeModal(modalProducto); openModal(modalDistribucion);
            return;
        }
        if(btnDel){ const idx = parseInt(btnDel.dataset.idx,10); if(isNaN(idx)) return; Swal.fire({title:'Confirmar', text:'Eliminar producto?', icon:'warning', showCancelButton:true}).then(res=>{ if(res.isConfirmed){ initialProducts.splice(idx,1); renderProductosTable(); } }); return; }
    });

    // Form submit: serialize products into hidden inputs
    const requisicionForm = q('#requisicionForm');
    requisicionForm?.addEventListener('submit', function(e){
        // limpiar inputs previos
        qAll('input[name^="productos"]').forEach(i=>i.remove());
        // validar que hay al menos 1
        if(initialProducts.length === 0){ e.preventDefault(); Swal.fire({icon:'error', title:'Error', text:'Agrega al menos un producto.'}); return; }
        initialProducts.forEach((p, idx)=>{
            const base = `productos[${idx}]`;
            const inpId = document.createElement('input'); inpId.type='hidden'; inpId.name = base + '[id]'; inpId.value = p.id; requisicionForm.appendChild(inpId);
            const inpCant = document.createElement('input'); inpCant.type='hidden'; inpCant.name = base + '[requisicion_amount]'; inpCant.value = p.cantidadTotal; requisicionForm.appendChild(inpCant);
            if(p.proveedorId){ const inpProv = document.createElement('input'); inpProv.type='hidden'; inpProv.name = base + '[proveedor_id]'; inpProv.value = p.proveedorId; requisicionForm.appendChild(inpProv); }
            if(p.centros && p.centros.length){ p.centros.forEach((c, j)=>{
                const inpc = document.createElement('input'); inpc.type='hidden'; inpc.name = base + '[centros]['+j+'][id]'; inpc.value = c.id; requisicionForm.appendChild(inpc);
                const inpc2 = document.createElement('input'); inpc2.type='hidden'; inpc2.name = base + '[centros]['+j+'][cantidad]'; inpc2.value = c.cantidad; requisicionForm.appendChild(inpc2);
            }); }
        });
        // allow submission
    });

    // Initialize dropdowns with initial products available so the selector shows same list
    function populateProductSelect(){
        const existingIds = initialProducts.map(p => p.id);
        // nothing to change: we assume the select already contains server-provided list
    }

    // Inicial render
    document.addEventListener('DOMContentLoaded', function(){ renderProductosTable(); populateProductSelect(); });
})();
