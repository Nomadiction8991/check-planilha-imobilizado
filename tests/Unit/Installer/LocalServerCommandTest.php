<?php

declare(strict_types=1);

namespace Tests\Unit\Installer;

use App\Support\Installer\LocalServerCommand;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocalServerCommandTest extends TestCase
{
    public function test_monta_o_servidor_embutido_na_porta_escolhida(): void
    {
        $this->assertSame(
            ['php', '-S', '0.0.0.0:8080', '-t', 'public', 'public/index.php'],
            (new LocalServerCommand())->argumentos(8080),
        );
    }

    public function test_recusa_porta_que_exige_administrador(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LocalServerCommand())->argumentos(80);
    }

    public function test_recusa_porta_fora_da_faixa_valida(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new LocalServerCommand())->argumentos(70000);
    }
}
