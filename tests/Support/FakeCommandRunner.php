<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\CommandRunnerInterface;

/**
 * Executor de comandos falso: grava o que foi pedido e responde com o que o teste programou.
 * A resposta é escolhida pelo começo do comando (ex.: "nmcli -t -f DEVICE,TYPE device").
 */
final class FakeCommandRunner implements CommandRunnerInterface
{
    /** @var list<array{comando: list<string>, ambiente: array<string, string>}> */
    public array $chamadas = [];

    /**
     * @param  array<string, array{codigo: int, saida: string}>  $respostas
     */
    public function __construct(
        private array $respostas = [],
    ) {
    }

    public function executar(array $comando, array $ambiente = []): array
    {
        $this->chamadas[] = ['comando' => $comando, 'ambiente' => $ambiente];

        $linha = implode(' ', $comando);

        foreach ($this->respostas as $inicio => $resposta) {
            if (str_starts_with($linha, $inicio)) {
                return $resposta;
            }
        }

        return ['codigo' => 0, 'saida' => ''];
    }

    /**
     * @return list<list<string>>
     */
    public function comandos(): array
    {
        return array_map(fn (array $chamada): array => $chamada['comando'], $this->chamadas);
    }
}
