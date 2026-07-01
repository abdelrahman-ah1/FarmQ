<?php
/** @var \FarmQ\Localization\Translator $t */
$logoDataUri = \FarmQ\Services\ExportBranding::logoDataUri();
$logoUrl = \FarmQ\Services\ExportBranding::logoUrl();
$logoSrc = $logoDataUri ?? $logoUrl;
?>
<footer class="export-brand-footer">
    <img src="<?= htmlspecialchars($logoSrc) ?>" alt="FarmQ">
    <span><?= htmlspecialchars($t->get('export.footer')) ?></span>
</footer>
