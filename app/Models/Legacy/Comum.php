<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Comum extends Model
{
    protected $table = 'comums';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'codigo',
        'cnpj',
        'descricao',
        'estado',
        'cidade',
        'estado_administracao',
        'cidade_administracao',
        'setor',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Produto::class, 'comum_id', 'id');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('ativo', 1);
    }

    public function dependencies(): HasMany
    {
        return $this->hasMany(Dependencia::class, 'comum_id', 'id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(Usuario::class, 'comum_id', 'id');
    }
}
