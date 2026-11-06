<?php

declare(strict_types=1);

namespace App\Sync;

use Illuminate\Support\Facades\Http;

/**
 * Canal HTTP das planilhas: usa o código de acesso do aparelho e confere o SHA-256 do que chega.
 */
final class HttpFileTransport implements FileTransport
{
    public function __construct(
        private readonly string $urlBase,
        private readonly string $token,
    ) {
    }

    public function listar(): array
    {
        /** @var array{arquivos: list<array{nome: string, sha256: string, tamanho: int}>} $dados */
        $dados = Http::withToken($this->token)
            ->acceptJson()
            ->timeout(120)
            ->get($this->url('planilhas'))
            ->throw()
            ->json();

        return $dados['arquivos'];
    }

    public function enviar(string $nome, string $conteudo, string $sha256): void
    {
        Http::withToken($this->token)
            ->withHeaders(['X-Sha256' => $sha256])
            ->withBody($conteudo, 'application/octet-stream')
            ->timeout(300)
            ->put($this->url('planilhas/' . rawurlencode($nome)))
            ->throw();
    }

    public function baixar(string $nome): array
    {
        $resposta = Http::withToken($this->token)
            ->timeout(300)
            ->get($this->url('planilhas/' . rawurlencode($nome)))
            ->throw();

        $conteudo = $resposta->body();
        $sha256 = (string) $resposta->header('X-Sha256');

        if (hash('sha256', $conteudo) !== $sha256) {
            throw new ArquivoCorrompidoException("A planilha {$nome} chegou corrompida. Ela não foi gravada.");
        }

        return ['conteudo' => $conteudo, 'sha256' => $sha256];
    }

    private function url(string $rota): string
    {
        return rtrim($this->urlBase, '/') . '/api/sincronizacao/' . $rota;
    }
}
