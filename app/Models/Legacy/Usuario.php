<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Usuario extends Model
{
    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'administracao_id',
        'administracoes_permitidas',
        'comum_id',
        'nome',
        'email',
        'senha',
        'ativo',
        'cpf',
        'rg',
        'rg_igual_cpf',
        'telefone',
        'casado',
        'nome_conjuge',
        'cpf_conjuge',
        'rg_conjuge',
        'rg_conjuge_igual_cpf',
        'telefone_conjuge',
        'endereco_cep',
        'endereco_logradouro',
        'endereco_numero',
        'endereco_complemento',
        'endereco_bairro',
        'endereco_cidade',
        'endereco_estado',
        'tipo',
        'permissions',
    ];

    protected $casts = [
        'administracao_id' => 'integer',
        'administracoes_permitidas' => 'array',
        'comum_id' => 'integer',
        'ativo' => 'integer',
        'rg_igual_cpf' => 'integer',
        'casado' => 'integer',
        'rg_conjuge_igual_cpf' => 'integer',
        'permissions' => 'array',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('ativo', 1);
    }

    public function comum(): BelongsTo
    {
        return $this->belongsTo(Comum::class, 'comum_id', 'id');
    }

    public function administracao(): BelongsTo
    {
        return $this->belongsTo(Administracao::class, 'administracao_id', 'id');
    }

    public function isAdministrator(): bool
    {
        $type = mb_strtolower(trim((string) ($this->tipo ?? '')), 'UTF-8');
        $email = mb_strtoupper(trim((string) ($this->email ?? '')), 'UTF-8');

        return (int) $this->getKey() === 1
            || $email === 'ADMIN@LOCALHOST'
            || in_array($type, ['admin', 'administrador'], true);
    }

    public function isProtectedAdministratorAccount(): bool
    {
        return (int) $this->getKey() === 1 || $this->isAdministrator();
    }
}
