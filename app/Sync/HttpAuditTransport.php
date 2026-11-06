<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\Http;

/**
 * Canal HTTP do histórico de auditoria: usa o código de acesso do aparelho.
 */
final class HttpAuditTransport implements AuditTransport
{
    public function __construct(
        private readonly string $urlBase,
        private readonly string $token,
    ) {
    }

    public function baixar(): array
    {
        /** @var array{linhas: list<array{sha256: string, linha: string}>} $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(120)
            ->get($this->url('auditoria'))
            ->throw()
            ->json();

        return $dados['linhas'];
    }

    public function enviar(array $entradas): int
    {
        /** @var array{recebidas: int} $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(120)
            ->post($this->url('auditoria'), ['linhas' => $entradas])
            ->throw()
            ->json();

        return $dados['recebidas'];
    }

    private function url(string $rota): string
    {
        return rtrim($this->urlBase, '/') . '/api/sincronizacao/' . $rota;
    }
}
