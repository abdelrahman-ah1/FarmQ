<?php
$errorKey = static function (array $errors, string $field) use ($t): string {
    if (!isset($errors[$field])) {
        return '';
    }
    return htmlspecialchars($t->get('farms.errors.' . $errors[$field]));
};
$governorates = $governorates ?? [];
$selectedRegion = $old['region'] ?? 'delta';
ob_start();
?>
<section class="section">
    <div class="container narrow">
        <h1><?= htmlspecialchars($t->get('farms.create_title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('farms.create_hint')) ?></p>

        <div class="auth-card">
            <form method="post" action="/farms/create?lang=<?= $t->locale() ?>" class="form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="name"><?= htmlspecialchars($t->get('farms.name')) ?></label>
                    <input type="text" id="name" name="name" required value="<?= old('name', '', $old ?? []) ?>">
                    <?= $errorKey($errors ?? [], 'name') ? '<span class="field-error">' . $errorKey($errors, 'name') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="region"><?= htmlspecialchars($t->get('farms.region')) ?></label>
                    <select id="region" name="region" required>
                        <?php foreach (['delta', 'upper_egypt', 'reclaimed_desert'] as $region): ?>
                        <option value="<?= $region ?>" <?= ($selectedRegion === $region) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->get('regions.' . $region)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= $errorKey($errors ?? [], 'region') ? '<span class="field-error">' . $errorKey($errors, 'region') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="governorate"><?= htmlspecialchars($t->get('farms.governorate')) ?></label>
                    <select id="governorate" name="governorate">
                        <option value=""><?= htmlspecialchars($t->get('farms.governorate_optional')) ?></option>
                        <?php foreach ($governorates as $region => $govs): ?>
                        <?php foreach ($govs as $gov): ?>
                        <option value="<?= htmlspecialchars($gov) ?>" data-region="<?= htmlspecialchars($region) ?>"
                            <?= (($old['governorate'] ?? '') === $gov) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t->get('governorates.' . $gov)) ?>
                        </option>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    </select>
                    <?= $errorKey($errors ?? [], 'governorate') ? '<span class="field-error">' . $errorKey($errors, 'governorate') . '</span>' : '' ?>
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t->get('farms.create_submit')) ?></button>
            </form>
        </div>
    </div>
</section>
<script>
(function () {
    var region = document.getElementById('region');
    var gov = document.getElementById('governorate');
    if (!region || !gov) return;
    function filter() {
        var r = region.value;
        Array.prototype.forEach.call(gov.options, function (opt) {
            if (!opt.value) return;
            opt.hidden = opt.getAttribute('data-region') !== r;
        });
        if (gov.selectedOptions[0] && gov.selectedOptions[0].hidden) {
            gov.value = '';
        }
    }
    region.addEventListener('change', filter);
    filter();
})();
</script>
<?php
$content = ob_get_clean();
require base_path('views/layouts/app.php');
