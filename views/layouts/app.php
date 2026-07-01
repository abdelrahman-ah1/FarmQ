<?php
$otherLocale = $t->locale() === 'ar' ? 'en' : 'ar';
$activeFarm = $activeFarm ?? null;
$farms = $farms ?? [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($t->locale()) ?>" dir="<?= htmlspecialchars($t->direction()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $t->get('meta.title')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="app-body app-authenticated">
    <div id="connectivity-banner" class="connectivity-banner connectivity-online" hidden
         data-online="<?= htmlspecialchars($t->get('connectivity.online')) ?>"
         data-offline="<?= htmlspecialchars($t->get('connectivity.offline')) ?>"
         data-slow="<?= htmlspecialchars($t->get('connectivity.slow')) ?>"></div>

    <header class="app-header">
        <div class="app-header-inner">
            <a href="/dashboard?lang=<?= $t->locale() ?>" class="brand">
                <img src="<?= asset('img/FarmQ_Logo.png') ?>" alt="FarmQ" class="brand-logo brand-logo-sm">
            </a>

            <button type="button" class="nav-toggle" data-nav-toggle aria-controls="app-nav" aria-expanded="false" aria-label="<?= htmlspecialchars($t->get('app.nav.menu')) ?>">
                <span></span><span></span><span></span>
            </button>

            <?php if ($farms !== []): ?>
            <form method="post" action="/farms/switch?lang=<?= $t->locale() ?>" class="farm-switcher">
                <?= csrf_field() ?>
                <label for="farm-select" class="sr-only"><?= htmlspecialchars($t->get('farms.active_farm')) ?></label>
                <select name="farm_id" id="farm-select" onchange="this.form.submit()">
                    <?php foreach ($farms as $farm): ?>
                    <option value="<?= (int) $farm['id'] ?>" <?= ($activeFarm && (int) $activeFarm['id'] === (int) $farm['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($farm['name']) ?> (<?= htmlspecialchars($t->get('regions.' . $farm['region'])) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>

            <nav class="app-nav" id="app-nav" aria-label="App">
                <a href="/dashboard?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/dashboard' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.dashboard')) ?></a>
                <a href="/blueprint?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/blueprint' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.blueprint')) ?></a>
                <a href="/ingestion?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/ingestion' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.data')) ?></a>
                <a href="/map?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/map' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.map')) ?></a>
                <a href="/irrigation?lang=<?= $t->locale() ?>" class="<?= str_starts_with($currentPath ?? '', '/irrigation') ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.irrigation')) ?></a>
                <a href="/history?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/history' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.history')) ?></a>
                <?php if (in_array($user['role'] ?? '', ['agronomist', 'admin'], true)): ?>
                <a href="/portfolio?lang=<?= $t->locale() ?>" class="<?= str_starts_with($currentPath ?? '', '/portfolio') ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.portfolio')) ?></a>
                <?php endif; ?>
                <a href="/farms?lang=<?= $t->locale() ?>" class="<?= str_starts_with($currentPath ?? '', '/farms') ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.farms')) ?></a>
                <?php if (($user['role'] ?? '') === 'admin'): ?>
                <a href="/settings?lang=<?= $t->locale() ?>" class="<?= ($currentPath ?? '') === '/settings' ? 'active' : '' ?>"><?= htmlspecialchars($t->get('app.nav.settings')) ?></a>
                <?php endif; ?>
            </nav>

            <div class="app-header-actions">
                <span class="user-badge"><?= htmlspecialchars($user['full_name'] ?? '') ?></span>
                <a href="/locale/<?= $otherLocale ?>" class="lang-toggle"><?= htmlspecialchars($t->get('common.switch_lang')) ?></a>
                <a href="/logout?lang=<?= $t->locale() ?>" class="btn btn-ghost"><?= htmlspecialchars($t->get('auth.logout')) ?></a>
            </div>
        </div>
    </header>

    <main class="app-main">
        <?php if ($success = flash('success')): ?>
        <div class="container"><div class="alert alert-success"><?= htmlspecialchars((string) $success) ?></div></div>
        <?php endif; ?>
        <?= $content ?? '' ?>
    </main>
    <footer style="text-align: center; padding: 2rem 0; font-size: 0.85rem; color: var(--text-muted);">
        <p>Designed by LogiQ Studio</p>
    </footer>
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <script src="<?= asset('js/connectivity.js') ?>" defer></script>
</body>
</html>
