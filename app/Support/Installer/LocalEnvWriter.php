<?php

declare(strict_types=1);

namespace App\Support\Installer;

use InvalidArgumentException;
use RuntimeException;

/**
 * Gera o arquivo .env de uma cópia local (notebook da igreja).
 *
 * O .env do repositório aponta para o host "db" do Docker. Na cópia local o banco
 * roda no próprio notebook, então o arquivo é gerado do zero.
 */
final class LocalEnvWriter
{
    private const VALOR_SEM_ASPAS = '/^[A-Za-z0-9_.\/:@+=-]+$/';

    /**
     * Gera a chave de criptografia da instalação. Cada notebook tem a própria.
     */
    public static function gerarChaveDeAplicacao(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * @param  array<string, mixed>  $valores
     */
    public function render(array $valores): string
    {
        $porta = (int) $valores['porta'];

        $linhas = [
            'APP_NAME="Check Planilha"',
            'APP_ENV=production',
            'APP_DEBUG=false',
            'APP_KEY=' . $this->valor((string) $valores['app_key']),
            'APP_URL=' . $this->valor('http://localhost:' . $porta),
            'DB_CONNECTION=mysql',
            'DB_HOST=127.0.0.1',
            'DB_PORT=' . (int) $valores['banco_porta'],
            'DB_DATABASE=' . $this->valor((string) $valores['banco']),
            'DB_USERNAME=' . $this->valor((string) $valores['usuario']),
            'DB_PASSWORD=' . $this->valor((string) $valores['senha']),
            'SESSION_DRIVER=file',
            'SESSION_SECURE_COOKIE=false',
            'CACHE_STORE=file',
            'QUEUE_CONNECTION=database',
            'MODO_IGREJA_ATIVO=true',
            'MODO_IGREJA_PORTA=' . $porta,
        ];

        return implode("\n", $linhas) . "\n";
    }

    /**
     * Grava o arquivo de forma atômica, só para o dono (0600), porque tem segredos.
     *
     * @param  array<string, mixed>  $valores
     */
    public function write(string $caminho, array $valores): void
    {
        $pasta = dirname($caminho);

        if (! is_dir($pasta) || ! is_writable($pasta)) {
            throw new RuntimeException(
                "Não é possível gravar o .env em {$pasta}: a pasta não existe ou não permite escrita.",
            );
        }

        $conteudo = $this->render($valores);
        $temporario = $caminho . '.tmp';

        if (file_put_contents($temporario, $conteudo, LOCK_EX) === false) {
            throw new RuntimeException("Não foi possível escrever o arquivo temporário {$temporario}.");
        }

        chmod($temporario, 0600);

        if (! rename($temporario, $caminho)) {
            @unlink($temporario);

            throw new RuntimeException("Não foi possível trocar o arquivo {$caminho} pelo novo conteúdo.");
        }
    }

    /**
     * Valores simples ficam como estão. Os demais vão entre aspas simples.
     * Aspas simples dentro do valor não são aceitas, para não quebrar o arquivo.
     */
    private function valor(string $valor): string
    {
        if (preg_match(self::VALOR_SEM_ASPAS, $valor) === 1) {
            return $valor;
        }

        if (str_contains($valor, "'")) {
            throw new InvalidArgumentException('O valor não pode conter aspas simples. Escolha outro.');
        }

        return "'" . $valor . "'";
    }
}
