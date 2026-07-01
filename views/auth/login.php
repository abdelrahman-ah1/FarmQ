<?php
$errorKey = static function (array $errors, string $field) use ($t): string {
    if (!isset($errors[$field])) {
        return '';
    }
    return htmlspecialchars($t->get('auth.errors.' . $errors[$field]));
};
ob_start();
?>
<section class="section auth-section">
    <div class="container narrow">
        <div class="auth-card">
            <h1><?= htmlspecialchars($t->get('auth.login_title')) ?></h1>

            <?php if (isset($errors['credentials'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($t->get('auth.errors.invalid_credentials')) ?></div>
            <?php endif; ?>

            <form method="post" action="/login?lang=<?= $t->locale() ?>" class="form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email"><?= htmlspecialchars($t->get('auth.email')) ?></label>
                    <input type="email" id="email" name="email" required value="<?= old('email', '', $old ?? []) ?>">
                    <?= $errorKey($errors ?? [], 'email') ? '<span class="field-error">' . $errorKey($errors, 'email') . '</span>' : '' ?>
                </div>

                <div class="form-group">
                    <label for="password"><?= htmlspecialchars($t->get('auth.password')) ?></label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?= htmlspecialchars($t->get('auth.login_submit')) ?></button>
            </form>

            <p class="auth-footer">
                <?= htmlspecialchars($t->get('auth.no_account')) ?>
                <a href="/signup?lang=<?= $t->locale() ?>"><?= htmlspecialchars($t->get('nav.signup')) ?></a>
            </p>
        </div>
        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: var(--text-muted);">
            <p>Designed by LogiQ Studio</p>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
require base_path('views/layouts/base.php');
