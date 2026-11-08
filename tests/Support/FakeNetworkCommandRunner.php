<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CommandRunnerInterface;

final class FakeNetworkCommandRunner implements CommandRunnerInterface
{
    public int $chamadas = 0;
    public int $codigo = 0;
    public string $saida = '';

    public function executar(array $comando, array $ambiente = []): array
    {
        $this->chamadas++;

        return ['codigo' => $this->codigo, 'saida' => $this->saida];
    }
}
