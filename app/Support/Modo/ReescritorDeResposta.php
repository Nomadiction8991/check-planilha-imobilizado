<?php

namespace App\Support\Modo;

final class ReescritorDeResposta
{
    private string $origemServidor;

    private string $origemLocal;

    public function __construct(string $origemServidor, string $origemLocal)
    {
        $this->origemServidor = rtrim($origemServidor, '/');
        $this->origemLocal = rtrim($origemLocal, '/');
    }

    /** @param array<string, list<string>> $cabecalhos
     * @return array<string, list<string>>
     */
    public function cabecalhos(array $cabecalhos): array
    {
        foreach ($cabecalhos as $nome => $valores) {
            $chave = strtolower($nome);
            if (in_array($chave, ['strict-transport-security', 'transfer-encoding', 'connection', 'keep-alive', 'content-length'], true)) {
                unset($cabecalhos[$nome]);
            } elseif ($chave === 'set-cookie') {
                $cabecalhos[$nome] = array_map(static function (string $cookie): string {
                    $partes = explode(';', $cookie);
                    $partes = array_values(array_filter($partes, static fn (string $parte, int $i): bool => $i === 0 || ! preg_match('/^\s*(?:domain\s*=|secure\s*$)/i', $parte), ARRAY_FILTER_USE_BOTH));

                    return implode(';', array_map(static fn (string $parte): string => preg_replace('/^(\s*samesite\s*=\s*)none\s*$/i', '$1Lax', $parte) ?? $parte, $partes));
                }, $valores);
            } elseif ($chave === 'location') {
                $cabecalhos[$nome] = array_map(fn (string $valor): string => str_starts_with($valor, $this->origemServidor) && in_array(substr($valor, strlen($this->origemServidor), 1), ['', '/', '?', '#'], true) ? $this->origemLocal.substr($valor, strlen($this->origemServidor)) : $valor, $valores);
            } elseif (in_array($chave, ['content-security-policy', 'content-security-policy-report-only'], true)) {
                $politicas = [];
                foreach ($valores as $valor) {
                    $diretivas = array_filter(array_map('trim', explode(';', $valor)), static fn (string $diretiva): bool => $diretiva !== '' && ! preg_match('/^upgrade-insecure-requests(?:\s|$)/i', $diretiva));
                    if ($diretivas !== []) {
                        $politicas[] = implode('; ', $diretivas);
                    }
                }
                if ($politicas === []) {
                    unset($cabecalhos[$nome]);
                } else {
                    $cabecalhos[$nome] = $politicas;
                }
            }
        }

        return $cabecalhos;
    }

    public function corpo(string $corpo, string $contentType): string
    {
        return self::ehTexto($contentType) ? str_replace([$this->origemServidor, str_replace('/', '\/', $this->origemServidor)], [$this->origemLocal, str_replace('/', '\/', $this->origemLocal)], $corpo) : $corpo;
    }

    public static function ehTexto(string $contentType): bool
    {
        $tipo = strtolower(trim(explode(';', $contentType, 2)[0]));

        return str_starts_with($tipo, 'text/') || in_array($tipo, ['application/json', 'application/javascript', 'application/xml', 'application/xhtml+xml', 'application/x-javascript'], true) || str_ends_with($tipo, '+json') || str_ends_with($tipo, '+xml');
    }
}
