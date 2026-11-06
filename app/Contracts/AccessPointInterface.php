<?php

declare(strict_types=1);

namespace App\Contracts;

use RuntimeException;

/**
 * Liga e desliga o ponto de acesso Wi-Fi do notebook, para os celulares se conectarem sem internet.
 */
interface AccessPointInterface
{
    /**
     * Liga a rede com o nome e a senha dados.
     *
     * @throws RuntimeException com uma mensagem que pode ser mostrada ao usuário (nunca com a senha)
     */
    public function ligar(string $nomeDaRede, string $senha): void;

    public function desligar(): void;

    public function estaLigado(): bool;

    /**
     * Endereço deste notebook dentro da rede do ponto de acesso (ex.: 10.42.0.1), ou null.
     */
    public function enderecoDaRede(): ?string;
}
