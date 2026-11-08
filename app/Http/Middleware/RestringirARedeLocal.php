<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * No modo igreja, o notebook só atende a rede local e o próprio computador.
 *
 * A checagem usa o endereço real da conexão (REMOTE_ADDR), e não $request->ip(). O sistema confia em
 * cabeçalhos de proxy, e esses cabeçalhos podem ser falsificados por quem está na rede.
 */
final class RestringirARedeLocal
{
    /** Redes privadas, o próprio computador e os endereços locais do IPv6. */
    private const REDES_LOCAIS = [
        '127.0.0.0/8',
        '::1/128',
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '169.254.0.0/16',
        'fc00::/7',
        'fe80::/10',
    ];

    /** Cabeçalhos que mostram que o pedido passou por um proxy ou um túnel. */
    private const CABECALHOS_DE_PROXY = [
        'X-Forwarded-For',
        'X-Real-IP',
        'Forwarded',
        'CF-Connecting-IP',
        'True-Client-IP',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! config('modo_igreja.ativo')) {
            return $next($request);
        }

        // Um túnel na mesma máquina chega como localhost. Por isso, pedido com cabeçalho de proxy é recusado.
        foreach (self::CABECALHOS_DE_PROXY as $cabecalho) {
            if ($request->headers->has($cabecalho)) {
                abort(403, 'Este computador só atende a rede local da igreja.');
            }
        }

        $enderecoDaConexao = (string) $request->server->get('REMOTE_ADDR', '');
        if (! IpUtils::checkIp($enderecoDaConexao, self::REDES_LOCAIS)) {
            abort(403, 'Este computador só atende a rede local da igreja.');
        }

        return $next($request);
    }
}
