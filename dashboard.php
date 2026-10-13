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

$esAdminDash = $usuario['rol'] === 'admin';
$buzonCount = contar_buzon($pdo, (int) $usuario['id'], $usuario['rol']);
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
            <div class="section-head">
                <div>
                    <h2 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>Hola, <?= e(mb_strtoupper($usuario['nombre'], 'UTF-8')) ?></h2>
                    <p class="card__subtitle">
                        <?php if ($esAdminDash): ?>
                            <?= $buzonCount > 0
                                ? 'Tenes ' . e((string) $buzonCount) . ' mensaje(s) sin leer.'
                                : 'No tenes mensajes sin leer.' ?>
                        <?php else: ?>
                            <?= $buzonCount > 0
                                ? 'Tenes ' . e((string) $buzonCount) . ' respuesta(s) nueva(s).'
                                : ($usuario['rol'] === 'admin' ? '' : 'Envia consultas al administrador.') ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="section-head__tools">
                    <a href="mensajes.php" class="btn btn--blue btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>Mensajes</a>
                    <?php if ($buzonCount > 0): ?>
                        <span class="badge badge--nuevo"><?= e((string) $buzonCount) ?> nuevo(s)</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
