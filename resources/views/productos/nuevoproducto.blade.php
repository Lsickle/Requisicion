@extends('layouts.app')

@section('title', 'Crear Requisición')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<style>
    .swal2-confirm { background-color: #2563eb !important; border-color: #2563eb !important; }
    .swal2-confirm:hover { background-color: #1d4ed8 !important; }
    .border-red-500 { border-color: #ef4444; }
    /* Scrollbar fino y clamp multilinea */
    .thin-scrollbar { scrollbar-width: thin; scrollbar-color: #94a3b8 #e2e8f0; }
    .thin-scrollbar::-webkit-scrollbar { height: 8px; width: 8px; }
    .thin-scrollbar::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 8px; }
    .thin-scrollbar::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 8px; }
    .thin-scrollbar::-webkit-scrollbar-thumb:hover { background: #64748b; }
    .clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>
{{-- eliminado </head> inválido --}}
@section('content')
<x-sidebar />

<div class="min-h-[70vh]">
    <div class="container mx-auto px-4 py-8 mt-10">
        <div class="bg-white/95 rounded-2xl shadow-2xl overflow-hidden border border-slate-200 ring-1 ring-slate-100">
            <div class="px-6 py-5 border-b bg-indigo-50">
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center shadow-inner">
                        <i class="fas fa-box-open"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold text-gray-800">Solicitar nuevo producto</h2>
                        <p class="text-xs text-gray-500">Ingresa los detalles del producto a solicitar.</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 px-6 py-5">
                <div>
                    <form id="productoForm" action="{{ route('nuevo_producto.store') }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="nombre" class="block text-gray-700 text-sm font-medium mb-1">Nombre del producto</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}"
                                   class="w-full px-3 py-2 border border-indigo-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300/60 focus:border-indigo-400 shadow-sm"
                                   placeholder="Ingresa el nombre del producto" required>
                            @error('nombre')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-6">
                            <label for="descripcion" class="block text-gray-700 text-sm font-medium mb-1">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4" required
                                      class="w-full px-3 py-2 border border-indigo-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300/60 focus:border-indigo-400 shadow-sm"
                                      placeholder="Describe el producto que deseas solicitar">{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-center">
                            <button type="submit"
                                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-300 shadow-sm transition">
                                <i class="fas fa-paper-plane"></i>
                                Solicitar
                            </button>
                        </div>
                    </form>
                </div>

                <div>
                    <h3 class="text-gray-800 font-semibold mb-3">Mis solicitudes</h3>
                    @if($misSolicitudes->isEmpty())
                        <p class="text-sm text-gray-500">No tienes solicitudes registradas.</p>
                    @else
                        <div class="overflow-auto max-h-80 border rounded-xl bg-white thin-scrollbar">
                            <table class="w-full text-sm">
                                <thead class="bg-indigo-50 sticky top-0 text-indigo-900 text-xs font-semibold">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Fecha</th>
                                        <th class="px-3 py-2 text-left">Producto</th>
                                        <th class="px-3 py-2 text-left">Descripción</th>
                                        <th class="px-3 py-2 text-left">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($misSolicitudes as $s)
                                        <tr class="border-t odd:bg-white even:bg-slate-50 hover:bg-indigo-50/40 transition">
                                            <td class="px-3 py-2">{{ isset($s->created_at) ? (\Carbon\Carbon::parse($s->created_at)->format('d/m/Y')) : '' }}</td>
                                            <td class="px-3 py-2 font-medium text-slate-800">{{ $s->nombre ?? ($s->name ?? '-') }}</td>
                                            <td class="px-3 py-2 text-slate-700">
                                                <div class="clamp-2" title="{{ $s->descripcion ?? ($s->comentario ?? '-') }}">{{ $s->descripcion ?? ($s->comentario ?? '-') }}</div>
                                            </td>
                                            @php
                                                $isDeleted = !empty($s->deleted_at);
                                                $isRejected = $isDeleted && !empty($s->comentario);
                                                $statusText = $isRejected ? 'Rechazado' : ($isDeleted ? 'Añadido' : 'Pendiente');
                                                $statusCls = $isRejected ? 'bg-red-100 text-red-700' : ($isDeleted ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700');
                                                $tooltip = $isRejected ? ('Motivo: '.($s->comentario ?? '')) : ($isDeleted ? 'Producto creado a partir de esta solicitud' : 'En revisión');
                                            @endphp
                                            <td class="px-3 py-2">
                                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusCls }}" title="{{ $tooltip }}">{{ $statusText }}</span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('productoForm');
    if (!form) return;

    // obtener token CSRF desde el formulario
    const csrfToken = form.querySelector('input[name="_token"]')?.value || '';

    function showValidationError(messages) {
        let html = '<ul style="text-align:left;margin:0;padding-left:18px;">';
        messages.forEach(m => { html += `<li>${m}</li>`; });
        html += '</ul>';
        Swal.fire({ icon: 'error', title: 'Error', html: html, confirmButtonColor: '#2563eb' });
    }

    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const nombre = document.getElementById('nombre');
        const descripcion = document.getElementById('descripcion');

        // Reset visual errors
        nombre.classList.remove('border-red-500');
        descripcion.classList.remove('border-red-500');

        // Client-side validation
        const errors = [];
        if (!nombre || !nombre.value.trim()) { errors.push('El nombre es requerido'); nombre.classList.add('border-red-500'); }
        if (!descripcion || !descripcion.value.trim()) { errors.push('La descripción es requerida'); descripcion.classList.add('border-red-500'); }

        if (errors.length) {
            showValidationError(errors);
            return;
        }

        // Confirmación con SweetAlert
        Swal.fire({
            title: '¿Confirmar envío?',
            text: "¿Estás seguro de que deseas solicitar este producto?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, solicitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) return;

            // Mostrar loading
            Swal.fire({ title: 'Enviando solicitud...', text: 'Por favor espera...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

            // Enviar por fetch (AJAX) para manejar respuesta y errores sin depender del comportamiento de submit del navegador
            const fd = new FormData(form);

            fetch(form.action, {
                method: (form.method || 'POST').toUpperCase(),
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: fd
            }).then(async (res) => {
                let data = null;
                try { data = await res.json(); } catch (e) { data = null; }

                // Si el servidor redirige (res.redirected), seguir la redirección
                if (res.redirected) {
                    window.location.href = res.url;
                    return;
                }

                // Cerrar loading antes de mostrar resultado
                Swal.close();

                if (res.ok) {
                    // Si envia mensaje de éxito en JSON
                    const msg = (data && (data.success ? (data.message || 'Solicitud enviada') : (data.message || '')) ) || 'Solicitud enviada';
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: msg, confirmButtonColor: '#2563eb' }).then(() => {
                        // Si el servidor indica una ruta de redirect, usarla; sino recargar
                        if (data && data.redirect) window.location.href = data.redirect;
                        else window.location.reload();
                    });
                } else {
                    // Manejar errores de validación u otros
                    if (data && data.errors) {
                        // data.errors puede ser objeto de arrays
                        const msgs = [];
                        Object.keys(data.errors).forEach(k => { (data.errors[k] || []).forEach(m => msgs.push(m)); });
                        showValidationError(msgs);
                    } else if (data && data.message) {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#2563eb' });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Error al enviar la solicitud', confirmButtonColor: '#2563eb' });
                    }
                }
            }).catch((err) => {
                console.error('Network error sending nuevo producto:', err);
                Swal.close();
                Swal.fire({ icon: 'error', title: 'Error', text: 'Error de red al enviar la solicitud', confirmButtonColor: '#2563eb' });
            });
        });
    });

    // Mostrar alerta de éxito si viene de una redirección con sesión flash (caso no-AJAX)
    @if(session('success'))
        Swal.fire({ icon: 'success', title: '¡Éxito!', text: '{{ session('success') }}', confirmButtonColor: '#2563eb' });
    @endif
});
</script>
@endsection
