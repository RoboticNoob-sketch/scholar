<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

App\Controllers\Admin\UserFormController::handle($pdo);
