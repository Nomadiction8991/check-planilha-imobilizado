<?php

declare(strict_types=1);

namespace Tests\Unit\Sync;

use App\Sync\SensitiveFieldException;
use App\Sync\SensitiveFields;
use Illuminate\Encryption\Encrypter;
use Tests\TestCase;

class SensitiveFieldsTest extends TestCase
{
    public function test_cifra_e_decifra_o_campo_com_a_chave_desta_instalacao(): void
    {
        $campos = new SensitiveFields();

        $cifrado = $campos->cifrar('configuracoes', ['mail_host' => 'smtp.teste.local', 'mail_password' => 'segredo']);

        $this->assertNotSame('segredo', $cifrado['mail_password']);
        $this->assertSame('smtp.teste.local', $cifrado['mail_host']);
        $this->assertSame('segredo', $campos->decifrar('configuracoes', $cifrado)['mail_password']);
    }

    public function test_campo_cifrado_com_outra_chave_nao_abre(): void
    {
        $cifradoPorOutraInstalacao = (new Encrypter(random_bytes(32), 'AES-256-CBC'))->encryptString('segredo');

        $this->expectException(SensitiveFieldException::class);

        (new SensitiveFields())->decifrar('configuracoes', ['mail_password' => $cifradoPorOutraInstalacao]);
    }

    public function test_tabela_sem_campos_cifrados_nao_muda_a_linha(): void
    {
        $linha = ['bem' => 'Mesa'];

        $this->assertSame($linha, (new SensitiveFields())->decifrar('produtos', $linha));
        $this->assertSame($linha, (new SensitiveFields())->cifrar('produtos', $linha));
    }
}
