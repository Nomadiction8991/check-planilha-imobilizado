<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Illuminate\Http\Request;

$app = require __DIR__ . '/../bootstrap/app.php';

$app->handleRequest(Request::capture());
