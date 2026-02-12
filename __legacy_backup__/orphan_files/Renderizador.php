<?php

namespace App\Core;

/**
 * Renderizador (DEPRECATED)
 * 
 * @deprecated Use ViewRenderer instead
 * @see ViewRenderer
 * 
 * Esta classe está mantida apenas para backward compatibility.
 * Será removida na próxima versão major.
 */
class Renderizador
{
    /**
     * @deprecated Use ViewRenderer::render() instead
     */
    public static function renderizar(string $arquivo, array $dados = []): string
    {
        // Delega para ViewRenderer
        return ViewRenderer::renderView($arquivo, $dados);
    }
}
