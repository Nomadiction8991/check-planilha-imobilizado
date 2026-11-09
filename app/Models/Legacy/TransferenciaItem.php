<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um bem dentro de uma transferência, com a cópia dos dados do momento.
 */
class TransferenciaItem extends Model
{
    protected $table = 'transferencia_itens';

    protected $fillable = [
        'transferencia_id',
        'produto_id',
        'codigo_anterior',
        'codigo_novo',
        'tipo_bem',
        'descricao',
        'quantidade',
        'valor',
        'dependencia_origem',
    ];

    protected $casts = [
        'transferencia_id' => 'integer',
        'produto_id' => 'integer',
        'quantidade' => 'integer',
        'valor' => 'decimal:2',
    ];

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(Transferencia::class, 'transferencia_id');
    }
}
