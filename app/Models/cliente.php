<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'cliente';

    protected $fillable = [
        'cli_name',
        'cli_nit',
        'cli_descrip',
        'cli_contacto',
        'cli_telefono',
        'cli_mail'
    ];
}