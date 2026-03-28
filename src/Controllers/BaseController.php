<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Traits\RequestHandlerTrait;
use App\Controllers\Traits\ResponseHandlerTrait;
use App\Controllers\Traits\SecurityTrait;

/**
 * Classe base para todos os controllers
 * Utiliza traits para separar responsabilidades:
 * - RequestHandlerTrait: extração de dados HTTP
 * - ResponseHandlerTrait: renderização e redirecionamento
 * - SecurityTrait: validação CSRF e segurança
 */
abstract class BaseController
{
    use RequestHandlerTrait;
    use ResponseHandlerTrait;
    use SecurityTrait;
}
