<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Executa um programa do sistema sem passar pelo shell. Os argumentos chegam separados,
 * então um nome ou uma senha com espaço ou aspas não quebram o comando.
 */
interface CommandRunnerInterface
{
    /**
     * @param  list<string>  $comando  programa e argumentos, cada um em sua posição
     * @param  array<string, string>  $ambiente  variáveis extras, só para este comando
     * @return array{codigo: int, saida: string}
     */
    public function executar(array $comando, array $ambiente = []): array;
}
