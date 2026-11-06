<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Contracts\AccessPointInterface;
use RuntimeException;

/**
 * Ponto de acesso falso para os testes da tela: não mexe na rede de verdade.
 */
final class FakeAccessPoint implements AccessPointInterface
{
    public bool $ligado = false;

    public ?string $erroAoLigar = null;

    public ?string $enderecoDaRede = '10.42.0.1';

    public function ligar(string $nomeDaRede, string $senha): void
    {
        if ($this->erroAoLigar !== null) {
            throw new RuntimeException($this->erroAoLigar);
        }

        $this->ligado = true;
    }

    public function desligar(): void
    {
        $this->ligado = false;
    }

    public function estaLigado(): bool
    {
        return $this->ligado;
    }

    public function enderecoDaRede(): ?string
    {
        return $this->ligado ? $this->enderecoDaRede : null;
    }
}
