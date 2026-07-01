<?php ob_start(); ?>
<section class="section auth-section">
    <div class="container narrow">
        <h1><?= htmlspecialchars($t->get('auth.demo_title')) ?></h1>
        <p class="muted"><?= htmlspecialchars($t->get('auth.demo_subtitle')) ?></p>

        <?php if (flash('demo_success')): ?>
        <div class="alert alert-success"><?= htmlspecialchars($t->get('auth.demo_success')) ?></div>
        <?php endif; ?>

        <?php if ($error = flash('demo_error')): ?>
        <div class="alert alert-error"><?= htmlspecialchars($t->get('auth.demo_errors.' . $error)) ?></div>
        <?php endif; ?>

        <div class="auth-card">
            <form method="post" action="/demo?lang=<?= $t->locale() ?>" class="form" data-requires-online>
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="full_name"><?= htmlspecialchars($t->get('auth.full_name')) ?></label>
                    <input type="text" id="full_name" name="full_name" required autocomplete="name">
                </div>
                <div class="form-group">
                    <label for="email"><?= htmlspecialchars($t->get('auth.email')) ?></label>
                    <input type="email" id="email" name="email" required autocomplete="email">
                </div>
                <div class="form-group">
                    <label for="phone"><?= htmlspecialchars($t->get('auth.demo_phone')) ?></label>
                    <input type="tel" id="phone" name="phone" autocomplete="tel">
                </div>
                <div class="form-group">
                    <label for="message"><?= htmlspecialchars($t->get('auth.demo_message')) ?></label>
                    <textarea id="message" name="message" rows="4" class="textarea-input"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t->get('auth.demo_submit')) ?></button>
            </form>
        </div>

        <p class="auth-footer"><a href="/?lang=<?= $t->locale() ?>"><?= htmlspecialchars($t->get('auth.demo_back')) ?></a></p>
        
        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
            <p>Designed by <a href="mailto:logiq.studio@gmail.com">LogiQ Studio</a></p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/base.php');
