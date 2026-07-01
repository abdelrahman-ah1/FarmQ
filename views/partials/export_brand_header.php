<?php
/** @var \FarmQ\Localization\Translator $t */
$logoDataUri = \FarmQ\Services\ExportBranding::logoDataUri();
$logoUrl = \FarmQ\Services\ExportBranding::logoUrl();
$logoSrc = $logoDataUri ?? $logoUrl;
?>
<header class="export-brand-header">
    <img src="<?= htmlspecialchars($logoSrc) ?>" alt="FarmQ" class="export-brand-logo">
    <div class="export-brand-text">
        <p class="export-brand-name">FarmQ</p>
        <p class="export-brand-tagline"><?= htmlspecialchars($t->get('export.brand_tagline')) ?></p>
        <p class="export-brand-by"><?= htmlspecialchars($t->get('export.brand_by')) ?></p>
    </div>
</header>
