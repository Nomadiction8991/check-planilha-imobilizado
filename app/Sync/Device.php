<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Database\Eloquent\Model;

/**
 * Notebook cadastrado para sincronizar (tabela sincronizacao_aparelhos).
 */
final class Device extends Model
{
    protected $table = 'sincronizacao_aparelhos';

    protected $fillable = ['nome', 'token_hash', 'usuario_id'];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'ultimo_contato_em' => 'datetime',
        'revogado_em' => 'datetime',
    ];

    public function estaRevogado(): bool
    {
        return $this->revogado_em !== null;
    }
}
