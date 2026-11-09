<?php

declare(strict_types=1);

namespace Tests\Unit\Marca;

use PHPUnit\Framework\TestCase;

final class IconesPadraoTest extends TestCase
{
    private const PASTA_ICONES = __DIR__.'/../../../public/icons';

    private const FAVICON = __DIR__.'/../../../public/favicon.ico';

    public function test_icones_do_app_tem_o_tamanho_certo(): void
    {
        foreach (['pwa-32.png' => 32, 'pwa-180.png' => 180, 'pwa-192.png' => 192, 'pwa-512.png' => 512] as $arquivo => $tamanho) {
            $info = getimagesize(self::PASTA_ICONES.'/'.$arquivo);

            self::assertNotFalse($info, $arquivo);
            self::assertSame([$tamanho, $tamanho, IMAGETYPE_PNG], [$info[0], $info[1], $info[2]], $arquivo);
        }
    }

    public function test_icone_padrao_tem_cantos_transparentes(): void
    {
        $imagem = imagecreatefrompng(self::PASTA_ICONES.'/pwa-192.png');

        self::assertSame(127, (imagecolorat($imagem, 0, 0) >> 24) & 0x7F, 'O canto deve ser transparente.');
        self::assertLessThan(127, (imagecolorat($imagem, 96, 96) >> 24) & 0x7F, 'O centro deve ser visível.');
    }

    public function test_favicon_traz_as_medidas_16_32_e_48(): void
    {
        $dados = (string) file_get_contents(self::FAVICON);
        $cabecalho = unpack('vreservado/vtipo/vquantidade', substr($dados, 0, 6));

        self::assertIsArray($cabecalho);
        self::assertSame(1, $cabecalho['tipo'], 'O arquivo precisa ser um ícone (ICO).');

        $medidas = [];
        for ($i = 0; $i < $cabecalho['quantidade']; $i++) {
            $entrada = unpack('Cwidth/Cheight', substr($dados, 6 + 16 * $i, 2));
            $medidas[] = $entrada['width'] === 0 ? 256 : $entrada['width'];
        }

        foreach ([16, 32, 48] as $medida) {
            self::assertContains($medida, $medidas, "O favicon precisa da medida {$medida}.");
        }
    }
}
