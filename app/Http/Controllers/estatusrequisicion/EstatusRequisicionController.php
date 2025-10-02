<?php

namespace App\Http\Controllers\estatusrequisicion;

use App\Http\Controllers\Controller;
use App\Models\Requisicion;
use App\Models\Estatus;
use App\Models\Estatus_Requisicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\PermissionHelper;
use App\Jobs\EstatusRequisicionActualizadoJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\OrdenCompra;
use Illuminate\Support\Facades\Mail;
use App\Mail\EstatusRequisicionActualizado as EstatusRequisicionMail;

class EstatusRequisicionController extends Controller
{
    public function index()
    {
        // Requiere permiso aprobar requisicion
        if (!PermissionHelper::hasPermission('aprobar requisicion')) {
            return redirect()->route('index')->with('error', 'Debes iniciar sesión o no tienes permisos suficientes.');
        }

        // Roles de usuario desde sesión (helper los mantiene crudos)
        $userRoles = PermissionHelper::getUserRoles();
        $normalize = function($txt){
            $txt = mb_strtolower(trim($txt ?? ''), 'UTF-8');
            return strtr($txt,[
                'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u','ñ'=>'n'
            ]);
        };
        $userRolesNorm = array_map($normalize, $userRoles);

        // Mapa operación => rol requerido
        $operacionRoleMap = [
            // Originales
            'Cedi Frio' => 'Gerente operaciones',
            'Cedi Frio - Mantenimiento' => 'Gerente operaciones',
            'Mary Kay' => 'Gerente operaciones',
            'Oriflame' => 'Gerente operaciones',
            'Sony' => 'Gerente operaciones',
            'Macmillan' => 'Gerente operaciones',
            'Kw' => 'Gerente operaciones',
            'Tranpsrtes Vigia' => 'Gerente operaciones',
            'Cumbria' => 'Gerente operaciones',
            'Ortopedicos Futuro' => 'Gerente operaciones',
            'Naos' => 'Gerente operaciones',
            'Mattel' => 'Gerente operaciones',
            'Huawei' => 'Gerente operaciones',
            'Cedi Frio Agrofrut' => 'Gerente operaciones',
            'Cedi Frio Kikes' => 'Gerente operaciones',
            'Cedi Frio La Fazenda' => 'Gerente operaciones',
            'Cedi Frio Calypso' => 'Gerente operaciones',
            'Cedi Frio Ibazan' => 'Gerente operaciones',
            'Cedi Frio Todos Comemos' => 'Gerente operaciones',
            'Cedi Frio Food Box' => 'Gerente operaciones',
            'Inventarios' => 'Gerente operaciones',
            'Transportes' => 'Gerente operaciones',
            'Mejoramiento Contínuo' => 'Gerente operaciones',
            // Aliases / variantes sin acentos o con diferencias de mayúsculas/typos
            'Cedi frio' => 'Gerente operaciones',
            'Cei frtio - Mantenimiento' => 'Gerente operaciones', // typo reportado
            'Mejoramiento Continuo' => 'Gerente operaciones',

            // Otros roles
            'Seguridad' => 'Director de proyectos',
            'HSEQ' => 'Director de proyectos',
            'Calidad' => 'Director de proyectos',
            'Compras' => 'Director de proyectos',

            'Talento Humano' => 'Gerente talento humano',
            'Financiera' => 'Director contable',
        ];

        // Roles por etapas (estatus 2 y 3) y bandera para área de compras
        $stage2Roles = ['director de proyectos'];
        $stage3Roles = ['director contable'];
        $hasAdmin = in_array('admin requisicion', $userRolesNorm, true);
        $hasAreaCompras = in_array('area de compras', $userRolesNorm, true);
        $hasGerenteFinanciero = in_array('gerente financiero', $userRolesNorm, true);
        $rolesEstatus2 = ['gerente operaciones','gerente talento humano','director contable','director de proyectos','gerente financiero','admin requisicion'];
        $rolesEstatus3 = ['director contable','gerente financiero','admin requisicion'];
        $watchStatuses = [];
        if ($hasAdmin) { $watchStatuses = [1,2,3]; }
        else {
            if ($hasAreaCompras) { $watchStatuses[] = 1; }
            if (count(array_intersect($userRolesNorm, $rolesEstatus2))>0) { $watchStatuses[] = 2; }
            if (count(array_intersect($userRolesNorm, $rolesEstatus3))>0) { $watchStatuses[] = 3; }
        }
        $watchStatuses = array_values(array_unique($watchStatuses));
        if ($hasAreaCompras && !$hasAdmin && $watchStatuses === [1]) { $watchStatuses = [1]; }
        if (empty($watchStatuses)) {
            $requisiciones = collect(); $estatusOptions = collect();
            return view('requisiciones.aprobacion',[ 'requisiciones'=>$requisiciones,'requisicionesFiltradas'=>$requisiciones,'estatusOptions'=>$estatusOptions ]);
        }

        // Obtener requisiciones en las etapas relevantes
        $requisiciones = Requisicion::with(['ultimoEstatus.estatusRelation','productos','estatusHistorial.estatusRelation'])
            ->whereHas('ultimoEstatus', fn($q)=> $q->whereIn('estatus_id',$watchStatuses))
            ->orderBy('created_at','desc')->get();

        // Filtrar por operaciones que corresponden al rol del usuario
        $requisicionesFiltradas = $requisiciones->filter(function($req) use ($hasAreaCompras,$userRolesNorm,$operacionRoleMap,$hasGerenteFinanciero,$hasAdmin){
            if ($hasAdmin) return true; // Admin ve todas
            $estatusActual = optional($req->ultimoEstatus)->estatus_id;
            if ($estatusActual==1) return $hasAreaCompras; // sólo Área de compras
            if ($estatusActual==2) {
                $op = $req->operacion_user; if(!$op || !isset($operacionRoleMap[$op])) return false; $rolReq = mb_strtolower($operacionRoleMap[$op],'UTF-8');
                return in_array($rolReq,$userRolesNorm,true);
            }
            if ($estatusActual==3) {
                if ($hasGerenteFinanciero) return true;
                $op = $req->operacion_user; if(!$op || !isset($operacionRoleMap[$op])) return false; $rolReq = mb_strtolower($operacionRoleMap[$op],'UTF-8');
                return in_array('director contable',$userRolesNorm,true) && ($rolReq==='director contable');
            }
            return false;
        })->values();

        // Opciones (se mantienen para compatibilidad; la vista ahora calcula por requisición)
        $nextOptions = collect();
        if ($hasAreaCompras) { $nextOptions = Estatus::whereIn('id',[2,9])->pluck('status_name','id'); }
        elseif (in_array(2,$watchStatuses)) { $nextOptions = Estatus::whereIn('id',[3,9])->pluck('status_name','id'); }
        if (in_array(3,$watchStatuses)) { // sobrescribe para etapa 3 cuando aplica (gerente financiero o director contable)
            $nextOptions = Estatus::whereIn('id',[4,9])->pluck('status_name','id');
        }

        Log::info('Aprobacion index debug', [ 'watch_statuses'=>$watchStatuses,'filtered_reqs'=>$requisicionesFiltradas->count(), 'admin'=>$hasAdmin ]);
        return view('requisiciones.aprobacion',[ 'requisiciones'=>$requisiciones,'requisicionesFiltradas'=>$requisicionesFiltradas,'estatusOptions'=>$nextOptions ]);
    }

    public function show($id)
    {
        $requisicion = Requisicion::with('estatus')->findOrFail($id);
        $estatusOrdenados = $requisicion->estatus->sortBy('pivot.created_at');
        $estatusActual = $estatusOrdenados->last();

        return view('requisiciones.estatus', compact('requisicion', 'estatusOrdenados', 'estatusActual'));
    }

    public function updateStatus(Request $request, $requisicionId)
    {
        $allowedRoles = [ 'Area de compras','Gerente operaciones','Gerente talento humano','Director de proyectos','Director contable','Gerente financiero','Admin requisicion' ];
        if (!PermissionHelper::hasAnyRole($allowedRoles) || !PermissionHelper::hasPermission('aprobar requisicion')) {
            return response()->json(['success'=>false,'message'=>'Debes iniciar sesión o no tienes permisos.'],403);
        }

        $request->validate(['estatus_id'=>'required|exists:estatus,id']);

        try {
            DB::beginTransaction();
            $requisicion = Requisicion::with('ultimoEstatus')->findOrFail($requisicionId);
            $currentStatus = $requisicion->ultimoEstatus->estatus_id ?? null;
            $targetStatus = (int)$request->estatus_id;
            $opNombre = mb_strtolower(trim($requisicion->operacion_user ?? ''), 'UTF-8');
            $opNombre = strtr($opNombre, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u']);
            $operacionEspecial = in_array($opNombre, ['tecnologia','compras'], true);

            // Si no cambia
            if ($currentStatus === $targetStatus) {
                DB::rollBack();
                return response()->json(['success'=>true,'message'=>'El estatus ya está en el valor solicitado. Sin cambios.']);
            }

            // Obtener roles del usuario
            $userRoles = PermissionHelper::getUserRoles();
            $rolesLower = array_map(fn($r)=> mb_strtolower($r,'UTF-8'), $userRoles);
            $hasRole = fn($name)=> in_array(mb_strtolower($name,'UTF-8'), $rolesLower, true);
            $hasAdmin = $hasRole('Admin requisicion');

            // Reglas por estatus actual
            $allowedNext = [];
            if ($currentStatus == 1) {
                if (!($hasRole('Area de compras') || $hasAdmin)) { return response()->json(['success'=>false,'message'=>'Solo Área de compras o Admin pueden aprobar estatus 1'],403); }
                // Si operación especial, permitir salto directo a 3
                $allowedNext = $operacionEspecial ? [2,3,9] : [2,9];
                if ($hasAdmin) { $allowedNext = array_unique(array_merge($allowedNext,[3])); }
            } elseif ($currentStatus == 2) {
                if (!($hasAdmin || $hasRole('Gerente operaciones') || $hasRole('Gerente talento humano') || $hasRole('Director de proyectos') || $hasRole('Director contable') || $hasRole('Gerente financiero'))) {
                    return response()->json(['success'=>false,'message'=>'No puedes aprobar requisiciones en estatus 2'],403);
                }
                $allowedNext = [3,9];
            } elseif ($currentStatus == 3) {
                if (!($hasAdmin || $hasRole('Director contable') || $hasRole('Gerente financiero'))) {
                    return response()->json(['success'=>false,'message'=>'No puedes aprobar requisiciones en estatus 3'],403);
                }
                $allowedNext = [4,9];
            } else {
                return response()->json(['success'=>false,'message'=>'Estatus actual no gestionable desde este panel'],403);
            }
            // Ajuste: si operación especial y target 2, forzar salto a 3
            if ($currentStatus == 1 && $operacionEspecial && $targetStatus == 2) {
                $targetStatus = 3; // salto directo
            }
            if (!in_array($targetStatus, $allowedNext, true)) {
                return response()->json(['success'=>false,'message'=>'Transición no permitida desde el estatus actual'],403);
            }

            // Desactivar históricos
            Estatus_Requisicion::where('requisicion_id', $requisicionId)->update(['estatus'=>0]);

            $comentario = $request->comentario ? trim($request->comentario) : null;
            $nuevoEstatus = null;
            $mensajeAccion = 'aprobada';

            if ($targetStatus == 9) { // Rechazo según etapa
                if ($currentStatus == 1) { // a corrección (11)
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>11,
                        'estatus'=>1,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'enviada a corrección';
                } elseif ($currentStatus == 2) { // registrar 13 histórico y 10 finalizada
                    Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>13,
                        'estatus'=>0,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>10,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'rechazada';
                } elseif ($currentStatus == 3) { // registrar 9 histórico y 10 finalizada
                    Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>9,
                        'estatus'=>0,
                        'comentario'=>$comentario,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $nuevoEstatus = Estatus_Requisicion::create([
                        'requisicion_id'=>$requisicionId,
                        'estatus_id'=>10,
                        'estatus'=>1,
                        'comentario'=>null,
                        'date_update'=>now(),
                        'user_id'=>session('user.id')
                    ]);
                    $mensajeAccion = 'rechazada';
                }
            } else { // Aprobación directa al estatus solicitado (2->3, 3->4, 1->2)
                $nuevoEstatus = Estatus_Requisicion::create([
                    'requisicion_id'=>$requisicionId,
                    'estatus_id'=>$targetStatus,
                    'estatus'=>1,
                    'comentario'=>null,
                    'date_update'=>now(),
                    'user_id'=>session('user.id')
                ]);
                if ($targetStatus == 4) {
                    Log::info("Aprobación final (estatus 4) requisición {$requisicionId}");
                }
            }

            // Notificación correo (igual anterior)
            try {
                $userEmail = $requisicion->email_user ?? null;
                if (!$userEmail) {
                    $info = $this->obtenerInformacionUsuario($requisicion->user_id);
                    $userEmail = $info['email'] ?? null;
                }
                if ($userEmail) {
                    if (empty($nuevoEstatus->user_id) && session('user.id')) $nuevoEstatus->user_id = session('user.id');
                    Mail::to($userEmail)->send(new EstatusRequisicionMail($requisicion, $nuevoEstatus));
                } else {
                    EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, null);
                }
            } catch (\Exception $e) {
                Log::error('Error correo updateStatus: '.$e->getMessage());
                try { EstatusRequisicionActualizadoJob::dispatch($requisicion, $nuevoEstatus, $requisicion->email_user ?? null); } catch(\Exception $e2) {}
            }

            DB::commit();
            return response()->json([
                'success'=>true,
                'message'=>'Requisición '.$mensajeAccion.' correctamente',
                'nuevo_estatus'=> optional($nuevoEstatus->estatusRelation)->status_name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERROR CRÍTICO al actualizar estatus: '.$e->getMessage());
            return response()->json(['success'=>false,'message'=>'Error interno del servidor al actualizar el estatus: '.$e->getMessage()],500);
        }
    }

    private function obtenerInformacionUsuario($userId)
    {
        try {
            $apiToken = session('api_token');
            if (!$apiToken) {
                return ['email' => null];
            }

            $possibleEndpoints = [
                env('VPL_CORE') . "/api/user/{$userId}",
                env('VPL_CORE') . "/api/users/{$userId}",
                env('VPL_CORE') . "/api/auth/user/{$userId}",
            ];

            foreach ($possibleEndpoints as $apiUrl) {
                $response = Http::withoutVerifying()
                    ->withToken($apiToken)
                    ->timeout(10)
                    ->get($apiUrl);

                if ($response->successful()) {
                    $userData = $response->json();
                    $email = $userData['email'] ??
                        $userData['user']['email'] ??
                        ($userData['data']['email'] ?? null);

                    if ($email) {
                        return ['email' => $email];
                    }
                }
            }

            return ['email' => null];
        } catch (\Throwable $e) {
            return ['email' => null];
        }
    }
}
