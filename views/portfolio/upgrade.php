<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <div class="upgrade-panel">
            <h1><?= htmlspecialchars($t->get('portfolio.title')) ?></h1>
            <p><?= htmlspecialchars($t->get('portfolio.upgrade_body')) ?></p>
            <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
