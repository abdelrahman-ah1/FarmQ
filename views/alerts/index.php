<?php ob_start(); ?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('alerts_page.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('alerts_page.subtitle', ['farm' => $activeFarm['name']])) ?></p>
            </div>
        </div>

        <div class="alert-counts">
            <span class="alert-count alert-count-high"><?= (int) $counts['high'] ?> <?= htmlspecialchars($t->get('alerts_page.sev_high')) ?></span>
            <span class="alert-count alert-count-moderate"><?= (int) $counts['moderate'] ?> <?= htmlspecialchars($t->get('alerts_page.sev_moderate')) ?></span>
            <span class="alert-count alert-count-low"><?= (int) $counts['low'] ?> <?= htmlspecialchars($t->get('alerts_page.sev_low')) ?></span>
        </div>

        <?php
        $lang = $t->locale();
        $buildUrl = static function (string $sev, string $cat) use ($lang): string {
            $q = ['lang' => $lang];
            if ($sev !== 'all') {
                $q['sev'] = $sev;
            }
            if ($cat !== 'all') {
                $q['cat'] = $cat;
            }

            return '/alerts?' . http_build_query($q);
        };
        ?>
        <div class="alert-filters">
            <div class="filter-row">
                <span class="filter-label"><?= htmlspecialchars($t->get('alerts_page.filter_severity')) ?></span>
                <?php foreach (['all', 'high', 'moderate', 'low'] as $sev): ?>
                <a class="filter-chip<?= $sevFilter === $sev ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildUrl($sev, $catFilter)) ?>">
                    <?= htmlspecialchars($sev === 'all' ? $t->get('alerts_page.filter_all') : $t->get('alerts_page.sev_' . $sev)) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if ($availableCats !== []): ?>
            <div class="filter-row">
                <span class="filter-label"><?= htmlspecialchars($t->get('alerts_page.filter_category')) ?></span>
                <a class="filter-chip<?= $catFilter === 'all' ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildUrl($sevFilter, 'all')) ?>">
                    <?= htmlspecialchars($t->get('alerts_page.filter_all')) ?>
                </a>
                <?php foreach ($availableCats as $cat): ?>
                <a class="filter-chip<?= $catFilter === $cat ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildUrl($sevFilter, $cat)) ?>">
                    <?= htmlspecialchars($t->get('alerts_page.cat_' . $cat)) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($alertList === []): ?>
        <div class="dashboard-card empty-state">
            <p class="muted"><?= htmlspecialchars($t->get($hasFilter ? 'alerts_page.filter_none' : 'alerts_page.none')) ?></p>
            <?php if ($hasFilter): ?>
            <a class="btn btn-secondary" href="<?= htmlspecialchars($buildUrl('all', 'all')) ?>"><?= htmlspecialchars($t->get('alerts_page.filter_clear')) ?></a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <?php foreach (['high', 'moderate', 'low'] as $sev): ?>
        <?php if ($grouped[$sev] !== []): ?>
        <div class="dashboard-card alerts-group">
            <h2 class="alerts-group-title alert-<?= $sev ?>-text"><?= htmlspecialchars($t->get('alerts_page.sev_' . $sev)) ?></h2>
            <ul class="alert-detail-list">
                <?php foreach ($grouped[$sev] as $alert): ?>
                <li class="alert-detail alert-detail-<?= htmlspecialchars($sev) ?>">
                    <div class="alert-detail-head">
                        <span class="alert-cat-tag"><?= htmlspecialchars($t->get('alerts_page.cat_' . $alert['category'])) ?></span>
                        <?php if (!empty($alert['pest'])): ?>
                        <span class="alert-kind-tag alert-kind-<?= htmlspecialchars($alert['kind']) ?>"><?= htmlspecialchars($t->get('alerts_page.kind_' . $alert['kind'])) ?></span>
                        <?php endif; ?>
                        <strong><?= htmlspecialchars($t->get('alerts.' . $alert['type'], ['date' => $alert['date'] ?? ''])) ?></strong>
                        <?php if (!empty($alert['days']) && (int) $alert['days'] > 1): ?>
                        <span class="alert-window"><?= htmlspecialchars($t->get('alerts_page.persistence', ['days' => (int) $alert['days']])) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="alert-action"><?= htmlspecialchars($t->get('alert_actions.' . $alert['action'])) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="dashboard-card">
            <h2><?= htmlspecialchars($t->get('alerts_page.forecast_title')) ?></h2>
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
                            <td><?= htmlspecialchars((string) $day['date']) ?></td>
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
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
