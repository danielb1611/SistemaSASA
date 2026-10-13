<?php

declare(strict_types=1);

require __DIR__ . '/config/functions.php';
require_modulo('comparativa');

$tituloPagina = 'Comparativa de precios';

require __DIR__ . '/partials/head.php';
require __DIR__ . '/partials/header.php';
?>
<main class="dashboard dashboard--wide">
    <section class="card card--wide">
        <h1 class="card__title card__title--icon"><svg class="card__title-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"></path><path d="M7 21h10"></path><path d="M12 3v18"></path><path d="M3 7h2c2 0 5-1 7-2 2 1 5 2 7 2h2"></path></svg>Comparativa de precios</h1>
        <p class="card__subtitle">Modulo en construccion.</p>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
