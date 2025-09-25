<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompraEstatus extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'orden_compra_estatus';

    protected $fillable = [
        'estatus_id',
        'orden_compra_id',
        'activo',
        'date_update',
        'comentario',
        'user_id',
    ];

    public function estatusRelation()
    {
        return $this->belongsTo(Estatus::class, 'estatus_id');
    }
}
