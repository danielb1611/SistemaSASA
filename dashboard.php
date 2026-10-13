<?php

declare(strict_types=1);

require __DIR__ . '/config/db.php';
require __DIR__ . '/config/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$stmt = $pdo->prepare('SELECT id, nombre, email, rol, creado_en FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION = [];
    session_destroy();
    redirect('login.php');
}

$_SESSION['rol'] = $usuario['rol'];
$_SESSION['nombre'] = $usuario['nombre'];
$_SESSION['modulos'] = modulos_de_usuario($pdo, (int) $usuario['id'], $usuario['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard · Gestion Insumos</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= e((string) @filemtime(__DIR__ . '/assets/css/style.css')) ?>">
</head>
<body class="app">
    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="dashboard">
        <section class="card card--wide">
            <h1 class="card__title card__title--muted card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>Hola, <?= e($usuario['nombre']) ?></h1>
            <p class="card__subtitle">Bienvenido al dashboard del sistema.</p>
        </section>
    </main>
</body>
</html>
