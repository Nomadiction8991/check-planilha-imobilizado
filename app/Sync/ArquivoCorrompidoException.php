<?php

declare(strict_types=1);

namespace App\Sync;

use RuntimeException;

/**
 * O conteúdo recebido não confere com o SHA-256 declarado: o arquivo é recusado.
 */
final class ArquivoCorrompidoException extends RuntimeException
{
}
