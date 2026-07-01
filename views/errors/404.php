<?php ob_start(); ?>
<section class="section">
    <div class="container narrow">
        <h1>404</h1>
        <p><?= htmlspecialchars($path ?? 'Page not found') ?></p>
        <a href="/" class="btn btn-secondary">Home</a>
    </div>
</section>
<?php
$content = ob_get_clean();
$t = new FarmQ\Localization\Translator();
require base_path('views/layouts/base.php');
