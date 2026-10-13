<?php

declare(strict_types=1);

require __DIR__ . '/config/functions.php';
require_modulo('proveedores');

$tituloPagina = 'Proveedores';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main class="dashboard dashboard--wide">
    <section class="card card--wide">
        <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17h4V5H2v12h3"></path><path d="M20 17h2v-3.34a4 4 0 0 0-1.17-2.83L19 9h-5v8h1"></path><circle cx="7.5" cy="17.5" r="2.5"></circle><circle cx="17.5" cy="17.5" r="2.5"></circle></svg>Proveedores</h1>
        <p class="card__subtitle">Modulo en construccion.</p>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
