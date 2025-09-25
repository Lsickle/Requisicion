<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstatusOrdenCompra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'estatus_orden_compra';

    protected $fillable = ['status_name'];
}
