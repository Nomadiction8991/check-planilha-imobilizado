<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\LegacyBrandingServiceInterface;
use Illuminate\Http\Response;

final class LegacyBrandingController extends Controller
{
    public function logo(LegacyBrandingServiceInterface $branding): Response
    {
        $logo = $branding->logo();

        return response($logo['conteudo'], 200, [
            'Content-Type' => $logo['mime'],
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
