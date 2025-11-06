@extends('layouts.app')

@section('title','Asignar Subcentros a Usuarios')

@section('content')
<x-sidebar />
<div class="container mx-auto px-4 py-8 mt-20">
    <div class="max-w-4xl mx-auto bg-white rounded shadow p-6">
        <h2 class="text-xl font-semibold mb-4">Asignar Subcentros a Usuarios</h2>
        <p class="text-sm text-gray-500 mb-4">Seleccione un usuario desde la lista (traída desde la API) y asigne los subcentros disponibles.</p>

        @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'success',
                    title: '¡Listo!',
                    text: '{{ session('success') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif
        @if(session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function(){
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '{{ session('error') }}',
                    confirmButtonText: 'OK'
                });
            });
        </script>
        @endif

        <!-- Buscador -->
        <div class="mb-4 flex items-center gap-2">
            <input type="text" id="usersSearch" placeholder="Buscar por nombre o email..." class="flex-1 px-3 py-2 border rounded" />
            <button id="usersClearSearch" class="px-3 py-2 bg-gray-200 rounded">Limpiar</button>
        </div>

        <div id="usersList" class="space-y-2">
            <p class="text-gray-500">Cargando usuarios...</p>
        </div>
        <div id="usersPaginationContainer" class="mt-3"></div>

        <!-- Modal asignación -->
        <div id="assignModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow p-6 w-11/12 md:w-3/4 max-h-[90vh] overflow-auto">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 id="assignUserTitle" class="text-lg font-semibold">Asignar subcentros</h3>
                        <div id="assignUserInfo" class="text-sm text-gray-600">&nbsp;</div>
                    </div>
                    <button type="button" class="text-gray-500" onclick="closeAssignModal()">Cerrar ✕</button>
                </div>

                <form id="assignForm" method="POST" action="{{ route('centros.user_subcentros.store') }}">
                    @csrf
                    <input type="hidden" name="email_user" id="assign_email_user">
                    <!-- JSON con subcentros asignados (se llenará antes del submit) -->
                    <input type="hidden" name="subcentros_json" id="subcentros_json" value="">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Select de subcentros y botón Agregar -->
                        <div class="border rounded p-3 max-h-64 overflow-auto">
                            <h4 class="text-sm font-medium mb-2">Subcentros disponibles</h4>
                            <div class="flex gap-2 mb-3">
                                <!-- Selector de Centros -->
                                @php
                                    // Construir lista de centros únicos (compatible con PHP 7.x)
                                    $centrosUnicos = collect();
                                    if (!empty($subcentros)) {
                                        foreach ($subcentros as $s) {
                                            if (isset($s->centro) && $s->centro) {
                                                $centrosUnicos->push($s->centro);
                                            }
                                        }
                                    }
                                    $centrosUnicos = $centrosUnicos->unique('id')->values();
                                @endphp
                                <select id="centroSelect" class="flex-1 px-2 py-2 border rounded">
                                    <option value="">-- Selecciona un centro --</option>
                                    @foreach($centrosUnicos as $c)
                                        <option value="{{ $c->id }}">{{ $c->name_centro }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="loadSubcentrosBtn" class="px-3 py-2 bg-indigo-600 text-white rounded">Cargar</button>

                            </div>
                            <div class="flex gap-2 mb-3">
                                 <select id="subcentroSelect" class="flex-1 px-2 py-2 border rounded">
                                     <option value="">-- Selecciona un subcentro --</option>
                                    @foreach($subcentros as $s)
                                    <option value="{{ $s->id }}" data-centro-id="{{ isset($s->centro) ? $s->centro->id : '' }}">{{ $s->name_subcentro }} @if(isset($s->centro) && $s->centro) ({{ $s->centro->name_centro }})@endif</option>
                                    @endforeach
                                 </select>
                                 <button type="button" id="addSubcentroBtn" class="px-3 py-2 bg-green-600 text-white rounded">Agregar</button>
                             </div>
                             <div class="text-xs text-gray-500">Selecciona y pulsa Agregar para añadir a la lista de asignados.</div>
                         </div>

                        <!-- Tabla dinámica de asignados -->
                        <div class="border rounded p-3 max-h-64 overflow-auto">
                            <h4 class="text-sm font-medium mb-2">Subcentros asignados</h4>
                            <table class="min-w-full text-sm" id="assignedTable">
                                <thead>
                                    <tr class="text-left text-xs text-gray-500">
                                        <th class="px-2 py-1">Subcentro</th>
                                        <th class="px-2 py-1">Centro</th>
                                        <th class="px-2 py-1">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="assignedTbody">
                                    <tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button" class="px-3 py-2 bg-gray-300 rounded" onclick="closeAssignModal()">Cancelar</button>
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

@php
    // Preparar arrays simples para JSON (compatible con PHP 7.x)
    $allSubcentros = [];
    if (!empty($subcentros)) {
        foreach ($subcentros as $s) {
            $centroName = '';
            $centroId = null;
            if (isset($s->centro) && $s->centro) {
                $centroName = isset($s->centro->name_centro) ? $s->centro->name_centro : '';
                $centroId = isset($s->centro->id) ? $s->centro->id : null;
            }
            $allSubcentros[] = ['id' => $s->id, 'name' => $s->name_subcentro, 'centro' => $centroName, 'centro_id' => $centroId];
        }
    }

    $allCentros = [];
    if (!empty($centrosUnicos)) {
        foreach ($centrosUnicos as $c) {
            $allCentros[] = ['id' => $c->id, 'name' => isset($c->name_centro) ? $c->name_centro : ''];
        }
    }
@endphp

<script id="all-subcentros-json" type="application/json">@json($allSubcentros)</script>
<script id="all-centros-json" type="application/json">@json($allCentros)</script>
<script>
    window.USER_SUBCENTROS_CFG = {
        apiBase: '{{ rtrim(env('VPL_CORE'), '/') }}',
        token: '{{ session('api_token') ?? '' }}'
    };
    // El JS externo expone openAssignModal y unassignSub
</script>
<script src="{{ asset('js/user_subcentros.js') }}"></script>

<!-- Función mínima para cerrar modal y limpiar datos creados por el JS externo -->
<script>
    function closeAssignModal(){
        try {
            const modal = document.getElementById('assignModal'); if (modal) modal.classList.add('hidden');
            const tbody = document.getElementById('assignedTbody'); if (tbody) tbody.innerHTML = '<tr><td colspan="3" class="text-sm text-gray-500">Sin asignaciones</td></tr>';
            // eliminar inputs ocultos creados por user_subcentros.js (name="subcentro_ids[]")
            document.querySelectorAll('input[name="subcentro_ids[]"]').forEach(n => n.remove());
            const emailInput = document.getElementById('assign_email_user'); if (emailInput) emailInput.value = '';
        } catch (e) { console.warn('closeAssignModal', e); }
    }
</script>

@endsection
