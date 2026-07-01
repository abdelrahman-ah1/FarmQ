<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use FarmQ\App;

$app = App::create();
$app->run();
