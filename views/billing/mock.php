<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <div class="dashboard-card mock-gateway">
            <p class="mock-gateway-tag"><?= htmlspecialchars($t->get('billing.mock_tag')) ?></p>
            <h1><?= htmlspecialchars($t->get('billing.mock_title')) ?></h1>
            <p class="muted"><?= htmlspecialchars($t->get('billing.mock_body')) ?></p>
            <p class="price"><?= htmlspecialchars(number_format($price, 0)) ?> <?= htmlspecialchars($t->get('billing.egp')) ?></p>

            <form method="post" action="/billing/mock/complete?lang=<?= $t->locale() ?>" class="mock-actions">
                <?= csrf_field() ?>
                <input type="hidden" name="txn_id" value="<?= (int) $txnId ?>">
                <button type="submit" name="decision" value="approve" class="btn btn-primary"><?= htmlspecialchars($t->get('billing.mock_approve')) ?></button>
                <button type="submit" name="decision" value="decline" class="btn btn-ghost"><?= htmlspecialchars($t->get('billing.mock_decline')) ?></button>
            </form>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
