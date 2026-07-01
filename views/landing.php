<?php
$otherLocale = $t->locale() === 'ar' ? 'en' : 'ar';
ob_start();
?>
<header class="site-header">
    <div class="container header-inner">
        <a href="/" class="brand">
            <img src="<?= asset('img/FarmQ_Logo.png') ?>" alt="FarmQ" class="brand-logo">
        </a>
        <button type="button" class="nav-toggle" data-nav-toggle aria-controls="site-nav" aria-expanded="false" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="site-nav" aria-label="Primary">
            <a href="#how-it-works"><?= htmlspecialchars($t->get('nav.how_it_works')) ?></a>
            <a href="#plans"><?= htmlspecialchars($t->get('nav.plans')) ?></a>
            <a href="#payments"><?= htmlspecialchars($t->get('nav.payments')) ?></a>
            <a href="#credibility"><?= htmlspecialchars($t->get('nav.credibility')) ?></a>
        </nav>
        <div class="header-actions">
            <button id="theme-toggle" class="theme-toggle" aria-label="Toggle Dark Mode">
                <span class="theme-icon">🌙</span>
            </button>
            <a href="/locale/<?= $otherLocale ?>" class="lang-toggle"><?= htmlspecialchars($t->get('common.switch_lang')) ?></a>
            <a href="/login?lang=<?= $t->locale() ?>" class="btn btn-ghost"><?= htmlspecialchars($t->get('nav.login')) ?></a>
            <a href="/signup?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('nav.signup')) ?></a>
        </div>
    </div>
</header>

<main>
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <p class="eyebrow">FarmQ</p>
                <h1><?= htmlspecialchars($t->get('hero.title')) ?></h1>
                <p class="lead"><?= htmlspecialchars($t->get('hero.subtitle')) ?></p>
                <div class="hero-cta">
                    <a href="/signup?lang=<?= $t->locale() ?>" class="btn btn-primary btn-lg"><?= htmlspecialchars($t->get('hero.cta_primary')) ?></a>
                    <a href="/demo?lang=<?= $t->locale() ?>" class="btn btn-secondary btn-lg"><?= htmlspecialchars($t->get('hero.cta_secondary')) ?></a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-card">
                    <span class="hero-card-label"><?= htmlspecialchars($t->get('plans.free')) ?></span>
                    <strong><?= htmlspecialchars($t->get('plans.free_price')) ?></strong>
                    <p><?= htmlspecialchars($t->get('how_it_works.steps.0.title')) ?> → <?= htmlspecialchars($t->get('how_it_works.steps.2.title')) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section">
        <div class="container">
            <h2><?= htmlspecialchars($t->get('how_it_works.title')) ?></h2>
            <div class="steps-grid">
                <?php foreach ($t->array('how_it_works.steps') as $i => $step): ?>
                <article class="step-card">
                    <span class="step-num"><?= $i + 1 ?></span>
                    <h3><?= htmlspecialchars($step['title']) ?></h3>
                    <p><?= htmlspecialchars($step['body']) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="plans" class="section section-alt">
        <div class="container">
            <h2><?= htmlspecialchars($t->get('plans.title')) ?></h2>
            <div class="plan-prices">
                <div class="plan-price-card">
                    <h3><?= htmlspecialchars($t->get('plans.free')) ?></h3>
                    <p class="price"><?= htmlspecialchars($t->get('plans.free_price')) ?></p>
                </div>
                <div class="plan-price-card plan-price-card--paid">
                    <h3><?= htmlspecialchars($t->get('plans.paid')) ?></h3>
                    <p class="price"><?= htmlspecialchars($t->get('plans.paid_price')) ?></p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="plan-table">
                    <thead>
                        <tr>
                            <th><?= htmlspecialchars($t->get('plans.title')) ?></th>
                            <th><?= htmlspecialchars($t->get('plans.free')) ?></th>
                            <th><?= htmlspecialchars($t->get('plans.paid')) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($t->array('plans.features') as $feature): ?>
                        <tr>
                            <td><?= htmlspecialchars($feature['name']) ?></td>
                            <td><?= $feature['free'] ? '✓' : '—' ?></td>
                            <td><?= $feature['paid'] ? '✓' : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section id="payments" class="section">
        <div class="container">
            <h2><?= htmlspecialchars($t->get('payments.title')) ?></h2>
            <p class="section-intro"><?= htmlspecialchars($t->get('payments.subtitle')) ?></p>
            <div class="rails-grid">
                <?php foreach ($t->array('payments.rails') as $rail): ?>
                <article class="rail-card">
                    <h3><?= htmlspecialchars($rail['name']) ?></h3>
                    <p><?= htmlspecialchars($rail['desc']) ?></p>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="credibility" class="section section-alt">
        <div class="container narrow">
            <h2><?= htmlspecialchars($t->get('credibility.title')) ?></h2>
            <p><?= htmlspecialchars($t->get('credibility.body')) ?></p>
            <div class="ref-badges">
                <span>MALR / ARC / SWERI</span>
                <span>NARSS</span>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <div>
            <strong>FarmQ</strong>
            <p><?= htmlspecialchars($t->get('footer.org')) ?></p>
        </div>
        <div>
            <p><?= htmlspecialchars($t->get('footer.support_hours')) ?></p>
            <p><a href="mailto:<?= htmlspecialchars($t->get('footer.contact')) ?>"><?= htmlspecialchars($t->get('footer.contact')) ?></a></p>
        </div>
        <div class="footer-cta">
            <a href="/signup?lang=<?= $t->locale() ?>" class="btn btn-primary"><?= htmlspecialchars($t->get('nav.signup')) ?></a>
            <a href="/demo?lang=<?= $t->locale() ?>" class="btn btn-secondary"><?= htmlspecialchars($t->get('nav.demo')) ?></a>
        </div>
    </div>
    <p class="footer-copy"><?= htmlspecialchars($t->get('footer.rights', ['year' => date('Y')])) ?> | Designed by <a href="mailto:logiq.studio@gmail.com">LogiQ Studio</a></p>
</footer>
<?php
$content = ob_get_clean();
require base_path('views/layouts/base.php');
