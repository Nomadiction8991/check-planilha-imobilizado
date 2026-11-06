<?php

namespace App\Support\Modo;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RepassePedido
{
    public function __construct(private string $servidorUrl) {}

    public function enviar(Request $pedido): Response
    {
        $servidor = rtrim($this->servidorUrl, '/');
        $local = $pedido->getSchemeAndHttpHost();
        $reescritor = new ReescritorDeResposta($servidor, $local);
        $cabecalhos = $pedido->headers->all();
        foreach ($cabecalhos as $nome => $valores) {
            if (in_array(strtolower($nome), ['host', 'connection', 'keep-alive', 'proxy-authorization', 'proxy-connection', 'te', 'trailer', 'transfer-encoding', 'upgrade', 'content-length', 'accept-encoding', 'x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto'], true)) {
                unset($cabecalhos[$nome]);
            } elseif (in_array(strtolower($nome), ['origin', 'referer'], true)) {
                $cabecalhos[$nome] = array_map(static fn (string $v): string => str_starts_with($v, $local) && in_array(substr($v, strlen($local), 1), ['', '/', '?', '#'], true) ? $servidor.substr($v, strlen($local)) : $v, $valores);
            }
        }
        $cabecalhos['Accept-Encoding'] = ['identity'];
        $cabecalhos['X-Forwarded-Proto'] = ['https'];
        $cabecalhos['X-Forwarded-Host'] = [(string) parse_url($servidor, PHP_URL_HOST)];
        $cabecalhos['X-Forwarded-For'] = [$pedido->ip() ?? ''];
        $multipart = str_starts_with(strtolower((string) $pedido->headers->get('Content-Type')), 'multipart/form-data');
        if ($multipart) {
            unset($cabecalhos['content-type']);
        }
        $cliente = Http::withHeaders($cabecalhos)->connectTimeout(5)->timeout(120)->withoutRedirecting()->withOptions(['stream' => true]);
        $opcoes = [];
        $arquivos = [];
        if (! in_array($pedido->method(), ['GET', 'HEAD'], true)) {
            if ($multipart) {
                $partes = [];
                $adicionar = function (mixed $valor, string $nome) use (&$adicionar, &$partes, &$arquivos): void {
                    if (is_array($valor)) {
                        foreach ($valor as $chave => $item) {
                            $adicionar($item, $nome === '' ? (string) $chave : $nome.'['.$chave.']');
                        }
                    } elseif ($valor instanceof UploadedFile) {
                        $arquivo = fopen($valor->getRealPath(), 'rb');
                        $arquivos[] = $arquivo;
                        $partes[] = ['name' => $nome, 'contents' => $arquivo, 'filename' => $valor->getClientOriginalName()];
                    } elseif ($valor !== null) {
                        $partes[] = ['name' => $nome, 'contents' => (string) $valor];
                    }
                };
                $adicionar($pedido->all(), '');
                $cliente = $cliente->asMultipart();
                $opcoes['multipart'] = $partes;
            } else {
                $opcoes['body'] = $pedido->getContent();
            }
        }
        try {
            $resposta = $cliente->send($pedido->method(), $servidor.$pedido->getRequestUri(), $opcoes);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new ServidorIndisponivelException('Não foi possível conectar ao servidor.', 0, $e);
        } finally {
            foreach ($arquivos as $arquivo) {
                if (is_resource($arquivo)) {
                    fclose($arquivo);
                }
            }
        }
        $originais = $resposta->headers();
        $headers = $reescritor->cabecalhos($originais);
        $tipo = $resposta->header('Content-Type') ?? '';
        if (ReescritorDeResposta::ehTexto($tipo)) {
            $corpo = $reescritor->corpo($resposta->body(), $tipo);
            $headers['Content-Length'] = [(string) strlen($corpo)];

            return new Response($corpo, $resposta->status(), $headers);
        }
        $tamanho = $resposta->header('Content-Length');
        if ($tamanho !== null) {
            $headers['Content-Length'] = [$tamanho];
        }
        $fluxo = $resposta->toPsrResponse()->getBody();

        return new StreamedResponse(static function () use ($fluxo): void {
            while (! $fluxo->eof()) {
                echo $fluxo->read(65536);
            }
        }, $resposta->status(), $headers);
    }
}
