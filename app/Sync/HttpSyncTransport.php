<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\Http;

/**
 * Canal HTTP entre o notebook e o servidor online. Usa o código de acesso do aparelho.
 * Erro de rede ou resposta de erro vira exceção, para quem chama decidir o que fazer.
 */
final class HttpSyncTransport implements SyncTransport
{
    public function __construct(
        private readonly string $urlBase,
        private readonly string $token,
    ) {
    }

    public function enviar(array $alteracoes): array
    {
        /** @var array<string, mixed> $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(60)
            ->post($this->url('enviar'), ['alteracoes' => $alteracoes])
            ->throw()
            ->json();

        return $dados;
    }

    public function receber(int $depois): array
    {
        /** @var array{alteracoes: list<array<string, mixed>>, cursor: int, mais: bool} $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(60)
            ->get($this->url('receber'), ['depois' => $depois])
            ->throw()
            ->json();

        return $dados;
    }

    private function url(string $rota): string
    {
        return rtrim($this->urlBase, '/') . '/api/sincronizacao/' . $rota;
    }
}
