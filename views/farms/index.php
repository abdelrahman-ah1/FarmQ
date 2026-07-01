<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <h1><?= htmlspecialchars($t->get('farms.title')) ?></h1>
            <a href="/farms/create?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('farms.create_btn')) ?></a>
        </div>

        <?php if ($farms === []): ?>
        <div class="dashboard-card empty-state">
            <p class="muted"><?= htmlspecialchars($t->get('farms.empty_body')) ?></p>
        </div>
        <?php else: ?>
        <div class="farm-list">
            <?php foreach ($farms as $farm): ?>
            <article class="farm-card">
                <div>
                    <h3><?= htmlspecialchars($farm['name']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($t->get('regions.' . $farm['region'])) ?></p>
                    <p class="farm-id muted"><?= htmlspecialchars($t->get('farms.farm_id')) ?>: <strong>#<?= (int) $farm['id'] ?></strong></p>
                    <?php if (!empty($farm['governorate'])): ?>
                    <p class="muted"><?= htmlspecialchars($t->get('governorates.' . $farm['governorate'])) ?></p>
                    <?php endif; ?>
                </div>
                <div class="farm-card-actions">
                <?php if ((int) ($farm['owner_user_id'] ?? 0) === (int) ($user['id'] ?? 0)): ?>
                <a href="/farms/boundary?lang=<?= $t->locale() ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars($t->get('farms.edit_boundary')) ?></a>
                <?php endif; ?>
                <span class="tier-badge tier-<?= htmlspecialchars($farm['tier']) ?>">
                    <?= htmlspecialchars($t->get('plans.' . $farm['tier'])) ?>
                </span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
