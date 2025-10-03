<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cliente';

    protected $fillable = [
        'cli_name',
        'cli_nit',
        'cli_descrip',
        'cli_contacto',
        'cli_telefono',
        'cli_mail',
    ];

    protected $dates = ['deleted_at'];
}
