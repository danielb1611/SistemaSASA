<?php

declare(strict_types=1);

require __DIR__ . '/config/functions.php';
require_modulo('marcas');

$tituloPagina = 'Marcas';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main class="dashboard dashboard--wide">
    <section class="card card--wide">
        <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"></path><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"></circle></svg>Marcas</h1>
        <p class="card__subtitle">Modulo en construccion.</p>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
