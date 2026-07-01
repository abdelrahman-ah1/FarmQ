<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('portfolio.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('portfolio.subtitle')) ?></p>
            </div>
        </div>

        <?php if ($error = flash('portfolio_error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('portfolio.errors.' . $error)) ?></div>
        <?php endif; ?>

        <?php if ($tierGate->can('multi_farm')): ?>
        <div class="dashboard-card">
            <h2><?= htmlspecialchars($t->get('portfolio.link_farm')) ?></h2>
            <p class="muted"><?= htmlspecialchars($t->get('portfolio.link_hint')) ?></p>
            <form method="post" action="/portfolio/link?lang=<?= $t->locale() ?>" class="form inline-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="farm_id"><?= htmlspecialchars($t->get('portfolio.farm_id')) ?></label>
                    <input type="number" id="farm_id" name="farm_id" min="1" required>
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('portfolio.link_submit')) ?></button>
            </form>
        </div>
        <?php else: ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('portfolio.paid_required')) ?></div>
        <?php endif; ?>

        <div class="farm-list" style="margin-top:1rem;">
            <?php foreach ($summaries as $item): ?>
            <?php $farm = $item['farm']; ?>
            <article class="farm-card portfolio-card">
                <div>
                    <h3><?= htmlspecialchars($farm['name']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($t->get('regions.' . $farm['region'])) ?></p>
                    <dl class="metric-list compact-metrics">
                        <div><dt>N</dt><dd><?= $item['latest_n'] ?? '—' ?></dd></div>
                        <div><dt>NDVI</dt><dd><?= $item['ndvi_mean'] ?? '—' ?></dd></div>
                        <div><dt><?= htmlspecialchars($t->get('ingestion.crop_title')) ?></dt><dd><?= htmlspecialchars($item['crop'] ?? '—') ?></dd></div>
                    </dl>
                </div>
                <form method="post" action="/farms/switch?lang=<?= $t->locale() ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                    <button type="submit" class="btn btn-secondary"><?= htmlspecialchars($t->get('portfolio.open_farm')) ?></button>
                </form>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
