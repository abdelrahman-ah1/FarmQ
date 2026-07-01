<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <h1><?= htmlspecialchars($t->get('dashboard.welcome')) ?>, <?= htmlspecialchars($user['full_name']) ?></h1>

        <?php if ($farms !== [] && !($onboardingComplete ?? true)): ?>
        <div class="dashboard-card onboarding-card">
            <h2><?= htmlspecialchars($t->get('onboarding.title')) ?></h2>
            <p class="muted"><?= htmlspecialchars($t->get('onboarding.subtitle')) ?></p>
            <ol class="onboarding-list">
                <?php foreach ($onboardingSteps as $key => $step): ?>
                <li class="<?= $step['done'] ? 'done' : 'pending' ?>">
                    <?php if ($step['done']): ?>
                    <span class="onboarding-check" aria-hidden="true">✓</span>
                    <span><?= htmlspecialchars($t->get('onboarding.steps.' . $key)) ?></span>
                    <?php else: ?>
                    <a href="<?= htmlspecialchars($step['href']) ?>?lang=<?= $t->locale() ?>">
                        <?= htmlspecialchars($t->get('onboarding.steps.' . $key)) ?>
                    </a>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ol>
        </div>
        <?php endif; ?>

        <?php if ($farms !== [] && ($planStale ?? false) && ($planRow ?? null)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('dashboard.stale_blueprint')) ?>
            <a href="/blueprint?lang=<?= $t->locale() ?>"><?= htmlspecialchars($t->get('blueprint.regenerate_btn')) ?></a>
        </div>
        <?php endif; ?>

        <?php if ($farms === []): ?>
        <div class="dashboard-card empty-state">
            <h2><?= htmlspecialchars($t->get('farms.empty_title')) ?></h2>
            <p class="muted"><?= htmlspecialchars($t->get('farms.empty_body')) ?></p>
            <a href="/farms/create?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('farms.create_btn')) ?></a>
        </div>
        <?php else: ?>
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><?= htmlspecialchars($t->get('dashboard.active_farm')) ?></h2>
                    <span class="tier-badge tier-<?= htmlspecialchars($tierGate?->tier() ?? 'free') ?>">
                        <?= htmlspecialchars($t->get('plans.' . ($tierGate?->tier() ?? 'free'))) ?>
                    </span>
                </div>
                <p><strong><?= htmlspecialchars($activeFarm['name']) ?></strong></p>
                <p class="muted"><?= htmlspecialchars($t->get('regions.' . $activeFarm['region'])) ?></p>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('dashboard.free_summary')) ?></h2>
                <?php if ($latestSample): ?>
                <dl class="metric-list">
                    <div><dt>N</dt><dd><?= htmlspecialchars((string) ($latestSample['npk_n'] ?? '—')) ?> mg/kg</dd></div>
                    <div><dt>P</dt><dd><?= htmlspecialchars((string) ($latestSample['npk_p'] ?? '—')) ?> mg/kg</dd></div>
                    <div><dt>K</dt><dd><?= htmlspecialchars((string) ($latestSample['npk_k'] ?? '—')) ?> mg/kg</dd></div>
                    <div><dt>pH</dt><dd><?= htmlspecialchars((string) ($latestSample['ph'] ?? '—')) ?></dd></div>
                </dl>
                <p class="muted sample-date"><?= htmlspecialchars($t->get('ingestion.latest_sample', ['date' => $latestSample['sample_date']])) ?></p>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('dashboard.no_soil')) ?></p>
                <a href="/ingestion?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('ingestion.upload_submit')) ?></a>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('ingestion.crop_title')) ?></h2>
                <?php if ($selectedCrop): ?>
                <p><strong><?= htmlspecialchars($selectedCrop['display_name']) ?></strong></p>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('dashboard.no_crop')) ?></p>
                <a href="/ingestion?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('ingestion.crop_submit')) ?></a>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('dashboard.blueprint_summary')) ?></h2>
                <?php if ($planRow && !$planStale && !empty($planRow['plan'])): ?>
                <?php $plan = $planRow['plan']; ?>
                <dl class="metric-list">
                    <div><dt><?= htmlspecialchars($t->get('blueprint.products.urea')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['urea'] ?? 0)) ?> kg/ha</dd></div>
                    <div><dt><?= htmlspecialchars($t->get('blueprint.products.dap')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['dap'] ?? 0)) ?> kg/ha</dd></div>
                    <div><dt><?= htmlspecialchars($t->get('blueprint.products.potassium_sulfate')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['potassium_sulfate'] ?? 0)) ?> kg/ha</dd></div>
                </dl>
                <a href="/blueprint?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('blueprint.view_full')) ?></a>
                <?php elseif ($latestSample && $selectedCrop): ?>
                <p class="muted"><?= htmlspecialchars($t->get('dashboard.generate_blueprint_hint')) ?></p>
                <a href="/blueprint?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('blueprint.generate_btn')) ?></a>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('dashboard.blueprint_prereq')) ?></p>
                <?php endif; ?>
            </div>

            <div class="dashboard-card locked-card">
                <h2><?= htmlspecialchars($t->get('dashboard.satellite_summary')) ?></h2>
                <?php if ($tierGate && $tierGate->can('satellite')): ?>
                    <?php if (!empty($mapData['ndvi'])): ?>
                    <dl class="metric-list">
                        <div><dt>NDVI</dt><dd>
                            <?= htmlspecialchars((string) $mapData['ndvi']['ndvi_mean']) ?>
                            <?php if (isset($mapData['ndvi_delta']) && $mapData['ndvi_delta'] !== null): ?>
                            <span class="ndvi-trend ndvi-trend-<?= $mapData['ndvi_delta'] >= 0 ? 'up' : 'down' ?>">
                                <?= $mapData['ndvi_delta'] >= 0 ? '▲' : '▼' ?>
                            </span>
                            <?php endif; ?>
                        </dd></div>
                        <div><dt>NDRE</dt><dd><?= htmlspecialchars((string) ($mapData['ndvi']['ndre_mean'] ?? '—')) ?></dd></div>
                        <?php if (!empty($mapData['last_scan_date'])): ?>
                        <div><dt><?= htmlspecialchars($t->get('map.scan_date')) ?></dt><dd><?= htmlspecialchars((string) $mapData['last_scan_date']) ?></dd></div>
                        <?php endif; ?>
                    </dl>
                    <?php if (!empty($mapData['rescan_recommended'])): ?>
                    <p class="muted scan-hint"><?= htmlspecialchars($t->get('map.rescan_recommended')) ?></p>
                    <?php endif; ?>
                    <a href="/map?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('map.title')) ?></a>
                    <?php elseif (!empty($mapData['scan_in_progress'])): ?>
                    <p class="muted"><?= htmlspecialchars($t->get('map.scan_in_progress')) ?></p>
                    <?php else: ?>
                    <p class="muted"><?= htmlspecialchars($t->get('map.no_scan')) ?></p>
                    <a href="/map?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('map.run_scan')) ?></a>
                    <?php endif; ?>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('upgrade.locked')) ?></p>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('dashboard.forecast_summary')) ?></h2>
                <?php if ($tierGate && $tierGate->can('forecast') && $forecast): ?>
                <div class="forecast-mini">
                    <?php foreach ($forecast as $day): ?>
                    <div class="forecast-day">
                        <span class="forecast-date"><?= htmlspecialchars(substr($day['date'], 5)) ?></span>
                        <span><?= htmlspecialchars((string) $day['temp_max']) ?>° / <?= htmlspecialchars((string) $day['temp_min']) ?>°</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif ($tierGate && $tierGate->can('forecast')): ?>
                <p class="muted"><?= htmlspecialchars($t->get('dashboard.forecast_loading')) ?></p>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('upgrade.locked')) ?></p>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('dashboard.alerts_summary')) ?></h2>
                <?php if ($tierGate && $tierGate->can('alerts')): ?>
                    <?php if ($alertList !== []): ?>
                    <ul class="alert-list compact">
                        <?php foreach (array_slice($alertList, 0, 3) as $alert): ?>
                        <li class="alert-badge alert-<?= htmlspecialchars($alert['severity']) ?>">
                            <?= htmlspecialchars($t->get('alerts.' . $alert['type'], ['date' => $alert['date'] ?? ''])) ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="/alerts?lang=<?= $t->locale() ?>" class="btn btn-secondary btn-sm"><?= htmlspecialchars($t->get('dashboard.view_all_alerts')) ?></a>
                    <?php else: ?>
                    <p class="muted"><?= htmlspecialchars($t->get('dashboard.no_alerts')) ?></p>
                    <?php endif; ?>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('upgrade.locked')) ?></p>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('dashboard.irrigation_summary')) ?></h2>
                <?php if ($tierGate && $tierGate->can('irrigation')): ?>
                    <?php if ($irrigationSchedule && !empty($irrigationSchedule['days'])): ?>
                    <?php $today = $irrigationSchedule['days'][0]; ?>
                    <p><?= htmlspecialchars($t->get('irrigation.today')) ?>: <strong><?= htmlspecialchars((string) $today['irrigation_mm']) ?> mm</strong></p>
                    <a href="/irrigation?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('irrigation.view_schedule')) ?></a>
                    <?php else: ?>
                    <p class="muted"><?= htmlspecialchars($t->get('irrigation.no_schedule')) ?></p>
                    <a href="/irrigation?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('irrigation.generate_btn')) ?></a>
                    <?php endif; ?>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('upgrade.locked')) ?></p>
                <?php endif; ?>
            </div>

            <?php if ($tierGate && !$tierGate->isPaid() && env('DEV_UNLOCK_PAID', '0') !== '1'): ?>
            <div class="dashboard-card upgrade-card">
                <h2><?= htmlspecialchars($t->get('upgrade.title')) ?></h2>
                <p><?= htmlspecialchars($t->get('upgrade.body')) ?></p>
                <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
                <a href="/billing?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('upgrade.cta')) ?></a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
