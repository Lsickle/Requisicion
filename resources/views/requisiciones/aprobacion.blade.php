@extends('layouts.app')

@section('title', 'Aprobación de Requisiciones')

@section('content')
<div class="flex pt-20">
    <!-- Sidebar -->
    <x-sidebar />

    <!-- Contenido principal -->
    <div class="flex-1 px-4 md:px-8 pb-10">
        <div class="max-w-7xl mx-auto bg-gray-50 rounded-xl shadow-lg p-6 flex flex-col min-h-[80vh]">

            <!-- Encabezado -->
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Panel de Aprobación de Requisiciones</h1>
                <div>
                    <a href="{{ route('requisiciones.menu') }}"
                        class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg shadow transition">
                        ← Volver
                    </a>
                </div>
            </div>

            <!-- Búsqueda -->
            <div class="mb-4">
                <input type="text" id="busquedaAprob" placeholder="Buscar requisición..."
                    class="border px-4 py-2 rounded-lg w-full md:w-1/3 shadow-sm focus:ring focus:ring-blue-300 focus:outline-none">
            </div>

            <!-- Contenedor scrollable -->
            <div class="flex-1 overflow-y-auto">
                <!-- Tabla en escritorio -->
                <div class="bg-white rounded-lg shadow overflow-x-auto hidden md:block">
                    <table class="min-w-full table-auto border-collapse">
                        <thead class="bg-gray-200 text-gray-700 text-sm uppercase tracking-wide">
                            <tr>
                                <th class="px-4 py-2 text-left">#</th>
                                <th class="px-4 py-2 text-left">Detalle</th>
                                <th class="px-4 py-2 text-left">Prioridad</th>
                                <th class="px-4 py-2 text-left">Solicitante</th>
                                <th class="px-4 py-2 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requisicionesFiltradas as $req)
                            <tr class="aprob-item border-b hover:bg-gray-50 transition" data-id="{{ $req->id }}">
                                <td class="px-4 py-2">{{ $req->id }}</td>
                                <td class="px-4 py-2">{{ $req->detail_requisicion }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold 
                                        {{ $req->prioridad_requisicion == 'alta' ? 'bg-red-100 text-red-800' : 
                                           ($req->prioridad_requisicion == 'media' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-green-100 text-green-800') }}">
                                        {{ ucfirst($req->prioridad_requisicion) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">{{ $req->name_user }}</td>
                                <td class="px-4 py-2 text-center">
                                    <button onclick="toggleModal('modal-{{ $req->id }}')"
                                        class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">Ver</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-gray-500">No hay requisiciones para sus
                                    operaciones</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Vista móvil -->
                <div class="md:hidden space-y-4">
                    @forelse($requisicionesFiltradas as $req)
                    <div class="aprob-item bg-white rounded-lg shadow p-4" data-id="{{ $req->id }}">
                        <h2 class="font-bold text-lg mb-2">#{{ $req->id }} - {{ $req->detail_requisicion }}</h2>
                        <p><strong>Solicitante:</strong> {{ $req->name_user }}</p>
                        <p><strong>Prioridad:</strong> {{ ucfirst($req->prioridad_requisicion) }}</p>
                        <div class="mt-3">
                            <button onclick="toggleModal('modal-{{ $req->id }}')"
                                class="bg-blue-600 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-700 transition">Ver</button>
                        </div>
                    </div>
                    @empty
                    <p class="text-center text-gray-500">No hay requisiciones para sus operaciones</p>
                    @endforelse
                </div>

                <!-- Paginación inferior -->
                <div class="flex items-center justify-between mt-4" id="paginationBarAprob">
                    <div class="text-sm text-gray-600">
                        Mostrar
                        <select id="pageSizeSelectAprob" class="border rounded px-2 py-1">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                        </select>
                        por página
                        <span id="paginationInfoAprob" class="ml-4 text-sm text-gray-600"></span>
                    </div>
                    <div class="flex flex-wrap gap-1" id="paginationControlsAprob"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modales -->
    @foreach($requisicionesFiltradas as $req)
    @php
        // Detectar si el usuario pertenece a Área de compras o es Admin requisicion
        $rolesRaw = session('user_roles') ?? (session('user.roles') ?? []);
        $rolesNorm = [];
        if (is_array($rolesRaw)) {
            foreach ($rolesRaw as $r) {
                if (is_string($r)) { $rolesNorm[] = mb_strtolower($r, 'UTF-8'); }
                elseif (is_array($r) && isset($r['roles'])) { $rolesNorm[] = mb_strtolower($r['roles'], 'UTF-8'); }
                elseif (is_object($r) && isset($r->roles)) { $rolesNorm[] = mb_strtolower($r->roles, 'UTF-8'); }
            }
        }
        $singleRole = session('user.role') ?? null;
        $singleRoleNorm = $singleRole ? mb_strtolower($singleRole, 'UTF-8') : null;
        $isComprasOrAdmin = in_array('area de compras', $rolesNorm, true) || in_array('admin requisicion', $rolesNorm, true) || $singleRoleNorm === 'admin requisicion';
        $estatusActual = $req->ultimoEstatus->estatus_id ?? null;
    @endphp
    <div id="modal-{{ $req->id }}" data-estatus-actual="{{ $estatusActual }}"
        class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex-1 overflow-y-auto p-6 relative">
                <button onclick="toggleModal('modal-{{ $req->id }}')"
                    class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 font-bold text-2xl">&times;</button>

                <h2 class="text-2xl font-bold mb-4">Requisición #{{ $req->id }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Información del Solicitante</h3>
                        <p><strong>Nombre:</strong> {{ $req->name_user }}</p>
                        <p><strong>Email:</strong> {{ $req->email_user }}</p>
                        <p><strong>Operación:</strong> {{ $req->operacion_user }}</p>
                        <p><strong>Prioridad:</strong> {{ ucfirst($req->prioridad_requisicion) }}</p>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-2">Detalles de la Requisición</h3>
                        <p><strong>Detalle:</strong> {{ $req->detail_requisicion }}</p>
                        <p><strong>Justificación:</strong> {{ $req->justify_requisicion }}</p>
                    </div>
                </div>


                <h3 class="text-xl font-semibold mt-6 mb-3">Productos</h3>
                <div class="overflow-x-auto">
                    @php $totalGeneral = 0; @endphp
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-100 text-gray-700">
                            <tr>
                                <th class="px-4 py-2 text-left">Producto</th>
                                <th class="w-20 px-2 py-2 text-center">Cant</th>
                                <th class="w-30 px-2 py-2 text-left">Proveedor</th>
                                <th class="px-4 py-2 text-right">Precio</th>
                                <th class="px-4 py-2 text-right">Total</th>
                                <th class="px-4 py-2 text-left">Distribución por Centros</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($req->productos as $prod)
                            @php
                                $cantidad = (float) ($prod->pivot->pr_amount ?? 0);
                                $provList = $prod->provList ?? collect();
                                $provJson = $prod->provJson ?? collect();
                                $pivotPxpId = $prod->pivotPxpId ?? ($prod->pivot->id_productoxproveedor ?? null);
                                $selProvId = $prod->selProvId ?? null;
                                $selProvName = $prod->selProvName ?? null;
                                $selPrice = (float) ($prod->selPrice ?? 0);

                                // localizar selección
                                $selected = null;
                                if ($provJson instanceof \Illuminate\Support\Collection) {
                                    if ($pivotPxpId) { $selected = $provJson->firstWhere('pxp_id', $pivotPxpId); }
                                    if (!$selected && $selProvId) { $selected = $provJson->firstWhere('id', $selProvId); }
                                } else if (is_array($provJson)) {
                                    foreach ($provJson as $pj) { if ($pivotPxpId && (($pj['pxp_id'] ?? null) == $pivotPxpId)) { $selected = $pj; break; } }
                                    if (!$selected && $selProvId) { foreach ($provJson as $pj) { if (($pj['id'] ?? null) == $selProvId) { $selected = $pj; break; } } }
                                }
                                // fallback BD
                                $fallbackRow = null;
                                if (!$selected && $pivotPxpId) {
                                    try {
                                        $fallbackRow = \Illuminate\Support\Facades\DB::table('productoxproveedor as pxp')
                                            ->join('proveedores as prov','prov.id','=','pxp.proveedor_id')
                                            ->where('pxp.id', $pivotPxpId)
                                            ->select('prov.id as prov_id','prov.prov_name','pxp.price_produc','pxp.moneda','pxp.id as pxp_id')
                                            ->first();
                                    } catch (\Throwable $e) { $fallbackRow = null; }
                                }

                                // valores a mostrar
                                $selectedName = $selProvName
                                    ?: ($selected['prov_name'] ?? ($selected->prov_name ?? null))
                                    ?: ($fallbackRow->prov_name ?? null);
                                $selectedPrice = $selPrice
                                    ?: (float) ($selected['price_produc'] ?? ($selected->price_produc ?? 0))
                                    ?: (float) ($fallbackRow->price_produc ?? 0);
                                $selectedCurrency = $prod->selCurrency
                                    ?? ($selected['moneda'] ?? ($selected->moneda ?? null))
                                    ?? ($fallbackRow->moneda ?? 'COP');
                                $selectedPriceCop = (float) ($prod->selPriceCop ?? ($selected['price_cop'] ?? ($selected->price_cop ?? 0)));
                                if (!$selectedPriceCop) { $selectedPriceCop = ($selectedCurrency === 'COP') ? $selectedPrice : $selectedPrice; }
                                // pxp id final para exponer
                                $pxpId = $pivotPxpId ?? ($selected['pxp_id'] ?? ($selected->pxp_id ?? ($fallbackRow->pxp_id ?? null)));

                                $totalProd = round(((float)$selectedPrice ?: 0) * $cantidad, 2);
                                $totalGeneral = round($totalGeneral + $totalProd, 2);
                                $distribucion = $prod->distribucion ?? collect();
                            @endphp
                            <tr class="align-top" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-pxp-id="{{ $pxpId }}">
                                <td class="px-4 py-3">{{ $prod->name_produc }}</td>
                                <td class="w-20 px-2 py-1 text-center font-semibold">{{ number_format($cantidad, 0, ',', '.') }}</td>
                                <td class="w-36 px-2 py-2 align-top">
                                     @if($isComprasOrAdmin && (int)$estatusActual === 1)
                                         @if($provList->count() === 1)
                                             @php $only = $provJson->first(); @endphp
                                             <div class="flex flex-col items-start gap-1">
                                                 <div class="w-full bg-green-50 border border-green-100 rounded-md p-2">
                                                     <div class="text-sm font-semibold text-green-800">{{ $only['prov_name'] ?? 'Proveedor' }}</div>
                                                     <div class="text-xs text-gray-600">{{ number_format($only['price_produc'] ?? 0, 2, ',', '.') }} {{ $only['moneda'] ?? 'COP' }}</div>
                                                 </div>
                                                 <select class="prov-select hidden" name="prov_select[{{ $prod->id }}]" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-qty="{{ $cantidad }}">
                                                     <option value="{{ $only['pxp_id'] ?? '' }}" data-prov-id="{{ $only['id'] ?? '' }}" data-price="{{ (float)($only['price_cop'] ?? ($only['price_produc'] ?? 0)) }}" data-price-original="{{ (float)($only['price_produc'] ?? 0) }}" data-currency-original="{{ $only['moneda'] ?? 'COP' }}" selected>{{ $only['prov_name'] ?? 'Proveedor' }}</option>
                                                 </select>
                                             </div>
                                          @else
                                             <div class="flex flex-col items-start gap-2">
                                                 <button type="button" title="Seleccionar proveedor" class="open-prov-modal-btn inline-flex items-center gap-2 px-3 py-2 rounded-md bg-blue-600 hover:bg-blue-700 text-white" data-providers='@json($provJson)' data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-selected="{{ $pxpId ?? '' }}" aria-label="Seleccionar proveedor">
                                                     <i class="fas fa-store"></i>
                                                     <span class="text-sm font-medium">Seleccionar proveedor</span>
                                                 </button>
                                                 <div class="mt-1 w-56">
                                                     <div id="selprov-name-{{ $req->id }}-{{ $prod->id }}" class="text-sm font-semibold truncate">{{ $selectedName ?? 'No seleccionado' }}</div>
                                                 </div>
                                             </div>

                                              <select class="prov-select hidden" name="prov_select[{{ $prod->id }}]" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-qty="{{ $cantidad }}">
                                                  <option value="">Seleccione</option>
                                                  @foreach($provJson as $pvj)
                                                      <option value="{{ $pvj['pxp_id'] }}" data-prov-id="{{ $pvj['id'] }}" data-price="{{ (float)($pvj['price_cop'] ?? ($pvj['price_produc'] ?? 0)) }}" data-price-original="{{ (float)($pvj['price_produc'] ?? 0) }}" data-currency-original="{{ $pvj['moneda'] ?? 'COP' }}" {{ ($pxpId && $pxpId == $pvj['pxp_id']) ? 'selected' : '' }}>{{ $pvj['prov_name'] }} ({{ number_format($pvj['price_produc'],2,',','.') }} {{ $pvj['moneda'] }})</option>
                                                  @endforeach
                                               </select>
                                          @endif
                                     @else
                                        <div class="text-sm truncate">{{ $selectedName ?? 'Proveedor' }}</div>
                                        <select class="prov-select hidden" name="prov_select[{{ $prod->id }}]" data-req="{{ $req->id }}" data-prod="{{ $prod->id }}" data-qty="{{ $cantidad }}">
                                            @php
                                                $optProvId = $selected['id'] ?? ($selected->id ?? ($fallbackRow->prov_id ?? ''));
                                                $optPriceCop = (float)($selected['price_cop'] ?? ($selected->price_cop ?? $selectedPrice)); if (!$optPriceCop) { $optPriceCop = $selectedPrice; }
                                                $optPriceOrig = (float)($selected['price_produc'] ?? ($selected->price_produc ?? $selectedPrice));
                                                $optCurrency = $selected['moneda'] ?? ($selected->moneda ?? $selectedCurrency);
                                            @endphp
                                            @if($pxpId)
                                                <option value="{{ $pxpId }}" data-prov-id="{{ $optProvId }}" data-price="{{ $optPriceCop }}" data-price-original="{{ $optPriceOrig }}" data-currency-original="{{ $optCurrency }}" selected>{{ $selectedName ?? 'Proveedor' }}</option>
                                            @endif
                                        </select>
                                     @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="text-sm" id="precio-{{ $req->id }}-{{ $prod->id }}">{{ number_format($selectedPrice,2,',','.') }} {{ $selectedCurrency }}</div>
                                    <div class="text-xs text-gray-500" id="preciocop-{{ $req->id }}-{{ $prod->id }}">{{ number_format($selectedPriceCop,2,',','.') }} COP</div>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold"><span class="total-cell" id="total-{{ $req->id }}-{{ $prod->id }}">{{ number_format($totalProd,2,',','.') }}</span> COP</td>
                                <td class="px-4 py-3">
                                    @if($distribucion->count() > 0)
                                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                        @foreach($distribucion as $centro)
                                        <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded">
                                            <span class="font-medium text-sm truncate">{{ $centro->name_centro }}</span>
                                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-bold">{{ $centro->amount }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @else
                                    <span class="text-gray-500 text-sm">No hay distribución registrada</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-4 py-3 text-right font-semibold" colspan="4">Total general</td>
                                <td class="px-4 py-3 text-right font-bold"><span id="total-general-{{ $req->id }}">{{ number_format($totalGeneral, 2, ',', '.') }}</span> COP</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Botones Aprobar/Rechazar -->
            @php
            $opNorm = mb_strtolower(trim($req->operacion_user ?? ''), 'UTF-8');
            $opNorm = strtr($opNorm, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
            $especial = in_array($opNorm, ['tecnologia','compras']);
            $estatusAprobar = null;
            if ($estatusActual === 1) {
                $estatusAprobar = $especial ? 3 : 2;
            } elseif ($estatusActual === 2) {
                $estatusAprobar = 3;
            } elseif ($estatusActual === 3) {
                $estatusAprobar = 4;
            }
            $estatusRechazar = 9;
            @endphp
            <div class="flex justify-end gap-2 p-4 border-t bg-gray-50">
                @if($estatusAprobar)
                <button class="status-btn bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusAprobar }}" data-action="aprobar" data-estatus-actual="{{ $estatusActual }}" data-requires-providers="{{ ($isComprasOrAdmin && (int)$estatusActual === 1) ? '1' : '0' }}">Aprobar</button>
                @endif
                <button class="status-btn bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition"
                    data-id="{{ $req->id }}" data-estatus="{{ $estatusRechazar }}" data-action="rechazar">Rechazar</button>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Modal global para elegir proveedor -->
    <div id="providerChoiceModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-60 p-4">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-xl max-h-[80vh] overflow-y-auto">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold">Seleccionar proveedor</h3>
                <button onclick="closeProviderChoiceModal()" class="text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-4">
                <div id="providerChoiceList" class="space-y-2"></div>
            </div>
            <div class="p-4 border-t flex justify-end gap-2">
                <button onclick="confirmProviderChoice()" class="px-4 py-2 bg-green-600 text-white rounded">Confirmar</button>
                <button onclick="closeProviderChoiceModal()" class="px-4 py-2 bg-gray-300 rounded">Cancelar</button>
            </div>
        </div>
    </div>

    @endsection

    @section('scripts')
    <input type="hidden" id="csrf_token" value="{{ csrf_token() }}" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/aprobacion.js') }}"></script>
    @endsection