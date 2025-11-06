@extends('layouts.app')

@section('title','Gestor de Aprobadores por Centro')

@section('content')
<x-sidebar/>
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Gestión de aprobadores por Centro</h2>
            <p class="text-sm text-gray-500">Asignar Aprobador 1 y Aprobador 2 por centro. Los usuarios se cargan desde la API.</p>
        </div>

        @if(session('success'))
            <script>document.addEventListener('DOMContentLoaded',()=> Swal.fire({icon:'success',title:'Listo',text: {!! json_encode(session('success')) !!}}));</script>
        @endif
        @if(session('error'))
            <script>document.addEventListener('DOMContentLoaded',()=> Swal.fire({icon:'error',title:'Error',text: {!! json_encode(session('error')) !!}}));</script>
        @endif

        <div class="overflow-auto">
            <table class="min-w-full text-sm border">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-3 py-2 text-left">Centro</th>
                        <th class="px-3 py-2 text-left">Aprobador 1</th>
                        <th class="px-3 py-2 text-left">Aprobador 2</th>
                        <th class="px-3 py-2 text-left">Acción</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($centros as $centro)
                    <tr class="border-t">
                        <td class="px-3 py-2 align-top">{{ $centro->name_centro }}</td>
                        <td class="px-3 py-2">
                            <form method="POST" action="{{ route('requisiciones.aprobadores.store') }}" class="approver-form" data-centro-id="{{ $centro->id }}">
                                @csrf
                                <input type="hidden" name="centro_id" value="{{ $centro->id }}">
                                <select name="approver_1" class="approver-select w-full px-2 py-2 border rounded" data-current="{{ $centro->approver_1_id ?? '' }}">
                                    <option value="">-- Cargando usuarios... --</option>
                                </select>
                        </td>
                        <td class="px-3 py-2">
                                <select name="approver_2" class="approver-select w-full px-2 py-2 border rounded" data-current="{{ $centro->approver_2_id ?? '' }}">
                                    <option value="">-- Cargando usuarios... --</option>
                                </select>
                        </td>
                        <td class="px-3 py-2 align-top">
                                <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Guardar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-4 text-gray-500">No hay centros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-xs text-gray-500 mt-3">Nota: si un centro sólo requiere un aprobador, deje Aprobador 2 vacío.</p>
    </div>
</div>

@php
    // Exponer configuración para JS (API base y token desde sesión)
    $apiBase = rtrim(env('VPL_CORE') ?? '', '/');
    $apiToken = session('api_token') ?? '';
@endphp

<script>
    window.APROBADORES_CFG = { apiBase: '{{ $apiBase }}', token: '{{ $apiToken }}' };

    async function fetchUsers(){
        try{
            const base = window.APROBADORES_CFG.apiBase || '';
            const token = window.APROBADORES_CFG.token || '';
            if (!base || !token) return [];
            const url = base + '/api/users?per_page=1000';
            const res = await fetch(url, { headers: { 'Authorization': 'Bearer ' + token, 'Accept':'application/json' } });
            if (!res.ok) return [];
            const data = await res.json();
            // soportar respuesta paginada o lista plana
            if (Array.isArray(data)) return data;
            if (Array.isArray(data.data)) return data.data;
            // intentar extraer list via common keys
            return data.items || data.users || [];
        } catch(e){ console.warn('fetchUsers', e); return []; }
    }

    function buildOption(u){
        const name = (u.name || u.username || u.fullname || '').trim();
        const email = u.email ? (' <'+u.email+'>') : '';
        const label = (name || u.email || ('#'+(u.id||''))).toString();
        return { value: u.id, label: label + (u.email ? ' (' + u.email + ')' : '') };
    }

    document.addEventListener('DOMContentLoaded', async function(){
        const users = await fetchUsers();
        const opts = users.map(buildOption);
        // Poblar cada select
        document.querySelectorAll('select.approver-select').forEach(function(sel){
            const cur = sel.getAttribute('data-current') || '';
            sel.innerHTML = '<option value="">-- Sin asignar --</option>' + opts.map(o => `<option value="${o.value}" ${String(o.value)===String(cur)?'selected':''}>${o.label}</option>`).join('');
        });

        // manejar envío por AJAX para feedback
        document.querySelectorAll('form.approver-form').forEach(function(frm){
            frm.addEventListener('submit', async function(e){
                e.preventDefault();
                const form = e.currentTarget;
                const action = form.action;
                const data = new FormData(form);
                try{
                    const res = await fetch(action, { method: 'POST', headers: { 'X-Requested-With':'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }, body: data });
                    const json = await (res.json ? res.json() : Promise.resolve({success:res.ok}));
                    if (json && json.success) {
                        Swal.fire({ icon: 'success', title: 'Guardado', text: json.message || 'Aprobadores actualizados.' });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: (json && json.message) ? json.message : 'No se pudo guardar.' });
                    }
                } catch(err){
                    console.error(err);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Error conectando con el servidor.' });
                }
            });
        });
    });
</script>

@endsection
