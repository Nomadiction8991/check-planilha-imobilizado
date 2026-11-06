<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Installer\LocalEnvWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Cria a configuração do notebook (modo igreja) na primeira vez.
 *
 * Se o arquivo já existe, ele não é mexido: trocar a chave de criptografia faria os dados
 * cifrados deste computador sumirem.
 */
class ConfigureLocalModeCommand extends Command
{
    private const PORTA_DO_BANCO_LOCAL = 3307;

    protected $signature = 'modo-igreja:configurar {--arquivo= : Caminho do arquivo de configuração a criar} {--socket= : Caminho do socket do MySQL; quando informado, o sistema conecta só por ele}';

    protected $description = 'Cria a configuração do modo igreja neste computador (só na primeira vez)';

    public function handle(LocalEnvWriter $escritor): int
    {
        $arquivo = (string) $this->option('arquivo');

        if ($arquivo === '') {
            $this->error('Informe o caminho do arquivo com --arquivo.');

            return self::FAILURE;
        }

        if (is_file($arquivo)) {
            $this->info("Configuração já existe em {$arquivo}. Nada foi alterado.");

            return self::SUCCESS;
        }

        try {
            $escritor->write($arquivo, [
                'app_key' => LocalEnvWriter::gerarChaveDeAplicacao(),
                'porta' => (int) config('modo_igreja.porta'),
                'banco' => 'check_planilha',
                'usuario' => 'check_planilha',
                'senha' => Str::random(24),
                'banco_porta' => self::PORTA_DO_BANCO_LOCAL,
                'socket' => (string) $this->option('socket'),
            ]);
        } catch (InvalidArgumentException | RuntimeException $erro) {
            $this->error($erro->getMessage());

            return self::FAILURE;
        }

        $this->info("Configuração criada em {$arquivo}.");

        return self::SUCCESS;
    }
}
