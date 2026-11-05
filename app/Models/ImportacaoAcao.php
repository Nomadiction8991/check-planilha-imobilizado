<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacaoAcao extends Model
{
    protected $table = 'importacao_acoes';

    protected $fillable = ['importacao_id', 'chave', 'tipo', 'acao'];
}
