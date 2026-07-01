<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <h1><?= htmlspecialchars($t->get('farms.boundary_title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('farms.boundary_hint')) ?></p>

        <?php if ($boundaryError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('farms.boundary_errors.' . $boundaryError)) ?></div>
        <?php endif; ?>

        <div class="dashboard-card">
            <p class="muted"><?= htmlspecialchars($activeFarm['name']) ?> — <?= htmlspecialchars($t->get('regions.' . $activeFarm['region'])) ?></p>
            <div id="boundary-map" class="farm-map boundary-map"
                 data-lat="<?= htmlspecialchars((string) $center['lat']) ?>"
                 data-lng="<?= htmlspecialchars((string) $center['lng']) ?>"></div>

            <form method="post" action="/farms/boundary?lang=<?= $t->locale() ?>" class="form" id="boundary-form" data-requires-online>
                <?= csrf_field() ?>
                <input type="hidden" name="polygon_geojson" id="polygon_geojson" value="">
                <p class="muted" id="boundary-area"></p>
                <div class="export-actions">
                    <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('farms.boundary_save')) ?></button>
                    <a href="/map?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('farms.boundary_skip')) ?></a>
                </div>
            </form>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js" crossorigin=""></script>
<script>
window.FarmQBoundary = {
    center: <?= json_encode($center, JSON_UNESCAPED_UNICODE) ?>,
    polygon: <?= json_encode($polygon, JSON_UNESCAPED_UNICODE) ?>,
    locale: <?= json_encode($t->locale()) ?>,
    labels: {
        draw: <?= json_encode($t->get('farms.boundary_draw')) ?>,
        edit: <?= json_encode($t->get('farms.boundary_edit')) ?>,
        area: <?= json_encode($t->get('farms.boundary_area')) ?>
    }
};
</script>
<script src="<?= asset('js/farm-boundary.js') ?>"></script>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
