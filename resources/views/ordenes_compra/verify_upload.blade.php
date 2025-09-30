@extends('layouts.app')

@section('title', 'Verificar PDF de Orden de Compra')

@section('content')

<x-sidebar/>

<div class="max-w-2xl mx-auto p-6 mt-20">
    <div class="bg-white shadow rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Verificar PDF de Orden</h2>
        <p class="text-sm text-gray-600 mb-4">Sube un PDF de la orden para verificar su hash de validación.</p>

        <form method="post" action="{{ url('/ordenes/verify-file') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700">ID de la Orden</label>
                <input type="number" name="id" class="mt-1 block w-full border rounded px-3 py-2" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Archivo PDF</label>
                <style>
                    /* Estado visual cuando hay archivo cargado */
                    #drop-area.file-loaded { border-color: #16a34a /* green-600 */; background: #ecfdf5 /* green-50 */; }
                    #drop-area.file-loaded #file-name { color: #065f46 /* green-800 */; }
                    #drop-area .status-badge { display:none; }
                    #drop-area.file-loaded .status-badge { display:inline-block; background:#16a34a; color:white; padding:2px 8px; border-radius:9999px; font-size:12px; }
                </style>

                <div id="drop-area" class="flex items-center justify-between gap-4 p-4 border-2 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition cursor-pointer">
                    <div class="flex items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V8a2 2 0 012-2h6a2 2 0 012 2v8m-6-3v4m0-4H9m3 0h3" />
                        </svg>
                        <div>
                            <div class="text-sm text-gray-700">Arrastra el archivo aquí o haz clic para seleccionar</div>
                            <div id="file-name" class="text-xs text-gray-500 mt-1">PDF no seleccionado</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="status-badge">Cargado</span>
                        <button type="button" id="btn-select-file" class="px-3 py-1 bg-blue-600 text-white rounded">Seleccionar archivo</button>
                    </div>
                </div>
                <input id="pdf-input" type="file" name="pdf" accept="application/pdf" class="hidden" required>
            </div>

            <div class="flex items-center gap-3">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">Subir y verificar</button>
                <a href="{{ route('requisiciones.menu') }}" class="inline-block bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">Volver al menú</a>
            </div>
        </form>
    </div>
</div>

{{-- Resultado de la verificación (renderizado por PHP para no exponer lógica de hash en JS) --}}
@if(isset($valid))
    @if($orden === null)
        <div class="max-w-2xl mx-auto mt-4 p-4 rounded bg-red-50 border border-red-200 text-red-700">La orden de compra no existe o fue eliminada.</div>
    @else
        @if($valid)
            <div class="max-w-2xl mx-auto mt-4 p-4 rounded bg-green-50 border border-green-200 text-green-700">El archivo coincide con el original (hash SHA256 igual).</div>
        @else
            <div class="max-w-2xl mx-auto mt-4 p-4 rounded bg-red-50 border border-red-200 text-red-700">
                <p class="font-medium">El documento ha sido alterado o no es igual al original.</p>
                {{-- Hash details removed as not relevant to the user --}}
            </div>
        @endif
    @endif
@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // File input UX: abrir selector y mostrar nombre
    document.addEventListener('DOMContentLoaded', function() {
        const drop = document.getElementById('drop-area');
        const input = document.getElementById('pdf-input');
        const btn = document.getElementById('btn-select-file');
        const nameEl = document.getElementById('file-name');

        function setFileName(fn){
            nameEl.textContent = fn ? fn : 'PDF no seleccionado';
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
            if (f) input.files = e.dataTransfer.files;
            if (input.files[0]) setFileName(input.files[0].name);
        });

        input.addEventListener('change', ()=>{
            const f = input.files[0];
            if (f) setFileName(f.name);
        });

        // inicializar nombre si ya existe (por POST con errores)
        if (input.files && input.files[0]) setFileName(input.files[0].name);
    });

    @if(isset($valid))
    (function(){
        const run = function(){
            const valid = {{ $valid ? 'true' : 'false' }};
            const message = {!! json_encode($message ?? '') !!};
            const ordenExists = {{ (isset($orden) && $orden !== null) ? 'true' : 'false' }};

            if (!ordenExists) {
                Swal.fire({ icon: 'error', title: 'Orden no encontrada', text: 'La orden de compra no existe o fue eliminada.' });
                return;
            }

            if (valid) {
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
