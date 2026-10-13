<?php

$nombreHeader = $_SESSION['nombre'] ?? '';
$esAdminHeader = ($_SESSION['rol'] ?? '') === 'admin';
$paginaActual = basename($_SERVER['PHP_SELF']);

if (!isset($pdo)) {
    require __DIR__ . '/../config/db.php';
}

$buzonCount = is_logged_in()
    ? contar_buzon($pdo, (int) ($_SESSION['user_id'] ?? 0), $_SESSION['rol'] ?? null)
    : 0;

$perfilHeader = ['nombre' => $nombreHeader, 'apellido' => '', 'departamento' => '', 'email' => '', 'rol' => $_SESSION['rol'] ?? 'usuario'];
if (is_logged_in()) {
    $stmtPerfil = $pdo->prepare('SELECT nombre, apellido, departamento, email, rol FROM usuarios WHERE id = ? LIMIT 1');
    $stmtPerfil->execute([(int) ($_SESSION['user_id'] ?? 0)]);
    $filaPerfil = $stmtPerfil->fetch();
    if ($filaPerfil) {
        $perfilHeader = $filaPerfil;
    }
}

$menuModulos = [];
foreach (MODULOS as $clave => $label) {
    if (puede_ver($clave)) {
        $menuModulos[$clave . '.php'] = $label;
    }
}

$iconosMenu = [
    'dashboard.php'    => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="9"></rect><rect x="14" y="3" width="7" height="5"></rect><rect x="14" y="12" width="7" height="9"></rect><rect x="3" y="16" width="7" height="5"></rect></svg>',
    'proveedores.php'  => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17h4V5H2v12h3"></path><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>',
    'marcas.php'       => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>',
    'insumos.php'      => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>',
    'precios.php'      => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"></rect><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><path d="M12 11h4"></path><path d="M12 16h4"></path><path d="M8 11h.01"></path><path d="M8 16h.01"></path></svg>',
    'compras.php'      => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path></svg>',
    'comparativa.php'  => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="M7 21h10"></path><path d="M12 3v18"></path><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path></svg>',
    'reportes.php'     => '<svg class="mainmenu__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v5h5"></path><path d="M16 13H8"></path><path d="M16 17H8"></path><path d="M10 9H8"></path></svg>',
];
?>
<header class="topbar">
    <a href="dashboard.php" class="topbar__brand" aria-label="Inicio">
        <img class="topbar__logo" src="LogoEmpresa.png?v=<?= e((string) @filemtime(__DIR__ . '/../LogoEmpresa.png')) ?>" alt="Logo Empresa">
    </a>
    <nav class="topbar__nav">
        <button type="button" class="badge badge--<?= $esAdminHeader ? 'admin' : 'user' ?> badge--btn" id="btn-perfil" aria-label="Ver mis datos">
            <?php if ($esAdminHeader): ?>
                <svg class="badge__icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            <?php else: ?>
                <svg class="badge__icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            <?php endif; ?>
            <?= e($nombreHeader) ?>
        </button>
        <a href="mensajes.php" class="btn btn--blue is-active btn--icon" aria-label="Mensajes<?= $buzonCount > 0 ? ': ' . e((string) $buzonCount) . ' sin leer' : '' ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg><?php if ($buzonCount > 0): ?><span class="badge badge--count"><?= e((string) $buzonCount) ?></span><?php endif; ?></a>
        <?php if ($esAdminHeader): ?>
            <a href="admin.php" class="btn btn--blue is-active btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>Administrar</a>
        <?php endif; ?>
        <a href="logout.php" class="btn btn--red is-active btn--icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>Cerrar</a>
    </nav>
</header>
<nav class="mainmenu">
    <?php $indiceMenu = 0; foreach ($menuModulos as $href => $label): ?>
        <?php if ($indiceMenu > 0): ?>
            <span class="mainmenu__sep" aria-hidden="true">|</span>
        <?php endif; ?>
        <a href="<?= e($href) ?>" class="mainmenu__link<?= $paginaActual === $href ? ' is-active' : '' ?>"><?= $iconosMenu[$href] ?? '' ?><?= e($label) ?></a>
        <?php $indiceMenu++; ?>
    <?php endforeach; ?>
</nav>
<script>
    (function () {
        var btnCerrar = document.querySelector('.topbar__nav a[href="logout.php"]');
        if (!btnCerrar) {
            return;
        }
        btnCerrar.addEventListener('click', function (event) {
            event.preventDefault();
            if (btnCerrar.classList.contains('is-loading')) {
                return;
            }
            btnCerrar.classList.add('is-loading');
            btnCerrar.innerHTML = '<span class="btn__spinner" aria-hidden="true"></span>Cerrando...';
            setTimeout(function () {
                window.location.href = 'logout.php';
            }, 2000);
        });
    })();
</script>
<div class="modal" id="modal-perfil" data-modal>
    <div class="modal__backdrop"></div>
    <div class="modal__dialog" role="dialog" aria-modal="true" aria-labelledby="modal-perfil-titulo">
        <header class="modal__header">
            <h2 class="card__title card__title--icon" id="modal-perfil-titulo"><svg class="card__title-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>Datos</h2>
            <button type="button" class="modal__close" data-modal-close aria-label="Cerrar">&times;</button>
        </header>

        <dl class="perfil-lista">
            <div class="perfil-lista__item">
                <dd><?= e(mb_strtoupper(trim((string) $perfilHeader['nombre'] . ' ' . (string) $perfilHeader['apellido']), 'UTF-8')) ?></dd>
            </div>
            <div class="perfil-lista__item">
                <dt class="field__label">Rol</dt>
                <dd>
                    <?php $rolPerfil = $perfilHeader['rol'] ?? ($_SESSION['rol'] ?? 'usuario'); ?>
                    <span class="badge badge--<?= $rolPerfil === 'admin' ? 'admin' : 'user' ?>">
                        <?= e($rolPerfil === 'admin' ? 'Administrador' : 'Usuario') ?>
                    </span>
                </dd>
            </div>
            <div class="perfil-lista__item">
                <dt class="field__label">Departamento</dt>
                <dd><?= e((string) $perfilHeader['departamento'] !== '' ? $perfilHeader['departamento'] : 'Sin departamento') ?></dd>
            </div>
            <div class="perfil-lista__item">
                <dt class="field__label">Correo</dt>
                <dd><?= e((string) $perfilHeader['email']) ?></dd>
            </div>
        </dl>
    </div>
</div>
<script>
    (function () {
        var btnPerfil = document.getElementById('btn-perfil');
        var modalPerfil = document.getElementById('modal-perfil');
        if (!btnPerfil || !modalPerfil) {
            return;
        }
        btnPerfil.addEventListener('click', function () {
            modalPerfil.classList.add('is-open');
        });
        modalPerfil.querySelectorAll('[data-modal-close]').forEach(function (el) {
            el.addEventListener('click', function () {
                modalPerfil.classList.remove('is-open');
            });
        });
        modalPerfil.querySelector('.modal__backdrop').addEventListener('click', function () {
            modalPerfil.classList.remove('is-open');
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                modalPerfil.classList.remove('is-open');
            }
        });
    })();
</script>
