<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\EstatusOrdenCompra;
use App\Models\Recepcion;

class OrdenCompraEstatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compra_estatus';

    protected $fillable = [
        'estatus_id',
        'orden_compra_id',
        'recepcion_id',
        'activo',
        'date_update',
        'user_name',
        'user_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'date_update' => 'datetime',
        'user_id' => 'string',
    ];

    public function estatusRelation()
    {
        return $this->belongsTo(EstatusOrdenCompra::class, 'estatus_id');
    }
    
    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
    }
}
