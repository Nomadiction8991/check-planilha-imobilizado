<?php

declare(strict_types=1);

namespace App\Support\Connection;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Gera o QR code em SVG, para mostrar direto na página, sem imagem externa nem internet.
 */
final class QrCodeSvg
{
    public static function de(string $texto): string
    {
        $escritor = new Writer(new ImageRenderer(new RendererStyle(240, 2), new SvgImageBackEnd()));

        // Tira o cabeçalho XML do SVG, que não cabe dentro de uma página HTML.
        return (string) preg_replace('/^<\?xml[^>]*\?>\s*/', '', $escritor->writeString($texto));
    }
}
