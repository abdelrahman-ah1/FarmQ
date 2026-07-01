<?php
$center = $mapData['center'] ?? ['lat' => 30.9, 'lng' => 31.1];
$ndvi = $mapData['ndvi'] ?? null;
$deficiency = $mapData['deficiency'] ?? null;
$scanInProgress = $mapData['scan_in_progress'] ?? false;
ob_start();
?>
<section class="section map-section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('map.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('map.subtitle')) ?></p>
            </div>
            <?php if ($canAccess && ($canEdit ?? true)): ?>
            <form method="post" action="/map/scan?lang=<?= $t->locale() ?>" data-requires-online>
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary" <?= $scanInProgress ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($scanInProgress ? $t->get('map.scanning') : $t->get('map.run_scan')) ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($mapError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('map.errors.' . $mapError)) ?></div>
        <?php endif; ?>

        <?php if (!$canAccess): ?>
        <div class="upgrade-panel">
            <h2><?= htmlspecialchars($t->get('upgrade.title')) ?></h2>
            <p><?= htmlspecialchars($t->get('map.upgrade_body')) ?></p>
            <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
            <p class="muted"><?= htmlspecialchars($t->get('upgrade.payment_note')) ?></p>
        </div>
        <?php else: ?>

        <?php if ($scanInProgress): ?>
        <div class="alert alert-success"><?= htmlspecialchars($t->get('map.scan_in_progress')) ?></div>
        <?php endif; ?>

        <?php if ($ndvi): ?>
        <div class="map-stats dashboard-card">
            <div class="metric-list">
                <div><dt>NDVI</dt><dd><?= htmlspecialchars((string) ($ndvi['ndvi_mean'] ?? '—')) ?>
                    <?php if (isset($mapData['ndvi_delta']) && $mapData['ndvi_delta'] !== null): ?>
                    <span class="ndvi-trend ndvi-trend-<?= $mapData['ndvi_delta'] >= 0 ? 'up' : 'down' ?>">
                        <?= $mapData['ndvi_delta'] >= 0 ? '▲' : '▼' ?> <?= htmlspecialchars((string) abs($mapData['ndvi_delta'])) ?>
                    </span>
                    <?php endif; ?>
                </dd></div>
                <div><dt>NDRE</dt><dd><?= htmlspecialchars((string) ($ndvi['ndre_mean'] ?? '—')) ?></dd></div>
                <div><dt><?= htmlspecialchars($t->get('map.health')) ?></dt><dd><?= htmlspecialchars($t->get('map.health_' . ($ndvi['health'] ?? 'unknown'))) ?></dd></div>
                <div><dt><?= htmlspecialchars($t->get('map.scan_date')) ?></dt><dd><?= htmlspecialchars((string) ($ndvi['scan_date'] ?? '—')) ?></dd></div>
                <?php if (!empty($mapData['sentinel']['scene_id'])): ?>
                <div><dt><?= htmlspecialchars($t->get('map.scene_id')) ?></dt><dd class="scene-id"><?= htmlspecialchars((string) $mapData['sentinel']['scene_id']) ?></dd></div>
                <div><dt><?= htmlspecialchars($t->get('map.cloud_cover')) ?></dt><dd><?= htmlspecialchars((string) ($mapData['sentinel']['cloud_cover_pct'] ?? '—')) ?>%</dd></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($mapData['rescan_recommended'])): ?>
            <p class="alert alert-error"><?= htmlspecialchars($t->get('map.rescan_recommended')) ?></p>
            <?php endif; ?>
            <?php if (!empty($mapData['previous_ndvi'])): ?>
            <p class="muted"><?= htmlspecialchars($t->get('map.previous_ndvi', ['value' => $mapData['previous_ndvi']])) ?></p>
            <?php endif; ?>
            <p class="muted arc-note"><?= htmlspecialchars((string) ($ndvi['methodology'] ?? '')) ?></p>
            <?php if (!empty($ndvi['data_source'])): ?>
            <p class="muted arc-note"><?= htmlspecialchars((string) $ndvi['data_source']) ?></p>
            <?php endif; ?>
        </div>
        <?php elseif (!$scanInProgress): ?>
        <div class="dashboard-card">
            <p class="muted"><?= htmlspecialchars($t->get('map.no_scan')) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($canAccess && !empty($mapData['scan_history'])): ?>
        <div class="dashboard-card">
            <h3><?= htmlspecialchars($t->get('map.scan_history')) ?></h3>
            <ul class="history-list">
                <?php foreach ($mapData['scan_history'] as $scan): ?>
                <li><?= htmlspecialchars((string) $scan['date']) ?> — NDVI <?= htmlspecialchars((string) ($scan['ndvi_mean'] ?? '—')) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="map-layer-toggles dashboard-card">
            <span class="muted"><?= htmlspecialchars($t->get('map.layers')) ?>:</span>
            <label><input type="checkbox" id="layer-boundary" checked> <?= htmlspecialchars($t->get('map.layer_boundary')) ?></label>
            <label><input type="checkbox" id="layer-deficiency" checked> <?= htmlspecialchars($t->get('map.layer_deficiency')) ?></label>
        </div>

        <div id="farm-map" class="farm-map" data-lat="<?= htmlspecialchars((string) $center['lat']) ?>" data-lng="<?= htmlspecialchars((string) $center['lng']) ?>"></div>

        <div class="map-legend dashboard-card">
            <h3><?= htmlspecialchars($t->get('map.legend_title')) ?></h3>
            <ul class="legend-list">
                <li><span class="legend-swatch legend-n"></span> N <?= htmlspecialchars($t->get('map.deficiency')) ?></li>
                <li><span class="legend-swatch legend-p"></span> P <?= htmlspecialchars($t->get('map.deficiency')) ?></li>
                <li><span class="legend-swatch legend-k"></span> K <?= htmlspecialchars($t->get('map.deficiency')) ?></li>
            </ul>
            <?php if (empty($mapData['farm_polygon'])): ?>
            <p class="muted"><a href="/farms/boundary?lang=<?= $t->locale() ?>"><?= htmlspecialchars($t->get('map.draw_boundary')) ?></a></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($canAccess): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
window.FarmQMap = {
    center: <?= json_encode($center, JSON_UNESCAPED_UNICODE) ?>,
    farmPolygon: <?= json_encode($mapData['farm_polygon'] ?? null, JSON_UNESCAPED_UNICODE) ?>,
    deficiency: <?= json_encode($deficiency, JSON_UNESCAPED_UNICODE) ?>,
    scanInProgress: <?= $scanInProgress ? 'true' : 'false' ?>,
    locale: <?= json_encode($t->locale()) ?>
};
</script>
<script src="<?= asset('js/map.js') ?>"></script>
<?php if ($scanInProgress): ?>
<meta http-equiv="refresh" content="5">
<?php endif; ?>
<?php endif; ?>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
