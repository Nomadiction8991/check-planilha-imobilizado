<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class TipoBem extends Model
{
    protected $table = 'tipos_bens';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'administracao_id',
        'codigo',
        'descricao',
    ];

    protected $casts = [
        'administracao_id' => 'integer',
        'codigo' => 'integer',
    ];

    public function administracao(): BelongsTo
    {
        return $this->belongsTo(Administracao::class, 'administracao_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Produto::class, 'tipo_bem_id', 'id');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('ativo', 1);
    }
}
