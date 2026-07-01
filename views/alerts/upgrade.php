<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <div class="upgrade-panel">
            <h1><?= htmlspecialchars($t->get('alerts_page.title')) ?></h1>
            <p><?= htmlspecialchars($t->get('alerts_page.upgrade_body')) ?></p>
            <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
            <a href="/billing?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('upgrade.cta')) ?></a>
            <p class="muted"><?= htmlspecialchars($t->get('upgrade.payment_note')) ?></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
