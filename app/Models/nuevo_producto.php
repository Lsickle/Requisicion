<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nuevo_Producto extends Model
{
    use HasFactory, SoftDeletes;

    // Nombre de la tabla
    protected $table = 'nuevo_producto';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'name_user',
        'email_user',
        'nombre',
        'descripcion',
    ];

    // Campos que se tratan como fechas
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
}
