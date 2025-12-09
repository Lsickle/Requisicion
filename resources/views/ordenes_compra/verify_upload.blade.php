@extends('layouts.app')

@section('title', 'Verificar PDF de Orden de Compra')

@section('content')

<x-sidebar/>

<div class="max-w-3xl mx-auto p-6 mt-20 min-h-[70vh] rounded-2xl">
    <div class="bg-white/95 shadow-2xl rounded-2xl p-6 border border-slate-200 ring-1 ring-slate-100">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                    <i class="fas fa-file-signature text-xl"></i>
                </div>
                <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">Verificar PDF de Orden</h2>
            </div>
            <a href="{{ route('requisiciones.menu') }}" class="px-4 py-2 text-sm rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 shadow-sm transition">Volver</a>
        </div>
        <p class="text-sm text-gray-600 mb-4">Sube un PDF de la orden para verificar su hash de validación.</p>

        <form method="post" action="{{ url('/ordenes/verify-file') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">ID de la Orden</label>
                <input type="number" name="id" class="mt-1 block w-full border rounded-lg px-3 py-2 border-indigo-300 focus:border-indigo-400 focus:ring-indigo-300/40" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Archivo PDF</label>
                <style>
                    /* Estado visual cuando hay archivo cargado */
                    #drop-area { transition: border-color .2s, background-color .2s, box-shadow .2s; }
                    #drop-area:hover { background: #f8fafc; }
                    #drop-area:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.2); }
                    #drop-area.file-loaded { border-color: #16a34a; background: #ecfdf5; }
                    #drop-area.file-loaded #file-name { color: #065f46; }
                    #drop-area .status-badge { display:none; }
                    #drop-area.file-loaded .status-badge { display:inline-block; background:#16a34a; color:white; padding:2px 8px; border-radius:9999px; font-size:12px; }
                </style>

                <div id="drop-area" class="flex items-center justify-between gap-4 p-4 border-2 border-dashed rounded-xl bg-gray-50 hover:bg-gray-100 transition cursor-pointer">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V8a2 2 0 012-2h6a2 2 0 012 2v8m-6-3v4m0-4H9m3 0h3" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-700">Arrastra el archivo aquí o haz clic para seleccionar</div>
                            <div id="file-name" class="text-xs text-gray-500 mt-1">PDF no seleccionado</div>
                            <div id="file-meta" class="text-[11px] text-gray-400"></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="status-badge">Cargado</span>
                        <button type="button" id="btn-select-file" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm focus:ring-2 focus:ring-blue-300">Seleccionar archivo</button>
                    </div>
                </div>
                <input id="pdf-input" type="file" name="pdf" accept="application/pdf" class="hidden" required>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow-sm focus:ring-2 focus:ring-green-300">Subir y verificar</button>
                <a href="{{ route('requisiciones.menu') }}" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded-lg hover:bg-gray-300">Volver al menú</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // File input UX: abrir selector y mostrar nombre
    document.addEventListener('DOMContentLoaded', function() {
        const drop = document.getElementById('drop-area');
        const input = document.getElementById('pdf-input');
        const btn = document.getElementById('btn-select-file');
        const nameEl = document.getElementById('file-name');
        const metaEl = document.getElementById('file-meta');

        function humanSize(bytes){
            if (!bytes && bytes !== 0) return '';
            const units = ['B','KB','MB','GB'];
            let i = 0, v = Number(bytes);
            while (v >= 1024 && i < units.length-1) { v /= 1024; i++; }
            return v.toFixed(v >= 10 ? 0 : 1) + ' ' + units[i];
        }

        function setFileName(fn, size){
            nameEl.textContent = fn ? fn : 'PDF no seleccionado';
            metaEl.textContent = size ? ('Tamaño: ' + humanSize(size)) : '';
            if (fn) {
                drop.classList.add('file-loaded');
                btn.textContent = 'Cambiar archivo';
            } else {
                drop.classList.remove('file-loaded');
                btn.textContent = 'Seleccionar archivo';
            }
        }

        drop.addEventListener('click', (e) => {
            if (e.target === btn) return;
            input.click();
        });

        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            input.click();
        });

        drop.addEventListener('dragover', (e)=>{ e.preventDefault(); drop.classList.add('bg-gray-100'); });
        drop.addEventListener('dragleave', ()=>{ drop.classList.remove('bg-gray-100'); });
        drop.addEventListener('drop', (e)=>{
            e.preventDefault(); drop.classList.remove('bg-gray-100');
            const f = e.dataTransfer.files && e.dataTransfer.files[0];
            if (f) {
                if (f.type !== 'application/pdf') { Swal.fire({icon:'error',title:'Archivo inválido',text:'Debe seleccionar un PDF.'}); return; }
                input.files = e.dataTransfer.files;
            }
            if (input.files[0]) setFileName(input.files[0].name, input.files[0].size);
        });

        input.addEventListener('change', ()=>{
            const f = input.files[0];
            if (f) {
                if (f.type !== 'application/pdf') { Swal.fire({icon:'error',title:'Archivo inválido',text:'Debe seleccionar un PDF.'}); input.value=''; setFileName('',0); return; }
                setFileName(f.name, f.size);
            }
        });

        // inicializar nombre si ya existe (por POST con errores)
        if (input.files && input.files[0]) setFileName(input.files[0].name, input.files[0].size);
    });

    @if(isset($valid))
    (function(){
        const run = function(){
            const valid = {{ $valid ? 'true' : 'false' }};
            const outdated = {{ !empty($outdated) ? 'true' : 'false' }};
            const message = {!! json_encode($message ?? '') !!};
            const ordenExists = {{ (isset($orden) && $orden !== null) ? 'true' : 'false' }};

            if (!ordenExists) {
                Swal.fire({ icon: 'error', title: 'Orden no encontrada', text: 'La orden de compra no existe o fue eliminada.' });
                return;
            }

            if (valid && outdated) {
                Swal.fire({ icon: 'warning', title: 'PDF válido pero desactualizado', text: message || 'El archivo es válido pero no corresponde al hash más reciente.' });
            } else if (valid) {
                Swal.fire({ icon: 'success', title: 'PDF válido', text: message || 'El archivo coincide con el original.' });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'PDF no válido',
                    html: `<p>${message || 'El archivo no coincide con el original.'}</p>`
                });
            }
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', run);
        } else {
            run();
        }
    })();
    @endif
</script>
@endsection
