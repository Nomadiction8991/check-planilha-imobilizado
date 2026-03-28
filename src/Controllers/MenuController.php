<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\SessionManager;

class MenuController extends BaseController
{
    public function index(): void
    {
        if (!SessionManager::isAuthenticated()) {
            $this->redirecionar('/login');
            return;
        }

        if (SessionManager::getComumId()) {
            $this->redirecionar('/products/view');
            return;
        }

        $this->redirecionar('/churches');
    }
}
