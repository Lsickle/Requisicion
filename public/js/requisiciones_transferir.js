(function(){
    'use strict';

    function toggleModal(id){
        const modal = document.getElementById(id);
        if(!modal) return;
        if(modal.classList.contains('hidden')){
            modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden';
        } else {
            modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto';
        }
    }

    function openTransferModal(id){
        const modal = document.getElementById(`transfer-modal-${id}`);
        if(!modal) return;

        const currentSpan = document.getElementById(`current-owner-${id}`);
        if (!currentSpan || !currentSpan.textContent.trim()){
            const row = document.querySelector(`tr[data-req-id="${id}"]`);
            if (row && row.dataset && row.dataset.ownerName) {
                if (currentSpan) currentSpan.textContent = row.dataset.ownerName;
            }
        }

        const sel = document.getElementById(`new-owner-select-${id}`);
        const confirmBtn = document.getElementById(`confirm-transfer-${id}`);
        if (sel) { sel.innerHTML = '<option value="">Cargando usuarios...</option>'; sel.disabled = true; }
        if (confirmBtn) confirmBtn.disabled = true;

        const cfg = window.ReqTransferConfig || {};
        const usersUrl = cfg.usersUrl || '/requisiciones/usuarios-external';

        fetch(usersUrl, { headers: { 'Accept': 'application/json' } })
            .then(async r => {
                if (!r.ok) throw new Error('Error cargando usuarios');
                const j = await r.json();
                if (!j.ok) throw new Error(j.message || 'Respuesta inválida');
                const users = j.users || [];
                if (sel) {
                    sel.innerHTML = '<option value="">-- Seleccionar usuario --</option>';
                    users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value = u.id;
                        opt.textContent = u.name + (u.email ? ' <' + u.email + '>' : '');
                        sel.appendChild(opt);
                    });
                    sel.disabled = false;
                }
                if (confirmBtn) confirmBtn.disabled = false;
            }).catch(err => {
                console.error('Error fetching users', err);
                if (sel) { sel.innerHTML = '<option value="">No se pudieron cargar usuarios</option>'; sel.disabled = true; }
                if (confirmBtn) confirmBtn.disabled = true;
                try{ if(window.Swal) window.Swal.fire({ icon:'error', title:'Error', text:'No se pudieron cargar usuarios desde el servicio externo.' }); }catch(e){}
            });

        modal.classList.remove('hidden'); modal.classList.add('flex'); document.body.style.overflow='hidden';
    }

    function closeTransferModal(id){
        const modal = document.getElementById(`transfer-modal-${id}`);
        if(!modal) return;
        modal.classList.add('hidden'); modal.classList.remove('flex'); document.body.style.overflow='auto';
    }

    function submitTransfer(id){
        const sel = document.getElementById(`new-owner-select-${id}`);
        if(!sel) return (window.Swal || window.alert)('Selector no encontrado');
        const newUserId = sel.value;
        if(!newUserId) return (window.Swal || window.alert)('Selecciona un usuario');

        const cfg = window.ReqTransferConfig || {};
        const token = cfg.csrfToken || null;

        if(window.Swal){
            window.Swal.fire({
                title: 'Confirmar transferencia',
                text: `¿Deseas transferir la requisición #${id}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, transferir',
                cancelButtonText: 'Cancelar'
            }).then(res => {
                if(!res.isConfirmed) return;
                window.Swal.fire({ title: 'Procesando...', html: 'Realizando transferencia', allowOutsideClick:false, didOpen: ()=>window.Swal.showLoading() });
                doSubmit();
            });
        } else {
            if(!confirm(`¿Deseas transferir la requisición #${id}?`)) return;
            doSubmit();
        }

        function doSubmit(){
            fetch(`/requisiciones/${id}/transferir`, {
                method: 'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': token || '' },
                body: JSON.stringify({ new_user_id: newUserId })
            }).then(async r => {
                let json = {};
                try { json = await r.json(); } catch(e){ json = { success:false, message:'Respuesta inválida' }; }
                if(r.ok && json.success){
                    closeTransferModal(id);
                    try{ if(window.Swal) window.Swal.fire({ icon:'success', title:'Transferido', text: json.message || 'Transferencia exitosa' }).then(()=>location.reload()); else location.reload(); } catch(e){ location.reload(); }
                } else {
                    try{ if(window.Swal) window.Swal.fire({ icon:'error', title:'Error', text: json.message || 'No se pudo completar la transferencia' }); else alert(json.message || 'No se pudo completar la transferencia'); } catch(e){ alert(json.message || 'No se pudo completar la transferencia'); }
                }
            }).catch(()=>{ try{ if(window.Swal) window.Swal.fire({ icon:'error', title:'Error', text:'Error de comunicación' }); else alert('Error de comunicación'); } catch(e){}});
        }
    }

    // Paginación y búsqueda
    function initPagination(){
        const input = document.getElementById('busquedaTransferir');
        const pageSizeSel = document.getElementById('pageSizeSelectTransferir');
        let currentPage = 1;
        let pageSize = parseInt(pageSizeSel?.value || '10', 10) || 10;

        function getMatchedItems(){
            return Array.from(document.querySelectorAll('#tablaTransferir tbody tr')).filter(el => (el.dataset.match ?? '1') !== '0');
        }

        function showPage(page = 1){
            const items = getMatchedItems();
            const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage - 1) * pageSize;
            const end = start + pageSize;

            document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => el.style.display = 'none');
            items.slice(start, end).forEach(el => el.style.display = '');
            renderPagination(totalPages);
        }

        function renderPagination(totalPages){
            const container = document.getElementById('paginationControlsTransferir');
            if(!container) return;
            container.innerHTML = '';
            const start = Math.max(1, currentPage - 2);
            const end = Math.min(totalPages, currentPage + 2);

            const btnPrev = document.createElement('button');
            btnPrev.textContent = 'Anterior';
            btnPrev.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
            btnPrev.disabled = currentPage === 1;
            btnPrev.onclick = () => showPage(currentPage - 1);
            container.appendChild(btnPrev);

            for (let p = start; p <= end; p++){
                const btn = document.createElement('button');
                btn.textContent = p;
                btn.className = 'px-3 py-1 rounded text-sm ' + (p === currentPage ? 'bg-blue-600 text-white' : 'border hover:bg-gray-100');
                btn.onclick = () => showPage(p);
                container.appendChild(btn);
            }

            const btnNext = document.createElement('button');
            btnNext.textContent = 'Siguiente';
            btnNext.className = 'px-3 py-1 border rounded text-sm ' + (currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100');
            btnNext.disabled = currentPage === totalPages;
            btnNext.onclick = () => showPage(currentPage + 1);
            container.appendChild(btnNext);
        }

        document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => el.dataset.match = '1');
        if (pageSizeSel) pageSizeSel.addEventListener('change', (e)=>{ pageSize = parseInt(e.target.value,10)||10; showPage(1); });
        if (input) input.addEventListener('keyup', function(){
            const filtro = this.value.toLowerCase();
            document.querySelectorAll('#tablaTransferir tbody tr').forEach(el => {
                el.dataset.match = el.textContent.toLowerCase().includes(filtro) ? '1' : '0';
            });
            showPage(1);
        });

        showPage(1);
    }

    // export to global
    window.toggleModal = toggleModal;
    window.openTransferModal = openTransferModal;
    window.closeTransferModal = closeTransferModal;
    window.submitTransfer = submitTransfer;

    document.addEventListener('DOMContentLoaded', function(){
        try{ initPagination(); }catch(e){}
    });

})();
