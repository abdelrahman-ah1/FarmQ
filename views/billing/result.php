<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <div class="dashboard-card billing-result <?= $success ? 'billing-result-success' : 'billing-result-failed' ?>">
            <h1><?= htmlspecialchars($t->get($success ? 'billing.result_success_title' : 'billing.result_failed_title')) ?></h1>
            <p><?= htmlspecialchars($t->get($success ? 'billing.result_success_body' : 'billing.result_failed_body')) ?></p>
            <div class="export-actions">
                <a href="/dashboard?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('app.nav.dashboard')) ?></a>
                <?php if (!$success): ?>
                <a href="/billing?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('billing.try_again')) ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
