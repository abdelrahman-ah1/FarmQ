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
            <h2><?= htmlspecialchars($t->get('portfolio.accept_invite')) ?></h2>
            <p class="muted"><?= htmlspecialchars($t->get('portfolio.accept_hint')) ?></p>
            <form method="post" action="/portfolio/link?lang=<?= $t->locale() ?>" class="form inline-form">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="invite_code"><?= htmlspecialchars($t->get('portfolio.invite_code')) ?></label>
                    <input type="text" id="invite_code" name="invite_code" required autocomplete="off" placeholder="<?= htmlspecialchars($t->get('portfolio.invite_placeholder')) ?>">
                </div>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('portfolio.accept_submit')) ?></button>
            </form>
        </div>

        <form method="get" action="/portfolio" class="form portfolio-filters dashboard-card">
            <input type="hidden" name="lang" value="<?= htmlspecialchars($t->locale()) ?>">
            <div class="filter-row">
                <div class="form-group">
                    <label for="filter_region"><?= htmlspecialchars($t->get('portfolio.filter_region')) ?></label>
                    <select id="filter_region" name="region">
                        <option value=""><?= htmlspecialchars($t->get('portfolio.filter_all')) ?></option>
                        <?php foreach (['delta', 'upper_egypt', 'reclaimed_desert'] as $region): ?>
                        <option value="<?= $region ?>" <?= ($filters['region'] ?? '') === $region ? 'selected' : '' ?>><?= htmlspecialchars($t->get('regions.' . $region)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_crop"><?= htmlspecialchars($t->get('portfolio.filter_crop')) ?></label>
                    <select id="filter_crop" name="crop">
                        <option value=""><?= htmlspecialchars($t->get('portfolio.filter_all')) ?></option>
                        <?php foreach ($cropOptions as $code): ?>
                        <option value="<?= htmlspecialchars($code) ?>" <?= ($filters['crop'] ?? '') === $code ? 'selected' : '' ?>><?= htmlspecialchars($code) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="filter_flag"><?= htmlspecialchars($t->get('portfolio.filter_flag')) ?></label>
                    <select id="filter_flag" name="flag">
                        <option value=""><?= htmlspecialchars($t->get('portfolio.filter_all')) ?></option>
                        <option value="stale" <?= ($filters['flag'] ?? '') === 'stale' ? 'selected' : '' ?>><?= htmlspecialchars($t->get('portfolio.flag_stale')) ?></option>
                        <option value="low_ndvi" <?= ($filters['flag'] ?? '') === 'low_ndvi' ? 'selected' : '' ?>><?= htmlspecialchars($t->get('portfolio.flag_low_ndvi')) ?></option>
                    </select>
                </div>
                <button type="submit" class="btn btn-secondary"><?= htmlspecialchars($t->get('portfolio.filter_apply')) ?></button>
            </div>
            <p class="muted"><?= htmlspecialchars($t->get('portfolio.showing_count', ['shown' => count($summaries), 'total' => $totalCount])) ?></p>
        </form>
        <?php else: ?>
        <div class="dashboard-card upgrade-card">
            <p><?= htmlspecialchars($t->get('portfolio.paid_required')) ?></p>
            <a href="/billing?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('upgrade.cta')) ?></a>
        </div>
        <?php endif; ?>

        <div class="farm-list" style="margin-top:1rem;">
            <?php foreach ($summaries as $item): ?>
            <?php $farm = $item['farm']; ?>
            <article class="farm-card portfolio-card">
                <div>
                    <h3><?= htmlspecialchars($farm['name']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($t->get('regions.' . $farm['region'])) ?></p>
                    <?php if (!empty($item['access_role']) && $item['access_role'] !== 'owner'): ?>
                    <span class="tier-badge tier-free"><?= htmlspecialchars($t->get('portfolio.role_' . $item['access_role'])) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['is_stale'])): ?>
                    <span class="alert-badge"><?= htmlspecialchars($t->get('portfolio.badge_stale')) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($item['is_low_ndvi'])): ?>
                    <span class="alert-badge alert-badge-warn"><?= htmlspecialchars($t->get('portfolio.badge_low_ndvi')) ?></span>
                    <?php endif; ?>
                    <dl class="metric-list compact-metrics">
                        <div><dt>N</dt><dd><?= $item['latest_n'] ?? '—' ?></dd></div>
                        <div><dt>NDVI</dt><dd><?= $item['ndvi_mean'] ?? '—' ?></dd></div>
                        <div><dt><?= htmlspecialchars($t->get('ingestion.crop_title')) ?></dt><dd><?= htmlspecialchars($item['crop'] ?? '—') ?></dd></div>
                    </dl>
                </div>
                <div class="export-actions">
                <?php if (!empty($item['has_plan'])): ?>
                <a href="/portfolio/report?farm_id=<?= (int) $farm['id'] ?>&lang=<?= $t->locale() ?>" target="_blank" rel="noopener" class="btn btn-secondary btn-sm"><?= htmlspecialchars($t->get('portfolio.share_report')) ?></a>
                <?php endif; ?>
                <form method="post" action="/farms/switch?lang=<?= $t->locale() ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="farm_id" value="<?= (int) $farm['id'] ?>">
                    <button type="submit" class="btn btn-secondary"><?= htmlspecialchars($t->get('portfolio.open_farm')) ?></button>
                </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
