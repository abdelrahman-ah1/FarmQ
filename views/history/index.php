<?php
$soilChart = ['labels' => [], 'n' => [], 'p' => [], 'k' => [], 'ph' => [], 'ec' => []];
foreach ($history['soil_samples'] as $s) {
    $soilChart['labels'][] = $s['sample_date'];
    $soilChart['n'][] = $s['npk_n'] !== null ? (float) $s['npk_n'] : null;
    $soilChart['p'][] = $s['npk_p'] !== null ? (float) $s['npk_p'] : null;
    $soilChart['k'][] = $s['npk_k'] !== null ? (float) $s['npk_k'] : null;
    $soilChart['ph'][] = $s['ph'] !== null ? (float) $s['ph'] : null;
    $soilChart['ec'][] = $s['salinity_ec'] !== null ? (float) $s['salinity_ec'] : null;
}

$ndviChart = ['labels' => [], 'values' => []];
foreach (array_reverse($history['satellite_scans']) as $scan) {
    $ndviChart['labels'][] = substr((string) ($scan['completed_at'] ?? ''), 0, 10);
    $ndviChart['values'][] = isset($scan['result']['ndvi_mean']) ? (float) $scan['result']['ndvi_mean'] : null;
}

$planChart = ['labels' => [], 'counts' => []];
foreach ($history['fertilization_plans'] as $plan) {
    $month = substr((string) $plan['generated_at'], 0, 7);
    $planChart['labels'][$month] = ($planChart['labels'][$month] ?? 0) + 1;
}
$planLabels = array_keys($planChart['labels']);
$planCounts = array_values($planChart['labels']);

ob_start();
?>
<section class="section">
    <div class="container">
        <h1><?= htmlspecialchars($t->get('history.title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('history.subtitle')) ?></p>

        <div class="dashboard-card">
            <h2><?= htmlspecialchars($t->get('history.seasons')) ?></h2>
            <dl class="metric-list">
                <div><dt><?= htmlspecialchars($t->get('history.shatawi')) ?></dt><dd><?= (int) $history['season_summary']['shatawi'] ?></dd></div>
                <div><dt><?= htmlspecialchars($t->get('history.seifi')) ?></dt><dd><?= (int) $history['season_summary']['seifi'] ?></dd></div>
            </dl>
        </div>

        <div class="dashboard-card" style="margin-top:1rem;">
            <h2><?= htmlspecialchars($t->get('history.soil_timeline')) ?></h2>
            <?php if ($history['soil_samples'] === []): ?>
            <p class="muted"><?= htmlspecialchars($t->get('history.no_soil')) ?></p>
            <a href="/ingestion?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('ingestion.upload_submit')) ?></a>
            <?php else: ?>
            <div class="chart-wrap">
                <canvas id="soil-npk-chart" height="220" aria-label="<?= htmlspecialchars($t->get('history.chart_npk')) ?>"></canvas>
            </div>
            <div class="chart-wrap">
                <canvas id="soil-ph-ec-chart" height="180" aria-label="<?= htmlspecialchars($t->get('history.chart_ph_ec')) ?>"></canvas>
            </div>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('ingestion.col_date')) ?></th>
                            <th>N</th><th>P</th><th>K</th><th>pH</th><th>EC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history['soil_samples'] as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['sample_date']) ?></td>
                            <td><?= $s['npk_n'] ?? '—' ?></td>
                            <td><?= $s['npk_p'] ?? '—' ?></td>
                            <td><?= $s['npk_k'] ?? '—' ?></td>
                            <td><?= $s['ph'] ?? '—' ?></td>
                            <td><?= $s['salinity_ec'] ?? '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card" style="margin-top:1rem;">
            <h2><?= htmlspecialchars($t->get('history.blueprint_history')) ?></h2>
            <?php if ($history['fertilization_plans'] === []): ?>
            <p class="muted"><?= htmlspecialchars($t->get('history.no_plans')) ?></p>
            <a href="/blueprint?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('blueprint.generate_btn')) ?></a>
            <?php else: ?>
            <?php if ($planLabels !== []): ?>
            <div class="chart-wrap">
                <canvas id="blueprint-timeline-chart" height="160" aria-label="<?= htmlspecialchars($t->get('history.chart_plans')) ?>"></canvas>
            </div>
            <?php endif; ?>
            <ul class="history-list">
                <?php foreach ($history['fertilization_plans'] as $plan): ?>
                <li>
                    <?= htmlspecialchars(substr((string) $plan['generated_at'], 0, 10)) ?> —
                    <?= htmlspecialchars($plan['crop_code']) ?>
                    (<?= htmlspecialchars($t->get('plans.' . ($plan['tier_scope'] === 'full' ? 'paid' : 'free'))) ?>)
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>

        <?php if ($tierGate->can('satellite')): ?>
        <div class="dashboard-card" style="margin-top:1rem;">
            <h2><?= htmlspecialchars($t->get('history.satellite_history')) ?></h2>
            <?php if ($history['satellite_scans'] === []): ?>
            <p class="muted"><?= htmlspecialchars($t->get('history.no_scans')) ?></p>
            <a href="/map?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('map.run_scan')) ?></a>
            <?php else: ?>
            <div class="chart-wrap">
                <canvas id="ndvi-chart" height="180" aria-label="<?= htmlspecialchars($t->get('history.chart_ndvi')) ?>"></canvas>
            </div>
            <ul class="history-list">
                <?php foreach ($history['satellite_scans'] as $scan): ?>
                <li>
                    <?= htmlspecialchars(substr((string) ($scan['completed_at'] ?? ''), 0, 10)) ?> —
                    NDVI <?= htmlspecialchars((string) ($scan['result']['ndvi_mean'] ?? '—')) ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="dashboard-card locked-card" style="margin-top:1rem;">
            <p class="muted"><?= htmlspecialchars($t->get('history.satellite_locked')) ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($history['soil_samples'] !== [] || $history['fertilization_plans'] !== [] || $history['satellite_scans'] !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script defer>
window.addEventListener('load', function () {
    if (typeof Chart === 'undefined') return;

    var soil = <?= json_encode($soilChart, JSON_UNESCAPED_UNICODE) ?>;
    var ndvi = <?= json_encode($ndviChart, JSON_UNESCAPED_UNICODE) ?>;
    var planLabels = <?= json_encode($planLabels, JSON_UNESCAPED_UNICODE) ?>;
    var planCounts = <?= json_encode($planCounts, JSON_UNESCAPED_UNICODE) ?>;

    var npkEl = document.getElementById('soil-npk-chart');
    if (npkEl) {
        new Chart(npkEl, {
            type: 'line',
            data: {
                labels: soil.labels,
                datasets: [
                    { label: 'N', data: soil.n, borderColor: '#e74c3c', tension: 0.2 },
                    { label: 'P', data: soil.p, borderColor: '#3498db', tension: 0.2 },
                    { label: 'K', data: soil.k, borderColor: '#9b59b6', tension: 0.2 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: false } } }
        });
    }

    var phEl = document.getElementById('soil-ph-ec-chart');
    if (phEl) {
        new Chart(phEl, {
            type: 'line',
            data: {
                labels: soil.labels,
                datasets: [
                    { label: 'pH', data: soil.ph, borderColor: '#2ea84a', yAxisID: 'y' },
                    { label: 'EC', data: soil.ec, borderColor: '#4a3428', yAxisID: 'y1' }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { type: 'linear', position: 'left' },
                    y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false } }
                }
            }
        });
    }

    var planEl = document.getElementById('blueprint-timeline-chart');
    if (planEl && planLabels.length) {
        new Chart(planEl, {
            type: 'bar',
            data: { labels: planLabels, datasets: [{ label: 'Plans', data: planCounts, backgroundColor: '#2ea84a' }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    }

    var ndviEl = document.getElementById('ndvi-chart');
    if (ndviEl && ndvi.labels.length) {
        new Chart(ndviEl, {
            type: 'line',
            data: {
                labels: ndvi.labels,
                datasets: [{ label: 'NDVI', data: ndvi.values, borderColor: '#1f7a35', fill: false, tension: 0.2 }]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 1 } } }
        });
    }
});
</script>
<?php endif; ?>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
