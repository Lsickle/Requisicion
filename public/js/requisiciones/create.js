(function(){
  'use strict';

  document.addEventListener('DOMContentLoaded', function() {
    const $ = (s) => document.querySelector(s);

    // Globals expuestos por la vista
    const PREFILL_DATA = window.PREFILL_DATA || null;
    const PRODUCTOS_DATA = Array.isArray(window.PRODUCTOS_DATA) ? window.PRODUCTOS_DATA : [];
    const IS_REREQUEST = !!window.IS_REREQUEST;

    // Modales y elementos principales
    const modalProducto = $('#modalProducto');
    const modalDistribucion = $('#modalDistribucion');
    const cargandoAlert = $('#cargandoAlert');

    // Botones
    const abrirBtn = $('#abrirModalBtn');
    const cerrarBtn = $('#cerrarModalBtn');
    const cerrarDistribucionBtn = $('#cerrarModalDistribucionBtn');
    const siguienteBtn = $('#siguienteModalBtn');
    const volverBtn = $('#volverModalBtn');
    const agregarCentroBtn = $('#agregarCentroBtn');
    const guardarProductoBtn = $('#guardarProductoBtn');
    const submitBtn = $('#submitBtn');

    // Form y campos
    const requisicionForm = $('#requisicionForm');
    const productoSelect = $('#productoSelect');
    const cantidadTotalInput = $('#cantidadTotalInput');
    const categoriaFilter = $('#categoriaFilter');
    const productosListDiv = document.getElementById('productosList');
    const categoriasListDiv = document.getElementById('categoriasList');

    const productoSeleccionadoNombre = $('#productoSeleccionadoNombre');
    const productoSeleccionadoCantidad = $('#productoSeleccionadoCantidad');
    const productoSeleccionadoUnidad = $('#productoSeleccionadoUnidad');
    const cantidadDisponibleSpan = $('#cantidadDisponible');
    const unidadDisponibleSpan = $('#unidadDisponible');
    const totalAsignadoSpan = $('#totalAsignado');
    const unidadMedidaSpan = $('#unidadMedida');

    // Centros
    const centrosDropdown = $('#centrosDropdown');
    const centroFilter = $('#centroFilter');
    const centroSelect = $('#centroSelect');
    const cantidadCentroInput = $('#cantidadCentroInput');
    const centrosList = $('#centrosList');

    // Tabla
    const productosTable = document.querySelector('#productosTable tbody');

    // Operación
    const operacionFilter = document.getElementById('operacionFilter');
    const operacionesDropdown = document.getElementById('operacionesDropdown');
    const operacionSelectHidden = document.getElementById('operacionSelect');

    // Estado
    let productos = [];
    let productoActual = null;
    let cantidadTotal = 0;
    let cantidadAsignada = 0;
    let unidadMedida = '';
    let editIndex = null; // índice del producto editado
    let productoEsServicio = false; // indica si el producto seleccionado es servicio/alquiler

    // Utilidades
    function mostrarError(msg){
      window.Swal && Swal.fire({icon:'error', title:'Error', text: msg, confirmButtonText:'Entendido'});
    }
    function mostrarCarga(){ if (cargandoAlert) cargandoAlert.classList.remove('hidden'); }
    function ocultarCarga(){ if (cargandoAlert) cargandoAlert.classList.add('hidden'); }

    // Dropdown handlers attach
    function attachOptionHandlers(){
      if (categoriasListDiv) {
        categoriasListDiv.querySelectorAll('div').forEach(div => {
          if (div._handler) div.removeEventListener('mousedown', div._handler);
          const handler = function(e){ e.preventDefault(); window.seleccionarOpcion && window.seleccionarOpcion(e, div, 'categoriaFilter'); };
          div._handler = handler; div.addEventListener('mousedown', handler);
        });
      }
      if (productosListDiv) {
        productosListDiv.querySelectorAll('div').forEach(div => {
          if (div._handlerProd) div.removeEventListener('mousedown', div._handlerProd);
          const handler = function(e){ e.preventDefault(); window.seleccionarOpcion && window.seleccionarOpcion(e, div, 'productoSelect'); };
          div._handlerProd = handler; div.addEventListener('mousedown', handler);
        });
      }
      if (centrosDropdown) {
        centrosDropdown.querySelectorAll('div').forEach(div => {
          if (div._handlerCentro) div.removeEventListener('mousedown', div._handlerCentro);
          const handler = function(e){ e.preventDefault(); window.seleccionarCentro && window.seleccionarCentro(e, div); };
          div._handlerCentro = handler; div.addEventListener('mousedown', handler);
        });
      }
    }
    attachOptionHandlers();

    function filtrarDropdown(input, listId){
      const dropdown = document.getElementById(listId);
      if (!dropdown || !input) return;
      const filtro = (input.value || '').toLowerCase();
      let hay = false;
      dropdown.querySelectorAll('div').forEach(op => {
        const txt = (op.textContent||'').toLowerCase();
        if (txt.includes(filtro)) { op.style.display = 'block'; hay = true; }
        else { op.style.display = 'none'; }
      });
      dropdown.style.display = hay ? 'block' : 'none';
    }

    function filtrarProductosPorCategoria(){
      const categoriaSeleccionada = (categoriaFilter?.value || '').trim();
      const texto = (productoSelect?.value || '').toLowerCase();
      if (!productosListDiv) return;
      let hay = false;
      productosListDiv.querySelectorAll('div').forEach(item => {
        const cat = (item.getAttribute('data-categoria')||'').toString();
        const txt = (item.textContent||'').toLowerCase();
        const matchesCat = !categoriaSeleccionada || cat === categoriaSeleccionada;
        const matchesText = txt.includes(texto);
        if (matchesCat && matchesText) { item.style.display = 'block'; hay = true; }
        else { item.style.display = 'none'; }
      });
      if (document.activeElement === productoSelect) {
        productosListDiv.style.display = hay ? 'block' : 'none';
      } else {
        productosListDiv.style.display = 'none';
      }
    }

    // Exponer funciones globales usadas en onclick inline del HTML
    window.seleccionarOpcion = function(e, element, inputId){
      if (e) { e.preventDefault && e.preventDefault(); e.stopPropagation && e.stopPropagation(); }
      const input = document.getElementById(inputId);
      if (!input || !element) return;
      input.value = element.textContent.trim();
      if (inputId === 'productoSelect'){
        input.dataset.id = element.getAttribute('data-id') || '';
        input.dataset.nombre = element.getAttribute('data-nombre') || '';
        input.dataset.proveedor = element.getAttribute('data-proveedor') || '';
        input.dataset.categoria = element.getAttribute('data-categoria') || '';
        input.dataset.unidad = element.getAttribute('data-unidad') || '';
        if (unidadMedidaSpan) unidadMedidaSpan.textContent = input.dataset.unidad ? ('Unidad: ' + input.dataset.unidad) : 'Unidad: -';
        
        // Determinar si es servicio/alquiler
        const cat = (element.getAttribute('data-categoria') || '').toLowerCase();
        productoEsServicio = cat.includes('servicio') || cat.includes('alquiler');
      }
      if (inputId === 'categoriaFilter') filtrarProductosPorCategoria();
      if (element.parentElement) element.parentElement.style.display = 'none';
      input.focus();
    };

    window.seleccionarCentro = function(e, element){
      if (e) { e.preventDefault && e.preventDefault(); e.stopPropagation && e.stopPropagation(); }
      const id = element.getAttribute('data-id');
      const nombre = element.getAttribute('data-nombre') || element.textContent.trim();
      if (centroSelect) centroSelect.value = id;
      if (centroFilter) centroFilter.value = nombre;
      if (centrosDropdown) centrosDropdown.style.display = 'none';
      centroFilter && centroFilter.focus();
    };

    window.seleccionarOperacion = function(e, el){
      if (e) { e.preventDefault && e.preventDefault(); e.stopPropagation && e.stopPropagation(); }
      const val = el.getAttribute('data-value');
      if (operacionFilter) operacionFilter.value = val;
      if (operacionSelectHidden) operacionSelectHidden.value = val;
      operacionesDropdown && operacionesDropdown.classList.add('hidden');
      operacionFilter && operacionFilter.focus();
    };

    // Mostrar dropdowns al enfocar / escribir
    categoriaFilter && categoriaFilter.addEventListener('focus', function(){ filtrarDropdown(this, 'categoriasList'); });
    categoriaFilter && categoriaFilter.addEventListener('input', function(){ categoriasListDiv && (categoriasListDiv.style.display = 'block'); filtrarDropdown(this, 'categoriasList'); filtrarProductosPorCategoria(); });
    categoriaFilter && categoriaFilter.addEventListener('keydown', function(evt){ if (evt.key==='Backspace'||evt.key==='Delete'){ categoriasListDiv && (categoriasListDiv.style.display='block'); setTimeout(()=> filtrarDropdown(this, 'categoriasList'),0); filtrarProductosPorCategoria(); }});
    categoriaFilter && categoriaFilter.addEventListener('keyup', function(){ if ((this.value||'').trim()===''){ categoriasListDiv && (categoriasListDiv.style.display='block'); filtrarDropdown(this,'categoriasList'); filtrarProductosPorCategoria(); }});

    productoSelect && productoSelect.addEventListener('focus', function(){ filtrarDropdown(this, 'productosList'); filtrarProductosPorCategoria(); });
    productoSelect && productoSelect.addEventListener('input', function(){ productosListDiv && (productosListDiv.style.display = 'block'); filtrarProductosPorCategoria(); });
    productoSelect && productoSelect.addEventListener('keyup', function(){ if ((this.value||'').trim()===''){ productosListDiv && (productosListDiv.style.display='block'); filtrarProductosPorCategoria(); }});

    centroFilter && centroFilter.addEventListener('focus', function(){ centrosDropdown && (centrosDropdown.style.display='block'); });
    centroFilter && centroFilter.addEventListener('input', function(){
      const filtro = (this.value||'').toLowerCase();
      let any=false; if (!centrosDropdown) return;
      centrosDropdown.querySelectorAll('div').forEach(div=>{ const txt=(div.textContent||'').toLowerCase(); if (txt.includes(filtro)){div.style.display='block'; any=true;} else {div.style.display='none';} });
      centrosDropdown.style.display = any ? 'block' : 'none';
    });
    centroFilter && centroFilter.addEventListener('keydown', function(evt){ if (evt.key==='Backspace'||evt.key==='Delete'){ setTimeout(()=> centroFilter.dispatchEvent(new Event('input')),0); }});

    // Click fuera para cerrar dropdowns
    document.addEventListener('click', function(e){
      if (categoriasListDiv && !categoriasListDiv.contains(e.target) && !categoriaFilter.contains(e.target)) categoriasListDiv.style.display = 'none';
      if (productosListDiv && !productosListDiv.contains(e.target) && !productoSelect.contains(e.target)) productosListDiv.style.display = 'none';
      if (centrosDropdown && !centrosDropdown.contains(e.target) && !centroFilter.contains(e.target)) centrosDropdown.style.display = 'none';
      if (operacionesDropdown && !operacionesDropdown.contains(e.target) && !operacionFilter.contains(e.target)) operacionesDropdown.classList.add('hidden');
    });

    // Operación: abrir/filtrar
    if (operacionFilter) {
      operacionFilter.addEventListener('focus', ()=>{ filtrarOperaciones(); operacionesDropdown && operacionesDropdown.classList.remove('hidden'); });
      operacionFilter.addEventListener('input', filtrarOperaciones);
      operacionFilter.addEventListener('keydown', (e)=>{ if (e.key==='Backspace'||e.key==='Delete'){ setTimeout(filtrarOperaciones,0); } });
    }
    function filtrarOperaciones(){
      if (!operacionFilter || !operacionesDropdown) return;
      const filtro = (operacionFilter.value||'').toLowerCase();
      let any=false;
      operacionesDropdown.querySelectorAll('div[data-value]').forEach(div=>{
        const t=(div.textContent||'').toLowerCase(); if (!filtro || t.includes(filtro)){ div.style.display='block'; any=true; } else { div.style.display='none'; }
      });
      if (!any) operacionesDropdown.classList.add('hidden'); else operacionesDropdown.classList.remove('hidden');
    }

    // Modales
    abrirBtn && abrirBtn.addEventListener('click', ()=>{ modalProducto && modalProducto.classList.remove('hidden'); resetModalProducto(); attachOptionHandlers(); });
    cerrarBtn && cerrarBtn.addEventListener('click', ()=>{ modalProducto && modalProducto.classList.add('hidden'); resetModalProducto(); });
    cerrarDistribucionBtn && cerrarDistribucionBtn.addEventListener('click', ()=>{ modalDistribucion && modalDistribucion.classList.add('hidden'); resetModalDistribucion(); });

    volverBtn && volverBtn.addEventListener('click', ()=>{ modalDistribucion && modalDistribucion.classList.add('hidden'); modalProducto && modalProducto.classList.remove('hidden'); resetModalDistribucion(); });

    siguienteBtn && siguienteBtn.addEventListener('click', ()=>{
      const productoTexto = (productoSelect?.value || '').trim();
      const skuMatch = productoTexto.match(/\(([A-Za-z0-9-]+)\)/);
      const skuOrId = skuMatch ? skuMatch[1] : null;
      const parsedIdMatch = productoTexto.match(/\d+(?=\))/);
      const parsedId = parsedIdMatch ? parseInt(parsedIdMatch[0],10) : NaN;
      const prodSeleccionado = PRODUCTOS_DATA.find(p => {
        if (skuOrId && p.sku && p.sku === skuOrId) return true;
        if (skuOrId && !isNaN(parseInt(skuOrId)) && p.id === parseInt(skuOrId)) return true;
        if (!isNaN(parsedId) && p.id === parsedId) return true;
        if (p.display === productoTexto) return true;
        return ((p.nombre + ' (' + p.unidad + ')') === productoTexto);
      });
      cantidadTotal = parseInt(cantidadTotalInput?.value, 10);
      if (!prodSeleccionado) return mostrarError('Debes seleccionar un producto.');
      if (!cantidadTotal || cantidadTotal < 1) return mostrarError('Debes ingresar una cantidad válida.');
      if (productos.some(p => p.id === prodSeleccionado.id)) return mostrarError('Este producto ya fue agregado.');
      
      // Determinar si es servicio/alquiler basado en la categoría
      const cat = (prodSeleccionado.categoria || '').toLowerCase();
      productoEsServicio = cat.includes('servicio') || cat.includes('alquiler');
      
      // Mostrar/ocultar campos de observación y plan de ejecución según el tipo de producto
      const observacionSection = document.getElementById('observacionServicioSection');
      const observacionInput = document.getElementById('observacionServicio');
      const planEjecucionSection = document.getElementById('planEjecucionSection');
      const planEjecucionInput = document.getElementById('planEjecucion');
      
      if (observacionSection && planEjecucionSection) {
        if (productoEsServicio) {
          observacionSection.classList.remove('hidden');
          planEjecucionSection.classList.remove('hidden');
        } else {
          observacionSection.classList.add('hidden');
          planEjecucionSection.classList.add('hidden');
          if (observacionInput) observacionInput.value = '';
          if (planEjecucionInput) planEjecucionInput.value = '';
        }
      }
      
      productoActual = { id: prodSeleccionado.id, nombre: prodSeleccionado.nombre, proveedorId: prodSeleccionado.proveedor || null, cantidadTotal, unidad: prodSeleccionado.unidad, centros: [], esServicio: productoEsServicio, observacion: '', planEjecucion: '' };
      cantidadAsignada = 0; unidadMedida = prodSeleccionado.unidad;
      if (productoSeleccionadoNombre) productoSeleccionadoNombre.textContent = prodSeleccionado.nombre;
      if (productoSeleccionadoCantidad) productoSeleccionadoCantidad.textContent = cantidadTotal;
      if (productoSeleccionadoUnidad) productoSeleccionadoUnidad.textContent = prodSeleccionado.unidad;
      if (cantidadDisponibleSpan) cantidadDisponibleSpan.textContent = cantidadTotal;
      if (unidadDisponibleSpan) unidadDisponibleSpan.textContent = prodSeleccionado.unidad;
      if (totalAsignadoSpan) totalAsignadoSpan.textContent = '0';
      if (centrosList) centrosList.innerHTML = '';
      modalProducto && modalProducto.classList.add('hidden');
      modalDistribucion && modalDistribucion.classList.remove('hidden');
      attachOptionHandlers();
    });

    function resetModalProducto(){
      if (productoSelect) productoSelect.value='';
      if (cantidadTotalInput) cantidadTotalInput.value='';
      if (categoriaFilter) categoriaFilter.value='';
      if (unidadMedidaSpan) unidadMedidaSpan.textContent = 'Unidad: -';
    }
    function resetModalDistribucion(){
      if (centroSelect) centroSelect.value='';
      if (cantidadCentroInput) cantidadCentroInput.value='';
      if (centrosDropdown) centrosDropdown.style.display='none';
      if (centroFilter) centroFilter.value='';
      // Limpiar campos de observación y plan de ejecución de servicio
      const observacionSection = document.getElementById('observacionServicioSection');
      const observacionInput = document.getElementById('observacionServicio');
      const planEjecucionSection = document.getElementById('planEjecucionSection');
      const planEjecucionInput = document.getElementById('planEjecucion');
      if (observacionSection) observacionSection.classList.add('hidden');
      if (observacionInput) observacionInput.value = '';
      if (planEjecucionSection) planEjecucionSection.classList.add('hidden');
      if (planEjecucionInput) planEjecucionInput.value = '';
      productoActual = null; cantidadTotal = 0; cantidadAsignada = 0; unidadMedida = ''; editIndex = null; productoEsServicio = false;
    }

    function actualizarResumen(){ if (totalAsignadoSpan) totalAsignadoSpan.textContent = String(cantidadAsignada); }

    function renderCentrosList(){
      if (!centrosList || !productoActual) return;
      centrosList.innerHTML = '';
      const arr = Array.isArray(productoActual.centros) ? productoActual.centros : [];
      cantidadAsignada = arr.reduce((a,c)=> a + (parseInt(c.cantidad,10)||0), 0);
      actualizarResumen();
      arr.forEach(c => {
        const li = document.createElement('li');
        li.className = 'py-2 px-3 flex justify-between items-center';
        li.dataset.id = String(c.id);
        li.innerHTML = '<span>'+c.nombre+'</span>'+
          '<div class="flex items-center gap-2">'+
          '<span class="font-semibold mr-2">'+(parseInt(c.cantidad,10)||0)+' '+unidadMedida+'</span>'+
          '<button type="button" data-action="edit" data-id="'+c.id+'" class="px-2 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">Editar</button>'+
          '<button type="button" data-action="remove" data-id="'+c.id+'" class="px-2 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700">Quitar</button>'+
          '</div>';
        centrosList.appendChild(li);
      });
    }

    centrosList && centrosList.addEventListener('click', async (ev)=>{
      const btn = ev.target.closest('button[data-action]');
      if (!btn || !productoActual) return;
      const id = String(btn.getAttribute('data-id')||'');
      const idx = productoActual.centros.findIndex(c => String(c.id) === id);
      if (idx < 0) return;
      const action = btn.getAttribute('data-action');
      if (action === 'remove') {
        productoActual.centros.splice(idx,1);
        renderCentrosList();
      } else if (action === 'edit') {
        const c = productoActual.centros[idx];
        const cur = parseInt(c.cantidad,10)||0;
        const res = await (window.Swal && Swal.fire({ title:'Editar cantidad', input:'number', inputValue: cur, inputAttributes:{ min:0 }, showCancelButton:true, confirmButtonText:'Guardar', cancelButtonText:'Cancelar' }));
        if (!res || !res.isConfirmed) return;
        let nuevo = parseInt(res.value,10); if (isNaN(nuevo)||nuevo<0) nuevo=0;
        const totalSin = (cantidadAsignada - cur);
        if (totalSin + nuevo > cantidadTotal) {
          const maxP = Math.max(0, cantidadTotal - totalSin);
          return Swal && Swal.fire({icon:'error', title:'Excede la cantidad total', text:'Máximo permitido: '+maxP+' '+unidadMedida});
        }
        if (nuevo === 0) { productoActual.centros.splice(idx,1); } else { productoActual.centros[idx].cantidad = nuevo; }
        renderCentrosList();
      }
    });

    function actualizarTabla(){
      if (!productosTable) return;
      productosTable.innerHTML = '';
      productos.forEach((prod, i) => {
        let centrosHTML = '';
        prod.centros.forEach((centro, j) => {
          centrosHTML += '\n<span class="inline-block bg-gray-200 px-2 py-1 rounded-full text-xs mr-1 mb-1">'+
            centro.nombre+' <b>('+(centro.cantidad)+' '+prod.unidad+')</b></span>'+
            '\n<input type="hidden" name="productos['+i+'][centros]['+j+'][id]" value="'+centro.id+'">'+
            '\n<input type="hidden" name="productos['+i+'][centros]['+j+'][cantidad]" value="'+centro.cantidad+'">';
        });
        
        // Mostrar observación y plan de ejecución si es servicio
        let observacionHTML = '';
        if (prod.esServicio) {
          let detalles = '';
          if (prod.observacion) {
            detalles += '<div><b>Detalle:</b> ' + prod.observacion + '</div>';
          }
          if (prod.planEjecucion) {
            detalles += '<div><b>Plan:</b> ' + prod.planEjecucion + '</div>';
          }
          observacionHTML = '\n<div class="text-xs text-gray-500 mt-1">' + detalles + '</div>'+
            '\n<input type="hidden" name="productos['+i+'][observacion_servicio]" value="'+prod.observacion+'">'+
            '\n<input type="hidden" name="productos['+i+'][plan_ejecucion]" value="'+prod.planEjecucion+'">';
        } else {
          observacionHTML = '\n<input type="hidden" name="productos['+i+'][observacion_servicio]" value="">'+
            '\n<input type="hidden" name="productos['+i+'][plan_ejecucion]" value="">';
        }
        
        const tr = document.createElement('tr');
        tr.innerHTML = ''+
          '<td class="p-3">'+
          prod.nombre+' ('+prod.unidad+')'+
          observacionHTML+
          '\n<input type="hidden" name="productos['+i+'][id]" value="'+prod.id+'">'+
          (prod.proveedorId ? ('\n<input type="hidden" name="productos['+i+'][proveedor_id]" value="'+prod.proveedorId+'">') : '')+
          '\n<input type="hidden" name="productos['+i+'][unidad]" value="'+prod.unidad+'">'+
          (prod.esServicio ? '\n<input type="hidden" name="productos['+i+'][es_servicio]" value="1">' : '\n<input type="hidden" name="productos['+i+'][es_servicio]" value="0">')+
          '</td>'+
          '<td class="p-3">'+
          prod.cantidadTotal+' '+prod.unidad+
          '\n<input type="hidden" name="productos['+i+'][requisicion_amount]" value="'+prod.cantidadTotal+'">'+
          '</td>'+
          '<td class="p-3">'+centrosHTML+'</td>'+
          '<td class="p-3 text-right">'+
          (IS_REREQUEST ? ('<button type="button" onclick="editarProducto('+i+')" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-sm mr-2">Editar</button>') : '')+
          '<button type="button" onclick="eliminarProducto('+i+')" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">Eliminar</button>'+
          '</td>';
        productosTable.appendChild(tr);
      });
    }

    window.eliminarProducto = function(index){ productos.splice(index,1); actualizarTabla(); };

    window.editarProducto = function(index){
      const base = productos[index];
      if (!base) return mostrarError('Producto no encontrado');
      editIndex = index;
      productoActual = JSON.parse(JSON.stringify(base));
      cantidadTotal = parseInt(productoActual.cantidadTotal || 0,10) || 0;
      unidadMedida = productoActual.unidad || '';
      cantidadAsignada = (Array.isArray(productoActual.centros) ? productoActual.centros.reduce((a,c)=> a + (parseInt(c.cantidad,10)||0), 0) : 0);
      if (productoSeleccionadoNombre) productoSeleccionadoNombre.textContent = productoActual.nombre || '';
      if (productoSeleccionadoCantidad) productoSeleccionadoCantidad.textContent = cantidadTotal;
      if (productoSeleccionadoUnidad) productoSeleccionadoUnidad.textContent = unidadMedida;
      if (cantidadDisponibleSpan) cantidadDisponibleSpan.textContent = cantidadTotal;
      if (unidadDisponibleSpan) unidadDisponibleSpan.textContent = unidadMedida;
      if (totalAsignadoSpan) totalAsignadoSpan.textContent = cantidadAsignada;
      renderCentrosList();
      modalProducto && modalProducto.classList.add('hidden');
      modalDistribucion && modalDistribucion.classList.remove('hidden');
    };

    agregarCentroBtn && agregarCentroBtn.addEventListener('click', ()=>{
      if (!productoActual) return;
      const centroIdVal = centroSelect?.value;
      const centroNombre = centroFilter?.value;
      const cantidadCentro = parseInt(cantidadCentroInput?.value, 10);
      const cantidadRestante = cantidadTotal - cantidadAsignada;
      if (!centroIdVal) return mostrarError('Debes seleccionar un centro de costo.');
      if (!cantidadCentro || cantidadCentro < 1) return mostrarError('Debes ingresar una cantidad válida.');
      if (cantidadCentro > cantidadRestante) return mostrarError('No puedes asignar más de '+cantidadRestante+' '+unidadMedida+'.');
      const idx = productoActual.centros.findIndex(c => String(c.id) === String(centroIdVal));
      if (idx >= 0) { productoActual.centros[idx].cantidad += cantidadCentro; }
      else { productoActual.centros.push({ id: String(centroIdVal), nombre: centroNombre, cantidad: cantidadCentro }); }
      renderCentrosList();
      if (cantidadCentroInput) cantidadCentroInput.value='';
      if (centroSelect) centroSelect.value='';
      if (centroFilter) centroFilter.value='';
    });

    guardarProductoBtn && guardarProductoBtn.addEventListener('click', ()=>{
      // Validar observación y plan de ejecución si es servicio/alquiler
      if (productoActual && productoActual.esServicio) {
        const observacionInput = document.getElementById('observacionServicio');
        const observacion = (observacionInput?.value || '').trim();
        if (!observacion) {
          return mostrarError('La descripción del servicio es obligatoria.');
        }
        
        const planEjecucionInput = document.getElementById('planEjecucion');
        const planEjecucion = (planEjecucionInput?.value || '').trim();
        if (!planEjecucion) {
          return mostrarError('El plan de ejecución es obligatorio para servicios/alquiler.');
        }
        
        productoActual.observacion = observacion;
        productoActual.planEjecucion = planEjecucion;
      }
      if (!productoActual || !Array.isArray(productoActual.centros) || productoActual.centros.length === 0) return mostrarError('Debes añadir al menos un centro de costo.');
      if (cantidadAsignada !== cantidadTotal) return mostrarError('Debes distribuir toda la cantidad ('+(cantidadTotal-cantidadAsignada)+' '+unidadMedida+' restantes).');
      if (editIndex !== null) { productos[editIndex] = JSON.parse(JSON.stringify(productoActual)); }
      else { productos.push(productoActual); }
      actualizarTabla();
      modalDistribucion && modalDistribucion.classList.add('hidden');
      resetModalDistribucion();
      resetModalProducto();
      editIndex = null;
    });

    // Validación y envío
    requisicionForm && requisicionForm.addEventListener('submit', function(e){
      if (!operacionSelectHidden || !(operacionSelectHidden.value||'').trim()){
        e.preventDefault();
        return window.Swal && Swal.fire({icon:'error', title:'Operación requerida', text:'Debe seleccionar una operación.'}).then(()=>{ operacionFilter && operacionFilter.focus(); });
      }
      if (!productos || productos.length === 0){ e.preventDefault(); return mostrarError('Debes agregar al menos un producto.'); }
      mostrarCarga();
    });

    // Precarga desde ?from
    (function aplicarPrefill(){
      if (!PREFILL_DATA) return;
      try {
        if (operacionFilter && operacionSelectHidden && PREFILL_DATA.operacion_user){ operacionFilter.value = PREFILL_DATA.operacion_user; operacionSelectHidden.value = PREFILL_DATA.operacion_user; }
        const selRec = document.querySelector('select[name="Recobrable"]'); if (selRec && PREFILL_DATA.Recobrable) selRec.value = PREFILL_DATA.Recobrable;
        const selPri = document.querySelector('select[name="prioridad_requisicion"]'); if (selPri && PREFILL_DATA.prioridad_requisicion) selPri.value = PREFILL_DATA.prioridad_requisicion;
        const txtJust = document.querySelector('textarea[name="justify_requisicion"]'); if (txtJust && PREFILL_DATA.justify_requisicion) txtJust.value = PREFILL_DATA.justify_requisicion;
        const txtDet = document.querySelector('textarea[name="detail_requisicion"]'); if (txtDet && PREFILL_DATA.detail_requisicion) txtDet.value = PREFILL_DATA.detail_requisicion;
        if (Array.isArray(PREFILL_DATA.productos)){
          productos = PREFILL_DATA.productos.map(p => ({ id: p.id, nombre: p.nombre, proveedorId: p.proveedorId || null, cantidadTotal: p.cantidadTotal || 0, unidad: p.unidad || '', centros: Array.isArray(p.centros) ? p.centros.map(c => ({ id: String(c.id), nombre: c.nombre, cantidad: parseInt(c.cantidad,10)||0 })) : [] }));
          actualizarTabla();
        }
      } catch (err){ console.error('Error aplicando PREFILL_DATA', err); }
    })();
  });
})();
