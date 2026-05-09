<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Produto extends Model
{
    protected $table = 'produtos';

    protected $primaryKey = 'id_produto';

    public $timestamps = false;

    protected $fillable = [
        'comum_id',
        'codigo',
        'tipo_bem_id',
        'bem',
        'complemento',
        'altura_m',
        'largura_m',
        'comprimento_m',
        'dependencia_id',
        'editado_tipo_bem_id',
        'editado_bem',
        'editado_complemento',
        'editado_altura_m',
        'editado_largura_m',
        'editado_comprimento_m',
        'editado_dependencia_id',
        'novo',
        'importado',
        'checado',
        'editado',
        'imprimir_etiqueta',
        'imprimir_14_1',
        'condicao_14_1',
        'observacao',
        'nota_numero',
        'nota_data',
        'nota_valor',
        'nota_fornecedor',
        'administrador_acessor_id',
        'ativo',
    ];

    protected $casts = [
        'comum_id' => 'integer',
        'tipo_bem_id' => 'integer',
        'dependencia_id' => 'integer',
        'altura_m' => 'decimal:3',
        'largura_m' => 'decimal:3',
        'comprimento_m' => 'decimal:3',
        'editado_tipo_bem_id' => 'integer',
        'editado_altura_m' => 'decimal:3',
        'editado_largura_m' => 'decimal:3',
        'editado_comprimento_m' => 'decimal:3',
        'editado_dependencia_id' => 'integer',
        'novo' => 'integer',
        'importado' => 'integer',
        'checado' => 'integer',
        'editado' => 'integer',
        'imprimir_etiqueta' => 'integer',
        'imprimir_14_1' => 'integer',
        'nota_numero' => 'integer',
        'ativo' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('ativo', 1);
    }

    public function scopeNewProducts(Builder $query): Builder
    {
        return $query->where('novo', 1);
    }

    public function comum(): BelongsTo
    {
        return $this->belongsTo(Comum::class, 'comum_id', 'id');
    }

    public function dependencia(): BelongsTo
    {
        return $this->belongsTo(Dependencia::class, 'dependencia_id', 'id');
    }

    public function tipoBem(): BelongsTo
    {
        return $this->belongsTo(TipoBem::class, 'tipo_bem_id', 'id');
    }
}
