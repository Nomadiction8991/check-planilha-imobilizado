<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\CommandRunnerInterface;
use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\Process;

/**
 * Executor real de comandos, feito com o Symfony Process (sem shell).
 */
final class ProcessCommandRunner implements CommandRunnerInterface
{
    private const TEMPO_LIMITE_SEGUNDOS = 60.0;

    public function executar(array $comando, array $ambiente = []): array
    {
        $processo = new Process(
            $comando,
            null,
            array_merge(getenv(), $ambiente),
            null,
            self::TEMPO_LIMITE_SEGUNDOS,
        );

        try {
            $processo->run();
        } catch (ProcessStartFailedException) {
            // O programa não existe neste computador (ex.: nmcli fora do Linux com NetworkManager).
            return ['codigo' => 127, 'saida' => 'Programa não encontrado: ' . $comando[0]];
        }

        return [
            'codigo' => (int) ($processo->getExitCode() ?? 1),
            'saida' => trim($processo->getOutput() . "\n" . $processo->getErrorOutput()),
        ];
    }
}
