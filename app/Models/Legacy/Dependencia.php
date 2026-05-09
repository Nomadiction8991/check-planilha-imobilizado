<?php

declare(strict_types=1);

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class Dependencia extends Model
{
    protected $table = 'dependencias';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'comum_id',
        'descricao',
    ];

    protected $casts = [
        'comum_id' => 'integer',
    ];

    public function comum(): BelongsTo
    {
        return $this->belongsTo(Comum::class, 'comum_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Produto::class, 'dependencia_id', 'id');
    }

    public function activeProducts(): HasMany
    {
        return $this->products()->where('ativo', 1);
    }
}
