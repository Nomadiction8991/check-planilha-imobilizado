<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class Administracao extends Model
{
    protected $table = 'administracoes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'descricao',
        'cnpj',
        'estado',
        'cidade',
    ];
}
