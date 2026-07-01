<?php
$plan = $planRow['plan'] ?? null;
ob_start();
?>
<section class="section">
    <div class="container">
        <div class="page-header">
            <div>
                <h1><?= htmlspecialchars($t->get('blueprint.title')) ?></h1>
                <p class="muted"><?= htmlspecialchars($t->get('blueprint.subtitle')) ?></p>
            </div>
            <?php if ($planRow && !$isStale): ?>
            <div class="export-actions">
                <a href="/blueprint/export?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('blueprint.export_csv')) ?></a>
                <a href="/blueprint/print?lang=<?= $t->locale() ?>&autoprint=1" target="_blank" rel="noopener" class="btn btn-secondary"><?= htmlspecialchars($t->get('blueprint.export_pdf')) ?></a>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($error = flash('blueprint_error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('blueprint.errors.' . $error)) ?></div>
        <?php endif; ?>

        <?php if (!$readiness['ready']): ?>
        <div class="dashboard-card empty-state">
            <h2><?= htmlspecialchars($t->get('blueprint.not_ready_title')) ?></h2>
            <p class="muted">
                <?= htmlspecialchars($t->get('blueprint.not_ready_' . ($readiness['missing'] ?? 'unknown'))) ?>
            </p>
            <a href="/ingestion?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('app.nav.data')) ?></a>
        </div>
        <?php else: ?>

        <?php if ($isStale || !$planRow): ?>
        <?php if ($canEdit ?? true): ?>
        <div class="dashboard-card">
            <p class="muted"><?= htmlspecialchars($t->get($planRow ? 'blueprint.stale_hint' : 'blueprint.generate_hint')) ?></p>
            <form method="post" action="/blueprint/generate?lang=<?= $t->locale() ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary"><?= htmlspecialchars($t->get('blueprint.generate_btn')) ?></button>
            </form>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($planRow && $plan): ?>
        <div class="blueprint-meta dashboard-card">
            <div class="card-header">
                <h2><?= htmlspecialchars($cropName ?? '') ?></h2>
                <div class="card-badges">
                    <?php $season = $plan['metadata']['season'] ?? 'seifi'; ?>
                    <span class="season-badge"><?= htmlspecialchars($t->get('seasons.' . $season)) ?></span>
                    <span class="tier-badge tier-free"><?= htmlspecialchars($t->get('blueprint.soil_only')) ?></span>
                </div>
            </div>
            <p class="muted"><?= htmlspecialchars($t->get('blueprint.generated_at', ['date' => substr((string) $planRow['generated_at'], 0, 10)])) ?></p>
            <?php if (!empty($plan['metadata']['micronutrient_note'])): ?>
            <p class="arc-note micronutrient-note"><?= htmlspecialchars($plan['metadata']['micronutrient_note']) ?></p>
            <?php endif; ?>
            <?php if (!empty($plan['metadata']['arc_reference'])): ?>
            <p class="arc-note"><em><?= htmlspecialchars($plan['metadata']['arc_reference']) ?></em></p>
            <?php endif; ?>

            <?php if (!empty($plan['metadata']['warnings'])): ?>
            <div class="warnings-list">
                <?php foreach ($plan['metadata']['warnings'] as $code): ?>
                <div class="alert alert-error"><?= htmlspecialchars($t->get('blueprint.warnings.' . $code)) ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="dashboard-card" style="margin-top: 1rem;">
            <h2><?= htmlspecialchars($t->get('blueprint.comparison_title')) ?></h2>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('blueprint.col_element')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.col_current')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.col_target')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.col_deficit')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.col_kg_ha')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (['n' => 'N', 'p' => 'P', 'k' => 'K'] as $key => $label): ?>
                        <tr>
                            <td><?= $label ?></td>
                            <td><?= isset($plan['soil'][$key]) && $plan['soil'][$key] !== null ? htmlspecialchars((string) $plan['soil'][$key]) : '—' ?></td>
                            <td><?= htmlspecialchars((string) ($plan['targets'][$key] ?? '—')) ?></td>
                            <td><?= htmlspecialchars((string) ($plan['deficits_mg_kg'][$key] ?? 0)) ?></td>
                            <td><?= htmlspecialchars((string) ($plan['elements_kg_ha'][$key] ?? 0)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-card" style="margin-top: 1rem;">
            <h2><?= htmlspecialchars($t->get('blueprint.products_title')) ?></h2>
            <dl class="metric-list product-totals">
                <div><dt><?= htmlspecialchars($t->get('blueprint.products.urea')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['urea'] ?? 0)) ?> kg/ha</dd></div>
                <div><dt><?= htmlspecialchars($t->get('blueprint.products.dap')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['dap'] ?? 0)) ?> kg/ha</dd></div>
                <div><dt><?= htmlspecialchars($t->get('blueprint.products.potassium_sulfate')) ?></dt><dd><?= htmlspecialchars((string) ($plan['products_kg_ha']['potassium_sulfate'] ?? 0)) ?> kg/ha</dd></div>
            </dl>
        </div>

        <div class="dashboard-card" style="margin-top: 1rem;">
            <h2><?= htmlspecialchars($t->get('blueprint.schedule_title')) ?></h2>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('blueprint.col_stage')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.products.urea')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.products.dap')) ?></th>
                            <th><?= htmlspecialchars($t->get('blueprint.products.potassium_sulfate')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plan['schedule'] ?? [] as $stage): ?>
                        <tr>
                            <td><?= htmlspecialchars($t->get('blueprint.stages.' . $stage['stage'])) ?></td>
                            <td><?= htmlspecialchars((string) $stage['urea']) ?></td>
                            <td><?= htmlspecialchars((string) $stage['dap']) ?></td>
                            <td><?= htmlspecialchars((string) $stage['potassium_sulfate']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($isStale && ($canEdit ?? true)): ?>
        <form method="post" action="/blueprint/generate?lang=<?= $t->locale() ?>" style="margin-top: 1rem;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary"><?= htmlspecialchars($t->get('blueprint.regenerate_btn')) ?></button>
        </form>
        <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
