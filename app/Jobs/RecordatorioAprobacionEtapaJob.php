<?php

namespace App\Jobs;

use App\Models\Estatus_Requisicion;
use App\Models\Requisicion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\AprobacionEtapaMail;

class RecordatorioAprobacionEtapaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $afterCommit = true;

    public function handle(): void
    {
        $cutoff = now()->subDay();
        $pendientes = Estatus_Requisicion::with('requisicion')
            ->where('estatus',1)
            ->whereIn('estatus_id',[2,3])
            ->where('date_update','<=',$cutoff)
            ->limit(500) // seguridad
            ->get();

        if ($pendientes->isEmpty()) {
            Log::info('RecordatorioAprobacionEtapaJob: sin pendientes para recordar');
            return;
        }

        foreach ($pendientes as $estatus) {
            $req = $estatus->requisicion;
            if (!$req) continue;
            $stageKey = $estatus->estatus_id == 2 ? 'stage2' : 'stage3';
            $destinatarios = $this->resolveDestinatarios($req, $stageKey);
            if (empty($destinatarios)) {
                Log::info('RecordatorioAprobacionEtapaJob: sin destinatarios', ['req'=>$req->id,'stage'=>$stageKey]);
                continue;
            }
            $id = $req->id;
            $op = $req->operacion_user ?? 'N/A';
            $prioridad = ucfirst($req->prioridad_requisicion ?? '');
            $cant = (int)($req->amount_requisicion ?? 0);
            $detalleUrl = route('requisiciones.show', $id);
            $panelUrl = url('/requisiciones/aprobacion');
            $subject = "Recordatorio: Requisición #{$id} pendiente por aprobación ({$op})";
            $mensaje = $stageKey==='stage2'
                ? "Recordatorio: la requisición #{$id} (prioridad {$prioridad}, {$cant} producto(s)) sigue pendiente de tu aprobación."
                : "Recordatorio: la requisición #{$id} continúa en espera de aprobación financiera.";
            try {
                Mail::to($destinatarios)->send(new AprobacionEtapaMail($req, $estatus, $stageKey, $subject, $mensaje, $panelUrl, $detalleUrl));
                Log::info('RecordatorioAprobacionEtapaJob enviado', ['req'=>$id,'stage'=>$stageKey,'to'=>$destinatarios]);
            } catch (\Throwable $e) {
                Log::error('RecordatorioAprobacionEtapaJob error envío', ['req'=>$id,'err'=>$e->getMessage()]);
            }
        }
    }

    private function resolveDestinatarios(Requisicion $req, string $stageKey): array
    {
        $role = null;
        if ($stageKey==='stage2') {
            $role = $this->getRoleTargetForReq($req);
        } elseif ($stageKey==='stage3') {
            $role = 'Gerente financiero';
        }
        if (!$role) return [];
        $map = $this->roleEmailMap();
        $normTarget = $this->norm($role);
        foreach ($map as $r=>$email) {
            if ($this->norm($r) === $normTarget && filter_var($email, FILTER_VALIDATE_EMAIL)) return [$email];
        }
        return [];
    }

    private function roleEmailMap(): array
    {
        return [
            'Director contable' => 'alejandro.arango@vigiaplus.com',
            'Gerente financiero' => 'alejandro.ramirez@cumbriaholdings.com',
            'Gerente talento humano' => 'kelly.montenegro@cumbriaholdings.com',
            'Gerente operaciones' => 'raul.castellanos@vigiaplus.com',
            'Director de proyectos' => 'wilson.rivera@vigiaplus.com',
        ];
    }

    private function getRoleTargetForReq(Requisicion $req): ?string
    {
        $opNorm = $this->norm($req->operacion_user);
        $nameNorm = $this->norm($req->name_user);
        if (in_array($opNorm,['financiero','financiera'],true)) {
            if ($nameNorm==='linda lozano') return 'Gerente talento humano';
            if ($nameNorm==='zelena mendoza') return 'Director contable';
        }
        $map = [
            'operaciones'=>'Gerente operaciones',
            'seguridad'=>'Director de proyectos',
            'hseq'=>'Director de proyectos',
            'calidad'=>'Director de proyectos',
            'financiero'=>'Gerente financiero',
            'financiera'=>'Gerente financiero',
        ];
        return $map[$opNorm] ?? null;
    }

    private function norm(?string $t): string
    {
        $t = mb_strtolower(trim($t ?? ''),'UTF-8');
        return strtr($t,[ 'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ä'=>'a','ë'=>'e','ï'=>'i','ö'=>'o','ü'=>'u','ñ'=>'n' ]);
    }
}
