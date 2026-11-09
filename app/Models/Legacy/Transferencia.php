<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cabeçalho de uma transferência de bens entre igrejas.
 */
class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'comum_origem_id',
        'comum_destino_id',
        'dependencia_destino_id',
        'numero_saida',
        'numero_entrada',
        'usuario_id',
    ];

    protected $casts = [
        'comum_origem_id' => 'integer',
        'comum_destino_id' => 'integer',
        'dependencia_destino_id' => 'integer',
        'numero_saida' => 'integer',
        'numero_entrada' => 'integer',
        'usuario_id' => 'integer',
    ];

    public function itens(): HasMany
    {
        return $this->hasMany(TransferenciaItem::class, 'transferencia_id');
    }
}
