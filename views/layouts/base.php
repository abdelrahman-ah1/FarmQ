<!DOCTYPE html>
<html lang="<?= htmlspecialchars($t->locale()) ?>" dir="<?= htmlspecialchars($t->direction()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $t->get('meta.title')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script>
        var theme = localStorage.getItem('theme');
        if (theme) {
            document.documentElement.setAttribute('data-theme', theme);
        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body class="app-body">
    <div id="connectivity-banner" class="connectivity-banner connectivity-online" hidden
         data-online="<?= htmlspecialchars($t->get('connectivity.online')) ?>"
         data-offline="<?= htmlspecialchars($t->get('connectivity.offline')) ?>"
         data-slow="<?= htmlspecialchars($t->get('connectivity.slow')) ?>"></div>
    <?= $content ?? '' ?>
    <script src="<?= asset('js/app.js') ?>" defer></script>
    <script src="<?= asset('js/connectivity.js') ?>" defer></script>
</body>
</html>
