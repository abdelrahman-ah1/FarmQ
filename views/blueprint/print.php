<!DOCTYPE html>
<html lang="<?= htmlspecialchars($t->locale()) ?>" dir="<?= htmlspecialchars($t->direction()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t->get('blueprint.print_title')) ?> — FarmQ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;600;700&family=IBM+Plex+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: <?= $t->locale() === 'ar' ? '"IBM Plex Sans Arabic"' : '"IBM Plex Sans"' ?>, sans-serif; margin: 2rem; color: #1a1a1a; line-height: 1.5; }
        .export-brand-header { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid #4a3428; }
        .export-brand-logo { height: 56px; width: auto; flex-shrink: 0; }
        .export-brand-name { margin: 0; font-size: 1.35rem; font-weight: 700; color: #4a3428; }
        .export-brand-tagline { margin: 0.15rem 0 0; color: #5c5c5c; font-size: 0.9rem; }
        .export-brand-by { margin: 0.25rem 0 0; color: #8a8a8a; font-size: 0.8rem; }
        h1 { color: #4a3428; margin-bottom: 0.25rem; }
        .meta { color: #5c5c5c; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        th, td { border: 1px solid #ddd5cb; padding: 0.5rem 0.75rem; text-align: start; }
        th { background: #4a3428; color: #fff; }
        .season-badge { display: inline-block; background: #e8f5eb; color: #1f7a35; padding: 0.2rem 0.65rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; }
        .export-brand-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ddd5cb; font-size: 0.85rem; color: #5c5c5c; display: flex; align-items: center; gap: 0.75rem; }
        .export-brand-footer img { height: 28px; width: auto; }
        .no-print { margin-bottom: 1rem; }
        @media print {
            .no-print { display: none; }
            body { margin: 1rem; }
            .export-brand-header { break-inside: avoid; }
            .export-brand-footer { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()"><?= htmlspecialchars($t->get('blueprint.print_btn')) ?></button>
    </div>

    <?php require base_path('views/partials/export_brand_header.php'); ?>

    <h1><?= htmlspecialchars($t->get('blueprint.title')) ?></h1>
    <p class="meta">
        <?= htmlspecialchars($activeFarm['name']) ?> —
        <?= htmlspecialchars($t->get('regions.' . $activeFarm['region'])) ?><br>
        <?= htmlspecialchars($cropName ?? '') ?> ·
        <span class="season-badge"><?= htmlspecialchars($t->get('seasons.' . $season)) ?></span><br>
        <?= htmlspecialchars($t->get('blueprint.generated_at', ['date' => substr((string) $planRow['generated_at'], 0, 10)])) ?>
    </p>

    <?php if (!empty($plan['metadata']['arc_reference'])): ?>
    <p><em><?= htmlspecialchars($plan['metadata']['arc_reference']) ?></em></p>
    <?php endif; ?>

    <h2><?= htmlspecialchars($t->get('blueprint.comparison_title')) ?></h2>
    <table>
        <thead>
            <tr>
                <th><?= htmlspecialchars($t->get('blueprint.col_element')) ?></th>
                <th><?= htmlspecialchars($t->get('blueprint.col_current')) ?></th>
                <th><?= htmlspecialchars($t->get('blueprint.col_target')) ?></th>
                <th><?= htmlspecialchars($t->get('blueprint.col_kg_ha')) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (['n' => 'N', 'p' => 'P', 'k' => 'K'] as $key => $label): ?>
            <tr>
                <td><?= $label ?></td>
                <td><?= isset($plan['soil'][$key]) && $plan['soil'][$key] !== null ? htmlspecialchars((string) $plan['soil'][$key]) : '—' ?></td>
                <td><?= htmlspecialchars((string) ($plan['targets'][$key] ?? '—')) ?></td>
                <td><?= htmlspecialchars((string) ($plan['elements_kg_ha'][$key] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2><?= htmlspecialchars($t->get('blueprint.products_title')) ?></h2>
    <table>
        <tr><th><?= htmlspecialchars($t->get('blueprint.products.urea')) ?></th><td><?= htmlspecialchars((string) ($plan['products_kg_ha']['urea'] ?? 0)) ?> kg/ha</td></tr>
        <tr><th><?= htmlspecialchars($t->get('blueprint.products.dap')) ?></th><td><?= htmlspecialchars((string) ($plan['products_kg_ha']['dap'] ?? 0)) ?> kg/ha</td></tr>
        <tr><th><?= htmlspecialchars($t->get('blueprint.products.potassium_sulfate')) ?></th><td><?= htmlspecialchars((string) ($plan['products_kg_ha']['potassium_sulfate'] ?? 0)) ?> kg/ha</td></tr>
    </table>

    <h2><?= htmlspecialchars($t->get('blueprint.schedule_title')) ?></h2>
    <table>
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

    <?php require base_path('views/partials/export_brand_footer.php'); ?>

    <script>
        if (window.location.search.indexOf('autoprint=1') !== -1) {
            window.onload = function () { window.print(); };
        }
    </script>
</body>
</html>
