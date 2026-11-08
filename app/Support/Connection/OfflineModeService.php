<?php

declare(strict_types=1);

namespace App\Support\Connection;

use App\Contracts\AccessPointInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Modo offline do notebook: liga e desliga o ponto de acesso e reúne o que a tela Conexão mostra.
 */
final class OfflineModeService
{
    /** Por quanto tempo (em segundos) a situação do ponto de acesso fica guardada. */
    private const SEGUNDOS_NO_CACHE = 15;

    private const CHAVE_DO_CACHE = 'modo-igreja.ponto-de-acesso';

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
        $ponto = $this->situacaoDoPontoDeAcesso();

        if (! $ponto['ligado']) {
            return [
                'ligado' => false,
                'rede' => $rede,
                'senha' => null,
                'texto_rede' => null,
                'endereco_sistema' => null,
            ];
        }

        $senha = $this->credenciais->senha();

        return [
            'ligado' => true,
            'rede' => $rede,
            'senha' => $senha,
            'texto_rede' => WifiQrPayload::para($rede, $senha),
            'endereco_sistema' => $ponto['endereco'] === null
                ? null
                : 'http://' . $ponto['endereco'] . ':' . (int) config('modo_igreja.porta'),
        ];
    }

    public function ligar(): void
    {
        try {
            $this->pontoDeAcesso->ligar((string) config('modo_igreja.rede_nome'), $this->credenciais->senha());
        } finally {
            // Mesmo com erro, a próxima consulta precisa ler a rede de novo.
            Cache::forget(self::CHAVE_DO_CACHE);
        }
    }

    public function desligar(): void
    {
        try {
            $this->pontoDeAcesso->desligar();
        } finally {
            Cache::forget(self::CHAVE_DO_CACHE);
        }
    }

    /**
     * Guarda só se o ponto está ligado e qual é o endereço. A senha nunca entra aqui, porque o cache fica em disco.
     *
     * @return array{ligado: bool, endereco: ?string}
     */
    private function situacaoDoPontoDeAcesso(): array
    {
        return Cache::remember(self::CHAVE_DO_CACHE, self::SEGUNDOS_NO_CACHE, function (): array {
            $ligado = $this->pontoDeAcesso->estaLigado();

            return [
                'ligado' => $ligado,
                'endereco' => $ligado ? $this->pontoDeAcesso->enderecoDaRede() : null,
            ];
        });
    }
}
