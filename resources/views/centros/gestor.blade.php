@extends('layouts.app')

@section('title', 'Gestor de Centros')

@section('content')
<x-sidebar/>
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                <h2 class="text-white text-2xl font-bold">Gestión de Centros y Subcentros</h2>
                <p class="text-blue-100 mt-1">Crea, edita y organiza centros y sus subcentros.</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-start mb-6">
                    <div class="flex justify-end md:justify-end">
                        <div class="text-sm text-gray-500">Total centros: <span class="font-semibold text-gray-700">{{ $centros->count() }}</span></div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($centros as $centro)
                    <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition overflow-hidden flex flex-col h-full">
                        <div class="p-4 flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-gray-800">{{ $centro->name_centro }}</h3>
                                <p class="text-sm text-gray-500 mt-1">Subcentros: <span class="font-medium text-gray-700">{{ $centro->subcentros->count() }}</span></p>

                                {{-- Edición de centros deshabilitada desde aquí --}}
                                <div class="mt-3 text-sm text-gray-700 font-medium">{{ $centro->name_centro }}</div>
                             </div>
 
                             <div class="text-right flex flex-col items-end gap-2">
                                {{-- Eliminación de centros deshabilitada --}}
                                <button type="button" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200" onclick="toggleSubs(event,'subs-{{ $centro->id }}')" aria-expanded="false">
                                    <i class="fas fa-chevron-down"></i> Ver
                                </button>
                             </div>
                         </div>

                        <div class="border-t">
                            <div class="p-4">
                                <div class="mt-3 flex-shrink-0">
                                     <form action="{{ route('centros.subcentros.store', $centro->id) }}" method="POST" class="flex gap-2">
                                         @csrf
                                         <input type="text" name="name_subcentro" placeholder="Nuevo subcentro" class="flex-1 px-3 py-2 border rounded-md focus:ring-2 focus:ring-indigo-200" required>
                                         <button class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 flex items-center gap-2">
                                             <i class="fas fa-plus"></i> <span>Agregar</span>
                                         </button>
                                     </form>
                                 </div>

                                 <div id="subs-{{ $centro->id }}" class="mt-4 space-y-2 hidden max-h-56 overflow-auto pr-2">
                                     @foreach($centro->subcentros as $sub)
                                     <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
                                         <div class="flex-1">
                                            <form action="{{ route('centros.subcentros.update', $sub->id) }}" method="POST" class="flex gap-2 items-center">
                                                 @csrf
                                                 @method('PUT')
                                                 <input type="text" name="name_subcentro" value="{{ $sub->name_subcentro }}" class="flex-1 px-2 py-1 border rounded-md focus:ring-1 focus:ring-indigo-100">
                                                 <button class="px-2 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700" title="Guardar">
                                                     <i class="fas fa-save"></i>
                                                 </button>
                                             </form>
                                         </div>
                                         <div class="ml-3">
                                             <form action="{{ route('centros.subcentros.destroy', $sub->id) }}" method="POST" data-confirm="Eliminar subcentro?">
                                                 @csrf
                                                 @method('DELETE')
                                                 <button class="px-2 py-1 bg-red-600 text-white rounded-md hover:bg-red-700" title="Eliminar">
                                                     <i class="fas fa-trash"></i>
                                                 </button>
                                             </form>
                                         </div>
                                     </div>
                                     @endforeach

                                     @if($centro->subcentros->isEmpty())
                                     <div class="text-sm text-gray-500 italic">No hay subcentros registrados.</div>
                                     @endif
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

    // SweetAlert confirmation for forms with data-confirm
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('form[data-confirm]').forEach(function(form){
            form.addEventListener('submit', function(evt){
                // si ya fue confirmado por este handler, permitir el envío real
                if (form.dataset.confirmed === '1') {
                    // limpiar flag y permitir que el submit continúe
                    delete form.dataset.confirmed;
                    return true;
                }
                evt.preventDefault();
                const msg = form.getAttribute('data-confirm') || '¿Desea continuar?';
                Swal.fire({
                    title: msg,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar'
                }).then(function(res){
                    if (res.isConfirmed) {
                        // marcar como confirmado para que el siguiente submit no vuelva a abrir SweetAlert
                        form.dataset.confirmed = '1';
                        // show loading overlay
                        const ov = document.getElementById('centrosLoadingOverlay'); if (ov) ov.classList.remove('hidden');
                        // disable submit buttons
                        const submits = form.querySelectorAll('button[type=submit], input[type=submit]');
                        submits.forEach(b => { b.disabled = true; b.classList.add('opacity-70','cursor-not-allowed'); });
                        // submit programmatically; the submit handler above will detect the flag and allow it
                        if (typeof form.requestSubmit === 'function') form.requestSubmit(); else form.submit();
                     }
                 });
             });
         });
     });
 </script>

<style>
     /* Small tweaks for nicer cards */
     .fa-rotate-180 { transform: rotate(180deg); transition: transform 0.2s; }
    /* Scrollbar for subcentros list */
    .max-h-56::-webkit-scrollbar { width: 8px; }
    .max-h-56::-webkit-scrollbar-track { background: transparent; }
    .max-h-56::-webkit-scrollbar-thumb { background: rgba(59,130,246,0.35); border-radius: 8px; }
    .max-h-56 { scrollbar-width: thin; scrollbar-color: rgba(59,130,246,0.35) transparent; }
 </style>
 
 @endsection
