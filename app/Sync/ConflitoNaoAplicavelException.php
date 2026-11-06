<?php

declare(strict_types=1);

namespace App\Sync;

use RuntimeException;

/**
 * O valor do notebook não pode ser aplicado no servidor. A mensagem pode ser mostrada à pessoa.
 */
final class ConflitoNaoAplicavelException extends RuntimeException
{
}
