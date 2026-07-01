<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <div class="upgrade-panel">
            <h1><?= htmlspecialchars($t->get('irrigation.title')) ?></h1>
            <p><?= htmlspecialchars($t->get('irrigation.upgrade_body')) ?></p>
            <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
            <p class="muted"><?= htmlspecialchars($t->get('upgrade.payment_note')) ?></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
