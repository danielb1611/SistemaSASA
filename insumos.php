<?php

declare(strict_types=1);

require __DIR__ . '/config/functions.php';
require_modulo('insumos');

$tituloPagina = 'Insumos';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main class="dashboard dashboard--wide">
    <section class="card card--wide">
        <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>Insumos</h1>
        <p class="card__subtitle">Modulo en construccion.</p>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
