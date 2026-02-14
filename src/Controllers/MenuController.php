<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\ViewRenderer;

class MenuController extends BaseController
{
    public function index(): void
    {
        ViewRenderer::render('menu');
    }
}
