<?php

declare(strict_types=1);

namespace App\Sync;

/**
 * Mescla o histórico de auditoria nos dois sentidos: recebe as linhas que faltam e envia as que o
 * servidor não tem. Repetir a mescla não transfere nada de novo.
 */
final class AuditLogSync
{
    /**
     * @return array{enviadas: int, recebidas: int}
     */
    public function executar(AuditTransport $remoto, AuditLog $local): array
    {
        $doServidor = $remoto->baixar();
        $hashesDoServidor = array_flip(array_column($doServidor, 'sha256'));

        $recebidas = $local->acrescentar($doServidor);

        $queFaltaNoServidor = array_values(array_filter(
            $local->linhas(),
            fn (array $entrada): bool => ! isset($hashesDoServidor[$entrada['sha256']]),
        ));

        $enviadas = $queFaltaNoServidor === [] ? 0 : $remoto->enviar($queFaltaNoServidor);

        return ['enviadas' => $enviadas, 'recebidas' => $recebidas];
    }
}
