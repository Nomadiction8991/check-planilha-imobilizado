<?php

declare(strict_types=1);

namespace App\Sync;

use Generator;
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

    /**
     * Baixa a cópia completa em fluxo (uma linha JSON por registro), sem carregar tudo na memória.
     *
     * @return Generator<int, string>
     */
    public function copia(): Generator
    {
        $corpo = Http::withToken($this->token)
            ->timeout(600)
            ->withOptions(['stream' => true])
            ->get($this->url('copia'))
            ->throw()
            ->toPsrResponse()
            ->getBody();

        $pendente = '';

        while (! $corpo->eof()) {
            $pendente .= $corpo->read(65536);

            while (($posicao = strpos($pendente, "\n")) !== false) {
                yield substr($pendente, 0, $posicao);
                $pendente = (string) substr($pendente, $posicao + 1);
            }
        }

        if (trim($pendente) !== '') {
            yield $pendente;
        }
    }

    /**
     * Número a partir do qual este notebook cria itens novos (faixa própria do aparelho).
     */
    public function faixaInicial(): int
    {
        /** @var array{faixa_inicial: int} $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(60)
            ->get($this->url('faixa'))
            ->throw()
            ->json();

        return (int) $dados['faixa_inicial'];
    }

    private function url(string $rota): string
    {
        return rtrim($this->urlBase, '/') . '/api/sincronizacao/' . $rota;
    }
}
