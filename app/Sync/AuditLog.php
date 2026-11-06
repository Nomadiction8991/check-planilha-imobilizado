<?php

declare(strict_types=1);

namespace App\Sync;

use InvalidArgumentException;

/**
 * Histórico de auditoria: um evento por linha JSON, só acrescentado.
 *
 * Cada linha é identificada pelo SHA-256 do próprio texto. A mescla acrescenta só o que falta,
 * então repetir a sincronização não duplica nada.
 */
final class AuditLog
{
    public function __construct(
        private readonly string $arquivo,
    ) {
    }

    /**
     * @return list<array{sha256: string, linha: string}>
     */
    public function linhas(): array
    {
        if (! is_file($this->arquivo)) {
            return [];
        }

        $linhas = file($this->arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        return array_map(
            fn (string $linha): array => ['sha256' => hash('sha256', $linha), 'linha' => $linha],
            $linhas,
        );
    }

    /**
     * Acrescenta as linhas que ainda não existem. Se uma linha não confere, nenhuma entra.
     * Devolve quantas linhas foram acrescentadas.
     *
     * @param  list<array{sha256: string, linha: string}>  $entradas
     */
    public function acrescentar(array $entradas): int
    {
        $existentes = array_flip(array_column($this->linhas(), 'sha256'));
        $novas = [];

        foreach ($entradas as $entrada) {
            $linha = (string) $entrada['linha'];
            $sha256 = (string) $entrada['sha256'];

            if (hash('sha256', $linha) !== $sha256) {
                throw new InvalidArgumentException('Uma linha da auditoria chegou corrompida. O lote inteiro foi recusado.');
            }

            if (str_contains($linha, "\n") || str_contains($linha, "\r") || ! is_array(json_decode($linha, true))) {
                throw new InvalidArgumentException('Uma linha da auditoria não é um evento válido. O lote inteiro foi recusado.');
            }

            if (isset($existentes[$sha256])) {
                continue;
            }

            $existentes[$sha256] = true;
            $novas[] = $linha;
        }

        if ($novas === []) {
            return 0;
        }

        $pasta = dirname($this->arquivo);

        if (! is_dir($pasta) && ! mkdir($pasta, 0775, true) && ! is_dir($pasta)) {
            throw new InvalidArgumentException('Não foi possível criar a pasta da auditoria.');
        }

        file_put_contents($this->arquivo, implode(PHP_EOL, $novas) . PHP_EOL, FILE_APPEND | LOCK_EX);

        return count($novas);
    }
}
