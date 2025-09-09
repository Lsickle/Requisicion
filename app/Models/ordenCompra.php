<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenCompra extends Model
{
    use SoftDeletes;

    protected $table = 'orden_compras';

    protected $fillable = [
        'requisicion_id'
    ];

    public function requisicion(): BelongsTo
    {
        return $this->belongsTo(Requisicion::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(OrdenCompraProducto::class, 'orden_compras_id');
    }
}