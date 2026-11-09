<?php

declare(strict_types=1);

namespace Tests\Feature\Marca;

use Tests\TestCase;

final class NomeSistemaPadraoTest extends TestCase
{
    public function test_modelos_de_ambiente_definem_o_nome_padrao_do_sistema(): void
    {
        foreach (['.env.example', '.env.testing.example'] as $arquivo) {
            $conteudo = file_get_contents(base_path($arquivo));

            self::assertNotFalse($conteudo, "Não foi possível ler {$arquivo}.");
            self::assertMatchesRegularExpression('/^APP_NAME="Check Planilha"$/m', $conteudo);
        }
    }
}
