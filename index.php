<?php

declare(strict_types=1);

require __DIR__ . '/config/functions.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

redirect('login.php');
