<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InstaladorController extends Controller
{
    // Entrega o instalador do sistema (Windows .exe ou Linux .deb). A rota já exige login.
    public function baixar(string $sistema): BinaryFileResponse
    {
        // A chave vem da lista da configuração: nada que a URL informe vira caminho de arquivo.
        $arquivo = config("instaladores.arquivos.{$sistema}");
        abort_if($arquivo === null, 404);

        $caminho = rtrim((string) config('instaladores.pasta'), '/').'/'.$arquivo;
        abort_unless(is_file($caminho), 404, 'O instalador ainda não foi publicado neste servidor.');

        return response()->download($caminho, $arquivo);
    }
}
