<?php

declare(strict_types=1);

namespace App\Sync;

use RuntimeException;

/**
 * Um campo cifrado não abre com a chave desta instalação.
 */
final class SensitiveFieldException extends RuntimeException
{
}
