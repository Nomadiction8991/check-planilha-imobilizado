<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\AccessPointInterface;

/**
 * Modo offline do notebook: liga e desliga o ponto de acesso e reúne o que a tela Conexão mostra.
 */
final class OfflineModeService
{
    public function __construct(
        private readonly AccessPointInterface $pontoDeAcesso,
        private readonly HotspotCredentials $credenciais,
    ) {
    }

    /**
     * A senha e os QR codes só aparecem quando o ponto de acesso está ligado.
     *
     * @return array{ligado: bool, rede: string, senha: ?string, texto_rede: ?string, endereco_sistema: ?string}
     */
    public function status(): array
    {
        $rede = (string) config('modo_igreja.rede_nome');

        if (! $this->pontoDeAcesso->estaLigado()) {
            return [
                'ligado' => false,
                'rede' => $rede,
                'senha' => null,
                'texto_rede' => null,
                'endereco_sistema' => null,
            ];
        }

        $senha = $this->credenciais->senha();
        $endereco = $this->pontoDeAcesso->enderecoDaRede();

        return [
            'ligado' => true,
            'rede' => $rede,
            'senha' => $senha,
            'texto_rede' => WifiQrPayload::para($rede, $senha),
            'endereco_sistema' => $endereco === null
                ? null
                : 'http://' . $endereco . ':' . (int) config('modo_igreja.porta'),
        ];
    }

    public function ligar(): void
    {
        $this->pontoDeAcesso->ligar((string) config('modo_igreja.rede_nome'), $this->credenciais->senha());
    }

    public function desligar(): void
    {
        $this->pontoDeAcesso->desligar();
    }
}
