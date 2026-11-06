<?php

declare(strict_types=1);

namespace App\Support\Modo;

use Illuminate\Support\Facades\Http;
use Throwable;

final class VerificadorDeServidor
{
    public function verificar(): bool
    {
        $url = (string) config('modo_igreja.servidor_url');
        if ($url === '') {
            return false;
        }

        try {
            return Http::timeout(3)->get(rtrim($url, '/').'/up')->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
