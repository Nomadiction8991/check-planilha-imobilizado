<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Sync\ArquivoCorrompidoException;
use App\Sync\ConflitoDeArquivoException;
use App\Sync\SpreadsheetStore;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Pasta das planilhas enviadas: cada arquivo só entra se o SHA-256 conferir.
 */
class SpreadsheetStoreTest extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pasta = sys_get_temp_dir() . '/cp-planilhas-' . uniqid();
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->pasta));

        parent::tearDown();
    }

    public function test_grava_o_arquivo_e_lista_com_o_sha256(): void
    {
        $loja = new SpreadsheetStore($this->pasta);

        $this->assertSame('gravado', $loja->gravar('importacao_1_100.csv', 'codigo;bem', hash('sha256', 'codigo;bem')));

        $this->assertSame(
            [['nome' => 'importacao_1_100.csv', 'sha256' => hash('sha256', 'codigo;bem'), 'tamanho' => 10]],
            $loja->listar(),
        );
    }

    public function test_recusa_conteudo_que_nao_confere_com_o_sha256_e_nao_grava(): void
    {
        $loja = new SpreadsheetStore($this->pasta);

        try {
            $loja->gravar('importacao_1_200.csv', 'conteudo alterado', hash('sha256', 'conteudo original'));
            $this->fail('Arquivo corrompido precisava ser recusado.');
        } catch (ArquivoCorrompidoException) {
            $this->assertFileDoesNotExist($this->pasta . '/importacao_1_200.csv');
            $this->assertSame([], $loja->listar());
        }
    }

    public function test_arquivo_igual_nao_e_gravado_de_novo(): void
    {
        $loja = new SpreadsheetStore($this->pasta);
        $sha = hash('sha256', 'dados');

        $this->assertSame('gravado', $loja->gravar('a.csv', 'dados', $sha));
        $this->assertSame('ja_existe', $loja->gravar('a.csv', 'dados', $sha));
    }

    public function test_mesmo_nome_com_conteudo_diferente_vira_conflito_sem_sobrescrever(): void
    {
        $loja = new SpreadsheetStore($this->pasta);
        $loja->gravar('a.csv', 'versao um', hash('sha256', 'versao um'));

        try {
            $loja->gravar('a.csv', 'versao dois', hash('sha256', 'versao dois'));
            $this->fail('Conteúdo diferente com o mesmo nome precisa ser recusado.');
        } catch (ConflitoDeArquivoException) {
            $this->assertSame('versao um', file_get_contents($this->pasta . '/a.csv'));
        }
    }

    public function test_nome_com_caminho_ou_caractere_estranho_e_recusado(): void
    {
        $loja = new SpreadsheetStore($this->pasta);

        $this->expectException(InvalidArgumentException::class);

        $loja->gravar('../fora.csv', 'x', hash('sha256', 'x'));
    }
}
