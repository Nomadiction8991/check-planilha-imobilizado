<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class TipoBem extends Model
{
    protected $table = 'tipos_bens';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'descricao',
    ];

    protected $casts = [
        'codigo' => 'integer',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Produto::class, 'tipo_bem_id', 'id');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('ativo', 1);
    }
}
