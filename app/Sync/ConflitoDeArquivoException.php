<?php

declare(strict_types=1);

namespace App\Sync;

use RuntimeException;

/**
 * Já existe um arquivo com o mesmo nome e outro conteúdo: nada é sobrescrito.
 */
final class ConflitoDeArquivoException extends RuntimeException
{
}
