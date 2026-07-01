<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('irrigation.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('irrigation.subtitle')) ?></p>
            </div>
            <form method="post" action="/irrigation/generate?lang=<?= $t->locale() ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('irrigation.generate_btn')) ?></button>
            </form>
        </div>

        <?php if (!empty($schedule['canal_rotation_note'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($t->get('irrigation.notes.' . $schedule['canal_rotation_note'])) ?>
        </div>
        <?php endif; ?>

        <?php if ($alerts !== []): ?>
        <div class="dashboard-card alerts-card">
            <h2><?= htmlspecialchars($t->get('irrigation.alerts_title')) ?></h2>
            <ul class="alert-list">
                <?php foreach ($alerts as $alert): ?>
                <li class="alert-badge alert-<?= htmlspecialchars($alert['severity']) ?>">
                    <?= htmlspecialchars($t->get('alerts.' . $alert['type'], ['date' => $alert['date'] ?? ''])) ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <div class="ingestion-grid">
            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('irrigation.forecast_title')) ?></h2>
                <div class="table-wrap">
                    <table class="plan-table">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars($t->get('irrigation.col_date')) ?></th>
                                <th><?= htmlspecialchars($t->get('irrigation.col_max')) ?></th>
                                <th><?= htmlspecialchars($t->get('irrigation.col_min')) ?></th>
                                <th><?= htmlspecialchars($t->get('irrigation.col_rain')) ?></th>
                                <th>ET₀</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($forecast as $day): ?>
                            <tr>
                                <td><?= htmlspecialchars($day['date']) ?></td>
                                <td><?= htmlspecialchars((string) $day['temp_max']) ?>°</td>
                                <td><?= htmlspecialchars((string) $day['temp_min']) ?>°</td>
                                <td><?= htmlspecialchars((string) $day['rain_mm']) ?></td>
                                <td><?= htmlspecialchars((string) $day['et0_mm']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dashboard-card">
                <h2><?= htmlspecialchars($t->get('irrigation.schedule_title')) ?></h2>
                <?php if ($schedule && !empty($schedule['days'])): ?>
                <div class="table-wrap">
                    <table class="plan-table">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars($t->get('irrigation.col_date')) ?></th>
                                <th><?= htmlspecialchars($t->get('irrigation.col_crop_et')) ?></th>
                                <th><?= htmlspecialchars($t->get('irrigation.col_irrigation')) ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule['days'] as $day): ?>
                            <tr>
                                <td><?= htmlspecialchars($day['date']) ?></td>
                                <td><?= htmlspecialchars((string) $day['crop_et_mm']) ?> mm</td>
                                <td><strong><?= htmlspecialchars((string) $day['irrigation_mm']) ?> mm</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="muted"><?= htmlspecialchars($t->get('irrigation.no_schedule')) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
