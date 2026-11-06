<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Sincroniza as planilhas nos dois sentidos, pelo nome e pelo SHA-256.
 *
 * - Arquivo que só existe de um lado é copiado para o outro.
 * - Arquivo igual dos dois lados não é transferido de novo.
 * - Mesmo nome com conteúdo diferente vai para a lista de conflitos. Nada é sobrescrito.
 */
final class SpreadsheetSync
{
    /**
     * @return array{enviadas: int, recebidas: int, iguais: int, conflitos: list<string>}
     */
    public function executar(FileTransport $remoto, SpreadsheetStore $local): array
    {
        $resumo = ['enviadas' => 0, 'recebidas' => 0, 'iguais' => 0, 'conflitos' => []];

        $daOutraPonta = collect($remoto->listar())->keyBy('nome');
        $deste = collect($local->listar())->keyBy('nome');

        foreach ($deste as $nome => $arquivo) {
            if (! $daOutraPonta->has($nome)) {
                $remoto->enviar($nome, $local->ler((string) $nome)['conteudo'], (string) $arquivo['sha256']);
                $resumo['enviadas']++;

                continue;
            }

            if ($daOutraPonta[$nome]['sha256'] !== $arquivo['sha256']) {
                $resumo['conflitos'][] = (string) $nome;

                continue;
            }

            $resumo['iguais']++;
        }

        foreach ($daOutraPonta as $nome => $arquivo) {
            if ($deste->has($nome)) {
                continue;
            }

            $baixado = $remoto->baixar((string) $nome);
            $local->gravar((string) $nome, $baixado['conteudo'], $baixado['sha256']);
            $resumo['recebidas']++;
        }

        return $resumo;
    }
}
