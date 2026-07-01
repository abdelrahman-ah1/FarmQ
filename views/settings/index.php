<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <h1><?= htmlspecialchars($t->get('settings.title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('settings.subtitle')) ?></p>

        <div class="ingestion-grid">
            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('settings.deployment')) ?></h2>
                <dl class="metric-list">
                    <div><dt>APP_URL</dt><dd><?= htmlspecialchars($config['app_url']) ?></dd></div>
                    <div><dt>APP_ENV</dt><dd><?= htmlspecialchars($config['app_env']) ?></dd></div>
                    <div><dt><?= htmlspecialchars($t->get('settings.default_locale')) ?></dt><dd><?= htmlspecialchars($config['default_locale']) ?></dd></div>
                    <div><dt>Redis</dt><dd><?= htmlspecialchars($config['redis_host']) ?></dd></div>
                    <div><dt>DEV_UNLOCK_PAID</dt><dd><?= $config['dev_unlock_paid'] ? 'ON' : 'OFF' ?></dd></div>
                </dl>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('settings.payment')) ?></h2>
                <dl class="metric-list">
                    <div><dt><?= htmlspecialchars($t->get('settings.gateway')) ?></dt><dd><?= htmlspecialchars($config['payment_gateway']) ?></dd></div>
                    <div><dt><?= htmlspecialchars($t->get('settings.api_key')) ?></dt><dd><?= $config['payment_key_set'] ? '••••••••' : '—' ?></dd></div>
                </dl>
                <p class="muted arc-note"><?= htmlspecialchars($t->get('settings.payment_note')) ?></p>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('settings.stats')) ?></h2>
                <dl class="metric-list">
                    <div><dt><?= htmlspecialchars($t->get('settings.users')) ?></dt><dd><?= (int) $stats['users'] ?></dd></div>
                    <div><dt><?= htmlspecialchars($t->get('settings.farms_count')) ?></dt><dd><?= (int) $stats['farms'] ?></dd></div>
                </dl>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
